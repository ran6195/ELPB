#!/bin/bash
# Script per impostare i permessi corretti dei file

echo "🔐 Impostazione permessi per standalone-renderer..."

# Directory principale (se necessario)
# chmod 755 .

# File PHP - leggibili ed eseguibili dal webserver
echo "📄 Impostando permessi file PHP..."
chmod 644 page.php
chmod 644 config.php
chmod 644 BlockRenderer.php

# File di configurazione - solo owner può leggere (più sicuro)
echo "🔒 Proteggendo file .env..."
if [ -f .env ]; then
    chmod 600 .env
else
    echo "⚠️  File .env non trovato (normale se non ancora creato)"
fi

# File .env.example - leggibile
chmod 644 .env.example

# File .htaccess - leggibile dal webserver
echo "🌐 Impostando permessi .htaccess..."
chmod 644 .htaccess

# Documentazione - leggibile
echo "📚 Impostando permessi documentazione..."
chmod 644 README.md 2>/dev/null || true
chmod 644 QUICKSTART.md 2>/dev/null || true

# Script - eseguibili
echo "⚙️  Impostando permessi script..."
chmod 755 build.sh 2>/dev/null || true
chmod 755 set-permissions.sh

echo ""
echo "✅ Permessi impostati correttamente!"
echo ""
echo "📋 Riepilogo permessi:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
ls -la | grep -E "\.(php|sh|htaccess|env|md)$|^d"
echo ""
echo "🔍 Verifica:"
echo "   - File PHP: 644 (rw-r--r--)"
echo "   - .env: 600 (rw-------) ← IMPORTANTE!"
echo "   - .htaccess: 644 (rw-r--r--)"
echo "   - Script .sh: 755 (rwxr-xr-x)"
echo ""
echo "⚠️  Se il file .env non esiste ancora:"
echo "   1. Copia .env.example in .env"
echo "   2. Esegui di nuovo questo script"
echo "   3. Oppure: chmod 600 .env"
