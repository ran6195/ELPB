# Guida URL Rewriting - Landing Page Builder

## 📝 Obiettivo

Trasformare gli URL da:
```
https://ilprodotto.it/standalone-renderer/page.php?slug=pagina-test-1
```

A:
```
https://ilprodotto.it/pagina-test-1
```

---

## 🎯 Come Funziona

1. L'utente visita `https://ilprodotto.it/pagina-test-1`
2. Apache intercetta la richiesta tramite `.htaccess`
3. La reindirizza internamente a `standalone-renderer/page.php?slug=pagina-test-1`
4. Il browser mantiene l'URL pulito nella barra degli indirizzi

**Importante**: È un **reindirizzamento interno** (transparent), NON un redirect 301/302.

---

## 🚀 Procedura di Installazione

### Passo 1: Backup .htaccess Esistente

```bash
# SSH sul server
ssh user@ilprodotto.it

cd /home/ilprodotto.it/public_html

# Backup .htaccess esistente (se presente)
cp .htaccess .htaccess.backup-$(date +%Y%m%d)

# Verifica backup
ls -la .htaccess*
```

### Passo 2: Carica Nuovo .htaccess

**Opzione A - Via FTP/FileZilla:**

1. Rinomina `root-htaccess-urlrewrite.txt` in `.htaccess` sul tuo computer
2. Apri FileZilla e connettiti al server
3. Naviga nella root del sito (`/public_html` o `/httpdocs`)
4. Carica il file `.htaccess` (sovrascrivi se esiste, ma hai fatto backup!)
5. Imposta permessi: `644` (rw-r--r--)

**Opzione B - Via SSH:**

```bash
# Crea il file .htaccess nella root
nano /home/ilprodotto.it/public_html/.htaccess

# Copia il contenuto da .htaccess-root-clean
# oppure da root-htaccess-urlrewrite.txt

# Salva (Ctrl+O, Enter, Ctrl+X)

# Imposta permessi corretti
chmod 644 .htaccess
```

### Passo 3: Verifica mod_rewrite Attivo

```bash
# Controlla se mod_rewrite è abilitato
apache2ctl -M | grep rewrite
# Dovrebbe mostrare: rewrite_module (shared)

# Se non è attivo, attivalo:
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Nota:** Su hosting condivisi, mod_rewrite è solitamente già attivo.

### Passo 4: Test URL Rewriting

```bash
# Test 1: URL pulito (dovrebbe funzionare)
curl -I https://ilprodotto.it/pagina-test-1

# Dovrebbe restituire 200 OK (o 404 se la pagina non esiste nel database)

# Test 2: URL vecchio (dovrebbe ancora funzionare)
curl -I https://ilprodotto.it/standalone-renderer/page.php?slug=pagina-test-1

# Test 3: File reale (dovrebbe essere servito normalmente)
curl -I https://ilprodotto.it/favicon.ico
```

---

## ✅ Verifica Funzionamento

### Test 1: Nel Browser

1. Apri `https://ilprodotto.it/pagina-test-1`
2. Verifica che la pagina si carichi correttamente
3. Controlla che l'URL nella barra rimanga pulito (non cambi)
4. Apri DevTools (F12) → Network
5. Verifica che non ci siano redirect 301/302

### Test 2: Pagina Non Esistente

1. Apri `https://ilprodotto.it/slug-inesistente`
2. Dovrebbe mostrare il messaggio "Pagina non trovata" del renderer
3. L'URL dovrebbe rimanere pulito (non redirect)

### Test 3: Directory Esistenti

1. Se hai altre directory (es. `/admin`, `/images`):
   ```
   https://ilprodotto.it/admin/
   https://ilprodotto.it/images/logo.png
   ```
2. Dovrebbero essere servite normalmente (NON reindirizzate al renderer)

---

## 🔧 Personalizzazione

### Escludere Directory Aggiuntive

Se hai altre applicazioni/directory sul sito, aggiungi esclusioni nel `.htaccess`:

```apache
# Esempio: Escludere directory /shop
RewriteCond %{REQUEST_URI} ^/shop/ [NC]
RewriteRule ^ - [L]

# Esempio: Escludere directory /blog
RewriteCond %{REQUEST_URI} ^/blog/ [NC]
RewriteRule ^ - [L]
```

**Posiziona queste regole PRIMA di:**
```apache
RewriteRule ^([a-zA-Z0-9\-]+)/?$ standalone-renderer/page.php?slug=$1 [L,QSA]
```

### Modificare Pattern Slug Accettati

**Attuale** (solo lettere, numeri, trattini):
```apache
RewriteRule ^([a-zA-Z0-9\-]+)/?$ standalone-renderer/page.php?slug=$1 [L,QSA]
```

**Con underscore:**
```apache
RewriteRule ^([a-zA-Z0-9\-_]+)/?$ standalone-renderer/page.php?slug=$1 [L,QSA]
```

**Con slug multi-livello (es. /categoria/pagina):**
```apache
RewriteRule ^([a-zA-Z0-9\-/]+)/?$ standalone-renderer/page.php?slug=$1 [L,QSA]
```

---

## 🐛 Troubleshooting

### Problema: Errore 500 Internal Server Error

**Causa 1: mod_rewrite non attivo**
```bash
# Verifica
apache2ctl -M | grep rewrite

# Attiva (se hai accesso sudo)
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Causa 2: Sintassi errata in .htaccess**
```bash
# Controlla log Apache
tail -f /var/log/apache2/error.log

