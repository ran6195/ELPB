# Landing Page Builder

Applicazione full-stack per la creazione e gestione di landing pages tramite un editor drag & drop visuale. Supporta multi-tenancy, autenticazione JWT, cattura lead e rendering su più piattaforme.

---

## Stack Tecnologico

### Backend
| Tecnologia | Ruolo |
|---|---|
| **PHP 8** + **Slim Framework 4** | API REST, routing, middleware |
| **Eloquent ORM** | Accesso al database, modelli, relazioni |
| **MySQL 8** | Persistenza dei dati |
| **JWT (Firebase JWT)** | Autenticazione stateless |
| **PHPMailer** | Notifiche email sui nuovi lead |
| **vlucas/phpdotenv** | Gestione variabili d'ambiente |

### Frontend
| Tecnologia | Ruolo |
|---|---|
| **Vue 3** (Composition API) | Framework UI |
| **Vite** | Build tool e dev server |
| **Pinia** | State management |
| **Vue Router** | Navigazione SPA |
| **TailwindCSS** | Utility-first styling |
| **VueDraggable** | Drag & drop blocchi nell'editor |
| **VueQuill** | Editor rich text nei blocchi |
| **Swiper.js** | Blocco slider |
| **axios** | Client HTTP per le API |

### Renderer Pubblici
| Renderer | Descrizione |
|---|---|
| **Standalone PHP** | `standalone-renderer/` — file autonomo deployabile su qualsiasi hosting Apache/PHP |
| **Joomla Component** | `joomla-component/` — componente nativo per siti Joomla |

---

## Architettura

```
landing-page-builder/
├── backend/
│   ├── config/                  # Connessione database
│   ├── database/migrations/     # Script SQL di migrazione
│   └── src/
│       ├── Controllers/         # AuthController, PageController, LeadController, UploadController
│       ├── Middleware/          # AuthMiddleware (JWT)
│       ├── Models/              # User, Company, Page, Block, Lead
│       └── Services/            # EmailService (PHPMailer)
│
├── frontend/
│   └── src/
│       ├── components/
│       │   └── blocks/          # Un componente Vue per ogni tipo di blocco
│       ├── views/               # Login, PageEditor, AdminPanel, CompanyPanel, ecc.
│       ├── stores/              # authStore, pageStore (Pinia)
│       └── router/              # Route con guard per ruoli
│
├── standalone-renderer/
│   ├── page.php                 # Entry point renderer standalone
│   └── BlockRenderer.php        # Renderizza ogni tipo di blocco in PHP puro
│
└── joomla-component/
    └── com_landingpages/        # Componente Joomla installabile
```

### Modello dati principale

- **users** — tre ruoli: `admin`, `company`, `user`
- **companies** — raggruppamento multi-tenant degli utenti
- **pages** — slug univoco, JSON per `styles`, `tracking_settings`, `recaptcha_settings`, `legal_info`, `notification_settings`; soft delete
- **blocks** — appartengono a una pagina (cascade delete), `type` + `content` JSON + `styles` JSON + `order`
- **leads** — catturati dai form, collegati a una pagina

### Flusso di autenticazione

JWT con scadenza 7 giorni, inviato come `Authorization: Bearer <token>`. Le route sono protette per ruolo: pubbliche, utente autenticato, company, admin.

---

## Tipi di Blocco Disponibili

