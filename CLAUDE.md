# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 📝 Modifiche Recenti

### Sessione 2026-02-05
- **Fix Deliverability Gmail** 📧🔧: Risolto problema email non arrivano su Gmail, mittente cambiato da `noreply@yourdomain.com` a `elpbuilder@vu000816.arubabiz.net` (dominio autentico), EmailService ottimizzato con header anti-spam (Message-ID con dominio reale, X-Priority/Importance, encoding base64, XMailer rimosso), SMTP migliorato (SMTPKeepAlive, SMTPAutoTLS), logging dettagliato LeadController per debug, script test-email.php e test-lead-email.php per diagnostica, guida completa troubleshooting Gmail con checklist SPF/DKIM/blacklist, soluzioni: configurare SPF Aruba, usare servizio transazionale (SendGrid/Mailgun), dominio personalizzato (EmailService.php, .env, test-email.php, test-lead-email.php, TROUBLESHOOTING_EMAIL_GMAIL.md)
- **Implementazione Notifiche Email** 📧✅: Sistema completo notifiche email centralizzate (backend edysma.net/ELPB), PHPMailer 7.0.2 installato via Composer, EmailService con template HTML responsive gradient header, campo JSON `notification_settings` su pages (migration eseguita), integrazione LeadController::store() non bloccante con try/catch, endpoint API PUT/POST `/pages/{id}/notification-settings` con validazione email, UI PageSettings con toggle enable/disable + textarea email aggiuntive + feedback successo/errore, store action updateNotificationSettings, email inviata automaticamente all'owner pagina + email custom, SMTP Aruba configurato (vu000816.arubabiz.net:587 TLS), pronto per testing (EmailService.php, LeadController.php, PageController.php, Page.php, PageSettings.vue, pageStore.js, .env, migration, PIANO_IMPLEMENTAZIONE_EMAIL.md)
- **Piano Notifiche Email** 📧: Preparato piano completo implementazione notifiche email centralizzate (solo backend edysma.net/ELPB), configurato .env con SMTP Aruba (vu000816.arubabiz.net:587 TLS), architettura EmailService + PHPMailer, campo JSON `notification_settings` su pages, integrazione LeadController, template HTML responsive, UI frontend PageSettings per config destinatari, stima 3.5-4.5h implementazione, MVP: notifica "Nuovo lead" all'owner, features future: riepilogo giornaliero, conferma visitatore, queue asincrona (.env, PIANO_IMPLEMENTAZIONE_EMAIL.md)

### Sessione 2026-02-04
- **Thank-You Page Dinamica con Header/Footer** ⭐: Thank-you page con header e footer della landing page originale, URL cosmetico `/{slug}/thank-you` via `history.replaceState` (redirect effettivo `/{slug}?_mode=thankyou` che passa dal catch-all htaccess → page.php, compatibile con hosting restrittivi su path multi-segmento), funzione `renderThankYouPage()` in page.php, font/GTM/container-width/stili dalla pagina, fallback statico senza slug, hidden field `page_slug` nel form (page.php, BlockRenderer.php)

