# Guida Completa al Deployment - Landing Page Builder

Questa guida descrive il processo completo per deployare le tre componenti principali del progetto:

1. **Backend** - API REST PHP (Slim Framework)
2. **Frontend** - Applicazione Vue.js (editor)
3. **Standalone Renderer** - Renderer PHP per landing pages pubbliche

---

## 📦 Componenti del Progetto

### 1. Backend (API)
- **Tecnologia**: PHP 8+, Slim Framework 4, Eloquent ORM
- **Database**: MySQL
- **Funzione**: API REST per gestione utenti, aziende, pagine e lead
- **URL Produzione**: `https://edysma.net/ELPB/backend/public`

### 2. Frontend (Editor)
- **Tecnologia**: Vue 3, Vite, TailwindCSS, Pinia
- **Funzione**: Interfaccia drag-and-drop per creare landing pages
- **Build**: Genera file statici in `frontend/dist/`
- **URL Produzione**: `https://edysma.net/ELPB/`

### 3. Standalone Renderer
- **Tecnologia**: PHP puro
- **Funzione**: Visualizza landing pages pubblicate senza Vue/React
- **Cartelle Deploy**: Può essere deployato su più domini (ilprodotto.it, primehome.it, etc.)
- **URL Esempio**: `https://ilprodotto.it/page.php?slug=nome-pagina`

### 4. Componente Joomla (Opzionale)
- **Tecnologia**: Componente Joomla
- **Funzione**: Integrazione con siti Joomla esistenti
- **Path**: `joomla-component/com_landingpages/`

---

## 🚀 DEPLOYMENT STEP-BY-STEP

## PARTE 1: BACKEND (API)

### Step 1.1: Preparazione File Backend

```bash
# Dalla directory del progetto
cd backend

# Verifica che composer.json esista
ls composer.json

# Verifica la struttura
ls -la src/
ls -la database/
ls -la config/
```

### Step 1.2: Configurazione Database

**Sul server di produzione:**

1. Crea il database MySQL:
```sql
CREATE DATABASE landing_page_builder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lpb_user'@'localhost' IDENTIFIED BY 'password_sicura';
GRANT ALL PRIVILEGES ON landing_page_builder.* TO 'lpb_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 1.3: Configurazione .env

Crea il file `.env` da `.env.example`:

```bash
cp .env.example .env
nano .env
```

Configura i parametri:

```ini
# ===== DATABASE CONFIGURATION =====
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=landing_page_builder
DB_USERNAME=lpb_user
DB_PASSWORD=password_sicura

# ===== APPLICATION CONFIGURATION =====
APP_ENV=production
APP_DEBUG=false

# ===== APPLICATION URL =====
APP_URL=https://edysma.net/ELPB/backend/public

# ===== BASE PATH =====
# Se l'app è in sottocartella
BASE_PATH=/ELPB/backend/public

# ===== JWT CONFIGURATION =====
# Genera chiave sicura con: openssl rand -base64 64
JWT_SECRET=TUA_CHIAVE_JWT_SICURA_GENERATA_CON_OPENSSL
```

### Step 1.4: Upload File Backend

**Metodo 1: FTP/SFTP**
```bash
# Carica tutta la cartella backend su:
# /percorso/sito/ELPB/backend/
```

**Metodo 2: Git (consigliato)**
```bash
# Sul server
cd /percorso/sito/ELPB/
git clone [repository] .
cd backend
```

### Step 1.5: Installazione Dipendenze Backend

```bash
# Sul server, nella cartella backend
cd /percorso/sito/ELPB/backend

# Installa dipendenze con Composer
composer install --no-dev --optimize-autoloader
```

### Step 1.6: Esecuzione Migrazioni Database

```bash
# Nella cartella backend
php database/migrations/create_tables.php
php database/migrations/create_auth_tables.php
php database/migrations/add_appointment_fields.php
php database/migrations/add_missing_leads_fields.php
php database/migrations/add_recaptcha_settings.php
```

### Step 1.7: Creazione Utente Admin Iniziale

```bash
# Crea il primo utente admin
php database/migrations/create_tables.php
# (Questo script crea anche l'utente admin di default)
```

Oppure usa lo script per creare un utente specifico (modifica lo script secondo necessità).

### Step 1.8: Configurazione Permessi

```bash
# Imposta permessi corretti
chmod -R 755 /percorso/sito/ELPB/backend
chmod -R 775 /percorso/sito/ELPB/backend/public/uploads
chown -R www-data:www-data /percorso/sito/ELPB/backend
```

### Step 1.9: Test Backend

Verifica che l'API risponda:
```bash
curl https://edysma.net/ELPB/backend/public/api/debug/db
```

Dovresti vedere: `{"status":"success","message":"Database connected successfully"}`

---

## PARTE 2: FRONTEND (Editor Vue.js)

### Step 2.1: Configurazione .env.production

**In locale, prima del build:**

```bash
cd frontend
nano .env.production
```

Configura:
```ini
VITE_API_URL=https://edysma.net/ELPB/backend/public/api
```

### Step 2.2: Build Produzione

```bash
# Nella cartella frontend (IN LOCALE)
cd frontend

