# Fix Salvataggio Google Tag Manager

## 🐛 Problema Risolto

Il campo `tracking_settings` (Google Tag Manager) non veniva salvato nel database.

**Causa**: Il campo non era incluso nei metodi `store()`, `update()` e `duplicate()` del PageController.

---

## ✅ Modifiche Applicate

### File modificati:

1. **backend/src/Models/Page.php**
   - ✅ Aggiunto `tracking_settings` a `$fillable` (riga 19)
   - ✅ Aggiunto `tracking_settings` a `$casts` (riga 29)

2. **backend/src/Controllers/PageController.php**
   - ✅ Aggiunto `tracking_settings` al metodo `store()` (riga 97) - Creazione pagine
   - ✅ Aggiunto `tracking_settings` al metodo `update()` (riga 149) - Aggiornamento pagine
   - ✅ Aggiunto `tracking_settings` al metodo `duplicate()` (riga 321) - Duplicazione pagine

---

## 📦 Deploy Backend (Aggiornato)

### Archivio pronto:
**`deploy-backend-gtm-20260115.tar.gz`** - Include tutti i 3 file necessari:
```
backend/database/migrations/add_tracking_settings.php
backend/src/Models/Page.php
backend/src/Controllers/PageController.php
```

### Istruzioni Deploy:

**1. Carica i file sul server**

Via FTP o SSH:
```bash
# Estrai l'archivio
tar -xzf deploy-backend-gtm-20260115.tar.gz

# I file vengono estratti nelle posizioni corrette:
backend/database/migrations/add_tracking_settings.php
backend/src/Models/Page.php
backend/src/Controllers/PageController.php
```

**2. Esegui la migration** (se non già fatto)

```bash
cd backend
php database/migrations/add_tracking_settings.php
```

Output atteso:
```
Adding tracking_settings column to pages table...
✓ Column tracking_settings added successfully

Migration completed successfully!
```

**3. Riavvia PHP** (se necessario)

Se usi PHP-FPM o un server di produzione, riavvia PHP per caricare le modifiche al Model:
```bash
# Esempio per PHP-FPM
sudo systemctl restart php-fpm

# O per Apache con mod_php
sudo systemctl restart apache2
```

---

## 🧪 Test Funzionalità

### Test Locale (sviluppo)

1. Apri l'editor: `http://localhost:3000`
2. Crea o apri una pagina
3. Vai in **Impostazioni Pagina** (icona ingranaggio)
4. Scorri fino a "Integrazioni"
5. Attiva il toggle **"Google Tag Manager"**
6. Inserisci un GTM ID: `GTM-TEST123`
7. Clicca **"Salva Pagina"**

**Verifica console browser (F12):**
```javascript
// Nella risposta dell'API dovrebbe esserci:
{
  "id": 1,
  "title": "...",
  "tracking_settings": {
    "gtm_enabled": true,
    "gtm_id": "GTM-TEST123"
  },
  ...
}
```

8. Ricarica la pagina nell'editor
9. Apri di nuovo le Impostazioni
10. ✅ Il GTM ID dovrebbe essere ancora presente

### Test Produzione (online)

1. Apri l'editor su `edysma.net/ELPB`
2. Ripeti i passi sopra
3. Pubblica la pagina
4. Visualizza la pagina pubblica
5. Tasto destro → **"Visualizza sorgente pagina"**
6. Cerca `googletagmanager` nel codice

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

---

## 🔍 Verifica Database Diretta

Se hai accesso a phpMyAdmin o console MySQL:

```sql
-- Verifica che la colonna esista
DESCRIBE pages;

-- Dovrebbe mostrare tracking_settings di tipo JSON

-- Verifica il contenuto di una pagina
SELECT id, title, tracking_settings
FROM pages
WHERE id = 1;

-- Output esempio:
-- id | title      | tracking_settings
-- 1  | Test Page  | {"gtm_enabled": true, "gtm_id": "GTM-TEST123"}
```

---

## ⚠️ Troubleshooting

### Problema: GTM non viene ancora salvato

**Soluzione 1**: Verifica che tutti i file siano stati caricati
```bash
# Controlla che i file esistano e siano aggiornati
ls -lah backend/src/Models/Page.php
ls -lah backend/src/Controllers/PageController.php

# Verifica la data di modifica (deve essere recente)
```

**Soluzione 2**: Riavvia PHP/Apache
```bash
sudo systemctl restart php-fpm
# oppure
sudo systemctl restart apache2
```

**Soluzione 3**: Svuota cache del browser
- Apri in modalità incognito
- Oppure Ctrl+Shift+R per hard refresh

### Problema: Errore "Column 'tracking_settings' not found"

**Causa**: Migration non eseguita

**Soluzione**:
```bash
php backend/database/migrations/add_tracking_settings.php
```

### Problema: Gli snippet GTM non appaiono nell'HTML

**Causa**: Il frontend non è stato rebuildata o i renderer non sono aggiornati

**Soluzione**:
```bash
# Frontend
cd frontend
npm run build
# Carica frontend/dist/ sul server

# Renderer standalone - carica su tutti i siti:
standalone-renderer/page.php
standalone-renderer/BlockRenderer.php

# Joomla - installa componente v2.0.7
com_landingpages_v2.0.7_gtm.zip
```

---

## ✅ Checklist Completa

- [ ] Migration eseguita (colonna tracking_settings creata)
- [ ] Page.php aggiornato (fillable + casts)
- [ ] PageController.php aggiornato (store + update + duplicate)
- [ ] PHP riavviato (se necessario)
- [ ] Test salvataggio GTM (ID salvato correttamente)
- [ ] Test ricarica pagina (ID persiste)
- [ ] Frontend rebuildata e caricata (se necessario)
- [ ] Renderer standalone aggiornati (se necessario)
- [ ] Componente Joomla v2.0.7 installato (se necessario)
- [ ] Test snippet GTM nel sorgente HTML

---

## 📋 File Correlati

- **Guida completa deploy**: `DEPLOY_GTM_FORM_BUTTON.md`
- **Archivio backend**: `deploy-backend-gtm-20260115.tar.gz`
- **Archivio standalone**: `deploy-standalone-gtm-20260115.tar.gz`
- **Componente Joomla**: `com_landingpages_v2.0.7_gtm.zip`
