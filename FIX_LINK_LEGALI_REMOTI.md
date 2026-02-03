# Fix Link Footer Legale per Renderer Remoti

## 🔧 Problema
Quando il renderer standalone viene deployato su un server remoto (es. ilprodotto.it) che chiama l'API di un backend diverso (es. edysma.net), i link del footer legale (Privacy, Condizioni d'uso, Cookies) non funzionano perché puntano a URL relativi locali invece che al server dove sono effettivamente ospitate le pagine legali.

## ❌ Comportamento Prima della Fix

**Scenario**: Renderer su ilprodotto.it, Backend su edysma.net

- Link generati: `/legal/test-slug/privacy`
- URL finale: `https://ilprodotto.it/legal/test-slug/privacy` ❌ (404 - file non esiste)
- Expected: `https://edysma.net/ELPB/standalone-renderer/legal/test-slug/privacy` ✅

## ✅ Soluzione

È stato aggiunto il parametro **`LEGAL_BASE_URL`** al file `.env` del renderer standalone.

### File Modificati

1. **standalone-renderer/page.php**
   - Legge `LEGAL_BASE_URL` dal `.env`
   - Passa il valore al costruttore `BlockRenderer`

2. **standalone-renderer/.env** e **.env.ilprodotto**
   - Aggiunto parametro `LEGAL_BASE_URL` con documentazione

## 📝 Configurazione

### Per Deployment Locale (stesso server)

```bash
# .env
API_BASE_URL=https://edysma.net/ELPB/backend/public/api

# Lascia vuoto per usare URL relativi (default)
# LEGAL_BASE_URL=
```

**Comportamento**: Link generati come `/legal/{slug}/{tipo}` (relativi)

### Per Deployment Remoto (server diverso)

```bash
# .env su ilprodotto.it
API_BASE_URL=https://edysma.net/ELPB/backend/public/api

# Punta al server dove sono ospitate le pagine legali
LEGAL_BASE_URL=https://edysma.net/ELPB/standalone-renderer
```

**Comportamento**: Link generati come `https://edysma.net/ELPB/standalone-renderer/legal/{slug}/{tipo}` (assoluti)

## 🎯 Come Funziona

### BlockRenderer Constructor
```php
public function __construct(
    $apiUrl = '',
    $pageId = 0,
    $pageSlug = '',
    $hasLegalInfo = false,
    $legalBaseUrl = ''  // <-- Nuovo parametro
)
```

### page.php - Inizializzazione
```php
// Legge dal .env
$legalBaseUrl = $_ENV['LEGAL_BASE_URL'] ?? '';

// Passa al costruttore
$renderer = new BlockRenderer($apiUrl, $pageId, $pageSlug, $hasLegalInfo, $legalBaseUrl);
```

### BlockRenderer::renderLegalfooter()
```php
// Se legalBaseUrl è specificato, usa URL assoluti
$baseUrl = !empty($this->legalBaseUrl) ? $this->legalBaseUrl : '';

$legalLinks = [
    ['text' => 'Privacy', 'url' => $baseUrl . '/legal/' . $this->pageSlug . '/privacy'],
    ['text' => "Condizioni d'uso", 'url' => $baseUrl . '/legal/' . $this->pageSlug . '/condizioni'],
    ['text' => 'Cookies', 'url' => $baseUrl . '/legal/' . $this->pageSlug . '/cookies'],
];
```

## 🚀 Deployment su ilprodotto.it

### Step 1: Aggiorna Files Renderer
Copia su ilprodotto.it i file aggiornati:
- `page.php` (con fix `LEGAL_BASE_URL`)
- `.env.ilprodotto` → rinomina in `.env`

### Step 2: Configura .env
```bash
# ilprodotto.it/.env
API_BASE_URL=https://edysma.net/ELPB/backend/public/api
LEGAL_BASE_URL=https://edysma.net/ELPB/standalone-renderer
DEFAULT_SLUG=test-renderer-remoto
DEBUG=false
ENABLE_CACHE=true
CACHE_TTL=3600
```

### Step 3: Test Link
1. Apri pagina: `https://ilprodotto.it/test-renderer-remoto`
2. Scrolla a footer
3. Click su "Privacy" / "Condizioni d'uso" / "Cookies"
4. Verifica redirect a: `https://edysma.net/ELPB/standalone-renderer/legal/...`

## 🔄 Scenari di Utilizzo

### Scenario 1: Tutto su Stesso Server
```
Server: edysma.net/ELPB
Backend: edysma.net/ELPB/backend
Renderer: edysma.net/ELPB/standalone-renderer
Legal Pages: edysma.net/ELPB/standalone-renderer/legal/...

Config .env:
LEGAL_BASE_URL=  # (vuoto, usa URL relativi)
```

