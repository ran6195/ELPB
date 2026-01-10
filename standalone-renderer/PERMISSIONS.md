# 🔐 Guida Permessi File - Standalone Renderer

## Permessi Corretti

### Metodo Rapido (Consigliato)

```bash
chmod +x set-permissions.sh
./set-permissions.sh
```

### Metodo Manuale

```bash
# File PHP
chmod 644 page.php
chmod 644 config.php
chmod 644 BlockRenderer.php

# File di configurazione
chmod 600 .env              # ← IMPORTANTE: massima sicurezza!
chmod 644 .env.example
chmod 644 .htaccess

# Documentazione
chmod 644 README.md
chmod 644 QUICKSTART.md
chmod 644 PERMISSIONS.md

# Script
chmod 755 set-permissions.sh
chmod 755 build.sh

# Directory (se necessario)
chmod 755 .
```

## Tabella Riepilogo

| File/Tipo | Permessi | Ottale | Descrizione |
|-----------|----------|--------|-------------|
| **File PHP** | `rw-r--r--` | `644` | Leggibile da tutti, modificabile solo da te |
| **`.env`** | `rw-------` | `600` | ⚠️ **CRITICO**: Solo tu puoi leggerlo |
| **`.htaccess`** | `rw-r--r--` | `644` | Leggibile dal webserver Apache |
| **File Markdown** | `rw-r--r--` | `644` | Documentazione leggibile |
| **Script `.sh`** | `rwxr-xr-x` | `755` | Eseguibile da tutti |
| **Directory** | `rwxr-xr-x` | `755` | Navigabile e accessibile |

## Spiegazione Permessi

### Formato: `chmod XYZ file`

- **X** = Proprietario (Owner)
- **Y** = Gruppo (Group)
- **Z** = Altri (Others)

### Valori Numerici

| Valore | Permessi | Significato |
|--------|----------|-------------|
| `7` | `rwx` | Lettura, scrittura, esecuzione |
| `6` | `rw-` | Lettura e scrittura |
| `5` | `r-x` | Lettura ed esecuzione |
| `4` | `r--` | Solo lettura |
| `0` | `---` | Nessun permesso |

### Esempi Pratici

#### 644 (rw-r--r--)
```
Owner:  rw- (6) = Può leggere e modificare
Group:  r-- (4) = Può solo leggere
Others: r-- (4) = Può solo leggere
```
✅ **Ideale per**: File PHP, HTML, .htaccess, documenti

#### 600 (rw-------)
```
Owner:  rw- (6) = Può leggere e modificare
Group:  --- (0) = Nessun accesso
Others: --- (0) = Nessun accesso
```
✅ **Ideale per**: `.env`, file con password o API keys

#### 755 (rwxr-xr-x)
```
Owner:  rwx (7) = Può leggere, modificare, eseguire
Group:  r-x (5) = Può leggere ed eseguire
Others: r-x (5) = Può leggere ed eseguire
```
✅ **Ideale per**: Script shell, directory

## Problemi Comuni

### ❌ Errore: "Permission denied"

**Causa**: File non leggibile dal webserver

**Soluzione**:
```bash
# Verifica i permessi attuali
ls -la page.php

# Se vedi -------- o simili, correggi:
chmod 644 page.php
```

### ❌ Errore: "File not found" (ma il file esiste)

**Causa**: Directory non navigabile

**Soluzione**:
```bash
# Imposta permessi directory
chmod 755 /percorso/alla/directory
```

### ❌ Il file .env è leggibile da altri

**Causa**: Permessi troppo aperti (es. 644, 664, 777)

**Soluzione**:
```bash
# Massima sicurezza
chmod 600 .env

# Verifica
ls -la .env
# Deve mostrare: -rw------- (solo owner può accedere)
```

### ❌ Script .sh non eseguibile

**Causa**: Manca il flag esecuzione

**Soluzione**:
```bash
chmod +x set-permissions.sh
chmod +x build.sh
```

## Controllo Sicurezza

### ✅ Checklist Sicurezza Permessi

Esegui questi comandi per verificare:

```bash
# 1. Verifica .env (deve essere 600)
ls -l .env | grep "^-rw-------"
echo $? # Deve stampare 0

# 2. Verifica file PHP (devono essere 644)
ls -l *.php | grep "^-rw-r--r--"

# 3. Verifica che nessun file sia 777
find . -type f -perm 0777

# Se trova file, correggili immediatamente!
```

### ⚠️ Permessi da EVITARE

| Permessi | Perché Evitarli |
|----------|-----------------|
| `777` | **PERICOLOSISSIMO**: Chiunque può modificare ed eseguire |
| `666` | **INSICURO**: Chiunque può modificare i file |
| `755` su `.env` | Altri utenti sul server possono leggere le tue credenziali |

## Permessi su Hosting Condiviso

Su alcuni hosting condivisi, il webserver usa lo stesso utente del tuo account FTP. In questo caso:

```bash
# Questi permessi funzionano bene:
chmod 644 *.php
chmod 644 .htaccess
chmod 600 .env
```

Se riscontri problemi, **NON usare 777**. Contatta invece il supporto dell'hosting.

## Verifica Finale

Dopo aver impostato i permessi, verifica:

```bash
# Lista completa con permessi
ls -la

# Output atteso:
# -rw-r--r--  page.php
# -rw-r--r--  config.php
# -rw-r--r--  BlockRenderer.php
# -rw-------  .env            ← Solo owner
# -rw-r--r--  .htaccess
# -rwxr-xr-x  set-permissions.sh
```

## Ripristino in Caso di Errori

Se hai impostato permessi sbagliati e nulla funziona:

```bash
# 1. Resetta tutto ai permessi base
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# 2. Imposta permessi speciali
chmod 600 .env
chmod 755 *.sh

# 3. Testa il sito
```

## Ulteriori Risorse

- [Linux File Permissions Explained](https://www.linux.com/training-tutorials/understanding-linux-file-permissions/)
- [chmod Calculator](https://chmod-calculator.com/)

---

**Hai dubbi sui permessi?**

1. Usa lo script automatico: `./set-permissions.sh`
2. Verifica con: `ls -la`
3. Controlla che `.env` sia `600` (critico!)
4. Altri file PHP possono essere `644`

**In caso di problemi persistenti**, contatta il supporto del tuo hosting provider.
