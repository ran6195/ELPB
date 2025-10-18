# Landing Page Builder

Un'applicazione completa per creare landing pages con drag & drop builder, sviluppata con Vue 3 e PHP (Slim Framework).

## Stack Tecnologico

### Backend
- **PHP** con Slim Framework 4
- **Eloquent ORM** per la gestione del database
- **MySQL** per la persistenza dei dati
- **API REST** per la comunicazione frontend-backend

### Frontend
- **Vue 3** con Composition API
- **Vue Router** per la navigazione
- **Pinia** per lo state management
- **Vite** come build tool
- **TailwindCSS** per lo styling
- **VueDraggable** per il drag & drop

## Struttura del Progetto

```
landing-page-builder/
├── backend/
│   ├── config/          # Configurazioni (database, etc)
│   ├── database/
│   │   └── migrations/  # Script di migrazione database
│   ├── public/
│   │   └── index.php    # Entry point API
│   └── src/
│       ├── Controllers/ # Controller API
│       └── Models/      # Modelli Eloquent
└── frontend/
    ├── public/
    ├── src/
    │   ├── components/  # Componenti Vue
    │   │   └── blocks/  # Blocchi della landing page
    │   ├── stores/      # Pinia stores
    │   ├── views/       # Pagine principali
    │   └── router/      # Configurazione router
    └── index.html
```

## Database Schema

### Tabella `pages`
- `id` - Chiave primaria
- `title` - Titolo della pagina
- `slug` - URL friendly slug
- `meta_title` - Meta tag title (SEO)
- `meta_description` - Meta tag description (SEO)
- `is_published` - Stato pubblicazione
- `created_at`, `updated_at` - Timestamp

### Tabella `blocks`
- `id` - Chiave primaria
- `page_id` - Foreign key verso pages
- `type` - Tipo blocco (hero, text, form, etc)
- `content` - JSON con contenuto del blocco
- `styles` - JSON con stili personalizzati
- `position` - JSON con posizione e dimensioni
- `order` - Ordine di visualizzazione
- `created_at`, `updated_at` - Timestamp

### Tabella `leads`
- `id` - Chiave primaria
- `page_id` - Foreign key verso pages
- `name` - Nome del lead
- `email` - Email del lead
- `phone` - Telefono (opzionale)
- `message` - Messaggio (opzionale)
- `metadata` - JSON con dati extra
- `created_at`, `updated_at` - Timestamp

## Setup e Installazione

### Prerequisiti
- PHP 8.0+
- MySQL 8.0+
- Composer
- Node.js 18+ e npm

### Backend Setup

1. Entra nella cartella backend:
```bash
cd backend
```

2. Installa le dipendenze:
```bash
composer install
```

3. Configura il file `.env` (già presente con valori di default):
```
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=landing_page_builder
DB_USERNAME=root
DB_PASSWORD=
```

4. Esegui le migrazioni per creare il database e le tabelle:
```bash
php database/migrations/create_tables.php
```

5. Avvia il server di sviluppo PHP:
```bash
php -S localhost:8000 -t public
```

L'API sarà disponibile su `http://localhost:8000`

### Frontend Setup

1. Entra nella cartella frontend:
```bash
cd frontend
```

2. Installa le dipendenze:
```bash
npm install
```

3. Avvia il dev server:
```bash
npm run dev
```

L'applicazione sarà disponibile su `http://localhost:3000`

## Utilizzo

### Lista Pagine
La schermata principale mostra tutte le landing pages create. Puoi:
- Creare una nuova pagina
- Modificare una pagina esistente
- Vedere l'anteprima
- Eliminare una pagina

### Editor
L'editor permette di:
- Aggiungere blocchi dalla sidebar (Hero, Testo, Form)
- Modificare i contenuti direttamente con contenteditable
- Riordinare i blocchi con drag & drop
- Personalizzare stili (colori, padding, etc) dal pannello proprietà
- Salvare le modifiche

### Blocchi Disponibili

#### Hero Block
- Titolo principale
- Sottotitolo
- Call-to-action button con link
- Background personalizzabile

#### Text Block
- Titolo sezione
- Testo formattato

#### Form Block
- Form di contatto per catturare lead
- Campi personalizzabili (nome, email, telefono, messaggio)
- Salvataggio automatico nel database

### Anteprima
Visualizza la landing page come la vedranno gli utenti finali, con tutti i blocchi renderizzati e il form funzionante.

## API Endpoints

### Pages
- `GET /api/pages` - Lista tutte le pagine
- `GET /api/pages/{id}` - Dettaglio pagina
- `POST /api/pages` - Crea nuova pagina
- `PUT /api/pages/{id}` - Aggiorna pagina
- `DELETE /api/pages/{id}` - Elimina pagina

### Leads
- `POST /api/leads` - Salva un nuovo lead

## Sviluppi Futuri

- [ ] Più tipologie di blocchi (video, galleria immagini, testimonianze)
- [ ] Template pre-costruiti
- [ ] Sistema di autenticazione utenti
- [ ] Export HTML statico della landing page
- [ ] Analytics e tracking conversioni
- [ ] A/B testing
- [ ] Integrazione con email marketing (Mailchimp, SendGrid)
- [ ] Responsive preview (mobile, tablet, desktop)
- [ ] Sistema di versioning delle pagine

## Licenza

Progetto didattico - Uso libero
