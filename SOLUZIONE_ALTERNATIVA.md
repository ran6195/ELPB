# Soluzione Alternativa - Deploy senza Sottocartella

Se l'.htaccess continua a dare problemi, usa questa soluzione alternativa che elimina completamente il problema del base path.

## Opzione A: Serve l'app dalla Root del Dominio

### 1. Crea un Sottodominio o Dominio Dedicato

Esempio: `landingbuilder.edysma.net` invece di `edysma.net/ELPB/`

### Configurazione:
```apache
<VirtualHost *:80>
    ServerName landingbuilder.edysma.net
    DocumentRoot /var/www/html/ELPB

    <Directory /var/www/html/ELPB>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Rebuild Frontend:
```bash
cd frontend

# Modifica vite.config.js temporaneamente
# Cambia: base: mode === 'production' ? '/ELPB/' : '/',
# In:     base: '/',

npm run build

# Carica frontend/dist/* su /var/www/html/ELPB/
```

### .htaccess semplificato:
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . index.html [L]
</IfModule>
```

## Opzione B: Configura Direttamente nel VirtualHost (CONSIGLIATO)

Invece di usare .htaccess, configura tutto nel VirtualHost di Apache.

### File: `/etc/apache2/sites-available/edysma.conf`

```apache
<VirtualHost *:80>
    ServerName edysma.net
    DocumentRoot /var/www/html

    # Frontend - Landing Page Builder
    Alias /ELPB /var/www/html/ELPB
    <Directory /var/www/html/ELPB>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted

        # Serve i file statici direttamente
        <FilesMatch "\.(js|css|png|jpg|jpeg|gif|svg|webp|ico|json|woff|woff2|ttf|eot)$">
            Header set Content-Type "application/javascript" env=JS_FILE
            Header set Content-Type "text/css" env=CSS_FILE
        </FilesMatch>

        # Rewrite per Vue Router
        RewriteEngine On
        RewriteBase /ELPB/

        # NON reindirizzare assets
        RewriteRule ^assets/ - [L]

        # NON reindirizzare file statici
        RewriteRule \.(js|css|png|jpg|jpeg|gif|svg|webp|ico|json|woff|woff2|ttf|eot)$ - [L]

        # Reindirizza il resto a index.html
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /ELPB/index.html [L]
    </Directory>

    # Backend API
    Alias /ELPB/backend/public /var/www/html/ELPB/backend/public
    <Directory /var/www/html/ELPB/backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Applica la configurazione:
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo apache2ctl configtest
sudo systemctl restart apache2
```

### Vantaggi:
- ✅ Più performante (no .htaccess parsing)
- ✅ Più controllo sulla configurazione
- ✅ Meno problemi con i path

## Opzione C: Usa un Proxy Nginx (se disponibile)

Se hai Nginx disponibile:

```nginx
server {
    listen 80;
    server_name edysma.net;

    location /ELPB/ {
        alias /var/www/html/ELPB/;
        try_files $uri $uri/ /ELPB/index.html;

        location ~* \.(js|css)$ {
            add_header Content-Type application/javascript;
            expires 1M;
        }
    }

    location /ELPB/backend/public/ {
        alias /var/www/html/ELPB/backend/public/;
        try_files $uri $uri/ /ELPB/backend/public/index.php?$query_string;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        }
    }
}
```

## Debug: Testa i File Direttamente

Sul server, prova a fare una richiesta diretta:

```bash
# Testa se il file esiste
ls -la /var/www/html/ELPB/assets/

# Testa con curl
curl -I https://edysma.net/ELPB/assets/index-CzzTdsn5.js

# Dovrebbe restituire:
# HTTP/1.1 200 OK
# Content-Type: application/javascript

# Se restituisce text/html o 404, c'è un problema di configurazione
```

## Quick Fix: Disabilita Completamente .htaccess

```bash
# Sul server
cd /var/www/html/ELPB
rm .htaccess

# Poi testa di nuovo
```

Se funziona senza .htaccess:
1. Il problema è nell'.htaccess
2. Usa la configurazione VirtualHost (Opzione B)

Se NON funziona senza .htaccess:
1. Il problema è nella configurazione Apache
2. Controlla il VirtualHost
3. Verifica che AllowOverride sia All

---

**Scegli l'opzione che preferisci e fammi sapere!**
