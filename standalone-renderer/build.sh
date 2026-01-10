#!/bin/bash
# Build script per creare il package standalone-renderer.zip

echo "🔨 Building standalone-renderer package..."

# Nome del package
PACKAGE_NAME="standalone-renderer.zip"

# Directory temporanea
BUILD_DIR="/tmp/standalone-renderer-build"

# Pulisci directory precedente
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

echo "📦 Copiando files..."

# Copia i file necessari
cp page.php "$BUILD_DIR/"
cp config.php "$BUILD_DIR/"
cp BlockRenderer.php "$BUILD_DIR/"
cp .env.example "$BUILD_DIR/"
cp .htaccess "$BUILD_DIR/"
cp .gitignore "$BUILD_DIR/"
cp README.md "$BUILD_DIR/"
cp QUICKSTART.md "$BUILD_DIR/"
cp PERMISSIONS.md "$BUILD_DIR/"
cp TROUBLESHOOTING_REWRITE.md "$BUILD_DIR/"
cp FIX-NO-INPUT-FILE.md "$BUILD_DIR/"
cp set-permissions.sh "$BUILD_DIR/"
cp test-rewrite.php "$BUILD_DIR/"
cp .htaccess.testLPSA "$BUILD_DIR/"

echo "🗜️  Creando archivio ZIP..."

# Vai nella directory temporanea e crea lo zip
cd "$BUILD_DIR" || exit
zip -r "../$PACKAGE_NAME" ./*

# Sposta lo zip nella directory corrente
mv "../$PACKAGE_NAME" "$(dirname "$0")/"

echo "✅ Package creato: $PACKAGE_NAME"

# Pulisci
rm -rf "$BUILD_DIR"

echo ""
echo "📋 Contenuto del package:"
unzip -l "$PACKAGE_NAME"

echo ""
echo "🚀 Pronto per il deploy!"
echo "   Carica $PACKAGE_NAME sul tuo server e decomprimilo."
