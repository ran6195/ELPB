# Verifica Deployment su edysma.net

## 🔍 Situazione Attuale

Hai ricevuto 404 su `check-legal-setup.php`, che indica che il file non è presente sul server. Dobbiamo verificare cosa è effettivamente deployato su edysma.net.

## 📋 Test Sequenziali

### Test 1: Verifica Directory Standalone
Prova ad accedere a questi URL e segna il risultato:

```
✅ o ❌  http://edysma.net/ELPB/standalone-renderer/
✅ o ❌  http://edysma.net/ELPB/standalone-renderer/test.php
✅ o ❌  http://edysma.net/ELPB/standalone-renderer/page.php
```

**Risultato atteso:**
- Directory listing (se enabled) o 403 Forbidden
- `test.php` → phpinfo() o contenuto PHP
- `page.php` → Error o contenuto (anche se mancano parametri)

### Test 2: Verifica Backend
```
✅ o ❌  http://edysma.net/ELPB/backend/public/
✅ o ❌  http://edysma.net/ELPB/backend/public/api/page/test-slug
```

### Test 3: Verifica Renderer con Pagina Reale
Se hai una pagina pubblicata con slug "replica-aran":

```
✅ o ❌  http://edysma.net/ELPB/standalone-renderer/page.php?slug=replica-aran
✅ o ❌  http://edysma.net/ELPB/standalone-renderer/replica-aran
```

## 🎯 Scenari Possibili

### Scenario A: Tutti 404
**Causa:** Directory `standalone-renderer` non deployata su edysma.net

**Azione:**
1. Verifica via FTP/SFTP se la directory esiste
2. Se non esiste, deploy completo standalone-renderer
3. Se esiste, verifica path corretto

### Scenario B: Directory OK, ma file mancanti
**Causa:** Deployment parziale, mancano file recenti

**Azione:**
1. Carica file mancanti via FTP/SFTP
2. Lista file da caricare:
   - `check-legal-setup.php` (nuovo)
   - `test.php` (nuovo)
   - Verifica `page.php` aggiornato con fix LEGAL_BASE_URL

### Scenario C: Solo check-legal-setup.php dà 404
**Causa:** File non ancora caricato (normale, l'abbiamo appena creato)

**Azione:**
1. Carica `check-legal-setup.php` via FTP/SFTP
2. Riprova accesso

### Scenario D: .htaccess blocca accesso
**Causa:** Regole .htaccess troppo restrittive

**Azione:**
1. Verifica file `.htaccess` in standalone-renderer
2. Assicurati che non blocchi file .php

## 📂 Verifica Struttura Server

Connettiti via FTP/SFTP a edysma.net e verifica questa struttura:

```
edysma.net/
└── ELPB/
    ├── backend/
    │   └── public/
    │       └── api/
    ├── menu-legali/           ← DEVE ESISTERE (probabilmente mancante)
    │   ├── LegalTemplateProcessor.php
    │   ├── privacy.php
    │   ├── condizioni.php
    │   └── cookies.php
    └── standalone-renderer/   ← DEVE ESISTERE
        ├── .htaccess
        ├── .env
        ├── page.php
        ├── legal-pages.php
        ├── BlockRenderer.php
        ├── config.php
        ├── thank-you.php
        ├── check-legal-setup.php   ← DA CARICARE
        └── test.php                 ← DA CARICARE
```

## 🚀 Piano d'Azione Raccomandato

### Step 1: Verifica Accesso Base
1. Accedi via FTP/SFTP a edysma.net
2. Naviga a `/ELPB/`
3. Lista directory presenti
4. Annota cosa vedi

### Step 2: Carica File Mancanti

**File da caricare subito (appena creati):**
```
Locale → Server
standalone-renderer/test.php              → edysma.net/ELPB/standalone-renderer/test.php
standalone-renderer/check-legal-setup.php → edysma.net/ELPB/standalone-renderer/check-legal-setup.php
```

**File da aggiornare (con fix recenti):**
```
standalone-renderer/page.php              → edysma.net/ELPB/standalone-renderer/page.php
standalone-renderer/.env                  → edysma.net/ELPB/standalone-renderer/.env
```

### Step 3: Verifica Accesso File
Dopo upload, prova:
```
http://edysma.net/ELPB/standalone-renderer/test.php
```

Se vedi phpinfo() → ✅ accesso OK
Se vedi 404 → ❌ problema path o upload

### Step 4: Carica Directory menu-legali
```
Locale → Server
menu-legali/ → edysma.net/ELPB/menu-legali/
```

Con tutti e 4 i file:
- LegalTemplateProcessor.php
- privacy.php
- condizioni.php
- cookies.php

### Step 5: Test Diagnostica
```
http://edysma.net/ELPB/standalone-renderer/check-legal-setup.php
```

Dovrebbe mostrare report completo.

### Step 6: Test Pagina Legale
```
http://edysma.net/ELPB/standalone-renderer/legal/replica-aran/privacy
```

## 🔧 Fix Rapidi

### Se non hai accesso FTP/SFTP
Contatta l'amministratore del server e fornisci questa lista file da caricare.

### Se .htaccess blocca file .php
Aggiungi all'inizio del `.htaccess` in standalone-renderer:
```apache
# Permetti accesso a file PHP
<FilesMatch "\.php$">
    Order allow,deny
    Allow from all
</FilesMatch>
```

### Se path sono diversi
Se la struttura su edysma.net è diversa da quella assunta, dobbiamo:
1. Identificare path reale
2. Modificare `legal-pages.php` di conseguenza

## 📝 Feedback da Fornire

Dopo i test, segnala:
1. ✅ o ❌ per ogni URL testato
2. Cosa vedi quando accedi via FTP a `/ELPB/`
3. Se `standalone-renderer/` esiste e cosa contiene
4. Se `menu-legali/` esiste
5. Errori specifici nei log (se disponibili)

## 🎯 Obiettivo Finale

Arrivare a questa situazione:
- ✅ `test.php` → phpinfo()
- ✅ `check-legal-setup.php` → diagnostica completa (tutto verde)
- ✅ `page.php?slug=replica-aran` → pagina renderizzata
- ✅ `legal/replica-aran/privacy` → pagina privacy

## 💡 Nota Importante

I file `check-legal-setup.php` e `test.php` **non erano sul server** perché li abbiamo appena creati ora. È normale che diano 404 fino a quando non vengono caricati manualmente via FTP/SFTP.

Il fatto che `page.php` funzioni (o meno) ci dirà se il problema è solo file mancanti o se c'è un problema di deployment più ampio.
