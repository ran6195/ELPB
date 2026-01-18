#!/bin/bash

#############################################
# Fix Permissions Script
# Renderer Standalone - Landing Page Builder
#############################################

echo "======================================"
echo "Fix Permissions - Renderer Standalone"
echo "======================================"
echo ""

# Get current directory
CURRENT_DIR=$(pwd)
echo "Directory corrente: $CURRENT_DIR"
echo ""

# Check if we're in the right directory
if [ ! -f "BlockRenderer.php" ] || [ ! -f "page.php" ]; then
    echo "❌ ERRORE: Questo script deve essere eseguito dalla directory standalone-renderer/"
    echo "   File BlockRenderer.php e page.php non trovati."
    exit 1
fi

echo "✓ Directory corretta individuata"
echo ""

# Fix file permissions
echo "🔧 Correzione permessi file PHP..."
find . -type f -name "*.php" -exec chmod 644 {} \;
echo "✓ Permessi file PHP impostati a 644 (rw-r--r--)"
echo ""

# Fix directory permissions
echo "🔧 Correzione permessi directory..."
find . -type d -exec chmod 755 {} \;
echo "✓ Permessi directory impostati a 755 (rwxr-xr-x)"
echo ""

# Fix .htaccess permissions
if [ -f ".htaccess" ]; then
    chmod 644 .htaccess
    echo "✓ Permessi .htaccess impostati a 644"
    echo ""
fi

# Fix .env permissions (more restrictive for security)
if [ -f ".env" ]; then
    chmod 600 .env
    echo "✓ Permessi .env impostati a 600 (rw-------) per sicurezza"
    echo ""
fi

# Show current permissions
echo "📋 Permessi attuali:"
echo "==================="
ls -lah | grep -E "\.php$|\.htaccess|^d"
echo ""

# Show owner
echo "👤 Owner file:"
echo "=============="
ls -l BlockRenderer.php | awk '{print "Owner: " $3 "  Group: " $4}'
echo ""

echo "✅ COMPLETATO!"
echo ""
echo "Prossimi passi:"
echo "1. Verifica che owner sia corretto (utente web server)"
echo "2. Testa la landing page nel browser"
echo "3. Se l'errore 403 persiste, controlla i log del server"
echo ""
echo "Per verificare owner:"
echo "  ls -la"
echo ""
echo "Per cambiare owner (se necessario):"
echo "  chown -R TUOUTENTE:TUOGROUP ."
echo ""