# Installa dipendenze se non già fatto
npm install

# Build per produzione
npm run build
```

Questo genera i file ottimizzati in `frontend/dist/`

### Step 2.3: Configurazione .htaccess per Vue Router

Crea/verifica `.htaccess` in `frontend/dist/`:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /ELPB/

  # Escludi esplicitamente la directory assets
  RewriteCond %{REQUEST_URI} !^/ELPB/assets/

  # Escludi file statici
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d

  # Reindirizza tutto a index.html
  RewriteRule ^ index.html [L]
</IfModule>

# Forza MIME type corretto per JavaScript
<IfModule mod_mime.c>
  AddType application/javascript .js
  AddType application/json .json
</IfModule>

# Abilita compressione
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript application/json
</IfModule>

# Cache statico
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Step 2.4: Upload Frontend

**Carica tutto il contenuto di `frontend/dist/` su:**
```
/percorso/sito/ELPB/
```

La struttura finale dovrebbe essere:
```
/percorso/sito/ELPB/
├── index.html
├── assets/
│   ├── index-[hash].js
│   ├── index-[hash].css
│   └── ...
├── .htaccess
└── backend/
```

### Step 2.5: Test Frontend

Apri il browser e vai su:
```
https://edysma.net/ELPB/
```

Dovresti vedere la pagina di login.

---

## PARTE 3: STANDALONE RENDERER

Il renderer standalone permette di visualizzare landing pages su domini diversi dall'editor.

### Step 3.1: Configurazione .env

**Per ogni dominio dove vuoi deployare il renderer:**

```bash
cd standalone-renderer
cp .env.example .env
nano .env
```

Configura:
```ini
# API Configuration
API_BASE_URL=https://edysma.net/ELPB/backend/public/api

# Debug mode
DEBUG=false

# Cache settings
ENABLE_CACHE=true
CACHE_TTL=3600
```

### Step 3.2: File Necessari

I file del renderer sono:
```
standalone-renderer/
├── page.php           # Pagina principale
├── BlockRenderer.php  # Classe per renderizzare blocchi
├── .env              # Configurazione
└── .htaccess         # (opzionale) per URL puliti
```

### Step 3.3: Upload Renderer

**Esempio 1: Deploy su ilprodotto.it**
```bash
# Carica i file in:
/percorso/ilprodotto.it/
├── page.php
├── BlockRenderer.php
└── .env
```

**Esempio 2: Deploy su primehome.it**
```bash
# Carica i file in:
/percorso/primehome.it/
├── page.php
├── BlockRenderer.php
└── .env
```

### Step 3.4: URL Rewrite (Opzionale)

Per avere URL puliti tipo `ilprodotto.it/nome-pagina`, crea `.htaccess`:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On

  # Reindirizza /slug a page.php?slug=slug
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^([a-z0-9-]+)$ page.php?slug=$1 [L,QSA]
</IfModule>
```

### Step 3.5: Test Renderer

Accedi a una landing page pubblicata:
```
https://ilprodotto.it/page.php?slug=nome-pagina
```

Oppure con URL pulito (se configurato):
```
https://ilprodotto.it/nome-pagina
```

---

## PARTE 4: COMPONENTE JOOMLA (Opzionale)

### Step 4.1: Build Componente

```bash
cd joomla-component
zip -r com_landingpages.zip com_landingpages/
```

### Step 4.2: Installazione in Joomla

1. Accedi al pannello admin Joomla
2. Vai su: **Estensioni → Gestione → Installa**
3. Carica il file `com_landingpages.zip`
4. Clicca "Carica e Installa"

### Step 4.3: Creazione Voce di Menu

