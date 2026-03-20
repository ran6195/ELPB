# Landing Page Builder - Documentazione per IA

## Panoramica Generale
Landing Page Builder è un'applicazione web full-stack multi-tenant per la creazione di landing pages tramite drag-and-drop visual editor. Sistema completo con autenticazione JWT, gestione utenti multi-livello, cattura lead, e renderer pubblici standalone e Joomla.

## Stack Tecnologico

### Backend (PHP)
- **Framework**: Slim 4 (microframework REST API)
- **ORM**: Eloquent (Laravel components standalone)
- **Database**: MySQL 8.0+
- **Auth**: JWT (Firebase PHP-JWT) con secret in `.env`
- **Validazione**: Custom validators
- **CORS**: Middleware custom per cross-origin
- **Upload**: Gestione immagini (JPG/PNG/GIF/WebP) e video (MP4/MOV/AVI/MPEG/WebM)

### Frontend (Vue 3)
- **Framework**: Vue 3 Composition API
- **Build Tool**: Vite
- **State Management**: Pinia stores (authStore, pageStore)
- **Router**: Vue Router con navigation guards
- **Styling**: TailwindCSS 3.x
- **Drag & Drop**: VueDraggable (SortableJS wrapper)
- **Rich Text**: VueQuill (WYSIWYG editor)
- **Slider**: Swiper.js
- **Form Protection**: reCAPTCHA v2

## Architettura Database

### Tabella `users`
```sql
- id (PK)
- email (unique)
- password (hashed bcrypt)
- name
- role (enum: 'admin', 'company', 'user')
- company_id (FK nullable) // null per admin e company manager
- created_at, updated_at
```

### Tabella `companies`
```sql
- id (PK)
- name
- created_at, updated_at
```

### Tabella `pages`
```sql
- id (PK)
- title
- slug (unique, con withTrashed per soft delete)
- meta_title, meta_description, meta_keywords
- is_published (boolean)
- styles (JSON: backgroundColor, backgroundImage, fontFamily, containerWidth)
- tracking_settings (JSON: gtmId opzionale)
- recaptcha_settings (JSON: enabled, siteKey, secretKey)
- legal_info (JSON: ragioneSociale, partitaIva, sede, email, pec, telefono, etc.)
- user_id (FK)
- company_id (FK nullable)
- deleted_at (nullable, soft delete)
- created_at, updated_at
```

### Tabella `blocks`
```sql
- id (PK)
- page_id (FK, ON DELETE CASCADE)
- type (enum: 15 tipi blocchi)
- content (JSON: dati specifici per tipo blocco)
- styles (JSON: colori, padding, margin, etc.)
- position (JSON: coordinate drag-drop)
- order (int: ordinamento)
- created_at, updated_at
```

### Tabella `leads`
```sql
- id (PK)
- page_id (FK, ON DELETE SET NULL)
- name
- email
- phone (nullable)
- message (nullable)
- metadata (JSON: dati extra da form)
- created_at, updated_at
```

## Sistema di Autenticazione e Autorizzazione

### Ruoli
1. **admin**: Super-admin sistema
   - Gestisce companies (CRUD)
   - Gestisce tutti gli utenti
   - Visualizza tutti i leads

2. **company**: Manager azienda
   - Gestisce utenti della propria company
   - Visualizza pagine e leads della propria company
   - Riassegna pagine tra utenti company

3. **user**: Utente base
   - CRUD proprie pagine
   - Visualizza propri leads

### JWT Flow
- **Login**: POST `/api/auth/login` → token JWT valido 7 giorni
- **Header**: `Authorization: Bearer <token>`
- **Middleware**: `AuthMiddleware` verifica token e popola `$request->getAttribute('user')`
- **Guards**: Navigation guards Vue Router verificano `authStore.isAuthenticated/isAdmin/isCompany`

### Scope Dati
- Admin: vede tutto
- Company: vede solo dati con `company_id = user.company_id`
- User: vede solo dati con `user_id = user.id`

