# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 📝 File Modificati (Ultima Sessione - 2025-12-29)

### Fix Immagini Blocchi Two-Column
- **Problema risolto**: I blocchi `two-column-text-image` e `two-column-image-text` non visualizzavano le immagini nei renderer standalone e Joomla
- **Causa**: I renderer cercavano `content['imageUrl']` invece di `content['image']` (usato dai componenti Vue)

### File Modificati
- `standalone-renderer/BlockRenderer.php` - Cambiato `$imageUrl` → `$image` nei metodi:
  - `renderTwocolumntextimage()` (righe 761, 775)
  - `renderTwocolumnimagetext()` (righe 790, 800)
- `backend/standalone-renderer/BlockRenderer.php` - Stesse modifiche:
  - `renderTwocolumntextimage()` (righe 752, 766)
  - `renderTwocolumnimagetext()` (righe 781, 791)
- `joomla-component/com_landingpages/site/helpers/blockrenderer.php` - Stesse modifiche:
  - `renderTwocolumntextimage()` (righe 781, 795)
  - `renderTwocolumnimagetext()` (righe 810, 820)
- `joomla-update-slider-font/blockrenderer.php` - Stesse modifiche per compatibilità

### File Creati per Deploy
- `DEPLOY_FIX_TWOCOLUMN.md` - Guida completa per deploy fix su tutti gli ambienti
- `standalone-renderer-fix-twocolumn.zip` (13K) - Renderer aggiornato per ilprodotto.it, primehome.it, infortunisticaveneta.it
- `joomla-landingpages-v2.0.1-fix-twocolumn.zip` (14M) - Componente Joomla v2.0.1 per edysma.net

### Componente Joomla
- `joomla-component/com_landingpages/landingpages.xml` - Aggiornato a versione 2.0.1 con descrizione fix

---

## 📝 Sessione Precedente (2025-12-23)

### Nuove Funzionalità
- **Homepage standalone per test**: Pagina web semplice per testare landing pages in ambiente standalone
- **Fix font family slider Joomla**: Il font della pagina ora viene applicato correttamente agli elementi di testo dello slider

### File Creati
- `index.html` - Homepage standalone con:
  - Descrizione del progetto Landing Page Builder
  - Campo di testo per inserire slug e navigare verso landing pages
  - Link all'editor principale (https://edysma.net/ELPB)
  - Design responsive con Tailwind CSS
- `DEPLOY_ILPRODOTTO.md` - Guida per deploy su ilprodotto.it
- `DEPLOY_DUPLICATE_FIX.md` - Guida per caricare fix funzione duplicate su edysma.net
- `deploy-duplicate-fix.zip` - Archivio con file backend da aggiornare per abilitare duplicazione pagine
- `joomla-slider-font-fix.zip` - Archivio con fix font slider per componente Joomla

### File Modificati - Componente Joomla
- `joomla-component/com_landingpages/site/helpers/blockrenderer.php` - Aggiunto supporto font family globale:
  - Proprietà statica `$fontFamily`
  - Metodo `setFontFamily()` per impostare il font
  - Metodo `getFontFamilyStyle()` per ottenere lo stile CSS
  - Applicato font a titolo e testi dello slider
- `joomla-component/com_landingpages/site/views/page/tmpl/default.php` - Imposta font family nel renderer prima di renderizzare blocchi

### File Modificati - Standalone Renderer
- `standalone-renderer/.env` - Creato da .env.example, configurato per API su edysma.net

---

## 📝 Sessione Precedente (2025-12-19)

### Nuove Funzionalità
- **Protezione eliminazione pagine pubblicate**: Le pagine pubblicate non possono essere eliminate senza prima rimuovere la pubblicazione
- **Device preview con maschera**: Visualizzazione tablet e mobile con frame realistico e scroll interno
- **Componente Joomla aggiornato**: Aggiunto blocco Map e social media buttons nel Header

### File Modificati - Main App
- `backend/src/Controllers/PageController.php` - Aggiunto controllo eliminazione pagine pubblicate
- `frontend/src/views/PageList.vue` - Controllo preventivo stato pubblicazione prima del dialog di conferma
- `frontend/src/views/PageEditor.vue` - Implementate maschere device per tablet (768x1024) e mobile (390x844)

