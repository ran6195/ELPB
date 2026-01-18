# ✅ Checklist Rapida Deployment

## 🎯 RIEPILOGO COMPONENTI

| Componente | Tecnologia | Deploy Path | URL Produzione |
|------------|-----------|-------------|----------------|
| **Backend** | PHP 8 + MySQL | `/ELPB/backend/` | `https://edysma.net/ELPB/backend/public/api` |
| **Frontend** | Vue 3 (build) | `/ELPB/` | `https://edysma.net/ELPB/` |
| **Renderer** | PHP puro | `/` (root vari domini) | `https://ilprodotto.it/page.php?slug=...` |
| **Joomla** | Componente | Joomla extensions | `https://edysma.net/index.php?option=com_landingpages` |

---

## 1️⃣ BACKEND

### Preparazione (in locale)
- [ ] Verifica `composer.json` presente
- [ ] Verifica struttura `src/`, `database/`, `config/`

### Sul Server
- [ ] Crea database MySQL
  ```sql
  CREATE DATABASE landing_page_builder;
  CREATE USER 'lpb_user'@'localhost' IDENTIFIED BY 'password';
  GRANT ALL PRIVILEGES ON landing_page_builder.* TO 'lpb_user'@'localhost';
  ```

- [ ] Carica file backend su `/ELPB/backend/`

- [ ] Configura `.env`:
  ```bash
  cd backend
  cp .env.example .env
  nano .env
  ```
  - Imposta credenziali DB
  - Imposta `APP_URL` e `BASE_PATH`
  - Genera `JWT_SECRET`: `openssl rand -base64 64`
  - Imposta `APP_DEBUG=false`

- [ ] Installa dipendenze:
  ```bash
  composer install --no-dev --optimize-autoloader
  ```

- [ ] Esegui migrazioni:
  ```bash
  php database/migrations/create_tables.php
  php database/migrations/create_auth_tables.php
  php database/migrations/add_appointment_fields.php
  php database/migrations/add_missing_leads_fields.php
  php database/migrations/add_recaptcha_settings.php
  ```

- [ ] Imposta permessi:
  ```bash
  chmod -R 755 backend/
  chmod -R 775 backend/public/uploads/
  chown -R www-data:www-data backend/
  ```

- [ ] Test API: `curl https://edysma.net/ELPB/backend/public/api/debug/db`

---

## 2️⃣ FRONTEND

### Preparazione (in locale)
- [ ] Configura `.env.production`:
  ```bash
  cd frontend
  nano .env.production
  ```
  ```
  VITE_API_URL=https://edysma.net/ELPB/backend/public/api
  ```

- [ ] Build:
  ```bash
  npm install
  npm run build
  ```

- [ ] Verifica `.htaccess` in `dist/` (con `RewriteBase /ELPB/`)

### Sul Server
- [ ] Carica contenuto `frontend/dist/*` su `/ELPB/`

- [ ] Verifica struttura:
  ```
  /ELPB/
  ├── index.html
  ├── assets/
  ├── .htaccess
  └── backend/
  ```

- [ ] Test: Apri `https://edysma.net/ELPB/` e verifica login

---

## 3️⃣ STANDALONE RENDERER

### Per Ogni Dominio (es: ilprodotto.it, primehome.it)

- [ ] Configura `.env`:
  ```bash
  cd standalone-renderer
  cp .env.example .env
  nano .env
  ```
  ```
  API_BASE_URL=https://edysma.net/ELPB/backend/public/api
  DEBUG=false
  ENABLE_CACHE=true
  ```

- [ ] Carica file:
  ```
  - page.php
  - BlockRenderer.php
  - .env
  - .htaccess (opzionale per URL puliti)
  ```

- [ ] Test: `https://ilprodotto.it/page.php?slug=test-page`

---

## 4️⃣ COMPONENTE JOOMLA (Opzionale)

- [ ] Build: `cd joomla-component && zip -r com_landingpages.zip com_landingpages/`

- [ ] Installa in Joomla: **Estensioni → Gestione → Installa**

- [ ] Crea voce menu con slug e API URL

- [ ] Test componente

---

## 🔒 SICUREZZA

- [ ] `APP_DEBUG=false` in produzione
- [ ] `DEBUG=false` nel renderer
- [ ] JWT_SECRET sicuro (64+ caratteri)
- [ ] Password database sicura
- [ ] Permessi corretti (755/644)
- [ ] `.env` non accessibile via web
- [ ] Disabilita route `/api/debug/*` in produzione

---

## 🧪 TEST FINALI

### Backend
```bash
# Test DB
curl https://edysma.net/ELPB/backend/public/api/debug/db

# Test Login
curl -X POST https://edysma.net/ELPB/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'
```

### Frontend
- [ ] Login funziona
- [ ] Dashboard carica
- [ ] Editor apre
- [ ] Upload immagini funziona
- [ ] Salva pagina funziona
- [ ] Preview funziona

### Renderer
- [ ] Pagina pubblica si visualizza
- [ ] Immagini caricano
- [ ] Form lead invia
- [ ] reCAPTCHA funziona (se abilitato)

---

## 📦 FILE MODIFICATI (tracciamento sessione)

### Sessione corrente (2026-01-13):
- `frontend/src/views/Login.vue` - Rimosse credenziali di test
- `backend/database/create_fm_marketing_user.php` - Script creazione utente FM Marketing
- `GUIDA_DEPLOYMENT.md` - Guida completa deployment
- `DEPLOYMENT_CHECKLIST.md` - Questa checklist

---

## 🆘 TROUBLESHOOTING RAPIDO

| Problema | Soluzione |
|----------|-----------|
| API non risponde | Verifica `.htaccess` e `mod_rewrite` Apache |
| CORS errors | Verifica CORS middleware in `backend/public/index.php` |
| Upload fallisce | `chmod 775 backend/public/uploads/` |
| Frontend 404 | Verifica `.htaccess` e `RewriteBase` |
| Immagini non caricano | Verifica `API_BASE_URL` in renderer `.env` |
| DB connection fail | Verifica credenziali in `backend/.env` |

---

## 📞 COMANDI UTILI

```bash
# Build frontend
cd frontend && npm run build

# Install backend
cd backend && composer install --no-dev --optimize-autoloader

# Generate JWT
openssl rand -base64 64

# Backup DB
mysqldump -u lpb_user -p landing_page_builder > backup_$(date +%Y%m%d).sql

# Restore DB
mysql -u lpb_user -p landing_page_builder < backup.sql
```