| Blocco | Descrizione |
|---|---|
| **Header** | Barra di navigazione con logo, menu e link social |
| **Hero** | Sezione principale con titolo, sottotitolo, CTA e immagine di sfondo |
| **Hero Wide** | Variante hero a larghezza variabile |
| **Text** | Testo ricco con VueQuill (heading, grassetto, colori, allineamento) |
| **Two Column (Testo/Immagine)** | Layout a due colonne con testo a sinistra e immagine a destra |
| **Two Column (Immagine/Testo)** | Layout a due colonne invertito |
| **CTA** | Call to action con titolo, descrizione, pulsante e testo secondario |
| **Features** | Griglia vantaggi con icone personalizzabili (3 o 4 colonne) |
| **Services Grid** | Griglia servizi con immagine, titolo e descrizione |
| **Slider** | Carosello immagini con Swiper.js |
| **Video** | Blocco video (URL esterno o upload diretto) |
| **Video Info** | Video affiancato a testo informativo |
| **Map** | Mappa con immagine statica e dati di contatto |
| **Form** | Form lead con reCAPTCHA v2, layout personalizzabile |
| **Social** | Icone social media (Facebook, Instagram, X, LinkedIn, YouTube) |
| **Footer** | Piè di pagina con testo, link e contatti |
| **Legal Footer** | Footer con link a Privacy Policy, Cookie Policy e Condizioni d'uso |

---

## Funzionalità Principali

- **Editor visuale drag & drop** con anteprima in tempo reale (desktop / tablet / mobile)
- **Multi-tenant** con gerarchia admin → company → user
- **Cattura lead** con notifiche email automatiche all'owner della pagina
- **reCAPTCHA v2** integrato nei form
- **Google Tag Manager** configurabile per pagina
- **SEO** — meta title, meta description, noindex configurabile
- **Google Fonts** — 20 font selezionabili per pagina e per singolo blocco
- **Import / Export** pagine in JSON tra server diversi
- **Soft delete** con archivio e ripristino pagine
- **Pagine legali dinamiche** — Privacy Policy, Cookie Policy, Condizioni d'uso generate al volo
- **Renderer remoto** — il renderer standalone può puntare a un backend centralizzato su server diverso

---

## Installazione

### Prerequisiti

- PHP 8.0+
- MySQL 8.0+
- Composer
- Node.js 18+ e npm
- Apache con `mod_rewrite` abilitato

---

### 1. Backend

```bash
cd backend
composer install
```

Crea il file `.env` copiando l'esempio:

```bash
cp .env.example .env
```

Configura le variabili nel `.env`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=landing_page_builder
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=cambia-questo-con-una-stringa-segreta-lunga

APP_URL=http://localhost:8000
BASE_PATH=

APP_DEBUG=true

MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Landing Page Builder"
```

Esegui le migrazioni:

```bash
php database/migrations/create_tables.php
php database/migrations/add_soft_delete_to_pages.php
php database/migrations/add_tracking_settings.php
php database/migrations/add_legal_info_to_pages.php
php database/migrations/add_notification_settings_to_pages.php
```

Avvia il server di sviluppo:

```bash
php -S localhost:8000 -t public
```

---

### 2. Frontend

```bash
cd frontend
npm install
```

Avvia il dev server:

```bash
npm run dev
# disponibile su http://localhost:3000
```

Build per la produzione:

```bash
npm run build
# output nella cartella dist/
```

---

### 3. Renderer Standalone (opzionale)

Il renderer standalone permette di pubblicare le pagine su un hosting separato dal backend.

Copia la cartella `standalone-renderer/` sul server di destinazione e crea il file `.env`:

```env
API_BASE_URL=https://tuo-backend.example.com/api
APP_URL=https://tuo-backend.example.com
```

Assicurati che `.htaccess` sia attivo e che `mod_rewrite` sia abilitato sul server.

---

## Deployment in Produzione

### Backend

1. Carica la cartella `backend/` sul server
2. Configura `.env` con i valori di produzione (`APP_DEBUG=false`)
3. Imposta i permessi: `chmod 755 backend/public/uploads/`
4. Configura il virtual host Apache per puntare a `backend/public/`

### Frontend

1. Esegui `npm run build` dalla cartella `frontend/`
2. Carica il contenuto di `dist/` sul server
3. Configura Apache per gestire il routing SPA (tutte le route → `index.html`):

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

---

## Primo Accesso

Al primo avvio crea un utente admin eseguendo lo script:

```bash
php backend/database/migrations/create_admin_user.php
```

Oppure inserisci manualmente un record nella tabella `users` con `role = 'admin'` e password hashata con `password_hash()`.

---

## Licenza

Tutti i diritti riservati.
