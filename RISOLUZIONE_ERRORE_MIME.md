# Risoluzione Errore MIME Type

## Problema
```
Failed to load module script: Expected a JavaScript-or-Wasm module script
but the server responded with a MIME type of "text/html"
```

## Causa
Apache sta reindirizzando le richieste ai file JavaScript a `index.html` invece di servirli direttamente.

## Soluzioni

### ✅ Soluzione 1: Aggiornare .htaccess (CONSIGLIATO)

Il file `.htaccess` è già stato aggiornato in `frontend/dist/.htaccess`. Ricaricalo sul server:

```bash
# Sul server
cd /var/www/html/ELPB
# Ricarica il file .htaccess aggiornato
```

**Contenuto corretto dell'.htaccess:**
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /ELPB/

  # Non reindirizzare i file esistenti (JS, CSS, immagini, ecc.)
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d

  # Reindirizza tutto il resto a index.html per Vue Router
  RewriteRule ^ index.html [L]
</IfModule>

# Forza i tipi MIME corretti
<IfModule mod_mime.c>
  AddType application/javascript .js
  AddType text/css .css
  AddType image/svg+xml .svg
  AddType image/webp .webp
</IfModule>
```

### ✅ Soluzione 2: Verificare Configurazione Apache

Assicurati che Apache abbia:

```bash
# 1. Modulo rewrite abilitato
sudo a2enmod rewrite

# 2. Modulo mime abilitato
sudo a2enmod mime

# 3. Riavvia Apache
sudo systemctl restart apache2
```

### ✅ Soluzione 3: Verificare VirtualHost

Nel file di configurazione del VirtualHost, assicurati che `AllowOverride All` sia impostato:

```apache
<Directory "/var/www/html/ELPB">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### ✅ Soluzione 4: Verificare Path dei File

1. **Test pagina di debug:**
   - Apri: `https://edysma.net/ELPB/test.html`
   - Clicca sui link per verificare se i file assets sono accessibili

2. **Test manuale dei file JS:**
   ```bash
   curl -I https://edysma.net/ELPB/assets/index-CzzTdsn5.js
   ```

   Dovrebbe rispondere con:
   ```
   HTTP/1.1 200 OK
   Content-Type: application/javascript
   ```

   Se risponde con `Content-Type: text/html`, l'.htaccess non funziona.

### ✅ Soluzione 5: Alternativa - .htaccess nella Root

Se il problema persiste, prova a mettere l'.htaccess nella directory principale `/var/www/html/`:

```apache
# In /var/www/html/.htaccess

<Directory "/var/www/html/ELPB">
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteBase /ELPB/
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ /ELPB/index.html [L]
    </IfModule>
</Directory>
```

### ✅ Soluzione 6: Disabilita .htaccess Temporaneamente (Debug)

Per testare se il problema è nell'.htaccess:

```bash
# Rinomina temporaneamente .htaccess
cd /var/www/html/ELPB
mv .htaccess .htaccess.bak

# Testa se i file JS vengono serviti correttamente
curl -I https://edysma.net/ELPB/assets/index-CzzTdsn5.js

# Se funziona, il problema è nell'.htaccess
# Se NON funziona, il problema è nella configurazione Apache
```

### ✅ Soluzione 7: Configura nel VirtualHost (Alternativa)

Invece di usare .htaccess, configura direttamente nel VirtualHost:

```apache
<VirtualHost *:80>
    ServerName edysma.net
    DocumentRoot /var/www/html

    <Directory "/var/www/html/ELPB">
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted

        RewriteEngine On
        RewriteBase /ELPB/
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ /ELPB/index.html [L]

        <FilesMatch "\.(js|css)$">
            Header set Content-Type "application/javascript"
        </FilesMatch>
    </Directory>
</VirtualHost>
```

## Test Finale

Dopo aver applicato una soluzione:

1. **Svuota cache browser** (Ctrl+Shift+R o Cmd+Shift+R)
2. **Apri Developer Tools** (F12)
3. **Vai alla tab Network**
4. **Ricarica**: `https://edysma.net/ELPB/`
5. **Verifica** che i file .js abbiano:
   - Status: `200 OK`
   - Type: `application/javascript` o `script`

## Checklist Rapida

- [ ] File `.htaccess` aggiornato in `/var/www/html/ELPB/`
- [ ] `mod_rewrite` abilitato in Apache
- [ ] `mod_mime` abilitato in Apache
- [ ] `AllowOverride All` nel VirtualHost
- [ ] Apache riavviato
- [ ] Cache browser svuotata
- [ ] Test con `test.html` superato
- [ ] File JS accessibili direttamente

## Se Nulla Funziona

Prova il **Rebuild senza base path**:

```bash
# Locale
cd frontend
npm run build -- --mode development

# Questo builderà con base: '/' invece di '/ELPB/'
# Poi dovrai servire la app dalla root invece che dalla sottocartella
```

---

**Per assistenza ulteriore, verifica i log di Apache:**
```bash
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log
```
