# Landing Page Standalone Renderer

Script PHP standalone per renderizzare landing pages pubblicate senza dipendenze da framework.

## Caratteristiche

- ✅ **Zero dipendenze** - Solo PHP nativo e cURL
- ✅ **Facile installazione** - Basta copiare i file su qualsiasi hosting PHP
- ✅ **Configurazione semplice** - File .env per le impostazioni
- ✅ **Tutti i blocchi supportati** - 15 tipi di blocchi inclusi
- ✅ **Form funzionanti** - Invio lead all'API backend
- ✅ **Responsive** - Utilizza Tailwind CSS via CDN
- ✅ **SEO friendly** - Meta tags personalizzabili
- ✅ **reCAPTCHA integrato** - Protezione spam nei form

## Requisiti

- PHP 7.4 o superiore
- cURL abilitato (di solito già presente)
- Hosting web con supporto PHP

## Installazione

### 1. Copia i file sul server

Carica i seguenti file nella directory desiderata del tuo hosting:

```
standalone-renderer/
├── page.php          # Script principale
├── config.php        # Configurazione base
├── BlockRenderer.php # Rendering dei blocchi
├── .env              # Configurazione (da creare)
└── .htaccess         # URL rewriting (opzionale)
```

### 2. Configura il file .env

Copia `.env.example` in `.env` e modifica le impostazioni:

```bash
cp .env.example .env
```

Edita `.env`:

```env
# URL dell'API backend
API_BASE_URL=https://tuodominio.com/backend/public/api

# Debug (disattiva in produzione)
DEBUG=false

# Slug di default (opzionale)
# DEFAULT_SLUG=homepage
```

### 3. Configura URL Rewriting (Opzionale)

Per URL puliti tipo `tuodominio.com/pagina` invece di `page.php?slug=pagina`.

#### Apache (.htaccess)

Crea il file `.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Se il file o directory non esiste
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # Reindirizza tutto a page.php con PATH_INFO
    RewriteRule ^(.*)$ page.php/$1 [L]
</IfModule>
```

#### Nginx

Aggiungi alla configurazione del server:

```nginx
location / {
    try_files $uri $uri/ /page.php$uri;
}

location ~ \.php {
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_param PATH_INFO $fastcgi_path_info;
    # ... resto della configurazione PHP-FPM
}
```

### 4. Imposta i Permessi Corretti (IMPORTANTE!)

I permessi dei file sono fondamentali per la sicurezza. Usa lo script fornito:

```bash
chmod +x set-permissions.sh
./set-permissions.sh
```

Oppure imposta manualmente:

```bash
# File PHP - leggibili ed eseguibili dal webserver
chmod 644 page.php
chmod 644 config.php
chmod 644 BlockRenderer.php

# File .env - SOLO proprietario può leggere (massima sicurezza)
chmod 600 .env

# Altri file di configurazione
chmod 644 .env.example
chmod 644 .htaccess

# Documentazione
chmod 644 README.md
chmod 644 QUICKSTART.md

# Script (se necessari)
chmod 755 *.sh
```

**Riepilogo Permessi:**

| File/Directory | Permessi | Notazione | Descrizione |
|---------------|----------|-----------|-------------|
| `*.php` | `644` | `rw-r--r--` | Leggibile da tutti, scrivibile solo da owner |
| `.env` | `600` | `rw-------` | **CRITICO**: Solo owner può leggere |
| `.htaccess` | `644` | `rw-r--r--` | Leggibile dal webserver |
| `*.sh` | `755` | `rwxr-xr-x` | Eseguibile |
| Directory | `755` | `rwxr-xr-x` | Accessibile e navigabile |

**⚠️ IMPORTANTE:**
- Il file `.env` DEVE avere permessi `600` per evitare che altri utenti sul server leggano le tue credenziali API
- Se vedi errori di permessi negati, controlla che il webserver possa leggere i file (644 è sufficiente)
- NON usare `777` - è un rischio di sicurezza!

## Uso

### URL di base

```
# Con query string
https://tuodominio.com/page.php?slug=homepage

# Con URL rewriting
https://tuodominio.com/homepage
```

### Accedere alle pagine

Le pagine vengono recuperate dall'API in base allo slug. Solo le pagine **pubblicate** sono visibili.

**Esempi:**

```
# Pagina homepage
https://tuodominio.com/?slug=homepage
https://tuodominio.com/homepage

# Pagina chi-siamo
https://tuodominio.com/?slug=chi-siamo
https://tuodominio.com/chi-siamo

# Pagina contatti
https://tuodominio.com/?slug=contatti
https://tuodominio.com/contatti
```

## Configurazione Avanzata

### Cache (Opzionale)

Per migliorare le prestazioni, è possibile implementare un sistema di cache modificando `page.php`:

```php
// Esempio di cache semplice con file
$cacheFile = __DIR__ . '/cache/' . md5($slug) . '.html';
$cacheTime = 3600; // 1 ora

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    // Usa cache
    readfile($cacheFile);
    exit;
}

// Genera pagina...
$output = renderPage($page);

// Salva in cache
file_put_contents($cacheFile, $output);
echo $output;
```

### Debug

Per vedere messaggi di errore dettagliati, imposta nel `.env`:

```env
DEBUG=true
```

**⚠️ IMPORTANTE**: Disattiva sempre il debug in produzione!

### Personalizzazione

#### Modificare lo stile globale

Edita il tag `<style>` in `page.php` per aggiungere CSS personalizzato:

