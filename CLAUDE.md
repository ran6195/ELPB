# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 📝 Modifiche Recenti

### Sessione 2026-01-17

**Fix Slug Duplicati e Soft Delete**
- Risolto errore "Duplicate entry for key 'pages_slug_unique'" quando si creano/duplicano pagine
- **Problema**: Il database ha constraint UNIQUE sullo slug che vale anche per pagine soft-deleted, ma Eloquent con SoftDeletes esclude le pagine cancellate dalle query normali
- **Soluzione**: Usare `withTrashed()` per verificare unicità slug anche tra pagine archiviate
- Lo slug iniziale ora è impostato a 'nuova-pagina' invece di stringa vuota
- Il backend aggiunge automaticamente un contatore se lo slug esiste già (nuova-pagina-1, nuova-pagina-2, etc.)
- File modificati:
  - `frontend/src/views/PageEditor.vue` (riga 484: `slug: 'nuova-pagina'`)
  - `backend/src/Controllers/PageController.php`:
    - Metodo `store()` (riga 87: `Page::withTrashed()->where('slug', $slug)->exists()`)
    - Metodo `update()` (righe 155-165: validazione slug con `withTrashed()` e esclusione pagina corrente)
    - Metodo `duplicate()` (riga 316: `Page::withTrashed()->where('slug', $slug)->exists()`)
    - Aggiunto try-catch per errori dettagliati (righe 74-127)

**Nuovo Blocco Social Media**
- Creato nuovo blocco "Social Media" per raggruppare link ai profili social
- Piattaforme supportate: Facebook, Instagram, X (Twitter), LinkedIn, YouTube
- Due stili icone disponibili:
  - **Colorate (Standard)**: icone con colori brand ufficiali (Facebook blu, Instagram gradient, etc.)
  - **Monocromatiche**: icone monocromatiche che ereditano il colore dal testo del blocco
- Personalizzazioni disponibili:
  - Dimensione icone: 24-96px (default: 48px)
  - Spaziatura tra icone: 8-64px (default: 16px)
  - Sfondo bottoni: colore personalizzabile o trasparente
  - Curvatura bordi: 0-50px (0=squadrato, 50=rotondo)
  - Ombra: nessuna, piccola, media, grande, extra grande
  - Bordo: spessore, colore, stile (solido/tratteggiato/punteggiato)
- Layout centrato con icone responsive
- File creati/modificati:
  - **Frontend**: `SocialBlock.vue` (nuovo componente con SVG icons inline)
  - **Editor**: `BlockEditor.vue` (righe 1173-1348: configurazione completa social block, riga 1940: aggiunto 'social' a blockTypeNames)
  - **PageEditor**:
    - Aggiunto blocco social ai blockTypes (riga 551)
    - Import SocialBlock component (riga 472)
    - Mapping in getBlockComponent (riga 1017)
    - Default content per social block (righe 965-980)
  - **Renderer Standalone**: `BlockRenderer.php` (righe 1565-1716: metodo renderSocial con SVG inline)
  - **Renderer Joomla**: `blockrenderer.php` (righe 1592-1743: metodo renderSocial statico)
- SVG icons inline sia nel componente Vue che nei renderer PHP per rendering identico
- Supporto completo per hover effect (opacity: 0.8)
- Links aprono in nuova tab con `target="_blank" rel="noopener noreferrer"` per sicurezza
- Nascosta sezione "Colore Testo" nelle impostazioni (blocco senza testo, solo icone)
- File modificato per UI: `BlockEditor.vue` (riga 1834: condizione aggiornata a `v-if="block.type !== 'video' && block.type !== 'social'"`)

**UX Editor - Chiusura Reciproca Pannelli**
- Implementata chiusura automatica tra pannello impostazioni pagina e pannello impostazioni blocco
- Quando si apre "Impostazioni pagina", il blocco selezionato viene deselezionato
- Quando si seleziona un blocco, il pannello "Impostazioni pagina" si chiude automaticamente
- File modificato: `frontend/src/views/PageEditor.vue` (righe 22, 1007)