### Sessione 2026-02-03
- **Guida Deployment e Upload Files Server**: Identificato 404 su check-legal-setup.php perché file appena creato non ancora caricato su server, creato file test.php minimo per verifica accesso PHP, guida verifica deployment edysma.net con test sequenziali (directory, backend, renderer, pagina reale), 4 scenari possibili (tutti 404, deployment parziale, solo file nuovo mancante, htaccess blocca), verifica struttura server via FTP con checklist directory e file, piano azione 6 step (accesso base, upload mancanti, verifica, menu-legali, diagnostica, test pagina legale), fix rapidi per accesso FTP/htaccess/path diversi, lista completa files da caricare per edysma.net (urgenti: menu-legali 4 file, diagnostica 2 file, aggiornati 2 file) e ilprodotto.it (renderer standalone + opzionale legal locale), checklist upload con dimensioni file attese, errori comuni upload (directory sbagliata, file corrotti, permessi, .env non rinominato), client FTP raccomandati (test.php, check-legal-setup.php, VERIFICA_DEPLOYMENT_EDYSMA.md, FILES_DA_CARICARE_SERVER.md)
- **Fix 4 Bug legal-pages.php** ⭐: (1) `.htaccess` routing legali usava PATH_INFO (`legal-pages.php/$1/$2`) incompatibile con PHP-FPM → cambiato in query string (`legal-pages.php?slug=$1&type=$2`), (2) variabile .env errata `API_URL` → `API_BASE_URL` (allineato a page.php), (3) `fetchPageData()` aggiungeva `/api/page/` ma `API_BASE_URL` già include `/api` → corretto in `/page/` per evitare doppio `/api/api/`, (4) formato risposta API cercava wrapper `$response['success']` ma endpoint restituisce oggetto page direttamente → allineato a page.php con `$pageData = fetchPageData()` (.htaccess, legal-pages.php)
- **Fix 404 Pagine Legali**: Creato script diagnostica `check-legal-setup.php` per verificare setup completo (directory, template, permessi, LegalTemplateProcessor), guida deployment directory `menu-legali` (struttura corretta un livello sopra standalone-renderer, file da caricare: LegalTemplateProcessor.php + 3 template privacy/condizioni/cookies.php), checklist completa verifica, troubleshooting errori comuni (check-legal-setup.php, FIX_404_PAGINE_LEGALI.md)
- **Fix Link Footer Legale Renderer Remoti** ⭐: Risolto problema link pagine legali su renderer remoti, aggiunto parametro `LEGAL_BASE_URL` al `.env` per specificare base URL pagine legali (utile quando renderer su server diverso da legal pages), modificato `page.php` per leggere parametro e passarlo a BlockRenderer, BlockRenderer genera URL assoluti se legalBaseUrl specificato altrimenti relativi, configurazione `.env.ilprodotto` con `LEGAL_BASE_URL=https://edysma.net/ELPB/standalone-renderer`, 3 scenari deployment (stesso server, remoto centralizzato, remoto locale), documentazione completa troubleshooting e testing (page.php, .env, .env.ilprodotto, FIX_LINK_LEGALI_REMOTI.md)
- **Documentazione Renderer Remoto**: Piano completo deployment renderer standalone su server remoto (ilprodotto.it) con backend centralizzato (edysma.net/ELPB), architettura API-first già implementata, endpoint pubblico `/api/page/{slug}` disponibile, gestione URL assoluti immagini via `APP_URL`, 8 fasi deployment (verifica backend, preparazione files, deployment, testing multi-blocco, troubleshooting, multi-pagina, produzione, documentazione), checklist rapida con test funzionalità (rendering, form lead, responsive, browser, console, performance), template `.env.ilprodotto` per configurazione remota, troubleshooting comune (404, CORS, immagini, form, slider, stili), metriche successo e timeline stimata 5-7h primo deployment (PIANO_RENDERER_REMOTO.md, CHECKLIST_DEPLOYMENT_REMOTO.md, .env.ilprodotto)
- **Prompt AI**: Documentazione completa applicazione per IA con stack tecnologico (PHP Slim + Vue 3), architettura database (users, companies, pages, blocks, leads), sistema auth JWT multi-livello (admin/company/user), 15 tipi blocchi dettagliati, renderer pubblici (standalone + Joomla), flussi lavoro (creazione pagina, cattura lead, deployment), pattern e convenzioni (Pinia, API, styling, sicurezza, soft delete, performance), customizzazioni avanzate (device preview, GTM, quick contacts, import/export, URL rewriting, multi-tenant), file chiave backend/frontend/renderer, comandi utili, debug troubleshooting (PROMPT_AI.md)

### Sessione 2026-02-01
- **Migrazione Pagine Legali a Renderer Locali** ⭐: Implementata migrazione completa delle pagine legali da backend centralizzato a renderer locali (standalone + Joomla), eliminata dipendenza runtime dal backend, URL semplici (`/legal/{slug}/{tipo}`), risolti problemi BASE_PATH e sottocartelle, helper condiviso `LegalTemplateProcessor.php` per processamento template con validazione e sicurezza (escape HTML, path traversal prevention), standalone: `legal-pages.php` + routing `.htaccess` + `BlockRenderer.php` link relativi, Joomla: task `displayLegal()` in controller + `blockrenderer.php` link task, frontend: `LegalFooterBlock.vue` URL relativi, backend: controller deprecated ma mantenuto per retrocompatibilità, template sincronizzati `menu-legali/` → standalone/Joomla, build frontend completato, documentazione completa in `MIGRAZIONE_PAGINE_LEGALI.md` con checklist testing/deployment/rollback (LegalTemplateProcessor.php, legal-pages.php, .htaccess, BlockRenderer.php, controller.php, blockrenderer.php, LegalFooterBlock.vue, LegalPagesController.php)