## Blocchi Disponibili (15 tipi)

### 1. HeroBlock
Layout header con titolo, sottotitolo, CTA button, background image/color, overlay, altezza personalizzabile (300px-100vh)

### 2. HeaderBlock
Navigazione con logo, menu links, icone social (colorate/mono), sticky opzionale

### 3. TextBlock
Sezione contenuto con titolo opzionale, rich text VueQuill (formatting, heading, align, colors), line-height personalizzabile

### 4. TwoColumnTextBlock / TwoColumnImageLeftBlock / TwoColumnImageRightBlock
Layout 2 colonne con testo e/o immagine, ordine invertibile, responsive stacking mobile

### 5. FormBlock
Form cattura lead con campi nome/email/phone/messaggio, personalizzazioni:
- Layout 1 o 2 colonne
- Caption opzionale
- Placeholder custom textarea
- Border radius campi (0-16px)
- Button style (colori, dimensioni, border radius, full-width/centered)
- reCAPTCHA v2 (opzionale, configurato in page settings)
- Submit salva in tabella `leads`
- Redirect a thank-you page dopo invio

### 6. CtaBlock
Call-to-action con titolo, descrizione, button, background personalizzato

### 7. FeaturesBlock
Griglia features (3 o 4 colonne) con:
- Icone personalizzabili (16 icone Heroicons)
- Forme icona (cerchio/arrotondato/quadrato)
- Colori icona foreground/background
- Titolo, descrizione per ogni card
- Responsive stacking mobile

### 8. ServicesGridBlock
Griglia servizi 3 colonne con immagini, titolo, descrizione

### 9. SliderBlock
Carousel Swiper con autoplay, loop, pagination, navigation, slide multipli per view, responsive

### 10. ImageSlideBlock
Singola immagine con aspect ratio auto (mantiene proporzioni)

### 11. VideoBlock
Player video HTML5 con URL mp4, controls, loop, autoplay, muted

### 12. VideoInfoBlock
Layout video + info laterali (titolo, descrizione)

### 13. MapBlock
Iframe Google Maps embed

### 14. FooterBlock
Footer con testo copyright, logo, menu links, social, fullWidth toggle

### 15. SocialBlock
Icone social standalone (FB, IG, X, LinkedIn, YouTube) con dimensioni, spaziatura, background, bordi, ombra personalizzabili