### File Modificati - Componente Joomla
- `joomla-component/com_landingpages/site/helpers/blockrenderer.php` - Aggiornato con:
  - Nuovo blocco Map (Google Maps con contact info)
  - Header block aggiornato con social media buttons (Facebook, Instagram, X/Twitter)
  - Fix bug Footer block (inizializzazione variabile)

### File Creati
- `backend/.env.example` - Template configurazione backend
- `CHECKLIST_DEPLOY.md` - Checklist dettagliata per il deploy
- `DEPLOY_PRONTO.md` - Guida rapida deploy su edysma.net/ELPB/
- `frontend/dist/.htaccess` - Configurazione Apache per Vue Router in produzione (con fix MIME type)
- `RISOLUZIONE_ERRORE_MIME.md` - Guida risoluzione errori MIME type JavaScript
- `SOLUZIONE_ALTERNATIVA.md` - Soluzioni alternative per configurazione Apache
- `CHECKLIST_POST_DEPLOY.md` - Checklist post-deploy e sicurezza

### Build e Deploy
- Frontend buildato in `frontend/dist/` per produzione
- Configurazione pronta per deploy su `https://edysma.net/ELPB/`
- **✅ DEPLOY COMPLETATO CON SUCCESSO** - App online su https://edysma.net/ELPB/

### Fix Applicati
- `.htaccess` aggiornato per escludere esplicitamente directory `assets/` e file statici dal rewrite
- Aggiunto `AddType application/javascript .js` per forzare MIME type corretto
- Risolto errore "Expected a JavaScript module script but the server responded with MIME type text/html"

## Project Overview

Landing Page Builder is a full-stack drag-and-drop application for creating landing pages with multi-tenant authentication. The project is split into two separate applications:

- **Backend**: PHP REST API built with Slim Framework 4 and Eloquent ORM
- **Frontend**: Vue 3 SPA with Vite, using Composition API, Pinia, and TailwindCSS

## Development Commands

### Backend (PHP)

Navigate to `backend/` directory for all backend commands.

```bash
# Install dependencies
composer install

# Run database migrations (creates database and tables)
php database/migrations/create_tables.php
php database/migrations/create_auth_tables.php
php database/migrations/add_appointment_fields.php
php database/migrations/add_missing_leads_fields.php
php database/migrations/add_recaptcha_settings.php

# Start development server
php -S localhost:8000 -t public
```

The API runs on `http://localhost:8000` and serves routes under `/api/*`.

### Frontend (Vue)

Navigate to `frontend/` directory for all frontend commands.