### Sessione 2026-01-30
- **Form Dati Legali in BlockEditor**: Spostato form dati legali dalle impostazioni dashboard alle impostazioni del blocco Legal Footer nell'editor, più intuitivo e contestuale, form completo 13 campi integrato in BlockEditor.vue, carica dati esistenti da currentPage.legal_info, salva con pageStore.updateLegalInfo, feedback successo/errore, rimosso dialog e menu kebab da PageList.vue (BlockEditor.vue, PageList.vue)
- **Pagine Legali Dinamiche**: Sistema completo generazione Privacy Policy, Condizioni d'uso, Cookie Policy al volo con header/footer originali della landing page, LegalPagesController usa template da menu-legali/ con file_get_contents (non include), route pubbliche `/legal/{slug}/{privacy|condizioni|cookies}`, sostituisce variabili con dati legal_info, renderizza header e legal-footer con BlockRenderer per mantenere layout identico, contenuto legale al centro in container stilizzato, link dinamici in LegalFooterBlock (Vue + renderer standalone + Joomla), slug passato via prop/costruttore/metodo statico (LegalPagesController.php, index.php, LegalFooterBlock.vue, BlockRenderer.php standalone/Joomla, page.php, default.php)
- **Form Pagine Legali Database**: Sistema completo salvataggio dati legali nel database, campo JSON `legal_info` nullable in pages, migration, endpoint PUT `/api/pages/{id}/legal-info` con validazione backend campi obbligatori, controllo permessi, store action `updateLegalInfo` (add_legal_info_to_pages.php, Page.php, PageController.php, index.php, pageStore.js)
- **UI Card Dashboard Compatte**: Card homepage ottimizzate per 4 colonne su schermi grandi (xl:grid-cols-4), padding ridotto (p-4), spazi interni compattati, titolo text-base con line-clamp-2, slug truncate, gap-5 tra card (PageList.vue)
- **UI Card Dashboard Kebab Menu**: Semplificata UI card homepage, kebab menu (⋮) posizionato in alto a destra accanto badge, bottone "Modifica" full-width in basso, azioni secondarie (Anteprima, Duplica, Esporta, Elimina) in dropdown con icone SVG, chiusura automatica click esterno, fix overflow-hidden e z-index (PageList.vue)

### Sessione 2026-01-24
- **Form Thank You Page**: Aggiunto redirect automatico a pagina ringraziamento dopo invio form (standalone: `thank-you.php`, Joomla: parametro URL `?thankyou=1`), stesso comportamento dell'anteprima Vue (BlockRenderer.php, blockrenderer.php, default.php, thank-you.php)
- **Fix Slider Renderer**: Risolto conflitto inizializzazione Swiper tra script inline e globale, rimosso script inline dai renderer, aggiunto data attributes per configurazione, script globale DOMContentLoaded gestisce tutti gli slider (BlockRenderer.php, blockrenderer.php, page.php, default.php)
- **UI Icone Azioni**: Sostituiti tutti pulsanti testuali con icone SVG nelle tabelle Utenti/Società/Leads (Modifica=matita, Dettagli=occhio, Elimina=cestino) con tooltip hover (AdminPanel.vue, CompanyPanel.vue)
- **UI Leads Panel**: Rimossi campi Privacy/Appuntamento/Data-Ora da tabelle e modal dettagli, help text privacy lato client (AdminPanel.vue, CompanyPanel.vue)
- **Fix Leads Table**: Rimossi campi obsoleti `privacy_accepted`, `page_published`, `appointment_requested`, `appointment_datetime` da modello e controller, allineato a schema database attuale (Lead.php, LeadController.php)
- **Fix Export Filename**: Aggiunto `Access-Control-Expose-Headers: Content-Disposition` a CORS middleware per esporre header filename, migliorato regex parsing filename (index.php, pageStore.js)
- **Import/Export Pagine**: Sistema completo per trasferire pagine tra server, formato JSON, slug unico con suffisso random se duplicato, pagina importata appartiene all'utente importatore (PageController.php, pageStore.js, PageList.vue)
- **Troubleshooting ilprodotto.it**: Creati script diagnostici per "pagina non trovata" (check_published_pages.php, test-api-connection.php, TROUBLESHOOTING_ILPRODOTTO.md)

