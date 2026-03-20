# Fix 404 Pagine Legali

## 🔴 Problema
Link del footer legale corretti (es. `https://edysma.net/ELPB/standalone-renderer/legal/replica-aran/privacy`) ma risultato 404.

## 🔍 Causa
La directory **`menu-legali`** con i template delle pagine legali non è presente sul server o non è nella posizione corretta.

## ✅ Soluzione Rapida

### Step 1: Diagnostica
Carica il file `standalone-renderer/check-legal-setup.php` sul server e aprilo nel browser:

```
https://edysma.net/ELPB/standalone-renderer/check-legal-setup.php
```

Questo ti dirà esattamente cosa manca.

### Step 2: Carica Directory menu-legali

La directory `menu-legali` deve essere **un livello sopra** rispetto a `standalone-renderer`.

**Struttura corretta:**
```
edysma.net/ELPB/
├── menu-legali/                    ← QUESTA DIRECTORY
│   ├── LegalTemplateProcessor.php
│   ├── privacy.php
│   ├── condizioni.php
│   └── cookies.php
└── standalone-renderer/
    ├── legal-pages.php
    ├── page.php
    └── ...
```

**File da caricare:**
Dal tuo progetto locale, carica questi file sul server:

```
Locale:                                    → Server:
menu-legali/LegalTemplateProcessor.php    → edysma.net/ELPB/menu-legali/LegalTemplateProcessor.php
menu-legali/privacy.php                    → edysma.net/ELPB/menu-legali/privacy.php
menu-legali/condizioni.php                 → edysma.net/ELPB/menu-legali/condizioni.php
menu-legali/cookies.php                    → edysma.net/ELPB/menu-legali/cookies.php
```

### Step 3: Verifica Permessi

```bash
# Via SSH (se disponibile)
chmod 755 menu-legali
chmod 644 menu-legali/*.php
```

### Step 4: Test

Ricarica la diagnostica:
```
https://edysma.net/ELPB/standalone-renderer/check-legal-setup.php
```

Dovrebbe mostrare tutto verde ✅.

Poi testa una pagina legale:
```
https://edysma.net/ELPB/standalone-renderer/legal/replica-aran/privacy
```

## 🎯 Verifica Veloce via FTP/SFTP

Connettiti al server e verifica che questa struttura esista:

```
/ELPB/
├── menu-legali/
│   ├── LegalTemplateProcessor.php  (dimensione ~8 KB)
│   ├── privacy.php                 (dimensione ~12 KB)
│   ├── condizioni.php              (dimensione ~30 KB)
│   └── cookies.php                 (dimensione ~25 KB)
└── standalone-renderer/
    └── legal-pages.php
```

## ⚠️ Errori Comuni

### Errore: "Class 'LegalTemplateProcessor' not found"
**Causa**: File `LegalTemplateProcessor.php` mancante o non leggibile

**Fix**: Verifica che il file esista in `menu-legali/` e abbia permessi 644

### Errore: "Template non trovato: .../privacy.php"
**Causa**: File template mancanti

**Fix**: Carica tutti e 3 i file template (privacy.php, condizioni.php, cookies.php)

### Errore: "require_once(...): failed to open stream"
**Causa**: Directory `menu-legali` non trovata

**Fix**: Verifica che `menu-legali` sia nella posizione corretta (un livello sopra `standalone-renderer`)

## 🔧 Soluzione Alternativa: Spostare menu-legali Dentro standalone-renderer

Se non puoi modificare la struttura directory, puoi spostare `menu-legali` dentro `standalone-renderer`:

**Nuova struttura:**
```
edysma.net/ELPB/standalone-renderer/
├── menu-legali/                    ← DENTRO standalone-renderer
│   ├── LegalTemplateProcessor.php
│   ├── privacy.php
│   ├── condizioni.php
│   └── cookies.php
├── legal-pages.php
└── page.php
```

**Modifica richiesta in `legal-pages.php`:**
```php
// Riga 20: DA
require_once __DIR__ . '/../menu-legali/LegalTemplateProcessor.php';

// A
require_once __DIR__ . '/menu-legali/LegalTemplateProcessor.php';

// Riga 227: DA
$processor = new LegalTemplateProcessor(__DIR__ . '/../menu-legali');

// A
$processor = new LegalTemplateProcessor(__DIR__ . '/menu-legali');
```

## 📋 Checklist Completa

Prima di testare, verifica:

- [ ] Directory `menu-legali` presente sul server
- [ ] `menu-legali` nella posizione corretta (un livello sopra standalone-renderer)
- [ ] File `LegalTemplateProcessor.php` presente in `menu-legali/`
- [ ] File `privacy.php` presente in `menu-legali/`
- [ ] File `condizioni.php` presente in `menu-legali/`
- [ ] File `cookies.php` presente in `menu-legali/`
- [ ] Permessi corretti (755 directory, 644 file)
- [ ] File `legal-pages.php` presente in `standalone-renderer/`
- [ ] File `.htaccess` con routing legal configurato

## 🧪 Test Finale

1. **Diagnostica**: `check-legal-setup.php` → Tutto verde ✅
2. **Link diretto**: `/legal-pages.php?slug=test&type=privacy` → Pagina caricata
3. **Link routing**: `/legal/test/privacy` → Pagina caricata
4. **Footer link**: Click su "Privacy" dal footer → Pagina caricata

Se tutti i test passano, il problema è risolto! 🎉

## 💡 Per ilprodotto.it

Se vuoi che anche ilprodotto.it abbia le pagine legali locali:

1. Copia `menu-legali/` su ilprodotto.it (stessa struttura)
2. Copia `legal-pages.php` su ilprodotto.it
3. Configura `.env` su ilprodotto.it:
   ```bash
   # Opzione A: Link puntano a edysma.net (centralizzato)
   LEGAL_BASE_URL=https://edysma.net/ELPB/standalone-renderer

   # Opzione B: Link puntano locale (ilprodotto.it ha propria copia)
   LEGAL_BASE_URL=
   ```

## 📞 Supporto

File di riferimento:
- `check-legal-setup.php` - Script diagnostica
- `FIX_LINK_LEGALI_REMOTI.md` - Configurazione link footer
- `MIGRAZIONE_PAGINE_LEGALI.md` - Documentazione completa sistema
