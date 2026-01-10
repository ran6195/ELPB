# 🔧 Troubleshooting URL Rewriting

## Problema: URL Puliti Non Funzionano

Se `page.php?slug=test` funziona ma `/test` no, segui questa guida.

## Soluzione Rapida per /testLPSA/

### 1. Usa il file .htaccess già configurato

```bash
# Sostituisci .htaccess con la versione per testLPSA
cp .htaccess.testLPSA .htaccess
```

### 2. Verifica che funzioni

Apri nel browser:
```
https://edysma.net/testLPSA/testedysma
```

## Diagnosi Automatica

Accedi al file di test:
```
https://edysma.net/testLPSA/test-rewrite.php
```

Questo script ti dirà esattamente cosa non va.

## Configurazione Manuale

### Step 1: Identifica la Tua Directory

Se i file sono in:
- `https://edysma.net/testLPSA/` → RewriteBase è `/testLPSA/`
- `https://edysma.net/landing/` → RewriteBase è `/landing/`
- `https://edysma.net/` → RewriteBase è `/`

### Step 2: Modifica .htaccess

Apri il file `.htaccess` e trova la riga:

```apache
RewriteBase /
```

Cambiala con il tuo path:

```apache
RewriteBase /testLPSA/
```

### Step 3: Salva e Testa

Ricarica la pagina:
```
https://edysma.net/testLPSA/testedysma
```

## Problemi Comuni

### ❌ Errore 404 "Not Found"

**Causa**: RewriteBase errato

**Soluzione**:
```apache
# Nel file .htaccess, cambia:
RewriteBase /testLPSA/
```

### ❌ Errore 500 "Internal Server Error"

**Causa 1**: mod_rewrite non abilitato

**Soluzione**: Contatta l'hosting per abilitare mod_rewrite

**Causa 2**: Sintassi .htaccess errata

**Soluzione**: Controlla che non ci siano errori di battitura

### ❌ Pagina bianca o redirect infinito

**Causa**: RewriteRule sbagliata

**Soluzione**: Verifica che .htaccess contenga:
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ page.php/$1 [L,QSA]
```

### ❌ "No input file specified" ⚠️ PROBLEMA COMUNE

**Causa**: PATH_INFO non supportato dal server (tipico con PHP-FPM)

**Soluzione Rapida**:

1. Sul server, sostituisci `.htaccess`:
```bash
cp .htaccess.testLPSA .htaccess
```

2. Oppure modifica manualmente `.htaccess` e cambia la riga:
```apache
# DA (vecchio):
RewriteRule ^(.*)$ page.php/$1 [L,QSA]

# A (nuovo):
RewriteRule ^(.+)$ page.php?slug=$1 [L,QSA]
```

**Nota**: Non serve modificare `page.php`, gestisce già entrambi i metodi automaticamente.

**Verifica**: Dopo la modifica, ricarica la pagina. Ora dovrebbe funzionare!

## Verifica Configurazione Apache

### Controlla AllowOverride

Il VirtualHost di Apache deve avere:

```apache
<Directory /var/www/html/edysma.net/testLPSA>
    AllowOverride All
    # ...
</Directory>
```

Se `AllowOverride None`, l'.htaccess viene ignorato!

### Controlla mod_rewrite

```bash
# Su server con accesso SSH
apache2ctl -M | grep rewrite

# Dovrebbe mostrare:
# rewrite_module (shared)
```

## Template .htaccess per Diverse Directory

### Root (https://sito.com/)
```apache
RewriteBase /
```

### Sottodirectory Singola (https://sito.com/landing/)
```apache
RewriteBase /landing/
```

### Sottodirectory Multipla (https://sito.com/public/landing/)
```apache
RewriteBase /public/landing/
```

## Test Manuale

### 1. Test Base
```
https://edysma.net/testLPSA/page.php?slug=testedysma
```
✅ Se funziona: PHP è OK, API è OK

### 2. Test Rewrite
```
https://edysma.net/testLPSA/testedysma
```
✅ Se funziona: Rewrite configurato correttamente
❌ Se non funziona: Problema con .htaccess o mod_rewrite

### 3. Test PATH_INFO
```
https://edysma.net/testLPSA/page.php/testedysma
```
✅ Se funziona: PATH_INFO supportato
❌ Se non funziona: Usa soluzione alternativa query string

## Checklist Completa

- [ ] File .htaccess presente nella stessa directory di page.php
- [ ] RewriteBase configurato correttamente (/testLPSA/)
- [ ] mod_rewrite abilitato su Apache
- [ ] AllowOverride All nel VirtualHost
- [ ] Nessun errore nel file .htaccess
- [ ] PATH_INFO supportato dal server
- [ ] Permessi file corretti (644 per .htaccess)

## Still Not Working?

Se dopo aver seguito tutti i passi ancora non funziona:

### Soluzione Temporanea

Usa sempre il formato con query string:
```
https://edysma.net/testLPSA/page.php?slug=TUOSLUG
```

Funziona sempre, non dipende da mod_rewrite.

### Debugging Avanzato

1. Abilita debug Apache:
```apache
# Aggiungi in .htaccess
RewriteLog "/tmp/rewrite.log"
RewriteLogLevel 3
```

2. Controlla i log:
```bash
tail -f /tmp/rewrite.log
```

3. Guarda gli errori Apache:
```bash
tail -f /var/log/apache2/error.log
```

## Supporto

Se il problema persiste:

1. Esegui `test-rewrite.php` e copia l'output
2. Controlla i log di Apache
3. Verifica con l'hosting provider che:
   - mod_rewrite sia abilitato
   - AllowOverride sia impostato su All
   - PATH_INFO sia supportato

---

**Quick Fix per edysma.net/testLPSA/**

1. `cp .htaccess.testLPSA .htaccess`
2. Testa: `https://edysma.net/testLPSA/testedysma`
3. Se non funziona: `https://edysma.net/testLPSA/test-rewrite.php`
