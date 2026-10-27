#!/bin/bash
# Aggiorna backend/vendor/ su elpb.edysma.com via FTP (Aruba/Plesk, senza SSH)
#
# deploy-ftp.sh non carica vendor/: quando composer.json cambia si usa questo script.
#   1. costruisce vendor/ senza dipendenze di sviluppo (composer install --no-dev)
#      in una cartella temporanea, da composer.json e composer.lock locali
#   2. scarica vendor/composer/installed.json dal server e confronta i pacchetti
#   3. carica i pacchetti nuovi o con versione diversa, poi vendor/composer/,
#      per ultimo vendor/autoload.php: l'autoload nuovo arriva quando i file
#      che carica a ogni richiesta (es. illuminate/events/functions.php) ci sono già
#
# I pacchetti tolti restano sul server (innocui: l'autoload nuovo non li usa).
#
# Uso:
#   ./scripts/deploy-vendor.sh             # prova: mostra cosa farebbe, nessuna scrittura
#   ./scripts/deploy-vendor.sh --execute   # carica
#
# Credenziali in .credenziali_ftp.txt (come deploy-ftp.sh).

set -e
cd "$(dirname "$0")/.."

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
EXECUTE=0
[ "$1" = "--execute" ] && EXECUTE=1

CRED_FILE=".credenziali_ftp.txt"
[ -f "$CRED_FILE" ] || { echo -e "${RED}✗ $CRED_FILE non trovato${NC}"; exit 1; }
FTP_HOST=$(sed -n 's/^HOST=//p' "$CRED_FILE")
FTP_HOST=${FTP_HOST:-mu001625.arubabiz.net}
FTP_URL="ftp://$FTP_HOST"

CURL=/usr/bin/curl
for c in /opt/homebrew/opt/curl/bin/curl /usr/local/opt/curl/bin/curl; do
    [ -x "$c" ] && { CURL="$c"; break; }
done

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT
CURL_CFG="$WORK/curl.cfg"
umask 077
cat > "$CURL_CFG" <<EOF
user = "$(sed -n 's/^USER=//p' "$CRED_FILE"):$(sed -n 's/^PASSWORD=//p' "$CRED_FILE")"
ssl-reqd
tls-max = "1.2"
ftp-create-dirs
connect-timeout = 20
silent
show-error
EOF
umask 022

echo "📦 vendor/ su $FTP_HOST $([ $EXECUTE -eq 1 ] && echo '(ESECUZIONE)' || echo '(prova, nessuna scrittura)')"

# 1. vendor senza dipendenze di sviluppo
mkdir -p "$WORK/build"
cp backend/composer.json backend/composer.lock "$WORK/build/"
(cd "$WORK/build" && composer install --no-dev --no-scripts --no-interaction -q)

# 2. confronto con il server
"$CURL" -K "$CURL_CFG" "$FTP_URL/backend/vendor/composer/installed.json" -o "$WORK/server-installed.json"
CHANGED=$(python3 - "$WORK/server-installed.json" "$WORK/build/vendor/composer/installed.json" <<'PY'
import json, sys
def packages(path):
    data = json.load(open(path))
    return {p['name']: p['version'] for p in (data['packages'] if isinstance(data, dict) else data)}
server, local = packages(sys.argv[1]), packages(sys.argv[2])
for name in sorted(local):
    if server.get(name) != local[name]:
        print(f"{name}\t{server.get(name, '-')}\t{local[name]}")
PY
)

if [ -z "$CHANGED" ]; then
    echo -e "${GREEN}✓ Nessun pacchetto da aggiornare: vendor/ sul server è allineato${NC}"
    exit 0
fi

echo -e "\n${YELLOW}Pacchetti da caricare (server → nuovo):${NC}"
echo "$CHANGED" | awk -F'\t' '{printf "   ↑ %s  %s → %s\n", $1, $2, $3}'

# 3. elenco dei file nell'ordine di caricamento
: > "$WORK/pairs"
add_dir() { # $1 = cartella relativa a vendor/
    (cd "$WORK/build/vendor" && find "$1" -type f) | sort | while read -r f; do
        printf '%s\tbackend/vendor/%s\n' "$WORK/build/vendor/$f" "$f"
    done >> "$WORK/pairs"
}
echo "$CHANGED" | cut -f1 | while read -r name; do add_dir "$name"; done
add_dir composer
printf '%s\tbackend/vendor/autoload.php\n' "$WORK/build/vendor/autoload.php" >> "$WORK/pairs"
echo "   file: $(wc -l < "$WORK/pairs" | tr -d ' ') (pacchetti, poi vendor/composer/, per ultimo autoload.php)"

if [ $EXECUTE -eq 0 ]; then
    echo -e "\n${GREEN}Prova completata. Rilancia con --execute per caricare.${NC}"
    exit 0
fi

# Un solo curl per tutti i file (una connessione: troppe fanno bloccare l'IP da Plesk)
: > "$WORK/upload.cfg"
while IFS=$'\t' read -r local remote; do
    printf 'upload-file = "%s"\nurl = "%s/%s"\n' "$local" "$FTP_URL" "$remote" >> "$WORK/upload.cfg"
done < "$WORK/pairs"
"$CURL" -K "$CURL_CFG" -K "$WORK/upload.cfg"

# Verifica: installed.json sul server ora coincide
"$CURL" -K "$CURL_CFG" "$FTP_URL/backend/vendor/composer/installed.json" -o "$WORK/check.json"
if cmp -s "$WORK/check.json" "$WORK/build/vendor/composer/installed.json"; then
    echo -e "${GREEN}✅ vendor/ aggiornato${NC}"
else
    echo -e "${RED}✗ installed.json sul server diverso da quello atteso: rilanciare lo script${NC}"
    exit 1
fi
