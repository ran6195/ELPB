#!/bin/bash

# Deploy via FTP su elpb.edysma.com (Aruba/Plesk, senza SSH)
#
# Carica solo ciò che è cambiato dall'ultimo deploy:
#   - backend: i file modificati/eliminati in git dal commit dell'ultimo deploy
#   - frontend: build Vite, carica gli asset nuovi e per ultimo index.html
#
# Uso:
#   ./scripts/deploy-ftp.sh                 # prova: mostra cosa farebbe, nessuna scrittura
#   ./scripts/deploy-ftp.sh --execute       # esegue il deploy
#   ./scripts/deploy-ftp.sh --da <commit>   # confronta con un commit specifico (primo deploy)
#   ./scripts/deploy-ftp.sh --frontend      # forza build e upload del frontend
#   ./scripts/deploy-ftp.sh --pulisci       # elimina dal server gli asset vecchi non più usati
#
# Credenziali in .credenziali_ftp.txt (USER=, PASSWORD=, HOST= opzionale), ignorato da git.
# Il commit dell'ultimo deploy è salvato in .deploy-rev (ignorato da git).
# Ogni fase usa una sola connessione FTP: troppe connessioni ravvicinate fanno
# scattare il blocco temporaneo dell'IP da parte di Plesk.

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CRED_FILE="$ROOT/.credenziali_ftp.txt"
REV_FILE="$ROOT/.deploy-rev"
SITE_URL="https://elpb.edysma.com"

EXECUTE=0
FORCE_FRONTEND=0
CLEAN_ASSETS=0
FROM_REV=""

while [ $# -gt 0 ]; do
    case "$1" in
        --execute)  EXECUTE=1 ;;
        --frontend) FORCE_FRONTEND=1 ;;
        --pulisci)  CLEAN_ASSETS=1 ;;
        --da)       FROM_REV="$2"; shift ;;
        *) echo -e "${RED}Opzione sconosciuta: $1${NC}"; exit 1 ;;
    esac
    shift
done

cd "$ROOT"

# --- Credenziali e configurazione curl (password mai sulla riga di comando) ---
if [ ! -f "$CRED_FILE" ]; then
    echo -e "${RED}✗ $CRED_FILE non trovato${NC}"; exit 1
fi
FTP_USER=$(sed -n 's/^USER=//p' "$CRED_FILE")
FTP_PASS=$(sed -n 's/^PASSWORD=//p' "$CRED_FILE")
FTP_HOST=$(sed -n 's/^HOST=//p' "$CRED_FILE")
FTP_HOST=${FTP_HOST:-elpb.edysma.com}
FTP_URL="ftp://$FTP_HOST"

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT
CURL_CFG="$WORK/curl.cfg"
umask 077
cat > "$CURL_CFG" <<EOF
user = "$FTP_USER:$FTP_PASS"
ssl-reqd
insecure
ftp-create-dirs
connect-timeout = 20
silent
show-error
EOF
umask 022

ftp() { curl -K "$CURL_CFG" "$@"; }

# --- Controlli git ---
if [ -n "$(git status --porcelain --untracked-files=no -- backend frontend)" ]; then
    echo -e "${RED}✗ Ci sono modifiche non committate in backend/ o frontend/.${NC}"
    echo "  Il deploy carica solo lo stato committato: fai prima il commit."
    exit 1
fi

HEAD_REV=$(git rev-parse --short HEAD)
if [ -z "$FROM_REV" ] && [ -f "$REV_FILE" ]; then
    FROM_REV=$(cat "$REV_FILE")
fi
if [ -z "$FROM_REV" ]; then
    echo -e "${RED}✗ Nessun deploy precedente registrato in .deploy-rev.${NC}"
    echo "  Indica il commit attualmente in produzione con --da <commit>."
    exit 1
fi
git rev-parse --verify -q "$FROM_REV^{commit}" > /dev/null || {
    echo -e "${RED}✗ Commit $FROM_REV non trovato${NC}"; exit 1;
}

echo "🚀 Deploy FTP su $FTP_HOST"
echo "   da $FROM_REV a $HEAD_REV $([ $EXECUTE -eq 1 ] && echo '(ESECUZIONE)' || echo '(prova, nessuna scrittura)')"

