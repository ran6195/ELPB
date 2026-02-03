# Piano Deployment Renderer Remoto su ilprodotto.it

## 🎯 Obiettivo
Collaudare il rendering di pagine definite su **edysma.net/ELPB** (backend + editor) visualizzate su **ilprodotto.it** (solo renderer standalone).

## 📋 Architettura Target

```
┌─────────────────────────────┐         ┌─────────────────────────────┐
│   edysma.net/ELPB           │         │   ilprodotto.it             │
│                             │         │                             │
│  ┌────────────────────┐     │         │  ┌────────────────────┐     │
│  │  Backend API       │◄────┼─────────┼──│  Renderer          │     │
│  │  (Slim + MySQL)    │     │  HTTPS  │  │  Standalone        │     │
│  └────────────────────┘     │         │  └────────────────────┘     │
│           │                 │         │           │                 │
│  ┌────────▼───────────┐     │         │           │                 │
│  │  Database          │     │         │           │                 │
│  │  (pages, blocks)   │     │         │    (Nessun database)        │
│  └────────────────────┘     │         │                             │
│                             │         │                             │
│  ┌────────────────────┐     │         │                             │
│  │  Frontend Editor   │     │         │                             │
│  │  (Vue 3)           │     │         │                             │
│  └────────────────────┘     │         │                             │
└─────────────────────────────┘         └─────────────────────────────┘
```

## ✅ Vantaggi Architettura Attuale

Il renderer standalone è **già pronto** per deployment remoto:
- ✅ Usa chiamate API (cURL) invece di connessione database diretta
- ✅ Configurazione centralizzata via `.env`
- ✅ Endpoint pubblico `/api/page/{slug}` disponibile sul backend
- ✅ Nessuna dipendenza da database locale

## 📝 Fasi del Piano

### FASE 1: Verifica Backend su edysma.net

**Obiettivo**: Assicurarsi che l'endpoint API pubblico funzioni correttamente

#### 1.1 Test Endpoint Pubblico
```bash
# Test da terminale locale
curl -X GET "https://edysma.net/ELPB/backend/public/api/page/test-slug" \
  -H "Accept: application/json"

# Risposta attesa (200 OK):
{
  "id": 1,
  "title": "Pagina Test",
  "slug": "test-slug",
  "is_published": true,
  "styles": {...},
  "blocks": [...]
}

# Risposta errore (404):
{
  "error": "Page not found or not published"
}
```

#### 1.2 Verifica CORS
Il backend deve accettare richieste cross-origin da `ilprodotto.it`:
```php
// backend/public/index.php - CORS middleware già presente
->withHeader('Access-Control-Allow-Origin', '*')
```
✅ Già configurato con wildcard `*`

#### 1.3 Crea Pagina di Test
Dall'editor su edysma.net/ELPB:
1. Crea nuova pagina con slug `test-renderer-remoto`
2. Aggiungi vari tipi di blocchi (Hero, Text, Form, Slider, etc.)
3. **Pubblica** la pagina (`is_published = true`)
4. Testa rendering su edysma.net standalone prima di procedere

---

### FASE 2: Preparazione Files Renderer

**Obiettivo**: Preparare pacchetto renderer per ilprodotto.it

#### 2.1 Files da Copiare
```
standalone-renderer/
├── .htaccess              # Routing Apache
├── .env                   # Configurazione (DA MODIFICARE)
├── page.php               # Entry point renderer
├── legal-pages.php        # Pagine legali (opzionale)
├── thank-you.php          # Thank you page
├── config.php             # Configurazione base
├── BlockRenderer.php      # Classe rendering blocchi
└── helpers/
    └── LegalTemplateProcessor.php  # Helper pagine legali (opzionale)
```

#### 2.2 Configurazione .env per ilprodotto.it
Crea file `.env` specifico:

