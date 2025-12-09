#!/bin/bash

# Script di Deploy Automatico - Landing Page Builder
# Uso: ./scripts/deploy.sh

set -e  # Esce in caso di errore

echo "🚀 Inizio Deploy Landing Page Builder"
echo "======================================"

# Colori per output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Build Frontend
echo -e "\n${YELLOW}📦 Build Frontend...${NC}"
cd frontend
npm install
npm run build
echo -e "${GREEN}✓ Frontend buildato${NC}"

# 2. Verifica Backend
echo -e "\n${YELLOW}🔍 Verifica Backend...${NC}"
cd ../backend
if [ ! -f ".env" ]; then
    echo -e "${RED}✗ File .env non trovato!${NC}"
    echo "Copia .env.production in .env e configura le credenziali"
    exit 1
fi

# 3. Installa dipendenze Backend
echo -e "\n${YELLOW}📦 Installa dipendenze Backend...${NC}"
composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✓ Dipendenze installate${NC}"

# 4. Test connessione database
echo -e "\n${YELLOW}🗄️  Test Database...${NC}"
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
require 'config/database.php';
try {
    Illuminate\Database\Capsule\Manager::connection()->getPdo();
    echo '✓ Connessione database OK\n';
} catch (Exception \$e) {
    echo '✗ Errore database: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# 5. Esegui migration
echo -e "\n${YELLOW}🔄 Eseguo migration...${NC}"
for migration in database/migrations/*.php; do
    echo "  - $(basename $migration)"
    php "$migration"
done
echo -e "${GREEN}✓ Migration completate${NC}"

# 6. Verifica permessi
echo -e "\n${YELLOW}🔐 Verifica permessi...${NC}"
mkdir -p public/uploads/images
chmod 755 public/uploads
chmod 755 public/uploads/images
echo -e "${GREEN}✓ Permessi configurati${NC}"

# 7. Riepilogo
echo -e "\n${GREEN}======================================"
echo "✅ Deploy completato con successo!"
echo "======================================${NC}"
echo ""
echo "Prossimi passi:"
echo "1. Carica i file sul server (FTP/Git)"
echo "2. Sul server, esegui: composer install --no-dev"
echo "3. Sul server, copia .env.production in .env"
echo "4. Sul server, configura Apache/Nginx"
echo "5. Testa l'applicazione"
echo ""
echo "Consulta DEPLOY.md per i dettagli completi"