**Uniformazione Nomi Blocchi in Italiano**
- I nomi dei blocchi nel pannello destro (impostazioni) ora sono in italiano come nel pannello sinistro
- Aggiunta mappatura `blockTypeNames` con traduzioni per tutti i 15 tipi blocco
- Computed property `blockTypeName` per mostrare il nome tradotto
- Blocco "features" rinominato "Vantaggi" per coerenza
- Blocco "services-grid" rinominato "Griglia Servizi" (era "Servizi Grid") per coerenza tra pannelli
- File modificati: `BlockEditor.vue` (righe 7, 1733, 1748-1769), `PageEditor.vue` (riga 544)

**Upload Video e Fix Swiper**
- Creato endpoint dedicato `/api/upload/video` per upload video (separato da `/api/upload/image`)
- Supporto formati: MP4, MOV, AVI, MPEG, WebM (fino a 100MB)
- Directory upload: `backend/public/uploads/videos/`
- Validazione frontend: controllo dimensione file (max 100MB) e tipo file prima dell'upload
- Messaggi errore dettagliati per problemi upload (dimensione, formato, limiti PHP)
- Configurazione PHP: file `.user.ini` con limiti aumentati (upload_max_filesize: 100M, post_max_size: 110M)
- Documentazione: `UPLOAD_VIDEO_CONFIG.md` con istruzioni per configurare limiti PHP
- Fix warning Swiper: loop mode disabilitato automaticamente quando slide < (slidesPerView * 2)
- File modificati:
  - Backend: `UploadController.php` (nuovo metodo `uploadVideo`), `index.php` (riga 87)
  - Frontend: `BlockEditor.vue` (metodo `handleVideoUpload` righe 1938-1978), `SliderBlock.vue` (computed `loopEnabled`)
  - Config: `backend/public/.user.ini` (nuovo), `UPLOAD_VIDEO_CONFIG.md` (nuovo)

**Unificazione Pulsanti Dashboard**
- Rimosso pulsante "Vedi" (pagina pubblica) dalla dashboard
- Mantenuto solo pulsante "Anteprima" che funziona per tutte le pagine (pubblicate e non)
- Layout semplificato: Anteprima + Duplica (affiancati), Elimina (full width sotto)
- File modificato: `PageList.vue` (righe 132-152, rimossa funzione `viewPublicPage`)

**Eliminazione Pagine Pubblicate con Opzione**
- Quando si elimina una pagina pubblicata, viene chiesto all'utente se vuole togliere la pubblicazione
- Dialog: "Questa pagina è pubblicata. Vuoi toglierla dalla pubblicazione ed eliminarla?"
- Se l'utente conferma: la pagina viene prima tolta dalla pubblicazione, poi eliminata
- Se l'utente annulla: l'operazione viene interrotta
- Rimosso controllo backend che impediva eliminazione pagine pubblicate (ora gestito dal frontend)
- File modificati:
  - Frontend: `PageList.vue` (metodo `deletePage` righe 201-240)
  - Backend: `PageController.php` (rimosso controllo righe 192-196)

**Soft Delete - Archiviazione Pagine**
- Implementato soft delete per conservare storico pagine eliminate
- Le pagine eliminate NON vengono rimosse dal database, ma archiviate (campo `deleted_at`)
- Trait `SoftDeletes` di Eloquent gestisce automaticamente l'esclusione dalle query normali
- Vantaggi: recupero errori, storico completo, audit trail, relazioni preservate
- Metodi disponibili: `restore()` per ripristinare, `forceDelete()` per eliminazione definitiva
- Query normali escludono automaticamente pagine archiviate
- Documentazione completa in `SOFT_DELETE_PAGES.md` con esempi endpoint opzionali per gestire archivio
- File modificati/creati:
  - Migration: `add_soft_delete_to_pages.php` (aggiunge campo `deleted_at`)
  - Model: `Page.php` (trait SoftDeletes, cast deleted_at)
  - Documentazione: `SOFT_DELETE_PAGES.md`