```bash
# API Configuration - PUNTA A BACKEND REMOTO
API_BASE_URL=https://edysma.net/ELPB/backend/public/api

# Optional: Default slug se nessuno specificato
DEFAULT_SLUG=test-renderer-remoto

# Debug mode (disabilita in produzione)
DEBUG=false

# Cache settings (opzionale per performance)
ENABLE_CACHE=true
CACHE_TTL=3600

# Base path (se ilprodotto.it è in sottocartella)
BASE_PATH=
```

#### 2.3 Verifica .htaccess
```apache
# standalone-renderer/.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Escludi file fisici
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # Route tutti gli slug a page.php
    RewriteRule ^([a-zA-Z0-9\-]+)$ page.php?slug=$1 [L,QSA]
</IfModule>
```

---

### FASE 3: Deployment su ilprodotto.it

**Obiettivo**: Deploy renderer e test funzionamento

#### 3.1 Accesso Server
```bash
# Via FTP/SFTP o SSH
# Determina document root (es. /var/www/html o /public_html)
```

#### 3.2 Upload Files
**Opzione A: Directory Root**
```
ilprodotto.it/
├── .htaccess
├── .env
├── page.php
├── legal-pages.php
├── thank-you.php
├── config.php
├── BlockRenderer.php
└── helpers/
```

**Opzione B: Sottocartella /renderer**
```
ilprodotto.it/renderer/
├── .htaccess
├── .env
├── page.php
└── ...
```
Se usi sottocartella, modifica `.env`:
```bash
BASE_PATH=/renderer
```

#### 3.3 Permessi Files
```bash
chmod 644 .env
chmod 644 *.php
chmod 755 helpers/
```

#### 3.4 Verifica Requisiti Server
- ✅ PHP 7.4+ (meglio 8.0+)
- ✅ cURL extension abilitata
- ✅ Apache mod_rewrite abilitato
- ✅ allow_url_fopen = On

Test requisiti:
```bash
# Crea file test-requirements.php
<?php
phpinfo();
echo "PHP Version: " . phpversion() . "\n";
echo "cURL enabled: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";
echo "allow_url_fopen: " . ini_get('allow_url_fopen') . "\n";
?>
```

---

### FASE 4: Testing Rendering

**Obiettivo**: Verificare funzionamento completo

#### 4.1 Test Base - Rendering Pagina
```
URL: https://ilprodotto.it/test-renderer-remoto
oppure: https://ilprodotto.it/page.php?slug=test-renderer-remoto

Expected: Pagina renderizzata correttamente con tutti i blocchi
```

**Checklist Visiva:**
- [ ] Hero block con immagine di sfondo
- [ ] Header con logo e menu
- [ ] Text block con formattazione
- [ ] Immagini caricate da edysma.net
- [ ] Slider Swiper funzionante
- [ ] Form renderizzato correttamente
- [ ] Footer con link
- [ ] Stili applicati (colori, font, spacing)

#### 4.2 Test Funzionalità - Submit Form Lead
```
1. Compila form sulla pagina renderizzata
2. Submit form
3. Verifica redirect a thank-you page
4. Controlla su edysma.net/ELPB admin panel che lead sia stato salvato
```

**Nota**: L'endpoint `/api/leads` è **pubblico** quindi funzionerà da ilprodotto.it

#### 4.3 Test Pagine Legali (se usate)
```
URL: https://ilprodotto.it/legal-pages.php?slug=test-renderer-remoto&type=privacy
```

#### 4.4 Test Responsive
- [ ] Desktop (1920px)
- [ ] Tablet (768px)
- [ ] Mobile (375px)

#### 4.5 Test Google Fonts
Verifica che i font custom vengano caricati correttamente:
```html
<!-- Dovrebbe essere presente nel <head> -->
<link href="https://fonts.googleapis.com/css2?family=NomeFont..." rel="stylesheet">
```

#### 4.6 Test GTM (se configurato)
Verifica snippet Google Tag Manager nel source HTML

#### 4.7 Test Performance
```bash
# Test velocità caricamento
curl -w "@curl-format.txt" -o /dev/null -s https://ilprodotto.it/test-renderer-remoto

# Oppure usa tools online:
# - GTmetrix
# - PageSpeed Insights
# - WebPageTest
```

