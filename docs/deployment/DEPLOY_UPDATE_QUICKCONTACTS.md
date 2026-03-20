# 🚀 Deploy Update - Quick Contacts Feature

**Data:** 10 Gennaio 2026
**Versione:** Quick Contacts + Enhanced Features
**Server:** edysma.net/ELPB/

---

## 📦 File Pronti per l'Upload

Ho preparato due archivi compressi nella cartella principale del progetto:

```
deploy-backend-update-20260110.tar.gz   (16 KB)  - File backend modificati
deploy-frontend-dist-20260110.tar.gz    (329 KB) - Frontend completo buildato
```

---

## 🎯 Cosa Include Questo Update

### Nuove Funzionalità
- ✅ **Quick Contacts**: Pulsanti flottanti WhatsApp e Telefono configurabili
- ✅ **Protezione Pagine Pubblicate**: Non si possono eliminare pagine pubblicate
- ✅ **Device Preview Migliorato**: Frame realistici per tablet e mobile
- ✅ **Standalone Renderer**: Renderer PHP per deployment esterni

### File Modificati Backend
- `backend/src/Controllers/PageController.php` - Supporto quick_contacts e protezione delete
- `backend/src/Models/Page.php` - Nuovo campo quick_contacts
- `backend/database/migrations/add_quick_contacts.php` - Migration nuova colonna
- `backend/standalone-renderer/BlockRenderer.php` - Renderer standalone (nuovo)
- `backend/.env.example` - Template configurazione

### File Modificati Frontend
- Tutto il frontend è stato ribuilato con le nuove funzionalità
- Nuovo componente `QuickContactBlock.vue`
- PageSettings migliorato con UI per Quick Contacts
- Device preview con frame realistici

---

## 📋 STEP 1: Backup

**IMPORTANTE**: Prima di procedere, fai un backup!

### Via SSH sul server:
```bash
# Backup database
mysqldump -u USERNAME -p landing_page_builder > backup_$(date +%Y%m%d).sql

# Backup file
cd /percorso/ELPB
tar -czf ../backup-elpb-$(date +%Y%m%d).tar.gz .
```

### Via phpMyAdmin:
1. Vai su phpMyAdmin
2. Seleziona database `landing_page_builder`
3. Tab "Esporta" → Esporta

---

## 📋 STEP 2: Upload File

### Opzione A: Via FTP/FileZilla

#### 1. Upload Backend (16 KB)
1. Scarica `deploy-backend-update-20260110.tar.gz` dal tuo computer
2. Carica l'archivio nella home del server via FTP
3. Connettiti via SSH ed estrai:
   ```bash
   cd /percorso/ELPB
   tar -xzf ~/deploy-backend-update-20260110.tar.gz
   ```

#### 2. Upload Frontend (329 KB)
1. Scarica `deploy-frontend-dist-20260110.tar.gz` dal tuo computer
2. Carica l'archivio nella home del server via FTP
3. Connettiti via SSH ed estrai:
   ```bash
   cd /percorso/ELPB
   tar -xzf ~/deploy-frontend-dist-20260110.tar.gz
   ```

### Opzione B: Upload File Manuale via FTP

Se preferisci NON usare SSH, puoi estrarre gli archivi localmente e caricare i file uno per uno:

#### Backend - File da caricare manualmente:

1. **Estrai localmente** `deploy-backend-update-20260110.tar.gz`
2. **Carica questi file** su edysma.net mantenendo la struttura:

```
→ /ELPB/backend/src/Controllers/PageController.php
→ /ELPB/backend/src/Models/Page.php
→ /ELPB/backend/database/migrations/add_quick_contacts.php (NUOVO)
→ /ELPB/backend/standalone-renderer/BlockRenderer.php (NUOVA CARTELLA)
→ /ELPB/backend/.env.example (NUOVO)
```

#### Frontend - File da caricare manualmente:

1. **Estrai localmente** `deploy-frontend-dist-20260110.tar.gz`
2. **ELIMINA** la cartella `/ELPB/assets/` esistente sul server
3. **Carica tutto** il contenuto estratto in `/ELPB/`:

```
→ /ELPB/index.html (SOVRASCRIVI)
→ /ELPB/assets/ (CARTELLA NUOVA)
```

---

## 📋 STEP 3: Migration Database

Connettiti via SSH al server ed esegui:

```bash
cd /percorso/ELPB/backend
php database/migrations/add_quick_contacts.php
```

**Output atteso:**
```
Migration: add_quick_contacts
Column 'quick_contacts' added successfully to pages table
Migration completed successfully
```

