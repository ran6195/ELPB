# Deploy su ilprodotto.it

Guida per caricare l'homepage e lo standalone renderer su ilprodotto.it

## File da caricare

Carica i seguenti file/cartelle nella root di ilprodotto.it:

```
ilprodotto.it/
├── index.html                    # Homepage con form di test
└── standalone-renderer/          # Renderer per landing pages
    ├── page.php
    ├── config.php
    ├── BlockRenderer.php
    ├── .env
    └── .htaccess
```

## Configurazione

Il file `.env` è già configurato per connettersi all'API su edysma.net:

```env
API_BASE_URL=https://edysma.net/ELPB/backend/public/api
DEBUG=false
```

## Permessi File (IMPORTANTE!)

Dopo aver caricato i file, imposta i permessi corretti:

```bash
# Connettiti via SSH al server ilprodotto.it
ssh utente@ilprodotto.it

# Vai nella directory del sito
cd /percorso/al/sito

# Imposta permessi
chmod 644 standalone-renderer/*.php
chmod 600 standalone-renderer/.env
chmod 644 standalone-renderer/.htaccess
chmod 644 index.html
```

## Test

1. **Homepage**: Apri https://ilprodotto.it
   - Dovresti vedere la homepage con il form per testare le landing pages

2. **Renderer diretto**: Testa con uno slug pubblicato
   ```
   https://ilprodotto.it/standalone-renderer/page.php?slug=NOME-SLUG
   ```

3. **Con URL rewriting** (se configurato):
   ```
   https://ilprodotto.it/standalone-renderer/NOME-SLUG
   ```

## Troubleshooting

### Errore "Page not found or not published"
- Verifica che la pagina sia pubblicata nell'editor su edysma.net/ELPB
- Controlla che lo slug sia corretto

### Errore 500 o pagina bianca
- Verifica i permessi dei file (644 per .php, 600 per .env)
- Controlla che PHP sia abilitato
- Verifica che cURL sia abilitato: `php -m | grep curl`

### Form non funziona
- Abilita temporaneamente debug mode nel .env:
  ```env
  DEBUG=true
  ```
- Controlla che l'API sia raggiungibile da ilprodotto.it
- Verifica i log PHP del server

## Note

- Il backend rimane su edysma.net/ELPB
- Il renderer su ilprodotto.it si connette all'API via HTTPS
- Per creare/modificare landing pages, usa l'editor su edysma.net/ELPB
- Per visualizzarle pubblicamente, usa ilprodotto.it

## Deploy via FTP/SFTP

Se usi un client FTP (FileZilla, Cyberduck, etc.):

1. Connettiti a ilprodotto.it
2. Carica `index.html` nella root
3. Crea la cartella `standalone-renderer/`
4. Carica tutti i file dentro `standalone-renderer/`
5. Imposta i permessi tramite il pannello di controllo o SSH

## Deploy automatico (opzionale)

Puoi creare uno script di deploy:

```bash
#!/bin/bash
# deploy-ilprodotto.sh

# Configurazione
REMOTE_HOST="ilprodotto.it"
REMOTE_USER="tuoutente"
REMOTE_PATH="/percorso/al/sito"

# Upload files
scp index.html $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/
scp -r standalone-renderer $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

# Set permissions
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && chmod 644 standalone-renderer/*.php && chmod 600 standalone-renderer/.env && chmod 644 standalone-renderer/.htaccess"

echo "Deploy completato!"
```

Rendilo eseguibile:
```bash
chmod +x deploy-ilprodotto.sh
./deploy-ilprodotto.sh
```