---

### FASE 5: Troubleshooting

**Obiettivo**: Risolvere eventuali problemi

#### 5.1 Errore "Page not found"

**Causa**: API non raggiungibile o pagina non pubblicata

**Debug**:
```bash
# Abilita DEBUG in .env
DEBUG=true

# Test API manualmente
curl https://edysma.net/ELPB/backend/public/api/page/test-renderer-remoto

# Verifica slug corretto nel database edysma.net
SELECT id, title, slug, is_published FROM pages WHERE slug = 'test-renderer-remoto';
```

**Fix**:
- Verifica pagina pubblicata su edysma.net
- Controlla slug esatto (case-sensitive? no, ma verifica)
- Testa API endpoint direttamente con browser/Postman

#### 5.2 Immagini Non Caricate

**Causa**: Path immagini non corretto

**Debug**:
```html
<!-- Ispeziona source HTML, cerca tag img -->
<img src="..." />
```

**Fix**: Le immagini devono avere URL completi:
```
✅ https://edysma.net/ELPB/backend/public/uploads/images/foto.jpg
❌ /uploads/images/foto.jpg (path relativo non funziona)
```

Verifica `BlockRenderer.php` usi URL assoluti per immagini.

#### 5.3 CORS Errors

**Sintomo**: Console browser mostra errori CORS

**Fix**: Verifica header CORS nel backend:
```bash
curl -I https://edysma.net/ELPB/backend/public/api/page/slug
# Deve includere:
# Access-Control-Allow-Origin: *
```

#### 5.4 Form Submit Fallisce

**Causa**: Endpoint `/api/leads` non raggiungibile

**Debug**:
```javascript
// Apri console browser, guarda errori network
// Verifica URL submit form in BlockRenderer.php
```

**Fix**:
```php
// BlockRenderer.php - renderFormBlock()
// Usa URL completo backend per action form
$backendUrl = $_ENV['API_BASE_URL'] ?? 'https://edysma.net/ELPB/backend/public/api';
$formAction = $backendUrl . '/leads';
```

#### 5.5 Slider Non Funziona

**Causa**: Script Swiper non caricato

**Fix**: Verifica CDN Swiper nel `<head>`:
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
```

#### 5.6 Stili Non Applicati

**Causa**: TailwindCSS CDN non caricato

**Fix**: Verifica nel `<head>`:
```html
<script src="https://cdn.tailwindcss.com"></script>
```

#### 5.7 Performance Lenta

**Causa**: Troppe chiamate API, immagini pesanti

**Fix**:
```bash
# Abilita cache in .env
ENABLE_CACHE=true
CACHE_TTL=3600

# Ottimizza immagini su edysma.net
# Considera CDN per asset statici
```

---

### FASE 6: Testing Multi-Pagina

**Obiettivo**: Testare rendering di pagine diverse

#### 6.1 Crea Pagine Aggiuntive su edysma.net
```
- homepage-prodotto
- chi-siamo
- contatti
- landing-offerta
```

#### 6.2 Test Rendering su ilprodotto.it
```
https://ilprodotto.it/homepage-prodotto
https://ilprodotto.it/chi-siamo
https://ilprodotto.it/contatti
https://ilprodotto.it/landing-offerta
```

#### 6.3 Test Switch Pagine
Verifica che cambiando slug l'API restituisca pagine diverse con contenuti corretti.

---

### FASE 7: Configurazione Produzione

**Obiettivo**: Ottimizzare per produzione

#### 7.1 Disabilita Debug
```bash
# .env
DEBUG=false
```

#### 7.2 Abilita Cache
```bash
ENABLE_CACHE=true
CACHE_TTL=3600  # 1 ora
```

#### 7.3 HTTPS Enforce
```apache
# .htaccess - aggiungi
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### 7.4 Security Headers
```apache
# .htaccess - aggiungi
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

#### 7.5 robots.txt
Se non vuoi indicizzazione:
```
# ilprodotto.it/robots.txt
User-agent: *
Disallow: /
```

Oppure per indicizzare solo alcune pagine:
```
User-agent: *
Allow: /homepage-prodotto
Allow: /chi-siamo
Disallow: /test-*
```

---

### FASE 8: Documentazione

**Obiettivo**: Documentare deployment per riferimento futuro

#### 8.1 Documenta Configurazione
Crea file `DEPLOYMENT_ILPRODOTTO.md`:
```markdown
# Deployment Renderer su ilprodotto.it

