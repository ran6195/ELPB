# Configurazione Upload Video

Per consentire l'upload di video (fino a 100MB), è necessario configurare correttamente i limiti PHP.

## Metodo 1: .user.ini (Raccomandato per hosting condiviso)

Il file `.user.ini` è già presente in `backend/public/.user.ini` con i seguenti valori:

```ini
upload_max_filesize = 100M
post_max_size = 110M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

**Nota**: `.user.ini` funziona solo con PHP-FPM (FastCGI). Dopo averlo caricato, attendere 5 minuti affinché PHP ricarichi le impostazioni.

## Metodo 2: php.ini (Raccomandato per server dedicati/VPS)

Se hai accesso al file `php.ini`, modifica questi valori:

```ini
upload_max_filesize = 100M
post_max_size = 110M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

Dopo aver modificato `php.ini`, riavvia il servizio PHP:

```bash
# Apache
sudo systemctl restart apache2

# Nginx con PHP-FPM
sudo systemctl restart php8.1-fpm  # sostituisci 8.1 con la tua versione
```

## Metodo 3: .htaccess (Solo per Apache con mod_php)

Aggiungi queste righe al file `.htaccess` in `backend/public/`:

```apache
php_value upload_max_filesize 100M
php_value post_max_size 110M
php_value memory_limit 256M
php_value max_execution_time 300
php_value max_input_time 300
```

**Nota**: Questo metodo funziona solo se PHP è eseguito come modulo Apache (non FastCGI).

## Verifica Configurazione

Per verificare che le impostazioni siano applicate correttamente:

1. Crea un file `phpinfo.php` in `backend/public/`:
   ```php
   <?php phpinfo(); ?>
   ```

2. Visita `http://tuosito.com/backend/public/phpinfo.php`

3. Cerca questi valori nella pagina:
   - `upload_max_filesize` deve essere ≥ 100M
   - `post_max_size` deve essere ≥ 110M
   - `memory_limit` deve essere ≥ 256M

4. **IMPORTANTE**: Elimina `phpinfo.php` dopo la verifica per motivi di sicurezza.

## Formati Video Supportati

Il backend accetta i seguenti formati video:
- MP4 (video/mp4)
- MPEG (video/mpeg)
- QuickTime/MOV (video/quicktime)
- AVI (video/x-msvideo)
- WebM (video/webm)

## Troubleshooting

### Errore: "File exceeds upload_max_filesize"
- Aumenta `upload_max_filesize` nel metodo che stai usando
- Riavvia PHP/Apache se hai modificato php.ini

### Errore: "POST Content-Length exceeds the limit"
- Aumenta `post_max_size` (deve essere maggiore di upload_max_filesize)

### Errore: "Maximum execution time exceeded"
- Aumenta `max_execution_time` e `max_input_time`

### .user.ini non funziona
- Verifica che il server usi PHP-FPM (non mod_php)
- Attendi almeno 5 minuti dopo aver caricato il file
- Verifica con phpinfo() se i valori sono cambiati
- Prova metodi alternativi (php.ini o .htaccess)

## Directory Upload

I video vengono salvati in:
```
backend/public/uploads/videos/
```

Assicurati che questa directory abbia i permessi corretti:
```bash
chmod 755 backend/public/uploads/videos/
```