### Sessione 2026-01-23
- **Blocco Testo Titolo Condizionale**: Elemento h2 titolo non renderizzato se vuoto (TextBlock.vue, renderer PHP)
- **Fix Input Blocco Vantaggi**: Risolto bug reset titolo/descrizione cambiando icona, usati `:value` + eventi invece `v-model`, funzione `updateFeatureField` (BlockEditor.vue)
- **Stile Icone Blocco Vantaggi**: Personalizzazione completa icone con forma (cerchio/arrotondato/quadrato), colore foreground, colore background, campi `iconShape`/`iconColor`/`iconBackgroundColor` (FeaturesBlock.vue, BlockEditor.vue, renderer PHP)
- **Blocco Vantaggi 4 Colonne**: Aggiunta opzione per 3 o 4 colonne, campo `columns` default 3, selettore in editor (FeaturesBlock.vue, BlockEditor.vue, renderer PHP)
- **Slider Container Width**: Ristrutturato per rispettare larghezza pagina (SliderBlock.vue, BlockRenderer.php, blockrenderer.php)
- **Form Border Radius Campi**: 6 opzioni personalizzabili (0px-16px), campo `fieldBorderRadius` (FormBlock.vue, BlockEditor.vue)
- **Form Help Text Obbligatori**: Aggiunto "* Campi obbligatori" sotto caption (FormBlock.vue, renderer PHP)
- **Form Link Privacy**: Link stesso colore testo, sottolineatura solo hover (FormBlock.vue, renderer PHP)
- **Form Personalizzazioni**: Caption opzionale, placeholder textarea custom, layout button (full/centered) (FormBlock.vue, BlockEditor.vue, renderer PHP)
- **Form Layout 2 Colonne**: Input su 2 colonne, textarea full-width, placeholder invece label (FormBlock.vue, renderer PHP)
- **Container Width Personalizzabile**: 5 opzioni (max-w-4xl → max-w-full), CSS override `.container-width-{value}` (PageSettings.vue, PageEditor.vue, renderer)
- **VueQuill Allineamento**: Fix toolbar align con opzioni esplicite + CSS `.ql-align-*` (RichTextEditor.vue, TextBlock.vue, renderer)
- **VueQuill Toolbar Completa**: Aggiunti strike, H1-H3, colori, blockquote, code (RichTextEditor.vue, renderer)
- **Migrazione TipTap → VueQuill**: RichTextEditor riscritto, rimossi 66 package TipTap (package.json, RichTextEditor.vue)
- **Fix Font Blocchi**: Caricamento font da tutti i blocchi con Set() (PagePreview.vue, PublicPage.vue)
- **Fix Grassetto Heading**: Font-weight differenziato (h1-h3: 600, strong: 700, h1-h3 strong: 900) (TextBlock.vue, renderer)
- **Line-Height Blocco Testo**: 7 opzioni (1-2), campo `lineHeight` default 1.625 (TextBlock.vue, BlockEditor.vue, renderer)
- **Aspect Ratio Auto Immagini**: Immagini mantengono proporzioni originali, classe `w-full h-auto` (ImageSlideBlock.vue, renderer)

### Sessione 2026-01-18
- **Quick Contacts Testo**: Toggle "Mostra Testo" + campo custom, stili adattivi (larghezza auto, border-radius 30px), nascosto mobile (PageSettings.vue, QuickContactBlock.vue, renderer)
- **Meta Robots Noindex**: `<meta robots noindex>` in tutti i renderer per evitare indicizzazione (PagePreview.vue, page.php, default.php)
- **Pulsante Anteprima Editor**: Nuovo pulsante header con icona occhio, apre nuova finestra (PageEditor.vue)
- **Icone Blocco Vantaggi**: 16 icone SVG Heroicons selezionabili per card, fallback 'check' (FeaturesBlock.vue, BlockEditor.vue, renderer)
- **Colori Blocco Vantaggi**: 3 controlli colore separati (titolo, titoli card, descrizioni), fallback a stili globali (FeaturesBlock.vue, BlockEditor.vue, renderer)
- **Altezza Hero Personalizzabile**: 6 opzioni (300px-100vh), campo `height` come min-height, default 400px (HeroBlock.vue, BlockEditor.vue, renderer)
- **Stile Icone Social Header**: Scelta colorate/monocromatiche, gradient IDs unici (HeaderBlock.vue, BlockEditor.vue, renderer)
- **Anteprima Nuova Scheda**: Dashboard usa `window.open(_blank)` invece router.push (PageList.vue)
- **Fix Social Anteprima**: Aggiunto import e mapping SocialBlock (PagePreview.vue)