### Se NON hai accesso SSH:

Esegui questa query SQL via phpMyAdmin:

```sql
USE landing_page_builder;

ALTER TABLE `pages`
ADD COLUMN `quick_contacts` JSON NULL AFTER `recaptcha_settings`;

SELECT 'Migration completed successfully' as status;
```

---

## 📋 STEP 4: Verifica Permessi

Solo se hai caricato nuovi file, verifica i permessi:

```bash
cd /percorso/ELPB
chmod -R 755 .
chown -R www-data:www-data .

# Permessi upload (se non esistono già)
mkdir -p backend/public/uploads/images
chmod -R 775 backend/public/uploads
chown -R www-data:www-data backend/public/uploads
```

---

## 🧪 STEP 5: Test Funzionamento

### 1. Test Frontend
Apri nel browser: `https://edysma.net/ELPB/`

✅ Dovresti vedere la dashboard
✅ Verifica che il login funzioni

### 2. Test Quick Contacts

1. Apri una pagina esistente nell'editor
2. Nel pannello destro "Impostazioni Pagina", scorri fino a **"Contatti Rapidi"**
3. Attiva il toggle **WhatsApp**:
   - Inserisci un numero (es: `393331234567`)
   - Inserisci un messaggio
   - Salva
4. Clicca **"Anteprima"** in alto a destra
5. ✅ Dovresti vedere il pulsante WhatsApp verde in basso a destra
6. Clicca sul pulsante → si apre WhatsApp con il messaggio pre-compilato

### 3. Test Protezione Eliminazione

1. Vai alla dashboard
2. Pubblica una pagina (toggle verde)
3. Prova a eliminarla
4. ✅ Dovresti ricevere un messaggio: "Non puoi eliminare una pagina pubblicata"

### 4. Test Device Preview

1. Apri l'editor di una pagina
2. Clicca sui pulsanti in alto: Desktop / Tablet / Mobile
3. ✅ Tablet: Dovresti vedere un frame tipo iPad (768x1024px)
4. ✅ Mobile: Dovresti vedere un frame tipo iPhone (390x844px) con notch

---

## ❌ Rollback (in caso di problemi)

Se qualcosa non funziona:

### Via SSH:
```bash
cd /percorso/ELPB/..
tar -xzf backup-elpb-YYYYMMDD.tar.gz -C ELPB/

# Ripristina database
mysql -u USERNAME -p landing_page_builder < backup_YYYYMMDD.sql
```

### Via FTP:
1. Elimina le cartelle `/ELPB/backend/` e `/ELPB/assets/`
2. Ricarica dal backup

---

## 📊 Checklist Completa

- [ ] **STEP 1**: Backup database fatto
- [ ] **STEP 1**: Backup file fatto
- [ ] **STEP 2**: Backend uploadato (16 KB)
- [ ] **STEP 2**: Frontend uploadato (329 KB)
- [ ] **STEP 3**: Migration `add_quick_contacts.php` eseguita
- [ ] **STEP 4**: Permessi verificati
- [ ] **STEP 5**: Test frontend → Login OK
- [ ] **STEP 5**: Test Quick Contacts → Pulsanti visibili
- [ ] **STEP 5**: Test Protezione delete → Messaggio errore OK
- [ ] **STEP 5**: Test Device preview → Frame visibili

---

## 🆘 Troubleshooting

### "Pagina bianca dopo l'update"
- Controlla la console del browser (F12)
- Verifica che i file in `/ELPB/assets/` siano caricati correttamente
- Svuota cache del browser (Ctrl+Shift+R)

### "Errore 500 sul backend"
- Verifica che i permessi siano corretti (755 per directory, 644 per file)
- Controlla il log di Apache: `/var/log/apache2/error.log`

### "Migration fallita"
- Verifica che la colonna `quick_contacts` non esista già:
  ```sql
  DESCRIBE pages;
  ```
- Se esiste già, la migration è stata già eseguita (OK)

### "Pulsanti Quick Contacts non visibili"
- Verifica che la migration sia stata eseguita
- Apri le impostazioni della pagina e attiva i toggle
- Controlla che il numero WhatsApp sia inserito correttamente (solo numeri)

---

## 📞 Contatti

Se hai problemi, consulta:
- `TROUBLESHOOTING.md` nella repo
- `CHECKLIST_DEPLOY.md` per deploy completo
- Log del server: `/var/log/apache2/error.log`

---

**Tutto pronto! Segui gli step e testa ogni passaggio.** ✅
