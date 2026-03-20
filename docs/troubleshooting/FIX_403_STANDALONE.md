# Fix Errore 403 - Renderer Standalone

## 🔍 Problema
Errore 403 Forbidden quando si visita una landing page pubblica sul renderer standalone dopo aver aggiornato i file.

## ✅ Soluzione Rapida

### Opzione 1: Correggi Permessi via SSH

Connettiti al server via SSH e esegui:

```bash
# Naviga nella directory del renderer standalone
cd /percorso/al/sito/standalone-renderer

# Correggi permessi su tutti i file PHP
find . -type f -name "*.php" -exec chmod 644 {} \;

# Correggi permessi sulle directory
find . -type d -exec chmod 755 {} \;

# Verifica i permessi
ls -la
```

I permessi corretti devono essere:
- **File PHP**: `644` (rw-r--r--)
- **Directory**: `755` (rwxr-xr-x)

### Opzione 2: Correggi Permessi via FTP/FileZilla

1. Apri FileZilla e connettiti al server
2. Naviga in `standalone-renderer/`
3. Seleziona tutti i file `.php` (BlockRenderer.php, page.php, config.php)
4. Tasto destro → **Permessi file...**
5. Imposta permessi numerici: **644**
   - ☑ Lettura (Owner)
   - ☑ Scrittura (Owner)
   - ☑ Lettura (Group)
   - ☑ Lettura (Public)
6. Clicca **OK**

### Opzione 3: Usa lo Script Automatico

Carica questo script sul server e eseguilo:

```bash
# Carica fix-permissions.sh nella directory standalone-renderer/
# poi eseguilo:
chmod +x fix-permissions.sh
./fix-permissions.sh
```

---

## 🔎 Diagnosi Dettagliata

Se i permessi sono corretti ma l'errore persiste, verifica:

### 1. Controlla Ownership File

```bash
ls -la standalone-renderer/
```

L'owner deve essere l'utente del web server (es. `www-data`, `apache`, o il tuo utente hosting).

Se è sbagliato:
```bash
# Cambia owner (sostituisci USERNAME con il tuo utente)
chown -R USERNAME:USERNAME standalone-renderer/
```

### 2. Verifica .htaccess

Controlla che `.htaccess` non blocchi l'accesso:

```bash
cat standalone-renderer/.htaccess
```

Deve contenere solo le regole di rewrite, NON deve avere `Deny from all` o `Require all denied`.

### 3. Verifica Log Apache/Nginx

```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

Cerca messaggi tipo:
- "Permission denied"
- "client denied by server configuration"
- "SELinux is preventing"

### 4. SELinux (se applicabile)

Se il server usa SELinux, potrebbe bloccare l'accesso:

```bash
# Verifica se SELinux è attivo
sestatus

# Se attivo, imposta contesto corretto
chcon -R -t httpd_sys_content_t standalone-renderer/
```

---

## 🧪 Test Permessi

Dopo aver corretto i permessi, testa:

```bash
# Verifica permessi finali
ls -la standalone-renderer/

# Output atteso:
# -rw-r--r-- 1 user group  SIZE DATE BlockRenderer.php
# -rw-r--r-- 1 user group  SIZE DATE page.php
# -rw-r--r-- 1 user group  SIZE DATE config.php
```

Poi visita la landing page nel browser:
```
https://ilprodotto.it/standalone-renderer/page.php?slug=test
```

---

## 📋 Checklist Risoluzione

- [ ] Permessi file PHP impostati a `644`
- [ ] Permessi directory impostati a `755`
- [ ] Owner file corretto (utente web server)
- [ ] File `.htaccess` non blocca l'accesso
- [ ] SELinux configurato correttamente (se applicabile)
- [ ] Landing page visibile senza errore 403

---

## 🆘 Se il Problema Persiste

Fornisci questi dettagli per assistenza:

```bash
# Esegui questi comandi e inviami l'output:

# 1. Permessi attuali
ls -la standalone-renderer/

# 2. Contenuto .htaccess
cat standalone-renderer/.htaccess

# 3. Ultimi errori log
tail -20 /var/log/apache2/error.log
# oppure
tail -20 /var/log/nginx/error.log

# 4. Configurazione PHP
php -v
php -m | grep -i "mysql\|pdo"
```

---

## 💡 Prevenzione Futura

Quando carichi file in futuro:

1. **Via FTP**: Imposta permessi a 644 dopo ogni upload
2. **Via SSH/SCP**: Usa script `set-permissions.sh` dopo ogni deploy
3. **Via ZIP**: Dopo `unzip`, esegui sempre `chmod 644 *.php`

Aggiungi al tuo workflow di deploy:

```bash
# Dopo upload/unzip, esegui sempre:
find standalone-renderer -type f -name "*.php" -exec chmod 644 {} \;
find standalone-renderer -type d -exec chmod 755 {} \;
```