# O sul tuo hosting
tail -f ~/logs/error.log
```

**Causa 3: AllowOverride disabilitato**

Nel file di configurazione Apache (`/etc/apache2/sites-available/tuosito.conf`):
```apache
<Directory /var/www/tuosito>
    AllowOverride All  # ← Deve essere "All", non "None"
</Directory>
```

### Problema: 404 Not Found su URL Puliti

**Verifica 1: .htaccess nella root**
```bash
ls -la /home/ilprodotto.it/public_html/.htaccess
# Deve esistere con permessi 644
```

**Verifica 2: RewriteEngine attivo**
```bash
# Nel .htaccess, verifica:
RewriteEngine On  # ← Deve essere presente
```

**Verifica 3: Path standalone-renderer corretto**
```bash
# Nel .htaccess, verifica:
RewriteRule ^([a-zA-Z0-9\-]+)/?$ standalone-renderer/page.php?slug=$1 [L,QSA]
#                                   ^^^^^^^^^^^^^^^^^^^^ deve corrispondere alla directory reale
```

### Problema: Loop di Redirect

**Causa: Conflitto con altre regole .htaccess**

Assicurati che il `.htaccess` del standalone-renderer NON interferisca:

```apache
# In standalone-renderer/.htaccess
# Questa riga DEVE essere presente per evitare loop:
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
```

### Problema: Funziona in Locale ma non in Produzione

**Verifica RewriteBase:**

In `.htaccess` root, se il sito è in una sottodirectory:
```apache
# Se il sito è su https://example.com/mysite/
RewriteBase /mysite/
```

---

## 📊 Confronto URL

| Tipo | URL | Visibile nel Browser | Indicizzato da Google |
|------|-----|----------------------|-----------------------|
| **Vecchio** | `/standalone-renderer/page.php?slug=test` | ❌ Brutto | ❌ Penalizzante SEO |
| **Nuovo** | `/test` | ✅ Pulito | ✅ SEO Friendly |

---

## 🔐 Sicurezza

Il `.htaccess` fornito include:

- ✅ Protezione file `.env` e config
- ✅ Blocco directory listing
- ✅ Protezione file di backup
- ✅ Header HTTP security (X-Frame-Options, X-XSS-Protection)
- ✅ Blocco accesso al file `.htaccess` stesso

---

## 🎨 SEO e Sitemap

### Aggiorna Sitemap.xml

Dopo aver attivato URL rewriting, aggiorna la tua sitemap:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://ilprodotto.it/pagina-test-1</loc>
        <lastmod>2026-01-14</lastmod>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://ilprodotto.it/altra-pagina</loc>
        <lastmod>2026-01-14</lastmod>
        <priority>0.8</priority>
    </url>
</urlset>
```

Caricala in `/sitemap.xml` e inviala a Google Search Console.

### Redirect 301 Vecchi URL (Opzionale)

Se hai già indicizzato i vecchi URL, crea redirect permanenti:

```apache
# In .htaccess root, PRIMA delle regole di rewriting
RewriteCond %{QUERY_STRING} ^slug=(.+)$
RewriteRule ^standalone-renderer/page\.php$ /%1? [R=301,L]
```

Questo converte automaticamente:
```
/standalone-renderer/page.php?slug=test → /test (redirect 301)
```

---

## 📝 Checklist Post-Deploy

- [ ] Backup `.htaccess` esistente fatto
- [ ] Nuovo `.htaccess` caricato nella root
- [ ] Permessi `.htaccess` impostati a 644
- [ ] mod_rewrite attivo sul server
- [ ] URL puliti funzionanti (test nel browser)
- [ ] URL vecchi ancora funzionanti (compatibilità)
- [ ] Directory esistenti NON reindirizzate al renderer
- [ ] File reali (CSS, immagini) serviti correttamente
- [ ] Nessun errore 500 nei log Apache
- [ ] Sitemap.xml aggiornata con URL puliti
- [ ] Redirect 301 configurati (se necessario)

---

## 🔄 Rollback

Se qualcosa non funziona:

```bash
# Ripristina .htaccess precedente
cd /home/ilprodotto.it/public_html
cp .htaccess.backup-20260114 .htaccess

# Ricarica Apache (se hai accesso)
sudo systemctl reload apache2
```

---

## 📚 File di Riferimento

- **`root-htaccess-urlrewrite.txt`** - File completo con tutti i commenti e opzioni
- **`.htaccess-root-clean`** - Versione minimalista pronta all'uso
- **Documentazione Apache mod_rewrite**: https://httpd.apache.org/docs/current/mod/mod_rewrite.html

---

## 💡 Best Practices

1. **Testa sempre in staging** prima di applicare in produzione
2. **Fai backup** prima di modificare `.htaccess`
3. **Monitora i log Apache** dopo il deploy
4. **Usa Google Search Console** per verificare indicizzazione nuovi URL
5. **Mantieni compatibilità** con vecchi URL per un periodo di transizione

---

## 🎯 Vantaggi URL Rewriting

✅ **SEO**: URL puliti sono meglio indicizzati da Google
✅ **UX**: URL leggibili e facili da condividere
✅ **Branding**: URL professionali
✅ **Sicurezza**: Nasconde la struttura interna del sito
✅ **Flessibilità**: Puoi cambiare backend senza cambiare URL pubblici
