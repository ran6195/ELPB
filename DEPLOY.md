# Guida Deploy Produzione - Landing Page Builder

Questa guida ti aiuterà a deployare l'applicazione sul server di produzione.

## 📋 Pre-requisiti

- Server con PHP 7.4+ e MySQL 5.7+
- Composer installato
- Node.js e npm installati
- Accesso SSH al server
- Dominio configurato: `https://edysma.net/ELPB/`

## 🚀 Procedura di Deploy

### 1. Preparazione Locale

#### a) Build del Frontend
```bash
cd frontend
npm install
npm run build
```

Questo crea la cartella `frontend/dist/` con i file ottimizzati per produzione.

#### b) Verifica File .env.production
```bash
cd ../backend
cp .env.production .env
```

Modifica il file `.env` con le credenziali corrette:
```env
DB_HOST=localhost
DB_DATABASE=landing_page_builder
DB_USERNAME=tuo_username
DB_PASSWORD=tua_password_sicura

APP_URL=https://edysma.net/ELPB/backend/public
BASE_PATH=/ELPB/backend/public

# IMPORTANTE: Genera una chiave JWT sicura
# openssl rand -base64 64
JWT_SECRET=la-tua-chiave-jwt-generata
```

### 2. Upload dei File sul Server

#### Via FTP/SFTP:
```
Carica tutto il progetto in:
/percorso/del/server/ELPB/

Struttura finale:
ELPB/
├── backend/
│   ├── config/
│   ├── database/
│   ├── public/
│   │   ├── index.php
│   │   └── uploads/
│   ├── src/
│   ├── vendor/
│   ├── .env
│   └── composer.json
└── frontend/
    └── dist/
        ├── index.html
        └── assets/
```

#### Via Git (Raccomandato):
```bash
# Sul server
cd /percorso/del/server/
git clone [url-repository] ELPB
cd ELPB

# Checkout del branch main
git checkout main
git pull origin main
```

### 3. Configurazione Backend sul Server

```bash
cd backend

# Installa dipendenze PHP
composer install --no-dev --optimize-autoloader

# Copia il file .env di produzione
cp .env.production .env

# IMPORTANTE: Modifica .env con le credenziali corrette
nano .env

# Crea le directory necessarie
mkdir -p public/uploads/images
chmod 755 public/uploads
chmod 755 public/uploads/images

# Esegui le migration del database
php database/migrations/create_tables.php
php database/migrations/add_appointment_fields.php
php database/migrations/add_missing_leads_fields.php
php database/migrations/add_recaptcha_settings.php
```

### 4. Configurazione Frontend

Il frontend è già buildato in `frontend/dist/`. Deve essere servito dal webserver.

#### Crea file `.env.production` nel frontend (locale):
```bash
cd frontend
cat > .env.production << 'EOF'
VITE_API_URL=https://edysma.net/ELPB/backend/public/api
EOF
```

Poi rebuilda:
```bash
npm run build
```

E ri-carica i file `frontend/dist/*` sul server.

### 5. Configurazione Apache/Nginx

#### Apache (.htaccess già presente)

Il file `backend/public/.htaccess` è già configurato.

**Importante**: Assicurati che il DocumentRoot o Alias punti a `ELPB/frontend/dist/`:

```apache
# Nel tuo VirtualHost o .htaccess principale
<IfModule alias_module>
    Alias /ELPB "/percorso/completo/ELPB/frontend/dist"
    <Directory "/percorso/completo/ELPB/frontend/dist">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted

        # Rewrite per Vue Router
        RewriteEngine On
        RewriteBase /ELPB/
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /ELPB/index.html [L]
    </Directory>

    # Backend API
    Alias /ELPB/backend/public "/percorso/completo/ELPB/backend/public"
    <Directory "/percorso/completo/ELPB/backend/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</IfModule>
```

### 6. Permessi File

```bash
# Backend
cd /percorso/ELPB/backend
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 public/uploads

# Frontend
cd /percorso/ELPB/frontend/dist
chown -R www-data:www-data .
chmod -R 755 .
```

### 7. Creazione Utente Admin (Prima volta)

Accedi al database e crea manualmente il primo utente admin:

```sql
USE landing_page_builder;

-- Crea la prima company
INSERT INTO companies (name, created_at, updated_at)
VALUES ('Azienda Demo', NOW(), NOW());

-- Crea l'admin (password: admin123)
-- Password hash generato con: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password, role, company_id, created_at, updated_at)
VALUES (
    'Admin',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    NULL,
    NOW(),
    NOW()
);
```

**IMPORTANTE**: Cambia la password dopo il primo login!

### 8. Test del Deploy

1. **Test Backend API:**
   ```bash
   curl https://edysma.net/ELPB/backend/public/api/debug/database
   ```

   Dovrebbe rispondere con: `{"status":"success","message":"Database connection successful"}`

2. **Test Frontend:**
   - Apri `https://edysma.net/ELPB/`
   - Dovresti vedere la pagina di login
   - Prova a fare login con le credenziali admin

3. **Test Completo:**
   - Login come admin
   - Crea una pagina di test
   - Pubblica la pagina
   - Verifica che sia accessibile pubblicamente

## 🔄 Aggiornamenti Futuri

### Aggiornamento Codice (via Git)

```bash
cd /percorso/ELPB
git pull origin main

# Backend
cd backend
composer install --no-dev --optimize-autoloader

# Esegui eventuali nuove migration
ls database/migrations/*.php | while read migration; do php "$migration"; done

# Frontend - rebuilda localmente e ricarica dist/
cd ../frontend
npm run build
# Poi carica frontend/dist/* sul server
```

## ⚠️ Troubleshooting

### Errore "CORS"
- Verifica che `APP_URL` in `.env` corrisponda all'URL pubblico
- Controlla che il middleware CORS in `backend/public/index.php` sia configurato

### Errore "JWT token invalid"
- Verifica che `JWT_SECRET` sia lo stesso in `.env`
- Cancella i token nel browser (localStorage)

### Errore "Database connection failed"
- Verifica credenziali in `.env`
- Controlla che MySQL sia avviato
- Verifica che l'utente abbia i permessi corretti

### Immagini non caricate
- Verifica permessi su `backend/public/uploads/`
- Verifica che `APP_URL` in `.env` sia corretto

### Pagine non trovate (404)
- Verifica configurazione Apache/Nginx
- Controlla che `BASE_PATH` in `.env` sia corretto
- Verifica che il rewrite module sia abilitato

## 📞 Supporto

Per problemi o domande, consulta:
- `TROUBLESHOOTING.md` - Guida risoluzione problemi
- `CLAUDE.md` - Documentazione progetto

---

**Ultimo aggiornamento**: Dicembre 2024