```bash
# Install dependencies
npm install

# Start development server (runs on http://localhost:3000)
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

### Deployment

Use the automated deployment script from the project root:

```bash
# Build frontend, install backend dependencies, run migrations
./scripts/deploy.sh
```

For complete deployment instructions, see `DEPLOY.md`.

## Architecture

### Backend Architecture

**Entry Point**: `backend/public/index.php`
- Bootstraps Slim Framework
- Loads environment variables from `.env`
- Initializes Eloquent ORM via `config/database.php`
- Defines REST API routes with authentication middleware
- Configures CORS middleware to allow frontend requests
- Sets BASE_PATH for subfolder deployments (e.g., `/ELPB/backend/public`)

**Database Connection**:
- Uses Illuminate Database (Eloquent ORM standalone) configured in `config/database.php`
- Connection settings pulled from `.env` file (defaults to localhost MySQL)
- Database name: `landing_page_builder`

**Models** (`backend/src/Models/`):
- `User`: User accounts with three roles (admin, company, user)
- `Company`: Organizations that group users
- `Page`: Landing pages with meta fields, user/company ownership
- `Block`: Page building blocks with JSON fields (content, styles, position)
- `Lead`: Form submissions from landing pages

**Relationships**:
- `User` belongsTo `Company`, hasMany `Page`
- `Company` hasMany `User`, hasMany `Page`
- `Page` belongsTo `User`, belongsTo `Company`, hasMany `Block`
- `Block` belongsTo `Page` (cascade delete)
- `Lead` belongsTo `Page` (set null on delete)

**Controllers** (`backend/src/Controllers/`):
- `AuthController`: Login, register, user/company management, role-based operations
- `PageController`: Full CRUD for pages, includes `showBySlug()` for public page rendering, `duplicate()` for page cloning, `reassignPage()` for changing page ownership
- `LeadController`: Handles form submissions and lead management (admin only)
- `UploadController`: Handles image uploads to `public/uploads/images/`
- `DebugController`: Database connection testing (remove in production)

**Middleware** (`backend/src/Middleware/`):
- `AuthMiddleware`: JWT token validation, attaches user to request

**Utils** (`backend/src/Utils/`):
- `JWTHandler`: JWT token generation and validation using firebase/php-jwt

**Key Routes**:

*Public (no auth required)*:
- `POST /api/auth/login` - Login
- `POST /api/auth/register` - Register new user
- `GET /api/page/{slug}` - Get published page by slug (public viewing)
- `POST /api/leads` - Submit form lead (public form submission)

*Protected (requires authentication)*:
- `GET /api/auth/me` - Get current user info
- `GET /api/pages` - List pages (filtered by role/permissions)
- `GET /api/pages/{id}` - Get page with blocks
- `POST /api/pages` - Create page (auto-generates unique slug, sets user_id and company_id)
- `POST /api/pages/{id}/duplicate` - Duplicate existing page
- `PUT /api/pages/{id}` - Update page and all blocks
- `DELETE /api/pages/{id}` - Delete page (cascade deletes blocks)
- `POST /api/upload/image` - Upload image

*Admin Only*:
- `GET /api/auth/companies` - List all companies
- `GET /api/auth/users` - List all users
- `POST /api/admin/companies` - Create company (auto-creates company manager)
- `DELETE /api/admin/companies/{id}` - Delete company
- `PUT /api/admin/users/{id}` - Update user
- `DELETE /api/admin/users/{id}` - Delete user
- `GET /api/leads` - List all leads
- `DELETE /api/leads/{id}` - Delete lead

*Company Manager Only*:
- `POST /api/company/users` - Create user within company
- `DELETE /api/company/users/{id}` - Delete user from company (prevents deletion if user has pages)
- `GET /api/company/users-with-pages` - Get users with page count
- `PUT /api/company/pages/{id}/reassign` - Reassign page to different user

### Authentication System

**Three-Tier Permission System**:
1. **Admin**: Full access to all pages, users, and companies
2. **Company**: Company managers can manage users and pages within their company
3. **User**: Regular users can only manage their own pages

**JWT Implementation**:
- Token generated on login with 7-day expiration
- Token includes: user id, email, role, company_id
- Secret key configured in `.env` as `JWT_SECRET`
- Token sent as `Authorization: Bearer <token>` header
- AuthMiddleware validates token on protected routes

**Permission Methods** (User model):
- `canViewPage($page)` - Check if user can view page
- `canEditPage($page)` - Check if user can edit page
- `isAdmin()`, `isCompany()`, `isUser()` - Role checks

### Frontend Architecture

**Entry Point**: `frontend/src/main.js`
- Bootstraps Vue 3 application
- Initializes Pinia stores (authStore, pageStore)
- Configures Vue Router with authentication guards
- Sets up axios interceptors for JWT token injection

**Router** (`frontend/src/router/index.js`):
- `/login` - Login view (guest only)
- `/register` - Register view (guest only)
- `/` - PageList (dashboard, requires auth)
- `/admin` - AdminPanel (requires admin role)
- `/company` - CompanyPanel (requires company role)
- `/editor/:id?` - PageEditor (drag-drop builder, requires auth)
- `/preview/:id` - PagePreview (preview page, requires auth)
- `/p/:slug` - PublicPage (public landing page, no auth)
- `/thank-you` - ThankYouPage (form submission confirmation, no auth)

**Navigation Guards**:
- `requiresAuth`: Redirects to login if not authenticated
- `requiresAdmin`: Restricts access to admin users only
- `requiresCompany`: Restricts access to company managers only
- `requiresGuest`: Redirects to home if already authenticated

**State Management**:

*authStore* (`frontend/src/stores/authStore.js`):
- Handles authentication state (user, token, role)
- Login/logout actions
- Token persistence in localStorage
- Axios interceptor setup for automatic token injection
- User info retrieval

*pageStore* (`frontend/src/stores/pageStore.js`):
- Centralized Pinia store for all page operations
- API calls to backend using axios
- Base API URL: `http://localhost:8000/api` (configurable via VITE_API_URL)
- Handles CRUD operations, loading states, and error handling
- `submitLead()` action for form submissions
- `duplicatePage()` action for page cloning

