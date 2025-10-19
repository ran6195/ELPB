# Guida al Deploy dell'Applicazione

## Problemi risolti

### 1. Pagina bianca - URL hardcoded
La pagina bianca iniziale era causata da URL hardcoded che puntavano a `localhost`. Ora l'applicazione usa variabili d'ambiente.

### 2. Pagina bianca - Vue Router base path
Un secondo problema di pagina bianca era causato da Vue Router che non conosceva il base path `/ELPB/`.
**Fix applicato**: `src/router/index.js` ora usa `createWebHistory(import.meta.env.BASE_URL)` invece di `createWebHistory()`.

### 3. Stili dei blocchi (sfondo, colori) non salvati
I blocchi venivano creati con `styles: {}` vuoto. Se l'utente non apriva mai il pannello di modifica, gli stili rimanevano vuoti e non venivano salvati.
**Fix applicato**: I nuovi blocchi ora vengono creati con stili di default (`backgroundColor: 'transparent'`, `textColor: 'inherit'`, `padding: '40px 20px'`).

## IMPORTANTE: Sottocartella vs Root

### Se l'app è in una SOTTOCARTELLA (es: `/ELPB/`)

**1. Configura `vite.config.js`:**
```js
base: process.env.NODE_ENV === 'production' ? '/ELPB/' : '/',
```

**2. Configura `.htaccess`:**
```apache
RewriteBase /ELPB/
RewriteRule . /ELPB/index.html [L]
```

### Se l'app è nella ROOT del dominio

**1. Configura `vite.config.js`:**
```js
base: '/',
```

**2. Configura `.htaccess`:**
```apache
RewriteBase /
RewriteRule . /index.html [L]
```

## Passi per il deploy

### 1. Configurare l'URL dell'API in produzione

Modifica il file `/frontend/.env.production` e inserisci l'URL del tuo backend:

```env
# Esempio se in sottocartella
VITE_API_URL=https://tuodominio.com/ELPB/backend/public/api

# Esempio se nella root
VITE_API_URL=https://tuodominio.com/backend/public/api
```

### 2. Build del Frontend

```bash
cd frontend
npm run build
```

Questo creerà una cartella `dist` con i file compilati.

### 3. Upload dei file sul server

**Frontend:**
- Carica il contenuto della cartella `frontend/dist` nella root del tuo sito (es: `public_html/` o `www/`)
- Il file `.htaccess` è già incluso nella build per supportare Vue Router in history mode

**Backend:**
- Carica la cartella `backend` sul server (es: `public_html/backend/`)
- Assicurati che la cartella `backend/public` sia accessibile

### 4. Configurare il database in produzione

Modifica il file `backend/.env` con le credenziali del database di produzione:

```env
DB_HOST=localhost
DB_DATABASE=nome_database_produzione
DB_USERNAME=username_produzione
DB_PASSWORD=password_produzione
```

### 5. Eseguire le migrazioni sul server

```bash
cd backend
php database/migrations/create_tables.php
```

### 6. Configurare i permessi

Assicurati che le seguenti cartelle abbiano permessi di scrittura:
```bash
chmod 755 backend/public/uploads
```

## Struttura consigliata sul server

```
public_html/
├── index.html              (frontend build)
├── assets/                 (frontend assets)
├── .htaccess              (routing Vue)
└── backend/
    ├── public/
    │   ├── index.php
    │   └── uploads/
    ├── src/
    ├── config/
    ├── database/
    └── .env
```

## Troubleshooting

### Pagina bianca
- Verifica che `.htaccess` sia stato caricato
- Controlla la console del browser per errori
- Verifica che `VITE_API_URL` in `.env.production` sia corretto
- Verifica che Vue Router usi `import.meta.env.BASE_URL` in `src/router/index.js`

### Errori API
- Verifica che il backend sia accessibile dall'URL configurato
- Controlla i log del server PHP
- Assicurati che CORS sia configurato correttamente in `backend/public/index.php`

### Routing non funziona
- Verifica che `mod_rewrite` sia abilitato su Apache
- Controlla che `.htaccess` sia nella root del frontend

### Gli stili dei blocchi (colore sfondo, ecc) non vengono salvati

**Causa**: Il server usa MySQL < 5.7.8 che non supporta il tipo di campo `JSON` nativo.

**Soluzione**:
1. Sul server, esegui la migrazione compatibile invece di quella standard:
   ```bash
   cd backend
   php database/migrations/create_tables_compatible.php
   ```
   Questa usa il tipo `TEXT` invece di `JSON`, che è compatibile con tutte le versioni di MySQL.

2. **Importante**: Se hai già creato le tabelle con `create_tables.php`, devi prima rimuovere il database o le tabelle esistenti.

3. Per verificare quale versione di MySQL stai usando e vedere i dati raw nel database:
   ```
   GET https://tuodominio.com/ELPB/backend/public/api/debug/database
   ```
   Questo endpoint ti mostrerà:
   - La versione di MySQL
   - I dati raw salvati nel database
   - Come vengono deserializzati gli stili

**Nota**: I modelli Eloquent gestiscono automaticamente la serializzazione/deserializzazione JSON anche con campi TEXT, quindi non serve modificare il codice dell'applicazione.

## Test locale della build di produzione

Prima di fare l'upload, puoi testare la build:

```bash
cd frontend
npm run build
npm run preview
```

Poi apri `http://localhost:4173` per vedere la versione di produzione.
