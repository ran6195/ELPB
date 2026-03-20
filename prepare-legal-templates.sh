#!/bin/bash

# Script per preparare i template legali per il deploy

echo "📄 Preparazione template legali per deploy..."

# Crea la directory se non esiste
mkdir -p backend/templates/legal

# Copia i template
echo "Copiando template da menu-legali/ a backend/templates/legal/..."
cp menu-legali/privacy.php backend/templates/legal/
cp menu-legali/condizioni.php backend/templates/legal/
cp menu-legali/cookies.php backend/templates/legal/

echo "✓ Template copiati con successo!"
echo ""
echo "Template disponibili in backend/templates/legal/:"
ls -lh backend/templates/legal/

echo ""
echo "✅ Pronto per il deploy!"