**Views**:
- `Login.vue` - Login form with email/password
- `Register.vue` - Registration form
- `PageList.vue` - Dashboard grid showing all pages (filtered by role) with edit/delete/preview/duplicate actions
- `AdminPanel.vue` - Admin interface for managing users and companies
- `CompanyPanel.vue` - Company manager interface for managing company users and reassigning pages
- `PageEditor.vue` - Main editor with 3-panel layout (blocks sidebar, canvas, properties panel)
- `PagePreview.vue` - Read-only preview of page
- `PublicPage.vue` - Public-facing page renderer (fetches by slug, only shows published pages)
- `ThankYouPage.vue` - Confirmation page after form submission

**Block System**:
Located in `frontend/src/components/blocks/`. Each block is a Vue component that renders content from the `block.content` JSON object.

Available block types (15 total):
- `hero` - Hero section with title, subtitle, CTA button (HeroBlock.vue)
- `header` - Page header with logo, navigation menu, social media buttons (HeaderBlock.vue)
- `text` - Simple text block with title and rich text content using TipTap (TextBlock.vue)
- `two-column-text-image` - Text left, image right (TwoColumnTextImage.vue)
- `two-column-image-text` - Image left, text right (TwoColumnImageText.vue)
- `form` - Lead capture form with configurable fields, appointment fields, reCAPTCHA v2 (FormBlock.vue)
- `cta` - Call-to-action section with title, subtitle, button (CtaBlock.vue)
- `features` - Features grid with icons and descriptions (FeaturesBlock.vue)
- `services-grid` - Services grid with images and descriptions (ServicesGridBlock.vue)
- `slider` - Image slider/carousel with Swiper.js (SliderBlock.vue)
- `image-slide` - Image grid with text overlays (ImageSlideBlock.vue)
- `video` - Embedded video player (VideoBlock.vue)
- `video-info` - Video with text description (VideoInfoBlock.vue)
- `map` - Google Maps embed with customizable location (MapBlock.vue)
- `footer` - Footer with company info, links, contacts (FooterBlock.vue)

**Block Structure**:
```javascript
{
  id: number,           // Unique identifier
  type: string,         // Block type (hero, text, form, etc)
  content: object,      // JSON content specific to block type
  styles: object,       // JSON with backgroundColor, textColor, padding, roundedCorners
  position: object,     // JSON with layout info (currently unused)
  order: number         // Display order (updated on drag-drop)
}
```

**Editor Components**:
- `BlockEditor.vue` - Right panel for editing block properties (shows when block selected)
- `PageSettings.vue` - Right panel for editing page metadata (slug, SEO fields, publish status, custom styles, reCAPTCHA settings)

**Drag and Drop**:
- Uses `vuedraggable` library (wraps SortableJS)
- Applied to blocks in PageEditor.vue
- Updates `order` field on blocks after drag operations
- Footer block is always forced to bottom position

**Content Editing**:
- Blocks support `contenteditable` for inline text editing when in editor mode
- Rich text blocks use TipTap editor for formatting (bold, italic, underline, links, alignment)
- Block components receive `:editable="true"` prop in editor
- Changes reflected immediately in page.blocks array (Vue reactivity)

**Page Customization**:
- Custom page-level CSS in PageSettings
- Rounded corners toggle (applied to all blocks)
- Background colors and text colors per block
- Padding control per block

**reCAPTCHA Integration**:
- Google reCAPTCHA v2 for form spam protection
- Configurable per page in PageSettings
- Site key and secret key stored in page's recaptcha_settings JSON field
- Frontend renders reCAPTCHA widget in FormBlock when enabled
- Backend validates reCAPTCHA response before accepting lead submission

## Database Schema

### `users` table
- `id` - Primary key
- `name` - User name
- `email` - Email (unique)
- `password` - Hashed password
- `role` - Enum: 'admin', 'company', 'user'
- `company_id` - Foreign key to companies (nullable for admin)
- `created_at`, `updated_at` - Timestamps

### `companies` table
- `id` - Primary key
- `name` - Company name
- `created_at`, `updated_at` - Timestamps

### `pages` table
- `id` - Primary key
- `title` - Page title shown in editor
- `slug` - URL-friendly identifier (auto-generated from title, must be unique)
- `meta_title` - SEO meta title
- `meta_description` - SEO meta description
- `is_published` - Boolean, only published pages accessible via public URL
- `styles` - JSON field for custom CSS
- `recaptcha_settings` - JSON field with reCAPTCHA configuration (enabled, site_key, secret_key)
- `user_id` - Foreign key to users (required, cascade delete)
- `company_id` - Foreign key to companies (nullable, cascade delete)
- `created_at`, `updated_at` - Timestamps

