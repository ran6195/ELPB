# Deploy GTM e Form Button Styles - Guida Completa

Questa guida copre il deployment delle due nuove funzionalità:
1. **Google Tag Manager** - Integrazione GTM nelle landing pages
2. **Form Button Styles** - Personalizzazione completa del bottone submit del form

## 📋 Componenti da Aggiornare

- ✅ Backend API (database migration + model)
- ✅ Frontend Vue (rebuild necessario)
- ✅ Standalone Renderer (per ilprodotto.it, primehome.it, infortunisticaveneta.it)
- ✅ Componente Joomla (per edysma.net) - versione 2.0.7

---

## 1️⃣ Backend - Database e API

### A. Esegui Migration Database

**Sul server di produzione:**

```bash
cd /path/to/backend
php database/migrations/add_tracking_settings.php
```

**Output atteso:**
```
Adding tracking_settings column to pages table...
✓ Column tracking_settings added successfully

Migration completed successfully!
```

### B. Aggiorna Model e Controller

**File da caricare:**
```
backend/src/Models/Page.php
backend/src/Controllers/PageController.php
```

**Percorso server:**
```
/path/to/backend/src/Models/Page.php
/path/to/backend/src/Controllers/PageController.php
```

**Modifiche incluse in Page.php:**
- Aggiunto `tracking_settings` a `$fillable`
- Aggiunto `tracking_settings` a `$casts` (deserializzazione JSON)

**Modifiche incluse in PageController.php:**
- Aggiunto `tracking_settings` al metodo `store()` (riga 97)
- Aggiunto `tracking_settings` al metodo `update()` (riga 149)
- Aggiunto `tracking_settings` al metodo `duplicate()` (riga 321)

### C. Verifica Backend

Testa l'endpoint API:
```bash
# Sostituisci con lo slug di una tua pagina
curl https://edysma.net/ELPB/backend/public/api/page/test-gtm
```

Verifica che nel JSON ci sia il campo `tracking_settings`:
```json
{
  "id": 1,
  "title": "Test Page",
  "tracking_settings": {
    "gtm_enabled": true,
    "gtm_id": "GTM-XXXXXXX"
  },
  ...
}
```

---

## 2️⃣ Frontend - Vue App

### A. Build Produzione

**Sul tuo computer locale:**

```bash
cd frontend
npm run build
```

Questo crea la cartella `frontend/dist/` con i file compilati.

### B. Carica su Server

**File/cartelle da caricare:**
```
frontend/dist/  → /path/to/ELPB/ (su edysma.net)
```

**Struttura finale su server:**
```
/home/.../public_html/ELPB/
├── index.html
├── assets/
│   ├── index-[hash].js
│   ├── index-[hash].css
│   └── ...
└── .htaccess
```

### C. File Modificati nel Build

I seguenti componenti Vue sono stati modificati e saranno inclusi nel build:
- `PageSettings.vue` - UI per configurare GTM
- `FormBlock.vue` - Stili dinamici button submit
- `BlockEditor.vue` - Controlli editor per button styles
- `PageEditor.vue` - Default button styles

---

## 3️⃣ Standalone Renderer

### A. File da Aggiornare

**File modificati:**
```
standalone-renderer/page.php
standalone-renderer/BlockRenderer.php
```

### B. Carica su Server

**Per ciascun sito (ilprodotto.it, primehome.it, infortunisticaveneta.it):**

```bash
# Via FTP/SFTP carica:
standalone-renderer/page.php          → /percorso/standalone-renderer/page.php
standalone-renderer/BlockRenderer.php → /percorso/standalone-renderer/BlockRenderer.php
```

### C. Correggi Permessi

**Se ottieni errore 403, esegui via SSH:**

```bash
cd /path/to/standalone-renderer
chmod 644 *.php
chmod 755 .
```

### D. Modifiche Incluse

**page.php:**
- Righe 170-173: Estrazione `tracking_settings` da API
- Righe 180-188: Snippet GTM in `<head>`
- Righe 234-239: Snippet GTM noscript in `<body>`

**BlockRenderer.php:**
- Righe 935-956: Estrazione stili button nel metodo `renderForm()`
- Riga 1026: Applicazione stili al button submit

---

## 4️⃣ Componente Joomla

### A. Crea Archivio Componente

**Sul tuo computer:**

```bash
cd joomla-component
zip -r com_landingpages_v2.0.7.zip com_landingpages/
```

### B. Installa su Joomla

1. Accedi al backend Joomla (edysma.net/administrator)
2. Vai a **Sistema → Installa → Estensioni**
3. Carica `com_landingpages_v2.0.7.zip`
4. Joomla rileverà che è un aggiornamento (da 2.0.6 a 2.0.7)
5. Clicca "Aggiorna"

### C. Modifiche Incluse (v2.0.7)

**File modificati nel componente:**

**site/views/page/tmpl/default.php:**
- Righe 45-48: Estrazione dati GTM
- Righe 56-64: Snippet GTM in `<head>`
- Righe 103-108: Snippet GTM noscript in `<body>`

**site/helpers/blockrenderer.php:**
- Righe 962-983: Estrazione stili button in `renderForm()`
- Riga 1053: Applicazione stili al button submit

**landingpages.xml:**
- Versione: 2.0.7
- Descrizione: Include integrazione GTM e form button styles

---

## 🧪 Test Funzionalità

