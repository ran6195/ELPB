# 📦 Files da Caricare sul Server

## Per edysma.net/ELPB

### 🔴 URGENTI (Fix 404 pagine legali)

#### 1. Directory menu-legali (MANCANTE!)
```
Locale → Server
menu-legali/LegalTemplateProcessor.php → /ELPB/menu-legali/LegalTemplateProcessor.php
menu-legali/privacy.php                → /ELPB/menu-legali/privacy.php
menu-legali/condizioni.php             → /ELPB/menu-legali/condizioni.php
menu-legali/cookies.php                → /ELPB/menu-legali/cookies.php
```

**Importante:** Crea directory `menu-legali` allo **stesso livello** di `standalone-renderer`, NON dentro!

Struttura corretta:
```
/ELPB/
├── menu-legali/          ← Qui
└── standalone-renderer/
```

#### 2. File Diagnostica (Appena creati)
```
standalone-renderer/check-legal-setup.php → /ELPB/standalone-renderer/check-legal-setup.php
standalone-renderer/test.php              → /ELPB/standalone-renderer/test.php
```

#### 3. File Aggiornati (Fix LEGAL_BASE_URL)
```
standalone-renderer/page.php → /ELPB/standalone-renderer/page.php
standalone-renderer/.env     → /ELPB/standalone-renderer/.env
```

---

## Per ilprodotto.it (Quando pronto)

### Files Renderer Standalone
```
standalone-renderer/.htaccess        → /renderer/.htaccess
standalone-renderer/.env.ilprodotto  → /renderer/.env (rinomina!)
standalone-renderer/page.php         → /renderer/page.php
standalone-renderer/legal-pages.php  → /renderer/legal-pages.php
standalone-renderer/thank-you.php    → /renderer/thank-you.php
standalone-renderer/config.php       → /renderer/config.php
standalone-renderer/BlockRenderer.php → /renderer/BlockRenderer.php
```

### Opzionale: Pagine Legali Locali
Se vuoi che ilprodotto.it abbia le proprie pagine legali invece di puntare a edysma.net:

```
menu-legali/LegalTemplateProcessor.php → /menu-legali/LegalTemplateProcessor.php
menu-legali/privacy.php                → /menu-legali/privacy.php
menu-legali/condizioni.php             → /menu-legali/condizioni.php
menu-legali/cookies.php                → /menu-legali/cookies.php
```

E nel `.env`:
```bash
LEGAL_BASE_URL=  # Vuoto per usare URL relativi locali
```

---

## 📋 Checklist Upload edysma.net

Prima di iniziare:
- [ ] Backup file esistenti sul server
- [ ] Connessione FTP/SFTP attiva
- [ ] Path corrente verificato (`/ELPB/`)

Upload:
- [ ] Crea directory `/ELPB/menu-legali/` (se non esiste)
- [ ] Carica 4 file in `menu-legali/`
- [ ] Carica `check-legal-setup.php` in `standalone-renderer/`
- [ ] Carica `test.php` in `standalone-renderer/`
- [ ] Aggiorna `page.php` in `standalone-renderer/`
- [ ] Verifica `.env` in `standalone-renderer/` con `LEGAL_BASE_URL`

Permessi (via SSH se disponibile):
```bash
chmod 755 /ELPB/menu-legali
chmod 644 /ELPB/menu-legali/*.php
chmod 644 /ELPB/standalone-renderer/check-legal-setup.php
chmod 644 /ELPB/standalone-renderer/test.php
```

Test:
- [ ] http://edysma.net/ELPB/standalone-renderer/test.php → phpinfo()
- [ ] http://edysma.net/ELPB/standalone-renderer/check-legal-setup.php → diagnostica
- [ ] http://edysma.net/ELPB/standalone-renderer/legal/replica-aran/privacy → pagina privacy

---

## 🎯 Dimensioni File (per verifica upload)

```
menu-legali/LegalTemplateProcessor.php  ~8 KB
menu-legali/privacy.php                 ~12 KB
menu-legali/condizioni.php              ~30 KB
menu-legali/cookies.php                 ~25 KB
standalone-renderer/check-legal-setup.php ~12 KB
standalone-renderer/test.php            ~50 bytes
standalone-renderer/page.php            ~15 KB
standalone-renderer/.env                ~1 KB
```

Se i file caricati hanno dimensioni molto diverse, potrebbero essere corrotti o incompleti.

---

## ⚠️ Errori Comuni

### Upload in directory sbagliata
❌ `/ELPB/standalone-renderer/menu-legali/` (dentro)
✅ `/ELPB/menu-legali/` (stesso livello)

### File corrotti
- Usa modalità **Binary** per upload FTP
- Evita "Auto" mode che può corrompere PHP

### Permessi sbagliati
- Directory: 755
- File PHP: 644
- Mai 777 (insicuro)

### .env non rinominato
Su ilprodotto.it ricordati di:
`.env.ilprodotto` → `.env` (elimina `.ilprodotto`)

---

## 🔧 Client FTP/SFTP Raccomandati

- **FileZilla** (Windows/Mac/Linux) - Free
- **Cyberduck** (Mac/Windows) - Free
- **WinSCP** (Windows) - Free
- **Transmit** (Mac) - Paid
- **Command line**: `sftp`, `scp`, `rsync`

---

## 📞 Se Hai Problemi

1. **404 su tutti i file**: Verifica path upload corretto
2. **403 Forbidden**: Controlla permessi file
3. **500 Internal Error**: Controlla log PHP per errori sintassi
4. **Pagina bianca**: Abilita display_errors in php.ini o usa `test.php`

Documentazione:
- `VERIFICA_DEPLOYMENT_EDYSMA.md` - Guida test deployment
- `FIX_404_PAGINE_LEGALI.md` - Fix specifico 404 legal
- `PIANO_RENDERER_REMOTO.md` - Piano completo deployment remoto

---

## ✅ Quando Hai Finito

Tutti questi URL devono funzionare:

**edysma.net:**
- ✅ `/ELPB/standalone-renderer/test.php`
- ✅ `/ELPB/standalone-renderer/check-legal-setup.php`
- ✅ `/ELPB/standalone-renderer/page.php?slug=replica-aran`
- ✅ `/ELPB/standalone-renderer/replica-aran`
- ✅ `/ELPB/standalone-renderer/legal/replica-aran/privacy`

**ilprodotto.it (quando deployato):**
- ✅ `/renderer/test.php` o `/test.php`
- ✅ `/renderer/check-legal-setup.php` o `/check-legal-setup.php`
- ✅ `/renderer/replica-aran` o `/replica-aran`
- ✅ Footer link → `https://edysma.net/ELPB/standalone-renderer/legal/...`

Buon deployment! 🚀