### `blocks` table
- `id` - Primary key
- `page_id` - Foreign key to pages (cascade delete)
- `type` - Block type string
- `content` - JSON field with block-specific data
- `styles` - JSON field with backgroundColor, textColor, padding, roundedCorners
- `position` - JSON field for layout (currently unused)
- `order` - Integer for display order
- `created_at`, `updated_at` - Timestamps

### `leads` table
- `id` - Primary key
- `page_id` - Foreign key to pages (set null on delete)
- `name` - Lead name (nullable)
- `email` - Lead email (required)
- `phone` - Lead phone (nullable)
- `message` - Lead message (nullable)
- `appointment_date` - Appointment date (nullable)
- `appointment_time` - Appointment time (nullable)
- `metadata` - JSON field for extra form data
- `created_at`, `updated_at` - Timestamps

## Development Workflow

### Adding New Block Types

1. Create new Vue component in `frontend/src/components/blocks/`
2. Add block type to `blockTypes` array in PageEditor.vue
3. Add default content structure in `getDefaultContent()` function
4. Map component in `getBlockComponent()` function
5. Block content structure should match what the component expects in props
6. Handle `:editable` prop for editor mode vs. public view
7. Apply rounded corners conditionally based on `styles.roundedCorners`

### Updating Page Content

When updating a page via API, the backend deletes ALL existing blocks and recreates them from the payload. This means:
- Block IDs are regenerated on each save
- Frontend generates temporary IDs (using `Date.now()`) for new blocks
- On save, blocks are sent as array in page payload
- Backend creates new block records with proper IDs

### Authentication Flow

1. User logs in via `/login`
2. Backend validates credentials, generates JWT token
3. Token stored in localStorage via authStore
4. Axios interceptor automatically adds token to all requests
5. AuthMiddleware validates token on protected routes
6. User info loaded on app initialization from `/api/auth/me`

### Permission Checking

Pages are filtered on the backend based on user role:
- Admin sees all pages
- Company manager sees all pages in their company
- Regular user sees only their own pages

Frontend should not assume permissions - always rely on backend filtering.

### Slug Generation

Slugs are auto-generated from page title in PageEditor.vue:
- Converts to lowercase
- Replaces non-alphanumeric with hyphens
- Backend ensures uniqueness by appending counter if slug exists

### Image Uploads

Images are uploaded to `backend/public/uploads/images/` via `POST /api/upload/image`:
- Accepts multipart/form-data with `image` field
- Validates file type (JPEG, PNG, GIF, WebP)
- Generates unique filename with timestamp
- Returns full URL path for use in blocks

### Page Duplication

Pages can be duplicated via `POST /api/pages/{id}/duplicate`:
- Creates new page with "Copy of" prefix
- Copies all blocks with new IDs
- Generates unique slug
- Sets same user_id and company_id as original
- Marks as unpublished by default

## Key Patterns

- **JSON Storage**: Block content/styles stored as JSON in database, allows flexible schema per block type
- **Cascade Deletes**: Deleting a page automatically deletes all blocks (database constraint)
- **Multi-tenant**: Pages are scoped to users and companies, with role-based access control
- **JWT Auth**: Stateless authentication using JWT tokens with role and company information
- **Optimistic Updates**: Frontend updates local state before API confirmation for better UX
- **Component Reuse**: Same block components used in editor, preview, and public views with different props
- **Eloquent Relationships**: Always eager load blocks with `->with('blocks')` to avoid N+1 queries
- **Router Guards**: Authentication and authorization enforced at router level
- **Axios Interceptors**: JWT token automatically injected into all API requests

## Deployment Notes

- Frontend must be built before deployment: `npm run build`
- Backend `.env` must be configured with production database credentials and JWT_SECRET
- BASE_PATH must match server subfolder (e.g., `/ELPB/backend/public`)
- VITE_API_URL must point to production API endpoint
- Apache/Nginx must be configured for Vue Router (SPA) and API routes
- Run all migrations in order when setting up new database
- Set proper file permissions on `backend/public/uploads/` (755 or 775)
- Generate strong JWT_SECRET using `openssl rand -base64 64`
- Remove or protect debug routes in production (`/api/debug/*`)

## Common Issues

See `TROUBLESHOOTING.md` for detailed troubleshooting guide including:
- Slider rendering issues
- Image upload problems
- Database connection errors
- JWT authentication issues
- CORS configuration