### Test 1: Verifica Salvataggio GTM

1. Apri l'editor su edysma.net/ELPB
2. Apri una pagina esistente o creane una nuova
3. Clicca sull'icona ingranaggio (Impostazioni Pagina)
4. Scorri fino alla sezione "Integrazioni"
5. Attiva il toggle "Google Tag Manager"
6. Inserisci un GTM ID di test: `GTM-TEST123`
7. Clicca "Salva Pagina"
8. Riapri le impostazioni e verifica che il GTM ID sia salvato

### Test 2: Verifica Snippet GTM nel Renderer Standalone

1. Pubblica la pagina (toggle "Pubblica Pagina" verde)
2. Apri la pagina su uno dei siti standalone (es. ilprodotto.it/nome-pagina)
3. Apri il sorgente HTML (tasto destro → "Visualizza sorgente pagina")
4. Cerca `googletagmanager` nel codice

**Dovresti vedere:**

```html
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-TEST123');</script>
    <!-- End Google Tag Manager -->

    <meta charset="UTF-8">
    ...
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TEST123"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    ...
</body>
```

### Test 3: Verifica Snippet GTM nel Componente Joomla

1. Visualizza la pagina tramite Joomla (edysma.net/component/landingpages/?page_id=X)
2. Apri il sorgente HTML
3. Verifica la presenza degli snippet GTM (come sopra)

### Test 4: Verifica Form Button Styles

1. Nell'editor, aggiungi un blocco Form a una pagina
2. Seleziona il blocco Form
3. Nel pannello destro, dovresti vedere la nuova sezione "Stile Pulsante"
4. Modifica:
   - Colore sfondo: Rosso (#FF0000)
   - Colore testo: Bianco (#FFFFFF)
   - Dimensione testo: Grande (18px)
   - Arrotondamento: Pillola (9999px)
5. Salva e pubblica
6. Visualizza la pagina pubblica
7. Il bottone submit dovrebbe avere gli stili personalizzati

---

## 📦 Riepilogo File da Caricare

### Backend (edysma.net/ELPB/backend/)
```
✓ database/migrations/add_tracking_settings.php  (esegui migration)
✓ src/Models/Page.php
✓ src/Controllers/PageController.php
```

### Frontend (edysma.net/ELPB/)
```
✓ frontend/dist/*  (tutto il contenuto compilato)
```

### Standalone Renderer (ilprodotto.it, primehome.it, infortunisticaveneta.it)
```
✓ standalone-renderer/page.php
✓ standalone-renderer/BlockRenderer.php
```

### Joomla (edysma.net)
```
✓ com_landingpages_v2.0.7.zip  (installa via backend Joomla)
```

---

## ⚠️ Problemi Comuni

### Problema: GTM non appare nel sorgente HTML

**Causa 1:** Campo tracking_settings non salvato nel database
- **Soluzione:** Verifica che la migration sia stata eseguita
- Controlla con phpMyAdmin che la colonna `tracking_settings` esista nella tabella `pages`

**Causa 2:** Model Page non aggiornato
- **Soluzione:** Verifica che `backend/src/Models/Page.php` includa `tracking_settings` in `$fillable` e `$casts`

**Causa 3:** Cache browser/CDN
- **Soluzione:** Apri la pagina in incognito o fai un hard refresh (Ctrl+F5)

### Problema: Stili button form non applicati

**Causa 1:** Frontend non ribuildata
- **Soluzione:** Esegui `npm run build` e ricarica tutti i file della cartella `dist/`

**Causa 2:** Renderer PHP non aggiornato
- **Soluzione:** Verifica che `BlockRenderer.php` sia stato caricato correttamente su tutti i server

### Problema: Errore 403 dopo upload renderer

**Causa:** Permessi file non corretti dopo upload FTP
- **Soluzione:** Esegui via SSH:
  ```bash
  cd standalone-renderer
  chmod 644 *.php
  ```

---

## 🎯 Checklist Deployment

- [ ] Backend: Migration eseguita con successo
- [ ] Backend: Model Page aggiornato
- [ ] Backend: API restituisce tracking_settings
- [ ] Frontend: Build eseguito (npm run build)
- [ ] Frontend: File dist/ caricati su server
- [ ] Frontend: Editor mostra sezione GTM in PageSettings
- [ ] Frontend: Editor mostra sezione Stile Pulsante per Form
- [ ] Standalone: page.php aggiornato su tutti i siti
- [ ] Standalone: BlockRenderer.php aggiornato su tutti i siti
- [ ] Standalone: Permessi file corretti (644)
- [ ] Joomla: Componente v2.0.7 installato/aggiornato
- [ ] Test: GTM snippet visibile nel sorgente HTML
- [ ] Test: Button styles applicati correttamente

---

## 📊 Versioni Componenti

| Componente | Versione | Note |
|------------|----------|------|
| Backend API | - | + campo tracking_settings |
| Frontend Vue | - | + GTM UI + Form button UI |
| Standalone Renderer | - | + GTM snippets + Form button styles |
| Joomla Component | **2.0.7** | + GTM snippets + Form button styles |

---

## 🆘 Supporto

Se incontri problemi durante il deployment:
1. Verifica che tutti i file siano stati caricati correttamente
2. Controlla i log PHP del server per errori
3. Usa la console browser (F12) per verificare errori JavaScript
4. Testa prima su ambiente di staging se disponibile
