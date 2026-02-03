# ✅ Checklist Deployment Renderer Remoto su ilprodotto.it

## Pre-Deployment (su edysma.net)

### Backend
- [ ] Verificare `APP_URL` in `.env` backend:
  ```bash
  APP_URL=https://edysma.net/ELPB/backend/public
  ```
  ⚠️ **CRITICO**: Questo determina gli URL delle immagini

- [ ] Testare endpoint API pubblico:
  ```bash
  curl https://edysma.net/ELPB/backend/public/api/page/test-slug
  ```

- [ ] Verificare CORS middleware (wildcard `*` OK)

### Pagina Test
- [ ] Creare pagina con slug `test-renderer-remoto`
- [ ] Aggiungere blocchi vari (Hero, Text, Form, Slider, Image)
- [ ] **PUBBLICARE** la pagina (`is_published = true`)
- [ ] Testare rendering su edysma.net/ELPB standalone

---

## Files da Copiare su ilprodotto.it

### ⬇️ Download da locale/edysma.net:
```
standalone-renderer/
├── .htaccess
├── page.php
├── legal-pages.php (opzionale)
├── thank-you.php
├── config.php
├── BlockRenderer.php
└── helpers/
    └── LegalTemplateProcessor.php (opzionale)
```

### ⚙️ Crea nuovo .env per ilprodotto.it:
```bash
# NON copiare .env esistente! Crea nuovo con:
API_BASE_URL=https://edysma.net/ELPB/backend/public/api
DEFAULT_SLUG=test-renderer-remoto
DEBUG=false
ENABLE_CACHE=true
CACHE_TTL=3600
BASE_PATH=
```

---

## Deployment su ilprodotto.it

### Accesso Server
- [ ] Connessione FTP/SFTP/SSH
- [ ] Identificare document root (es. `/public_html`)

### Upload Files
- [ ] Upload tutti i file in root o sottocartella `/renderer`
- [ ] Verificare `.env` presente e configurato
- [ ] Verificare `.htaccess` presente

### Permessi
```bash
chmod 644 .env
chmod 644 *.php
chmod 755 helpers/
```

### Requisiti Server
- [ ] PHP 7.4+ (controllare con `php -v`)
- [ ] cURL extension abilitata
- [ ] Apache mod_rewrite abilitato
- [ ] allow_url_fopen = On

**Test rapido**: Crea `test.php`:
```php
<?php
echo "PHP: " . phpversion() . "\n";
echo "cURL: " . (function_exists('curl_init') ? 'OK' : 'FAIL') . "\n";
echo "allow_url_fopen: " . ini_get('allow_url_fopen') . "\n";
?>
```

---

## Testing Funzionalità

### 1. Test Rendering Base
```
✅ URL: https://ilprodotto.it/test-renderer-remoto
✅ Expected: Pagina renderizzata con tutti i blocchi
```

**Checklist visiva:**
- [ ] Hero block visibile
- [ ] Immagini caricate (da edysma.net)
- [ ] Text block formattato
- [ ] Slider funziona
- [ ] Form renderizzato
- [ ] Footer presente
- [ ] Stili applicati (colori, font)
- [ ] Google Fonts caricati

### 2. Test Form Lead
- [ ] Compilare form sulla pagina
- [ ] Submit form
- [ ] Redirect a `/thank-you`
- [ ] Verificare lead salvato su edysma.net admin panel

### 3. Test Responsive
- [ ] Desktop (1920px)
- [ ] Tablet (768px)
- [ ] Mobile (375px)

### 4. Test Browser
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

### 5. Test Console Errors
- [ ] Aprire DevTools → Console
- [ ] Nessun errore JavaScript
- [ ] Nessun errore caricamento risorse (404)
- [ ] Nessun errore CORS

### 6. Test Performance
- [ ] Page load < 3 secondi
- [ ] Lighthouse score > 80

---

## Troubleshooting Rapido

### ❌ Errore "Page not found"
```bash
# Debug:
1. Verificare pagina pubblicata su edysma.net
2. Testare API direttamente:
   curl https://edysma.net/ELPB/backend/public/api/page/test-renderer-remoto
3. Controllare slug esatto (case sensitive)
4. Abilitare DEBUG=true in .env
```

### ❌ Immagini non caricate (404)
```bash
# Verificare:
1. APP_URL corretto nel backend .env (edysma.net)
2. Immagini esistono in edysma.net/ELPB/backend/public/uploads/
3. Ispezionare HTML source, controllare URL img src
4. Testare URL immagine direttamente in browser
```

### ❌ CORS Error
```bash
# Verificare CORS headers:
curl -I https://edysma.net/ELPB/backend/public/api/page/slug

# Deve includere:
Access-Control-Allow-Origin: *
```

### ❌ Form submit fallisce
```bash
# Verificare:
1. Endpoint /api/leads raggiungibile
2. Console browser per errori network
3. reCAPTCHA configurato (se abilitato)
```

### ❌ Slider non funziona
```bash
# Verificare CDN Swiper caricato:
View Source → cerca "swiper-bundle"
```

### ❌ Stili mancanti
```bash
# Verificare TailwindCSS CDN:
View Source → cerca "cdn.tailwindcss.com"
```

---

## Configurazione Produzione

### Sicurezza
- [ ] `DEBUG=false` in .env
- [ ] `.env` non accessibile da web (test: https://ilprodotto.it/.env)
- [ ] HTTPS attivo
- [ ] Security headers in .htaccess:
  ```apache
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  ```

### Performance
- [ ] `ENABLE_CACHE=true`
- [ ] `CACHE_TTL=3600` (1 ora)
- [ ] Immagini ottimizzate su edysma.net

### SEO (se richiesto)
- [ ] robots.txt configurato
- [ ] Meta tags presenti (verificare nel source)
- [ ] robots noindex su pagine test

---

## Test Multi-Pagina (opzionale)

Se vuoi testare più pagine:

### Su edysma.net - Crea pagine:
- [ ] `homepage-ilprodotto`
- [ ] `chi-siamo`
- [ ] `contatti`

### Su ilprodotto.it - Testa rendering:
- [ ] https://ilprodotto.it/homepage-ilprodotto
- [ ] https://ilprodotto.it/chi-siamo
- [ ] https://ilprodotto.it/contatti

---

## Documentazione

### Crea file NOTES_ILPRODOTTO.txt sul server:
```
Server: ilprodotto.it
Document Root: [path]
PHP Version: [version]
Backend API: https://edysma.net/ELPB/backend/public/api
Cache: Enabled (1h)
Debug: Disabled

Deployed: [data]
Last Update: [data]

Active Pages:
- /test-renderer-remoto

Maintenance:
- Backup files renderer
- Monitor error logs
- Update BlockRenderer.php quando necessario
```

---

## ✅ Sign-Off

**Deployment completato quando:**
- [ ] ✅ Tutte le checklist Pre-Deployment completate
- [ ] ✅ Files caricati e configurati
- [ ] ✅ Test funzionalità tutti passati
- [ ] ✅ Nessun errore console/logs
- [ ] ✅ Performance accettabili
- [ ] ✅ Configurazione produzione applicata
- [ ] ✅ Documentazione creata

**Tempo stimato**: 2-4 ore (primo deployment)

---

## 📞 Contatti

- Backend edysma.net: [admin email]
- Server ilprodotto.it: [hosting info]
- Docs: `PIANO_RENDERER_REMOTO.md`

---

**Note**: Questa checklist è per deployment renderer standalone su dominio remoto. Per deployment completo (backend + frontend + renderer) consulta altri docs.
