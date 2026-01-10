# ✅ Checklist Deploy - Landing Page Builder

## 📋 Prima del Deploy

### 1. Build e Test Locale
- [ ] Build frontend: `cd frontend && npm run build`
- [ ] Verifica che `frontend/dist/` sia stata creata
- [ ] Test locale del backend: `cd backend && php -S localhost:8000 -t public`
- [ ] Verifica che le migration siano tutte presenti

### 2. Configurazione File .env Produzione

#### Backend (.env)
- [ ] Copia `.env.production` in `.env` sul server
- [ ] Configura credenziali database:
  ```
  DB_HOST=localhost
  DB_DATABASE=landing_page_builder
  DB_USERNAME=[il tuo username]
  DB_PASSWORD=[la tua password]
  ```
- [ ] Verifica URL produzione:
  ```
  APP_URL=https://edysma.net/ELPB/backend/public
  BASE_PATH=/ELPB/backend/public
  ```
- [ ] Genera JWT_SECRET sicuro:
  ```bash
  openssl rand -base64 64
  ```

#### Frontend (già nel build)
- [ ] Verifica `.env.production`:
  ```
  VITE_API_URL=https://edysma.net/ELPB/backend/public/api
  ```

## 🚀 Upload sul Server

### 3. Caricamento File
- [ ] Carica tutta la cartella `backend/` sul server
- [ ] Carica tutta la cartella `frontend/dist/` sul server
- [ ] Verifica la struttura:
  ```
  ELPB/
  ├── backend/
  │   ├── config/
  │   ├── database/
  │   ├── public/
  │   ├── src/
  │   ├── vendor/ (sarà creata dopo)
  │   ├── .env (da configurare)
  │   └── composer.json
  └── frontend/
      └── dist/
          ├── index.html
          └── assets/
  ```

### 4. Configurazione Backend sul Server
SSH nel server e esegui:

```bash
cd /percorso/ELPB/backend

# Installa dipendenze PHP
composer install --no-dev --optimize-autoloader

# Configura .env
cp .env.production .env
nano .env  # Modifica con le credenziali corrette

# Crea directory uploads
mkdir -p public/uploads/images
chmod 755 public/uploads
chmod 755 public/uploads/images

# Esegui migration (in ordine!)
php database/migrations/create_tables.php
php database/migrations/create_auth_tables.php
php database/migrations/add_appointment_fields.php
php database/migrations/add_missing_leads_fields.php
php database/migrations/add_recaptcha_settings.php
```

### 5. Configurazione Web Server

#### Apache
- [ ] Verifica che il modulo rewrite sia abilitato
- [ ] Configura VirtualHost o Alias (vedi DEPLOY.md)
- [ ] Verifica che `.htaccess` sia presente in `backend/public/`
- [ ] Verifica che `.htaccess` sia presente in `frontend/dist/`

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

## 🧪 Test Deploy

### 7. Verifica Funzionamento

- [ ] **Test Backend API:**
  ```bash
  curl https://edysma.net/ELPB/backend/public/api/debug/database
  ```
  Risposta attesa: `{"status":"success","message":"Database connection successful"}`

- [ ] **Test Frontend:**
  - Apri: https://edysma.net/ELPB/
  - Dovrebbe caricare la pagina di login

- [ ] **Test Login:**
  - Prova a fare login con le credenziali admin
  - Verifica che il token JWT venga salvato

- [ ] **Test Creazione Pagina:**
  - Crea una nuova pagina
  - Aggiungi alcuni blocchi
  - Salva la pagina

- [ ] **Test Upload Immagini:**
  - Carica un'immagine in un blocco
  - Verifica che l'immagine sia visibile

- [ ] **Test Pubblicazione:**
  - Pubblica la pagina
  - Verifica che sia accessibile all'URL pubblico

- [ ] **Test Form Lead:**
  - Inserisci un form in una pagina
  - Invia una lead di test
  - Verifica che la lead appaia nel pannello admin

## 🔐 Sicurezza Post-Deploy

### 8. Controlli Sicurezza
- [ ] Cambia password admin default
- [ ] Verifica che JWT_SECRET sia stato cambiato
- [ ] Verifica che APP_DEBUG sia `false` in produzione
- [ ] Disabilita o proteggi route di debug (`/api/debug/*`)
- [ ] Verifica che le credenziali del database siano sicure
- [ ] Backup del database

## 📝 Creazione Utente Admin (Prima volta)

Se non hai ancora creato l'utente admin, esegui via SQL:

```sql
USE landing_page_builder;

-- Password: admin123 (da cambiare dopo primo login!)
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

## 🔄 Aggiornamenti Futuri

Per aggiornare l'applicazione:

```bash
# Locale: rebuild frontend
cd frontend
npm run build

# Server: aggiorna codice
cd /percorso/ELPB
# Carica nuovi file via FTP o git pull

# Server: aggiorna dipendenze
cd backend
composer install --no-dev --optimize-autoloader

# Server: esegui eventuali nuove migration
```

---

**Note**: Per problemi consulta `TROUBLESHOOTING.md` e `DEPLOY.md`