**Sezione Archivio - UI Gestione Pagine Eliminate**
- Nuova sezione completa per visualizzare e ripristinare pagine archiviate
- Endpoint backend: `GET /api/pages/archived` (lista pagine archiviate), `POST /api/pages/{id}/restore` (ripristino)
- Permessi: ogni utente vede solo le proprie pagine archiviate (filtro per ruolo come pagine attive)
- Store: nuove funzioni `fetchArchivedPages()` e `restorePage()` in pageStore
- Vista: `ArchivedPages.vue` con grid card pagine eliminate, badge "Archiviata", pulsante "Ripristina"
- Informazioni mostrate: titolo, slug, data eliminazione, proprietario, azienda, stato pubblicazione
- Navigazione: pulsante "Archivio" nell'header di PageList (icona archivio + testo)
- Route: `/archived` con protezione autenticazione
- Ripristino: conferma utente, pagina torna nella lista attive
- File modificati/creati:
  - Backend: `PageController.php` (metodi `archived()` e `restore()`), `index.php` (route righe 79, 83)
  - Store: `pageStore.js` (stato `archivedPages`, azioni archivio)
  - Vista: `ArchivedPages.vue` (nuova)
  - Router: `index.js` (import e route `/archived`)
  - Navigazione: `PageList.vue` (pulsante Archivio nell'header)

**Pulizia UI Blocco Video**
- Rimossa sezione "Colore Testo" dagli stili del blocco Video (non ha testo da personalizzare)
- Condizione `v-if="block.type !== 'video'"` aggiunta alla sezione Colore Testo negli stili comuni
- File modificato: `BlockEditor.vue` (riga 1657)

### Sessione 2026-01-16

**Mobile Responsiveness Completo**
- Tutti i blocchi (ServicesGrid, Features, TwoColumn) ottimizzati per mobile
- Pattern: `grid-cols-1` mobile → `md:grid-cols-2/3` desktop
- Classi responsive: padding `px-4 sm:px-6`, font size `text-2xl sm:text-3xl`, gap `gap-6 md:gap-8`
- File modificati: `ServicesGridBlock.vue`, `FeaturesBlock.vue`, `TwoColumnTextImage.vue`, `TwoColumnImageText.vue`, renderer PHP (standalone e Joomla)

**Fix Viewport Preview Editor**
- Problema: Classi Tailwind responsive basate su window width, non container width
- Soluzione: CSS custom con classi `.viewport-mobile` e `.viewport-tablet` in `PageEditor.vue` (righe 1105-1132)
- Uso di `:deep()` per penetrare nei componenti figli e forzare `grid-template-columns: repeat(1, minmax(0, 1fr))`

**Rimozione Funzionalità Appuntamento**
- Rimossa richiesta appuntamento dai form (funzionalità non utilizzata)
- File modificati: `FormBlock.vue`, `BlockEditor.vue`, `PageEditor.vue`
- Impatto: nessuna modifica ai renderer PHP

**Piano Email Alerts**
- Documento creato: `PIANO_EMAIL_ALERTS.md` (pianificazione completa, implementazione NON ancora avviata)
- Servizio raccomandato: PHPMailer con SMTP
- Configurazione: campo JSON `email_settings` nella tabella `pages`

### Sessione 2026-01-15

**Google Tag Manager Integration**
- Campo `tracking_settings` JSON nella tabella `pages` (migration: `add_tracking_settings.php`)
- UI: nuova sezione GTM in `PageSettings.vue`
- Renderer: snippet GTM automatici in `<head>` e `<body>` (standalone-renderer/page.php e Joomla component)
- File modificati: `Page.php`, `PageController.php`, renderer standalone e Joomla (v2.0.7)

**Form Button Customization**
- Stili completamente personalizzabili per bottone submit (come Hero button)
- Computed `buttonStyles` in `FormBlock.vue`, sezione UI in `BlockEditor.vue`
- Supporto in renderer PHP (standalone e Joomla)
- Stili: backgroundColor, textColor, fontSize, padding, borderRadius, borderWidth, borderColor, borderStyle, shadow

### Sessione 2026-01-14

**Fix Rendering Stili**
- Fix `fontFamily` e `buttonStyle` in renderer standalone e Joomla
- File: `BlockRenderer.php` (metodi `getBlockStyle()`, `renderHero()`), versione Joomla 2.0.3

**Fix Google Fonts Blocchi**
- Raccolta automatica font unici da tutti i blocchi e caricamento multiplo nel HEAD
- File: `standalone-renderer/page.php`, `joomla-component/.../default.php`, versione Joomla 2.0.4

**URL Rewriting**
- Configurazione Apache mod_rewrite per URL puliti: `/standalone-renderer/page.php?slug=test` → `/test`
- File creati: `root-htaccess-urlrewrite.txt`, `.htaccess-root-clean`, `GUIDA_URL_REWRITING.md`, `test-url-rewriting.php`

**Footer fullWidth Option**
- Toggle "Larghezza Completa" per footer (default: true per retrocompatibilità)
- File: `FooterBlock.vue`, `BlockEditor.vue`, `PageEditor.vue`, renderer PHP, versione Joomla 2.0.5

**Fix Permessi 403**
- Script `fix-permissions.sh` per correggere permessi file dopo upload FTP/SFTP
- Guide: `FIX_403_STANDALONE.md`, `SOLUZIONE_RAPIDA_403.txt`

## 📝 Storico Modifiche (Dicembre 2025 - Gennaio 2026)

**2026-01-13**: Guide deployment (`GUIDA_DEPLOYMENT.md`, `DEPLOYMENT_CHECKLIST.md`), script creazione utente FM Marketing, rimosse credenziali test da Login.vue

**2025-12-29**: Fix immagini blocchi two-column (cambio `imageUrl` → `image` nei renderer), versione Joomla 2.0.1, guide deploy

**2025-12-23**: Homepage standalone (`index.html`), fix font slider Joomla, creazione `.env` standalone renderer

**2025-12-19**: Protezione eliminazione pagine pubblicate, device preview con maschera (tablet/mobile), blocco Map e social media buttons in Header (Joomla), deploy produzione su https://edysma.net/ELPB/, fix MIME type JavaScript

## Project Overview

**Landing Page Builder** - Full-stack drag-and-drop application per landing pages con autenticazione multi-tenant.

### Stack Tecnologico
- **Backend**: PHP REST API (Slim Framework 4 + Eloquent ORM)
- **Frontend**: Vue 3 SPA (Vite, Composition API, Pinia, TailwindCSS)
- **Database**: MySQL con tabelle: users, companies, pages, blocks, leads

### Architettura Backend

**Entry Point**: `backend/public/index.php`
- Slim Framework, Eloquent ORM, CORS middleware, JWT authentication

**Models** (`backend/src/Models/`):
- `User` (3 ruoli: admin, company, user) → belongsTo Company, hasMany Page
- `Company` → hasMany User, hasMany Page
- `Page` (meta fields, tracking_settings JSON) → belongsTo User/Company, hasMany Block
- `Block` (content/styles JSON) → belongsTo Page (cascade delete)
- `Lead` → belongsTo Page (set null on delete)

**Controllers**:
- `AuthController`: Login, register, gestione utenti/aziende
- `PageController`: CRUD pagine, `showBySlug()` pubblico, `duplicate()`, `reassignPage()`
- `LeadController`: Gestione lead (admin only)
- `UploadController`: Upload immagini in `public/uploads/images/`

**Key Routes**:
- Public: `POST /api/auth/login`, `GET /api/page/{slug}`, `POST /api/leads`
- Protected: `GET /api/pages`, `POST /api/pages`, `PUT /api/pages/{id}`, `POST /api/pages/{id}/duplicate`
- Admin: `GET /api/auth/companies`, `GET /api/leads`, gestione utenti/aziende
- Company Manager: gestione utenti azienda, reassign pagine

**Authentication**: JWT (7 giorni, `Authorization: Bearer <token>`), secret in `.env` come `JWT_SECRET`

### Architettura Frontend

**Entry Point**: `frontend/src/main.js`
- Vue 3 + Pinia stores (authStore, pageStore) + Vue Router

**Router** (`frontend/src/router/index.js`):
- `/login`, `/register` (guest only)
- `/` (dashboard), `/admin` (admin), `/company` (company manager)
- `/editor/:id?` (drag-drop builder), `/preview/:id`
- `/p/:slug` (public page), `/thank-you` (form confirmation)

**Navigation Guards**: requiresAuth, requiresAdmin, requiresCompany, requiresGuest

**Stores**:
- `authStore`: autenticazione, token, user info, axios interceptor
- `pageStore`: CRUD pagine, `submitLead()`, `duplicatePage()`

**Block System** (15 tipi):
- `hero`, `header`, `text`, `two-column-text-image`, `two-column-image-text`
- `form` (reCAPTCHA v2), `cta`, `features`, `services-grid`
- `slider` (Swiper.js), `image-slide`, `video`, `video-info`, `map`, `footer`

**Block Structure**:
```javascript
{
  id: number,
  type: string,
  content: object,      // JSON specifico per tipo blocco
  styles: object,       // backgroundColor, textColor, padding, roundedCorners, fontFamily
  position: object,     // layout (non usato)
  order: number         // ordine visualizzazione (drag-drop)
}
```

**Editor Components**:
- `BlockEditor.vue`: pannello destro proprietà blocco
- `PageSettings.vue`: pannello destro metadata pagina (slug, SEO, publish, tracking_settings, recaptcha_settings)

**Features**:
- Drag & drop (vuedraggable/SortableJS)
- Inline editing (contenteditable, TipTap per rich text)
- Device preview (desktop/tablet/mobile con CSS custom per viewport)
- Image uploads (multipart/form-data)
- reCAPTCHA v2 integration
- GTM integration

### Database Schema

**pages** table:
- `id`, `title`, `slug` (unique)
- `meta_title`, `meta_description`, `is_published`
- `styles` JSON (custom CSS)
- `tracking_settings` JSON (`{ gtm_enabled, gtm_id }`)
- `recaptcha_settings` JSON (`{ enabled, site_key, secret_key }`)
- `user_id` FK, `company_id` FK (nullable)

**blocks** table:
- `id`, `page_id` FK (cascade delete)
- `type`, `content` JSON, `styles` JSON, `position` JSON, `order`

**leads** table:
- `id`, `page_id` FK (set null)
- `name`, `email`, `phone`, `message`
- `appointment_date`, `appointment_time`, `metadata` JSON

### Development Commands

**Backend** (da `backend/`):
```bash
composer install
php database/migrations/create_tables.php
php database/migrations/create_auth_tables.php
php database/migrations/add_tracking_settings.php
php -S localhost:8000 -t public
```

**Frontend** (da `frontend/`):
```bash
npm install
npm run dev          # http://localhost:3000
npm run build        # produzione
```

### Key Patterns

- **JSON Storage**: contenuti/stili blocchi flessibili
- **Cascade Deletes**: eliminazione pagina → eliminazione blocchi automatica
- **Multi-tenant**: pagine scoped a user/company con RBAC
- **JWT Auth**: stateless con role/company info
- **Component Reuse**: stesso componente blocco in editor/preview/public
- **Eloquent**: eager loading `->with('blocks')` per evitare N+1 queries
- **Slug Generation**: auto da title, backend garantisce unicità

### Deployment

- Frontend: `npm run build`, deploy `frontend/dist/`
- Backend: configurare `.env` (DB, JWT_SECRET, BASE_PATH)
- Migrations: eseguire in ordine
- Permissions: `755` o `775` su `backend/public/uploads/`
- Apache: configurare per Vue Router (SPA) + API routes
- URL Rewriting: `.htaccess` root per URL puliti (opzionale)

### Renderer Pubblici

**Standalone Renderer** (`standalone-renderer/`):
- `page.php`: rendering pubblico pagine
- `BlockRenderer.php`: rendering blocchi PHP
- `.env`: configurazione API endpoint

**Joomla Component** (`joomla-component/com_landingpages/`):
- Versione attuale: 2.0.7
- `site/helpers/blockrenderer.php`: rendering blocchi
- `site/views/page/tmpl/default.php`: template pagina

**Sincronizzazione**: renderer PHP sincronizzati con componenti Vue per stessi stili/funzionalità

### Troubleshooting

Guide disponibili:
- `FIX_403_STANDALONE.md` - Risoluzione errori permessi
- `GUIDA_URL_REWRITING.md` - Configurazione URL rewriting
- `DEPLOY_*.md` - Guide deployment specifiche
- `PIANO_EMAIL_ALERTS.md` - Piano implementazione email alerts