1. Vai su: **Menu → Main Menu → Nuovo**
2. Tipo voce: **Landing Pages → Visualizza Landing Page**
3. Parametri:
   - **Slug**: nome-pagina
   - **API URL**: https://edysma.net/ELPB/backend/public/api
4. Salva

### Step 4.4: Test Componente Joomla

Accedi alla voce di menu creata:
```
https://edysma.net/index.php?option=com_landingpages&view=page&slug=nome-pagina
```

---

## ✅ CHECKLIST POST-DEPLOYMENT

### Backend
- [ ] Database creato e configurato
- [ ] File `.env` configurato correttamente
- [ ] Dipendenze installate con Composer
- [ ] Migrazioni database eseguite
- [ ] Permessi cartella uploads corretti (775)
- [ ] API risponde correttamente (`/api/debug/db`)
- [ ] CORS configurato correttamente
- [ ] JWT_SECRET generato e sicuro

### Frontend
- [ ] `.env.production` configurato
- [ ] Build eseguito con `npm run build`
- [ ] `.htaccess` presente in dist/
- [ ] File caricati sul server
- [ ] Applicazione accessibile via browser
- [ ] Login funziona
- [ ] Editor carica correttamente
- [ ] Upload immagini funziona

### Standalone Renderer
- [ ] `.env` configurato per ogni dominio
- [ ] File caricati sui domini target
- [ ] API_BASE_URL punta al backend corretto
- [ ] Landing page si visualizza correttamente
- [ ] Immagini caricate correttamente
- [ ] Form lead funziona (test invio)

### Sicurezza
- [ ] Password database sicura
- [ ] JWT_SECRET sicuro (64+ caratteri)
- [ ] APP_DEBUG=false in produzione
- [ ] DEBUG=false nel renderer
- [ ] Route `/api/debug/*` disabilitate in produzione
- [ ] Permessi file corretti (755 per directory, 644 per file)
- [ ] .env non accessibile via web
- [ ] Backup database configurato

---

## 🔧 TROUBLESHOOTING COMUNE

### Problema: API non risponde
**Soluzione:**
- Verifica `.htaccess` in `backend/public/`
- Verifica che `mod_rewrite` sia abilitato in Apache
- Controlla i log di Apache: `tail -f /var/log/apache2/error.log`

### Problema: CORS errors
**Soluzione:**
- Verifica CORS middleware in `backend/public/index.php`
- Aggiungi l'origine frontend agli allowed origins

### Problema: Upload immagini fallisce
**Soluzione:**
```bash
chmod -R 775 backend/public/uploads/images/
chown -R www-data:www-data backend/public/uploads/
```

### Problema: Frontend mostra 404 sulle rotte
**Soluzione:**
- Verifica `.htaccess` in `frontend/dist/`
- Verifica `RewriteBase` corrisponda al path reale

### Problema: Renderer non trova le immagini
**Soluzione:**
- Verifica `API_BASE_URL` in `.env`
- Le immagini sono servite dal backend, non dal renderer

### Problema: Database connection failed
**Soluzione:**
- Verifica credenziali in `backend/.env`
- Verifica che l'utente MySQL abbia i privilegi corretti
- Testa connessione: `mysql -u lpb_user -p landing_page_builder`

---

## 📝 COMANDI UTILI

### Build Frontend
```bash
cd frontend && npm run build
```

### Installazione Backend
```bash
cd backend && composer install --no-dev --optimize-autoloader
```

### Generare JWT Secret
```bash
openssl rand -base64 64
```

### Creare utente admin manualmente
```bash
cd backend
php database/create_fm_marketing_user.php
```

### Test API
```bash
# Test connessione DB
curl https://edysma.net/ELPB/backend/public/api/debug/db

# Test login
curl -X POST https://edysma.net/ELPB/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'
```

### Backup Database
```bash
mysqldump -u lpb_user -p landing_page_builder > backup_$(date +%Y%m%d).sql
```

### Ripristino Database
```bash
mysql -u lpb_user -p landing_page_builder < backup_20260113.sql
```

---

## 📚 RISORSE AGGIUNTIVE

- **CLAUDE.md** - Documentazione tecnica del progetto
- **CHECKLIST_POST_DEPLOY.md** - Checklist sicurezza e performance
- **frontend/dist/.htaccess** - Configurazione Apache per SPA

---

## 🆘 SUPPORTO

Per problemi o domande, consulta:
1. Questa guida
2. CLAUDE.md per dettagli tecnici
3. Log di sistema (`/var/log/apache2/error.log`)
4. Console browser (F12) per errori frontend