```php
<style>
    body {
        margin: 0;
        padding: 0;
        /* Il tuo CSS */
    }
</style>
```

#### Aggiungere script personalizzati

Aggiungi script prima della chiusura del tag `</body>` in `page.php`:

```php
<script>
    // Il tuo JavaScript
    console.log('Pagina caricata!');
</script>
```

## Blocchi Supportati

Lo script supporta tutti i 15 tipi di blocchi:

1. **Header** - Navbar con logo e menu
2. **Hero** - Sezione principale con CTA
3. **Text** - Blocco di testo con formattazione
4. **Two Column Text-Image** - Testo e immagine affiancati
5. **Two Column Image-Text** - Immagine e testo affiancati
6. **Features** - Griglia di vantaggi/caratteristiche
7. **Services Grid** - Griglia servizi con immagini
8. **CTA** - Call to Action centrato
9. **Form** - Form contatti con invio lead
10. **Video** - Video embed o file diretto
11. **Video Info** - Video con informazioni
12. **Image Slide** - Immagine a schermo intero
13. **Slider** - Carousel immagini con Swiper.js
14. **Map** - Google Maps embed
15. **Footer** - Footer con info azienda

## Form e Lead

I form inviano i dati all'API configurata in `API_BASE_URL`.

**Endpoint utilizzato:**
```
POST /api/leads
```

**Dati inviati:**
- `page_id` - ID della pagina
- Campi dinamici del form (nome, email, telefono, messaggio, ecc.)
- `g-recaptcha-response` (se reCAPTCHA attivo)

**Risposta:**
- Successo: redirect a `thank_you_url` o pagina di conferma
- Errore: messaggio di errore visualizzato

## Troubleshooting

### Pagina bianca o errore 500

1. Controlla i permessi dei file (644 per .php, 755 per directory)
2. Verifica che cURL sia abilitato: `php -m | grep curl`
3. Attiva debug mode nel `.env`
4. Controlla i log di PHP del server

### "Pagina non trovata"

1. Verifica che lo slug sia corretto
2. Controlla che la pagina sia **pubblicata** nel backend
3. Verifica che `API_BASE_URL` sia corretto nel `.env`
4. Testa l'API direttamente: `curl https://tuoapi.com/api/page/slug`

### Form non funziona

1. Verifica che `API_BASE_URL` sia configurato correttamente
2. Controlla che l'endpoint `/api/leads` sia raggiungibile
3. Verifica le impostazioni CORS del backend
4. Controlla la console browser per errori JavaScript

### reCAPTCHA non appare

1. Verifica che il `recaptchaSiteKey` sia configurato nel form
2. Controlla che lo script reCAPTCHA si carichi (console browser)
3. Verifica la connessione a `google.com/recaptcha`

### URL rewriting non funziona

1. Verifica che `mod_rewrite` sia abilitato su Apache
2. Controlla che `.htaccess` sia nella directory corretta
3. Verifica i permessi del file `.htaccess` (644)
4. Testa prima con `?slug=` se il rewriting non funziona

## Sicurezza

### Best Practices

1. **Disattiva debug in produzione**
   ```env
   DEBUG=false
   ```

2. **Proteggi il file .env**

   Aggiungi al `.htaccess`:
   ```apache
   <Files .env>
       Order allow,deny
       Deny from all
   </Files>
   ```

3. **HTTPS obbligatorio**

   Usa sempre HTTPS, specialmente per i form.

4. **Valida sempre lato server**

   Non fare affidamento solo sulla validazione frontend.

5. **Limita le richieste API**

   Implementa rate limiting se necessario.

## Integrazione con Altri CMS

### WordPress

Puoi integrare lo script in WordPress creando un template personalizzato:

```php
<?php
/**
 * Template Name: Landing Page
 */

// Carica lo script standalone
require_once ABSPATH . 'standalone-renderer/page.php';
```

### Drupal

Crea un modulo custom che include lo script.

### Altro CMS

Basta includere `page.php` dove necessario, assicurandoti di configurare correttamente lo slug.

## Performance

### Ottimizzazioni consigliate

1. **Abilita compressione GZIP** (solitamente già attivo)
2. **Usa CDN** per Tailwind CSS e Swiper.js
3. **Implementa cache** per le pagine (vedi sezione Cache)
4. **Ottimizza immagini** prima del caricamento
5. **Lazy loading** per immagini (già implementato via Tailwind)

## Limiti e Considerazioni

- ⚠️ Nessun caching integrato (da implementare se necessario)
- ⚠️ Dipende dalla disponibilità dell'API backend
- ⚠️ Carica risorse esterne da CDN (Tailwind, Swiper)
- ⚠️ Non supporta editing inline (solo visualizzazione)

## Supporto

Per problemi o domande:

1. Verifica la documentazione del progetto principale
2. Controlla i log di PHP e del server
3. Testa l'API backend separatamente
4. Abilita debug mode per vedere errori dettagliati

## Licenza

Questo script è parte del progetto Landing Page Builder.

## Changelog

### Versione 1.0.0 (2025-12-19)

- ✨ Prima release
- ✅ Supporto completo per tutti i 15 blocchi
- ✅ Sistema di configurazione con .env
- ✅ Form funzionanti con invio lead
- ✅ URL rewriting opzionale
- ✅ Debug mode
- ✅ SEO friendly
- ✅ Responsive design

---

**Nota**: Questo è uno script standalone. Per funzionalità complete di editing e gestione, usa l'applicazione principale Landing Page Builder.
