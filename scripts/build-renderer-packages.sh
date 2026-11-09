#!/bin/bash

# Crea il pacchetto di installazione del renderer standalone scaricabile dalla
# guida di pubblicazione dell'app (frontend/public/downloads/):
#   renderer-lp-elpb-nolegal.zip  solo renderer (il Footer Legale linka le pagine del sito)
#   renderer.json                 versione, data e dimensione, letto dalla guida
#
# Per ora si distribuisce solo la variante senza pagine legali. La variante con
# le pagine legali generate in locale (renderer-package/con-legali/) resta pronta:
# per riattivarla togliere il commento a "build renderer-lp-elpb ..." qui sotto e
# aggiungerla a renderer.json e alla guida (PublishGuide.vue).
#
# Codice da standalone-renderer/ e menu-legali/, file propri del pacchetto
# (.env, .htaccess, LEGGIMI.txt) da renderer-package/. La versione è quella di
# RENDERER_VERSION in standalone-renderer/config.php: il titolo dei LEGGIMI.txt
# deve citarla, altrimenti lo script si ferma (note di versione da aggiornare).
#
# Uso: ./scripts/build-renderer-packages.sh
# Poi commit dei file in frontend/public/downloads/ e deploy (li carica deploy-ftp.sh).

set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/standalone-renderer"
LEGAL="$ROOT/menu-legali"
PKG="$ROOT/renderer-package"
OUT="$ROOT/frontend/public/downloads"

VERSION=$(sed -n "s/^define('RENDERER_VERSION', '\([^']*\)');/\1/p" "$SRC/config.php")
if [ -z "$VERSION" ]; then
    echo "✗ RENDERER_VERSION non trovata in standalone-renderer/config.php"; exit 1
fi
for kind in senza-legali; do
    if ! head -1 "$PKG/$kind/LEGGIMI.txt" | grep -qF "RENDERER LANDING PAGE $VERSION "; then
        echo "✗ renderer-package/$kind/LEGGIMI.txt non è della versione $VERSION: aggiorna titolo e novità"; exit 1
    fi
done

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT
mkdir -p "$OUT"

# $1 = nome del pacchetto, $2 = cartella in renderer-package/, $3 = 1 con le pagine legali
build() {
    local name="$1" kind="$2" legal="$3"
    local dir="$WORK/$name"
    mkdir -p "$dir/lp"
    cp "$SRC"/{page.php,BlockRenderer.php,config.php,thank-you.php} "$dir/lp/"
    cp "$PKG/$kind/env" "$dir/lp/.env"
    cp "$PKG/$kind/htaccess" "$dir/lp/.htaccess"
    cp "$PKG/$kind/LEGGIMI.txt" "$dir/"
    if [ "$legal" = 1 ]; then
        cp "$SRC/legal-pages.php" "$dir/lp/"
        mkdir -p "$dir/menu-legali"
        cp "$LEGAL"/{LegalTemplateProcessor.php,privacy.php,condizioni.php,cookies.php} "$dir/menu-legali/"
        cp "$PKG/$kind/menu-legali.htaccess" "$dir/menu-legali/.htaccess"
    fi
    # Permessi come sul server: cartelle 755, file 644
    find "$dir" -type d -exec chmod 755 {} +
    find "$dir" -type f -exec chmod 644 {} +
    rm -f "$OUT/$name.zip"
    (cd "$WORK" && zip -qrX "$OUT/$name.zip" "$name")
    echo "✓ $name.zip ($(wc -c < "$OUT/$name.zip" | tr -d ' ') byte)"
}

# build renderer-lp-elpb con-legali 1
build renderer-lp-elpb-nolegal senza-legali 0

size() { wc -c < "$OUT/$1" | tr -d ' '; }
cat > "$OUT/renderer.json" <<EOF
{
  "version": "$VERSION",
  "built_at": "$(date +%Y-%m-%d)",
  "packages": {
    "senza_legali": { "file": "renderer-lp-elpb-nolegal.zip", "size": $(size renderer-lp-elpb-nolegal.zip) }
  }
}
EOF
echo "✓ renderer.json (versione $VERSION)"
