# ⚡ Fix: "No input file specified"

## Errore
Quando accedi a `https://edysma.net/testLPSA/testedysma` vedi:
```
No input file specified.
```

## Causa
Il tuo server PHP (probabilmente PHP-FPM) non supporta PATH_INFO nel modo in cui lo stiamo usando.

## ✅ Soluzione (2 minuti)

### Metodo 1: Automatico (VELOCE)

Sul server, nella directory `/testLPSA/`, esegui:

```bash
cp .htaccess.testLPSA .htaccess
```

Fatto! ✨

### Metodo 2: Manuale

1. Apri il file `.htaccess`

2. Trova questa riga:
```apache
RewriteRule ^(.*)$ page.php/$1 [L,QSA]
```

3. Cambiala in:
```apache
RewriteRule ^(.+)$ page.php?slug=$1 [L,QSA]
```

4. Salva il file

## Verifica

Ricarica la pagina:
```
https://edysma.net/testLPSA/testedysma
```

Ora dovrebbe funzionare! ✅

## Cosa Abbiamo Cambiato?

**Prima** (non funzionava):
```
https://edysma.net/testLPSA/test → page.php/test (PATH_INFO)
```

**Dopo** (funziona):
```
https://edysma.net/testLPSA/test → page.php?slug=test (Query String)
```

Entrambi danno lo stesso risultato visibile, ma il secondo è compatibile con più server.

## Se Ancora Non Funziona

1. Verifica che `.htaccess` sia nella stessa directory di `page.php`
2. Controlla i permessi: `chmod 644 .htaccess`
3. Esegui il test diagnostico: `https://edysma.net/testLPSA/test-rewrite.php`
4. Controlla che `RewriteBase /testLPSA/` sia corretto

## Configurazione Completa Funzionante

Il tuo `.htaccess` dovrebbe contenere:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /testLPSA/

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.+)$ page.php?slug=$1 [L,QSA]
</IfModule>
```

## Alternative

Se gli URL puliti continuano a dare problemi, usa sempre:
```
https://edysma.net/testLPSA/page.php?slug=TUOSLUG
```

Funziona al 100% senza bisogno di mod_rewrite.

---

**Quick Command**:
```bash
cd /path/to/testLPSA
cp .htaccess.testLPSA .htaccess
```

Ricarica e prova! 🚀