### LegalFooterBlock (speciale)
Link pagine legali (Privacy Policy, Condizioni d'uso, Cookie Policy), dati caricati da `page.legal_info`

## Renderer Pubblici

### 1. Standalone (`standalone-renderer/`)
- **Entry**: `page.php?slug={slug}` (o `/slug` con URL rewriting)
- **Renderer**: `BlockRenderer.php` (classe con metodi per ogni tipo blocco)
- **Helper**: `LegalTemplateProcessor.php` (processamento template pagine legali)
- **Legal Pages**: `legal-pages.php?slug={slug}&type={privacy|condizioni|cookies}`
- **Config**: `.env` (DB_*, BACKEND_URL, BASE_PATH, RECAPTCHA_SECRET_KEY)
- **Routing**: `.htaccess` per SPA routing e API proxy
- **Thank You**: `thank-you.php`
- **CSS**: TailwindCSS CDN + custom styles
- **JS**: Swiper CDN per slider

### 2. Joomla Component (`joomla-component/com_landingpages/`)
- **Version**: v2.0.7 (manifest XML)
- **Controller**: `site/controller.php` con task `displayLegal()`
- **View**: `site/views/page/tmpl/default.php`
- **Renderer**: `site/helpers/blockrenderer.php` (metodi statici)
- **Helper**: `site/helpers/LegalTemplateProcessor.php`
- **Config**: parametri component (database, apiUrl)
- **Menu Item**: link tipo component con parametro `slug`
- **Thank You**: `?thankyou=1` su stessa pagina

### Template Legali
- Directory: `menu-legali/` (sincronizzata tra standalone/Joomla)
- Files: `privacy-policy.html`, `condizioni-uso.html`, `cookie-policy.html`
- Variabili: `{{ragioneSociale}}`, `{{partitaIva}}`, `{{sede}}`, etc.
- Processamento: `LegalTemplateProcessor::process($templateFile, $legalInfo, $validFields)`
- Sicurezza: HTML escape, path traversal prevention, whitelist campi

## Flussi di Lavoro Principali

### Creazione Pagina
1. Dashboard → "Nuova Pagina"
2. Editor carica con template vuoto
3. Page Settings: title, slug (validato real-time), meta SEO
4. Drag blocchi da sidebar a canvas
5. Click blocco → pannello proprietà (BlockEditor)
6. Riordina blocchi con drag-drop (VueDraggable)
7. Save → API POST/PUT `/api/pages/{id}`
8. Pubblica toggle → `is_published = true`

### Cattura Lead
1. Utente compila form su landing pubblica
2. Submit → POST `/api/leads` (endpoint pubblico)
3. Validazione backend (required name, email)
4. reCAPTCHA verify (se abilitato)
5. Salvataggio database con `page_id`, `metadata` JSON
6. Redirect a thank-you page
7. Admin/Company visualizza leads in pannelli

### Deployment Landing
1. Frontend: `npm run build` → `dist/`
2. Deploy `dist/` su server web (Apache/Nginx)
3. Backend: deploy `backend/` con `.env` configurato
4. Standalone renderer: deploy `standalone-renderer/` con `.env`
5. Joomla component: installa ZIP via Extension Manager
6. Permessi: `755`/`775` su `backend/public/uploads/`
7. Apache mod_rewrite per routing

## Pattern e Convenzioni

### Gestione Stato
- **Pinia stores**: authStore (token, user, auth methods), pageStore (pages[], currentPage, CRUD methods)
- **Persistenza**: localStorage per JWT token
- **Reactivity**: `ref()`, `computed()` Composition API

### Comunicazione API
- **Base URL**: Axios instance con `baseURL` da env
- **Headers**: Token JWT auto-inject da interceptor
- **Error Handling**: Try-catch con feedback toast/alert
- **CORS**: Middleware backend con wildcard `*`

### Styling
- **TailwindCSS**: Utility-first, responsive prefixes (`md:`, `lg:`, `xl:`)
- **Custom Classes**: `.container-width-{value}` per override container
- **Dynamic Styles**: `:style="{ backgroundColor: block.styles.bgColor }"`
- **Google Fonts**: Caricamento dinamico via link tag (famiglia da blocchi/page)

### Sicurezza
- **Password**: bcrypt hash (cost 10)
- **JWT Secret**: 32+ chars in `.env`
- **SQL Injection**: Eloquent parametrized queries
- **XSS**: HTML escape con `htmlspecialchars()` in renderer
- **Path Traversal**: Basename validation upload, template paths
- **CSRF**: Non necessario (JWT stateless API)
- **File Upload**: Whitelist extensions, max size, move_uploaded_file

### Soft Delete
- **Trait**: `SoftDeletes` su Page model
- **Queries**: `withTrashed()`, `onlyTrashed()`, `restore()`, `forceDelete()`
- **UI**: Sezione "Archivio" con pulsanti ripristina/elimina definitiva

### Performance
- **Eager Loading**: `$page->with('blocks')->find($id)` per N+1 prevention
- **JSON Storage**: Blocchi in JSON per flessibilità schema-less
- **Cascade Delete**: ON DELETE CASCADE per blocks
- **Indexes**: Unique su slug, email; FK indexes auto

## Customizzazioni Avanzate

### Device Preview
Editor con toggle desktop/tablet/mobile, CSS classes `.viewport-mobile`, `.viewport-tablet` con `:deep()` override grid

### Google Tag Manager
Campo `tracking_settings.gtmId` in page, snippet auto-inject in `<head>` renderer

### Quick Contacts
Bottone floating con icone phone/WhatsApp/email, toggle testo, nascosto mobile

### Import/Export Pagine
JSON export con blocchi, slug unico con suffisso random se duplicato, pagina appartiene a utente importatore

### URL Rewriting
Apache `.htaccess` per `/slug` → `/page.php?slug={slug}`, requires mod_rewrite

### Multi-tenant
Isolamento dati per company_id, filtri Eloquent scope, reassign pagine tra utenti company

## File Chiave

### Backend
- `backend/public/index.php`: Entry point, Slim app, routes, middleware
- `backend/src/Controllers/PageController.php`: CRUD pagine, duplicate, export/import
- `backend/src/Controllers/LeadController.php`: CRUD leads (admin only)
- `backend/src/Controllers/AuthController.php`: Login, register, JWT issue
- `backend/src/Controllers/UploadController.php`: Image/video upload
- `backend/src/Models/{User,Company,Page,Block,Lead}.php`: Eloquent models
- `backend/src/Middleware/AuthMiddleware.php`: JWT verify + user attach

### Frontend
- `frontend/src/main.js`: Vue app bootstrap, Pinia, Router
- `frontend/src/stores/authStore.js`: Auth state + login/logout/register
- `frontend/src/stores/pageStore.js`: Pages CRUD + leads fetch
- `frontend/src/router/index.js`: Routes + navigation guards
- `frontend/src/views/PageEditor.vue`: Drag-drop canvas, block selection
- `frontend/src/components/PageSettings.vue`: Meta, SEO, publish, GTM, reCAPTCHA
- `frontend/src/components/BlockEditor.vue`: Proprietà blocco specifiche per tipo
- `frontend/src/components/blocks/{BlockType}.vue`: 15 componenti blocco

### Renderer
- `standalone-renderer/page.php`: Fetch page by slug, render con BlockRenderer
- `standalone-renderer/BlockRenderer.php`: Metodi render per ogni tipo blocco
- `standalone-renderer/legal-pages.php`: Render pagine legali dinamiche
- `joomla-component/.../default.php`: Template Joomla view
- `joomla-component/.../blockrenderer.php`: Static methods per blocchi

## Comandi Utili

```bash
# Backend
cd backend
composer install
php database/migrations/*.php  # Esegui tutte le migrations
php -S localhost:8000 -t public

# Frontend
cd frontend
npm install
npm run dev      # Dev server localhost:3000
npm run build    # Production build → dist/

# Database
mysql -u root -p
CREATE DATABASE landing_page_builder;

# Permissions (produzione)
chmod 755 backend/public/uploads
chmod 775 backend/public/uploads/images
chmod 775 backend/public/uploads/videos

# Apache config
a2enmod rewrite  # Abilita mod_rewrite
service apache2 restart
```

## Debug e Troubleshooting

### 403 Forbidden
- Check permissions (755/775)
- Verify `.htaccess` presente
- Check Apache AllowOverride All
- Script: `standalone-renderer/fix-permissions.sh`

### CORS Errors
- Verify CORS middleware in `index.php`
- Check `Access-Control-*` headers in response
- Usa browser dev tools Network tab

### Token Expired
- JWT expiration 7 giorni
- Logout/login per refresh token
- Check `JWT_SECRET` in `.env`

### Pagina Non Trovata
- Verify `is_published = 1`
- Check slug corretto (no spazi, lowercase)
- Script: `troubleshooting/check_published_pages.php`

### Upload Fails
- Check max_upload_size PHP (php.ini, .user.ini)
- Verify upload directory writable
- Check file extension whitelist

## Note Importanti
- **Lingua**: Tutti i testi UI in italiano
- **Timezone**: UTC in database, conversione locale in frontend
- **Encoding**: UTF-8 ovunque (database charset, HTTP headers, meta tags)
- **Browser Support**: Modern browsers (ES6+, no IE11)
- **Mobile First**: Layout responsive con TailwindCSS breakpoints
- **SEO**: Meta tags dinamici, slug SEO-friendly, robots noindex su preview
- **Accessibilità**: Semantic HTML, alt tags, ARIA labels dove necessario