### Sessione 2026-01-17
- **Fix Slug Duplicati**: `withTrashed()` per verificare unicità anche in archiviate, slug default 'nuova-pagina', contatore auto (PageEditor.vue, PageController.php)
- **Validazione Slug Real-time**: Endpoint `/api/pages/check-slug`, doppia validazione formato+unicità, debounce 500ms, feedback visivo (bordi colorati, spinner, messaggi), regex `/^[a-zA-Z0-9-]+$/` (PageController.php, pageStore.js, PageSettings.vue)
- **Eliminazione Definitiva Archiviate**: `forceDelete()` con conferma, pulsante rosso, endpoint `/DELETE /api/pages/{id}/force` (PageController.php, pageStore.js, ArchivedPages.vue)
- **Blocco Social Media**: 5 piattaforme (FB, IG, X, LinkedIn, YouTube), 2 stili icone (colorate/mono), personalizzazioni (dimensione 24-96px, spaziatura, sfondo, bordi, ombra), SVG inline (SocialBlock.vue, BlockEditor.vue, PageEditor.vue, renderer)
- **Chiusura Reciproca Pannelli**: Pannello pagina e blocco si chiudono a vicenda (PageEditor.vue)
- **Nomi Blocchi Italiano**: Mappatura `blockTypeNames` per traduzioni (BlockEditor.vue, PageEditor.vue)
- **Upload Video**: Endpoint `/api/upload/video`, formati MP4/MOV/AVI/MPEG/WebM max 100MB, `.user.ini` config, doc `UPLOAD_VIDEO_CONFIG.md` (UploadController.php, BlockEditor.vue)
- **Fix Swiper Loop**: Disabilitato auto quando slide < slidesPerView*2 (SliderBlock.vue)
- **Unificazione Pulsanti Dashboard**: Solo Anteprima+Duplica+Elimina (PageList.vue)
- **Eliminazione Pagine Pubblicate**: Dialog conferma con toggle pubblicazione (PageList.vue, PageController.php)
- **Soft Delete**: Trait SoftDeletes, campo `deleted_at`, metodi restore/forceDelete, doc `SOFT_DELETE_PAGES.md` (add_soft_delete_to_pages.php, Page.php)
- **Sezione Archivio**: UI completa con grid card, badge, ripristino, route `/archived`, endpoint `GET /api/pages/archived` e `POST /api/pages/{id}/restore` (ArchivedPages.vue, PageController.php, pageStore.js, router)
- **Pulizia UI Video**: Rimossa sezione "Colore Testo" (BlockEditor.vue)

### Sessione 2026-01-16
- **Mobile Responsive**: Ottimizzati tutti i blocchi con pattern `grid-cols-1 md:grid-cols-2/3`, classi responsive (ServicesGrid, Features, TwoColumn, renderer)
- **Fix Viewport Preview**: CSS custom `.viewport-mobile/tablet` con `:deep()` override grid (PageEditor.vue)
- **Rimozione Appuntamento**: Eliminata funzionalità non usata (FormBlock.vue, BlockEditor.vue, PageEditor.vue)
- **Piano Email Alerts**: Doc `PIANO_EMAIL_ALERTS.md` (pianificazione, NON implementato)

### Sessione 2026-01-15
- **GTM Integration**: Campo `tracking_settings` JSON, UI PageSettings, snippet auto in renderer (add_tracking_settings.php, Page.php, PageController.php, renderer v2.0.7)
- **Form Button Custom**: Stili completi come Hero button (FormBlock.vue, BlockEditor.vue, renderer)

### Sessione 2026-01-14
- **Fix Rendering Stili**: fontFamily e buttonStyle renderer (BlockRenderer.php, Joomla v2.0.3)
- **Fix Google Fonts**: Caricamento multiplo font blocchi (page.php, default.php, Joomla v2.0.4)
- **URL Rewriting**: Apache mod_rewrite config, guide e test (root-htaccess-urlrewrite.txt, GUIDA_URL_REWRITING.md)
- **Footer fullWidth**: Toggle larghezza completa, default true (FooterBlock.vue, BlockEditor.vue, renderer, Joomla v2.0.5)
- **Fix Permessi 403**: Script fix-permissions.sh, guide (FIX_403_STANDALONE.md)