## Server Info
- Hosting: [nome provider]
- IP: xxx.xxx.xxx.xxx
- Document Root: /var/www/html
- PHP Version: 8.1
- Accesso: FTP/SSH

## Configurazione
- Backend API: https://edysma.net/ELPB/backend/public/api
- Cache: Abilitata (1 ora)
- Debug: Disabilitato

## Pagine Attive
- /homepage-prodotto
- /chi-siamo
- /contatti

## Maintenance
- Update renderer: upload nuovo BlockRenderer.php
- Purge cache: elimina file cache/* (se implementato)
- Check logs: controllare error_log PHP
```

#### 8.2 Checklist Manutenzione
```
□ Backup settimanale files renderer
□ Monitor error logs
□ Test mensile endpoint API
□ Update PHP security patches
□ Review cache performance
```

---

## 📊 Metriche di Successo

Al termine del collaudo, verificare:

✅ **Funzionalità**
- [ ] Pagine renderizzano correttamente
- [ ] Tutti i tipi di blocchi funzionano
- [ ] Form submit salva lead su edysma.net
- [ ] Immagini/video caricano
- [ ] Slider funziona
- [ ] Responsive OK

✅ **Performance**
- [ ] Page load < 3 secondi
- [ ] API response time < 500ms
- [ ] Immagini ottimizzate

✅ **Sicurezza**
- [ ] HTTPS attivo
- [ ] Debug disabilitato
- [ ] .env non accessibile da web
- [ ] Security headers configurati

✅ **SEO** (se richiesto)
- [ ] Meta tags corretti
- [ ] robots.txt configurato
- [ ] Sitemap (opzionale)

---

## 🚀 Next Steps (Post-Collaudo)

Una volta verificato il funzionamento su ilprodotto.it:

1. **Scalabilità**: Testare su altri domini remoti
2. **CDN**: Valutare CDN per asset statici (immagini, video)
3. **Caching Avanzato**: Implementare Redis/Memcached
4. **Monitoring**: Setup uptime monitoring (Pingdom, UptimeRobot)
5. **Analytics**: Aggiungere tracking (GA4, Plausible)
6. **Multi-Tenant**: Gestire più clienti con renderer remoti

---

## 📞 Supporto

In caso di problemi:

1. Check logs PHP (`error_log`)
2. Test API endpoint manualmente
3. Verifica requisiti server (PHP, cURL, mod_rewrite)
4. Consulta `TROUBLESHOOTING_*.md` docs
5. Debug mode con `DEBUG=true` in `.env`

---

## 📅 Timeline Stimata

- **FASE 1** (Verifica Backend): 30 min
- **FASE 2** (Preparazione Files): 30 min
- **FASE 3** (Deployment): 1 ora
- **FASE 4** (Testing): 1 ora
- **FASE 5** (Troubleshooting): 30 min - 2 ore (variabile)
- **FASE 6** (Multi-Pagina): 30 min
- **FASE 7** (Produzione): 30 min
- **FASE 8** (Documentazione): 30 min

**Totale**: 5-7 ore (primo deployment)
**Deployments successivi**: 1-2 ore

---

## ✅ Conclusioni

Questa architettura permette di:
- ✅ Centralizzare gestione contenuti su edysma.net
- ✅ Distribuire rendering su domini multipli
- ✅ Mantenere unico database e backend
- ✅ Scalare facilmente aggiungendo renderer remoti
- ✅ Separare editor (privato) da renderer (pubblico)

L'applicazione è **già pronta** per questo scenario grazie all'architettura API-first del renderer standalone.