### Scenario 2: Renderer Remoto con Legal Pages Centralizzate
```
Server Backend: edysma.net/ELPB
Server Renderer: ilprodotto.it
Legal Pages: edysma.net/ELPB/standalone-renderer/legal/...

Config .env su ilprodotto.it:
LEGAL_BASE_URL=https://edysma.net/ELPB/standalone-renderer
```

### Scenario 3: Renderer Remoto con Legal Pages Locali
```
Server Backend: edysma.net/ELPB
Server Renderer: ilprodotto.it (con copia legal-pages.php)
Legal Pages: ilprodotto.it/legal/...

Config .env su ilprodotto.it:
LEGAL_BASE_URL=  # (vuoto, usa URL relativi locali)
```

## ⚠️ Note Importanti

### 1. Legal Pages Devono Esistere
Se imposti `LEGAL_BASE_URL=https://edysma.net/...`, assicurati che:
- Il server edysma.net abbia il file `legal-pages.php`
- Il file `.htaccess` con routing legal sia configurato
- Le pagine siano accessibili pubblicamente

### 2. CORS Non Necessario
I link legali sono semplici tag `<a href>`, non chiamate AJAX, quindi non servono configurazioni CORS speciali.

### 3. Template Menu-Legali
Le pagine legali usano template dalla directory `menu-legali/`:
- `privacy-policy.html`
- `condizioni-uso.html`
- `cookie-policy.html`

Assicurati che questa directory esista sul server che ospita le legal pages.

### 4. Fallback Sicuro
Se `LEGAL_BASE_URL` non è specificato:
- Link generati come `/legal/...` (relativi)
- Funziona se legal-pages.php è locale
- Altrimenti 404

## 🐛 Troubleshooting

### Link puntano a ilprodotto.it invece di edysma.net
**Causa**: `LEGAL_BASE_URL` non configurato nel `.env`

**Fix**:
```bash
# Aggiungi in .env
LEGAL_BASE_URL=https://edysma.net/ELPB/standalone-renderer
```

### 404 su Pagine Legali
**Causa 1**: Legal pages non esistono su server target

**Fix**: Verifica che `legal-pages.php` esista e sia accessibile

**Causa 2**: Routing .htaccess mancante

**Fix**: Verifica `.htaccess` con regola:
```apache
RewriteRule ^legal/([a-z0-9-]+)/(privacy|condizioni|cookies)$ legal-pages.php/$1/$2 [L,QSA]
```

### Link ancora relativi dopo config
**Causa**: File `page.php` non aggiornato con fix

**Fix**:
1. Scarica file `page.php` aggiornato dal repo
2. Carica su ilprodotto.it
3. Verifica che contenga:
   ```php
   $legalBaseUrl = $_ENV['LEGAL_BASE_URL'] ?? '';
   $renderer = new BlockRenderer($apiUrl, $pageId, $pageSlug, $hasLegalInfo, $legalBaseUrl);
   ```

### Cache .env
**Causa**: PHP sta cachando vecchie variabili ambiente

**Fix**:
```bash
# Riavvia PHP-FPM (se disponibile)
sudo service php-fpm restart

# Oppure riavvia Apache
sudo service apache2 restart

# Oppure tocca il file .env per aggiornare timestamp
touch .env
```

## ✅ Testing Checklist

Dopo la configurazione, verifica:

- [ ] File `page.php` aggiornato su server remoto
- [ ] `.env` con `LEGAL_BASE_URL` configurato
- [ ] Apri pagina renderizzata in browser
- [ ] Ispeziona HTML footer (View Source)
- [ ] Verifica link `<a href="https://edysma.net/...">` (assoluti)
- [ ] Click su link "Privacy"
- [ ] Verifica redirect corretto e pagina caricata
- [ ] Ripeti per "Condizioni d'uso" e "Cookies"
- [ ] Test anche da mobile/tablet

## 📚 Documentazione Correlata

- `PIANO_RENDERER_REMOTO.md` - Guida completa deployment remoto
- `CHECKLIST_DEPLOYMENT_REMOTO.md` - Checklist rapida
- `MIGRAZIONE_PAGINE_LEGALI.md` - Sistema pagine legali standalone

## 📅 Changelog

**2026-02-03**: Fix iniziale
- Aggiunto parametro `LEGAL_BASE_URL` al `.env`
- Modificato `page.php` per passare `legalBaseUrl` al BlockRenderer
- Documentazione completa