# --- Backend: file cambiati in git ---
# Esclusi: segreti e config del server, dati utente, dipendenze, documentazione,
# script di test e migration (si eseguono in phpMyAdmin, non vanno esposte sul web).
is_excluded() {
    case "$1" in
        backend/.env*|backend/.gitignore) return 0 ;;
        backend/public/uploads/*|backend/storage/*|backend/vendor/*) return 0 ;;
        backend/database/*|backend/test-*.php|*.md) return 0 ;;
    esac
    return 1
}

BACKEND_UPLOAD="$WORK/backend_upload"
BACKEND_DELETE="$WORK/backend_delete"
: > "$BACKEND_UPLOAD"; : > "$BACKEND_DELETE"
SKIPPED_DB=0; COMPOSER_CHANGED=0

while IFS=$'\t' read -r status path newpath; do
    case "$status" in
        R*) # rinomina: elimina il vecchio, carica il nuovo
            is_excluded "$path" || echo "$path" >> "$BACKEND_DELETE"
            path="$newpath"; status="M" ;;
    esac
    case "$path" in backend/database/*) SKIPPED_DB=1 ;; esac
    case "$path" in backend/composer.json|backend/composer.lock) COMPOSER_CHANGED=1 ;; esac
    is_excluded "$path" && continue
    if [ "$status" = "D" ]; then
        echo "$path" >> "$BACKEND_DELETE"
    else
        echo "$path" >> "$BACKEND_UPLOAD"
    fi
done < <(git diff --name-status -M "$FROM_REV" HEAD -- backend)

echo -e "\n${YELLOW}🔧 Backend${NC}"
if [ -s "$BACKEND_UPLOAD" ] || [ -s "$BACKEND_DELETE" ]; then
    sed 's/^/   ↑ /' "$BACKEND_UPLOAD"
    sed 's/^/   ✗ /' "$BACKEND_DELETE"
else
    echo "   nessuna modifica"
fi
[ $SKIPPED_DB -eq 1 ] && echo -e "   ${YELLOW}⚠ Modifiche in backend/database: le migration vanno eseguite a mano in phpMyAdmin (prima del deploy se il codice nuovo le richiede)${NC}"
[ $COMPOSER_CHANGED -eq 1 ] && echo -e "   ${YELLOW}⚠ composer.json/lock cambiati: vendor/ NON viene caricato, va aggiornato a parte${NC}"

# --- Frontend ---
DEPLOY_FRONTEND=$FORCE_FRONTEND
if [ -n "$(git diff --name-only "$FROM_REV" HEAD -- frontend ':!frontend/package.json')" ]; then
    DEPLOY_FRONTEND=1
fi

echo -e "\n${YELLOW}🎨 Frontend${NC}"
if [ $DEPLOY_FRONTEND -eq 0 ]; then
    echo "   nessuna modifica (usa --frontend per forzare)"
else
    echo "   build in corso..."
    (cd frontend && npm run build > "$WORK/build.log" 2>&1) || {
        echo -e "${RED}✗ Build fallita:${NC}"; tail -30 "$WORK/build.log"; exit 1;
    }
    # Asset già presenti sul server (nomi con hash: se il nome c'è, il file è identico)
    ftp --list-only "$FTP_URL/assets/" > "$WORK/remote_assets" 2>/dev/null || : > "$WORK/remote_assets"
    ls frontend/dist/assets > "$WORK/local_assets"
    NEW_ASSETS=$(grep -vxFf "$WORK/remote_assets" "$WORK/local_assets" || true)
    OLD_ASSETS=$(grep -vxFf "$WORK/local_assets" "$WORK/remote_assets" | grep -vxE '\.|\.\.' || true)
    echo "   asset nuovi: $(echo "$NEW_ASSETS" | grep -c . || true)"
    echo "$NEW_ASSETS" | grep . | sed 's/^/   ↑ assets\//' || true
    echo "   ↑ .htaccess, debug.html, version.json, index.html (per ultimo)"
    if [ -n "$OLD_ASSETS" ]; then
        if [ $CLEAN_ASSETS -eq 1 ]; then
            echo "   asset vecchi da eliminare: $(echo "$OLD_ASSETS" | grep -c .)"
        else
            echo "   asset vecchi sul server: $(echo "$OLD_ASSETS" | grep -c .) (restano; usa --pulisci per eliminarli)"
        fi
    fi
fi

if [ $EXECUTE -eq 0 ]; then
    echo -e "\n${GREEN}Prova completata. Rilancia con --execute per eseguire il deploy.${NC}"
    exit 0
fi

# --- Esecuzione ---
# Upload: un unico curl con tutte le coppie file/URL (una sola connessione)
upload_list() { # $1 = file con righe "locale<TAB>remoto"
    [ -s "$1" ] || return 0
    local cfg="$WORK/upload.cfg"; : > "$cfg"
    while IFS=$'\t' read -r local remote; do
        printf 'upload-file = "%s"\nurl = "%s/%s"\n' "$local" "$FTP_URL" "$remote" >> "$cfg"
    done < "$1"
    curl -K "$CURL_CFG" -K "$cfg"
}

delete_list() { # $1 = file con un percorso remoto per riga
    [ -s "$1" ] || return 0
    local args=()
    # con "*" davanti curl prosegue anche se il file è già assente
    while read -r remote; do args+=(-Q "*DELE $remote"); done < "$1"
    ftp --list-only "$FTP_URL/" "${args[@]}" > /dev/null
}

if [ -s "$BACKEND_UPLOAD" ] || [ -s "$BACKEND_DELETE" ]; then
    echo -e "\n${YELLOW}⬆ Backend...${NC}"
    awk '{print $0 "\t" $0}' "$BACKEND_UPLOAD" > "$WORK/backend_pairs"
    upload_list "$WORK/backend_pairs"
    delete_list "$BACKEND_DELETE"
    echo -e "${GREEN}✓ Backend aggiornato${NC}"
fi

if [ $DEPLOY_FRONTEND -eq 1 ]; then
    echo -e "\n${YELLOW}⬆ Frontend...${NC}"
    # Prima gli asset nuovi, poi index.html: chi apre il sito durante il deploy
    # non riceve mai un index.html che punta ad asset non ancora caricati
    : > "$WORK/frontend_pairs"
    echo "$NEW_ASSETS" | grep . | while read -r f; do
        printf 'frontend/dist/assets/%s\tassets/%s\n' "$f" "$f"
    done >> "$WORK/frontend_pairs" || true
    for f in .htaccess debug.html version.json index.html; do
        printf 'frontend/dist/%s\t%s\n' "$f" "$f" >> "$WORK/frontend_pairs"
    done
    upload_list "$WORK/frontend_pairs"
    if [ $CLEAN_ASSETS -eq 1 ] && [ -n "$OLD_ASSETS" ]; then
        echo "$OLD_ASSETS" | sed 's/^/assets\//' > "$WORK/old_assets"
        delete_list "$WORK/old_assets"
    fi
    echo -e "${GREEN}✓ Frontend aggiornato${NC}"
fi

echo "$HEAD_REV" > "$REV_FILE"

# --- Verifica ---
echo -e "\n${YELLOW}🔍 Verifica...${NC}"
if [ $DEPLOY_FRONTEND -eq 1 ]; then
    LOCAL_VERSION=$(node -p "require('./frontend/dist/version.json').version")
    REMOTE_VERSION=$(curl -s --max-time 15 "$SITE_URL/version.json?t=$(date +%s)" | node -e "let s='';process.stdin.on('data',d=>s+=d).on('end',()=>{try{console.log(JSON.parse(s).version)}catch{console.log('?')}})")
    echo "   versione sito: $REMOTE_VERSION (build $LOCAL_VERSION)"
fi
API_CHECK=$(curl -s -o /dev/null -w '%{http_code} %{content_type}' --max-time 15 "$SITE_URL/backend/public/api/page/__verifica-deploy__")
API_CODE=${API_CHECK%% *}
case "$API_CHECK" in
    500*) echo -e "${RED}✗ Il backend risponde con errore 500: controllare subito${NC}"; exit 1 ;;
    *application/json*) echo "   API: HTTP $API_CODE con risposta JSON, ok" ;;
    # la protezione anti-bot dell'hosting può rispondere con una pagina HTML di attesa
    *) echo -e "   ${YELLOW}⚠ API: risposta non JSON (HTTP $API_CODE), verificare dal browser${NC}" ;;
esac
echo -e "\n${GREEN}✅ Deploy completato ($HEAD_REV)${NC}"