## 📝 Storico Compatto
- **2026-01-13**: Guide deployment, script utente FM Marketing, rimossi credenziali test
- **2025-12-29**: Fix immagini two-column (`imageUrl` → `image`), Joomla v2.0.1
- **2025-12-23**: Homepage standalone, fix font slider Joomla, `.env` standalone
- **2025-12-19**: Protezione eliminazione pubblicate, device preview, Map block, deploy edysma.net/ELPB

## Project Overview

**Landing Page Builder** - Full-stack drag-and-drop per landing pages, multi-tenant con autenticazione JWT.

### Stack
- **Backend**: PHP (Slim 4 + Eloquent ORM + JWT auth)
- **Frontend**: Vue 3 (Vite + Pinia + TailwindCSS + VueQuill)
- **Database**: MySQL (users, companies, pages, blocks, leads)

### Architettura Backend
- **Entry**: `backend/public/index.php` (Slim + Eloquent + CORS)
- **Models**: User (admin/company/user), Company, Page (soft delete, tracking_settings JSON), Block (cascade delete), Lead
- **Controllers**: AuthController (login/register), PageController (CRUD + showBySlug + duplicate), LeadController (admin), UploadController (images/videos)
- **Routes**: Public (`/api/auth/login`, `/api/page/{slug}`, `/api/leads`), Protected (`/api/pages/*`), Admin (`/api/auth/companies`, `/api/leads`)
- **Auth**: JWT 7 giorni, `Authorization: Bearer <token>`, secret `.env`

### Architettura Frontend
- **Entry**: `frontend/src/main.js` (Vue 3 + Pinia + Router)
- **Routes**: `/login`, `/register`, `/` (dashboard), `/editor/:id`, `/preview/:id`, `/p/:slug` (public), `/archived`, `/thank-you`
- **Guards**: requiresAuth, requiresAdmin, requiresCompany, requiresGuest
- **Stores**: authStore (token + user), pageStore (CRUD + leads)
- **Blocchi** (15 tipi): hero, header, text, two-column-*, form (reCAPTCHA), cta, features, services-grid, slider (Swiper), image-slide, video, video-info, map, footer, social
- **Block**: `{ id, type, content (JSON), styles (JSON), position, order }` - drag-drop con vuedraggable
- **Editor**: BlockEditor.vue (proprietà blocco), PageSettings.vue (meta, SEO, publish, GTM)
- **Features**: Drag-drop, inline edit, VueQuill rich text, device preview (desktop/tablet/mobile), uploads, reCAPTCHA v2, GTM

### Database
- **pages**: id, title, slug (unique), meta_*, is_published, styles (JSON), tracking_settings (JSON), recaptcha_settings (JSON), deleted_at (soft delete), user_id FK, company_id FK
- **blocks**: id, page_id FK (cascade), type, content (JSON), styles (JSON), position (JSON), order
- **leads**: id, page_id FK (set null), name, email, phone, message, metadata (JSON)

### Dev Commands
```bash
# Backend
composer install
php database/migrations/*.php
php -S localhost:8000 -t public

# Frontend
npm install
npm run dev     # localhost:3000
npm run build   # produzione
```

### Key Patterns
- JSON storage per flessibilità blocchi
- Cascade deletes (page → blocks)
- Multi-tenant RBAC (user/company scope)
- Component reuse (editor/preview/public)
- Eloquent eager loading `->with('blocks')`
- Slug auto-generation + unicità (withTrashed)
- Soft delete pagine (restore/forceDelete)

### Deployment
- Frontend: `npm run build` → deploy `dist/`
- Backend: `.env` config (DB, JWT_SECRET, BASE_PATH)
- Permissions: `755`/`775` su `backend/public/uploads/`
- Apache: SPA routing + API routes
- URL Rewriting: `.htaccess` opzionale per `/slug`

### Renderer Pubblici
- **Standalone**: `standalone-renderer/` (page.php + BlockRenderer.php + .env)
- **Joomla**: `joomla-component/com_landingpages/` v2.0.7 (blockrenderer.php + default.php)
- Sincronizzati con componenti Vue per stili identici

### Docs
- `FIX_403_STANDALONE.md`, `GUIDA_URL_REWRITING.md`, `DEPLOY_*.md`, `PIANO_EMAIL_ALERTS.md`, `UPLOAD_VIDEO_CONFIG.md`, `SOFT_DELETE_PAGES.md`
