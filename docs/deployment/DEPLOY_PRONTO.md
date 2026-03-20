# 🎉 Deploy Pronto per edysma.net/ELPB/

## ✅ Completato

Il progetto è stato buildato e preparato per il deploy su **edysma.net/ELPB/**

### File Pronti:
- ✅ Frontend buildato in `frontend/dist/`
- ✅ Backend con dipendenze installate
- ✅ Configurazione produzione in `backend/.env.production`
- ✅ Migration database pronte
- ✅ File .htaccess creati

## 📦 Cosa Caricare sul Server

### Via FTP/SFTP:

1. **Backend completo**
   - Carica tutta la cartella `backend/` nella posizione `/ELPB/backend/` sul server
   - Include: `vendor/`, `src/`, `config/`, `database/`, `public/`, `.env.production`

2. **Frontend buildato**
   - Carica il contenuto di `frontend/dist/` nella posizione `/ELPB/` sul server
   - Include: `index.html`, `assets/`, `.htaccess`

### Struttura finale sul server:
```
/var/www/html/ELPB/
├── index.html              (da frontend/dist/)
├── assets/                 (da frontend/dist/)
├── .htaccess              (da frontend/dist/)
└── backend/
    ├── config/
    ├── database/
    │   └── migrations/
    ├── public/
    │   ├── index.php
    │   ├── .htaccess
    │   └── uploads/
    ├── src/
    ├── vendor/
    ├── .env               (copia di .env.production configurato)
    └── composer.json
```

## 🔧 Configurazione sul Server

### 1. Configura .env Backend
SSH nel server e modifica il file `.env`:

```bash
cd /var/www/html/ELPB/backend
cp .env.production .env
nano .env
```

**Modifica queste righe con i tuoi dati:**
```env
DB_DATABASE=landing_page_builder
DB_USERNAME=il_tuo_username_mysql
DB_PASSWORD=la_tua_password_mysql

# Genera una chiave sicura:
# openssl rand -base64 64
JWT_SECRET=[la_tua_chiave_jwt_sicura]
```

### 2. Esegui Migration Database

```bash
cd /var/www/html/ELPB/backend

# Esegui le migration in ordine:
php database/migrations/create_tables.php
php database/migrations/create_auth_tables.php
php database/migrations/add_appointment_fields.php
php database/migrations/add_missing_leads_fields.php
php database/migrations/add_recaptcha_settings.php
```

### 3. Imposta Permessi

```bash
# Directory uploads
mkdir -p /var/www/html/ELPB/backend/public/uploads/images
chmod 755 /var/www/html/ELPB/backend/public/uploads
chmod 755 /var/www/html/ELPB/backend/public/uploads/images
chown -R www-data:www-data /var/www/html/ELPB/backend/public/uploads

# Permessi generali
chown -R www-data:www-data /var/www/html/ELPB
```

### 4. Verifica Apache

Assicurati che Apache abbia:
- Modulo `rewrite` abilitato: `a2enmod rewrite`
- Modulo `headers` abilitato: `a2enmod headers`
- `AllowOverride All` per la directory ELPB

## 🧪 Test

### 1. Test Backend API
```bash
curl https://edysma.net/ELPB/backend/public/api/debug/database
```
Risposta attesa: `{"status":"success","message":"Database connection successful"}`

### 2. Test Frontend
Apri nel browser: `https://edysma.net/ELPB/`
Dovresti vedere la pagina di login

### 3. Test Completo
1. Login con: `admin@example.com` / `admin123`
2. Crea una pagina di test
3. Aggiungi alcuni blocchi
4. Salva e pubblica
5. Visualizza la pagina pubblica

## 🔐 Credenziali Default

**IMPORTANTE: Cambia queste password dopo il primo login!**

- **Admin**: admin@example.com / admin123
- **Company**: company@example.com / company123
- **User**: user@example.com / user123

## 📋 Checklist Finale

- [ ] File caricati sul server
- [ ] `.env` configurato con credenziali corrette
- [ ] JWT_SECRET generato e impostato
- [ ] Migration database eseguite
- [ ] Permessi impostati correttamente
- [ ] Apache configurato e riavviato
- [ ] Test backend API funzionante
- [ ] Test frontend accessibile
- [ ] Login funzionante
- [ ] Password admin cambiata

## 🆘 Problemi?

Consulta:
- `CHECKLIST_DEPLOY.md` - Checklist dettagliata
- `DEPLOY.md` - Guida completa al deploy
- `TROUBLESHOOTING.md` - Risoluzione problemi comuni

## 📞 Verifica Configurazione

### URL Configurati:
- **Frontend**: https://edysma.net/ELPB/
- **Backend API**: https://edysma.net/ELPB/backend/public/api
- **Uploads**: https://edysma.net/ELPB/backend/public/uploads/

### File .env Backend:
```env
APP_URL=https://edysma.net/ELPB/backend/public
BASE_PATH=/ELPB/backend/public
```

### File .env.production Frontend (già nel build):
```env
VITE_API_URL=https://edysma.net/ELPB/backend/public/api
```

---

**Tutto pronto per il deploy! 🚀**
