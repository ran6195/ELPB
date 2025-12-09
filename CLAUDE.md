# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Landing Page Builder is a full-stack drag-and-drop application for creating landing pages. The project is split into two separate applications:

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

## Architecture

### Backend Architecture

**Entry Point**: `backend/public/index.php`
- Bootstraps Slim Framework
- Loads environment variables from `.env`
- Initializes Eloquent ORM via `config/database.php`
- Defines REST API routes
- Configures CORS middleware to allow frontend requests

**Database Connection**:
- Uses Illuminate Database (Eloquent ORM standalone) configured in `config/database.php`
- Connection settings pulled from `.env` file (defaults to localhost MySQL)
- Database name: `landing_page_builder`

**Models** (`backend/src/Models/`):
- `Page`: Represents landing pages with meta fields (title, slug, meta_title, meta_description, is_published)
- `Block`: Represents page building blocks with JSON fields (content, styles, position)
- `Lead`: Stores form submissions from landing pages
- Models use Eloquent relationships: Page `hasMany` Blocks, Blocks ordered by `order` field

**Controllers** (`backend/src/Controllers/`):
- `PageController`: Full CRUD for pages, includes `showBySlug()` for public page rendering
- `LeadController`: Handles form submissions
- `UploadController`: Handles image uploads

**Key Routes**:
- `GET /api/pages` - List all pages
- `GET /api/pages/{id}` - Get page with blocks
- `POST /api/pages` - Create page (auto-generates unique slug)
- `PUT /api/pages/{id}` - Update page and all blocks (deletes old blocks, creates new ones)
- `DELETE /api/pages/{id}` - Delete page (cascade deletes blocks)
- `GET /api/page/{slug}` - Get published page by slug (for public viewing)
- `POST /api/leads` - Submit form lead
- `POST /api/upload/image` - Upload image

### Frontend Architecture

**Entry Point**: `frontend/src/main.js`
- Bootstraps Vue 3 application
- Initializes Pinia store
- Configures Vue Router

**Router** (`frontend/src/router/index.js`):
- `/` - PageList (dashboard showing all pages)
- `/editor/:id?` - PageEditor (drag-drop builder, optional id for edit mode)
- `/preview/:id` - PagePreview (preview page before publishing)
- `/p/:slug` - PublicPage (public-facing rendered landing page)

**State Management** (`frontend/src/stores/pageStore.js`):
- Centralized Pinia store for all page operations
- API calls to backend using axios
- Base API URL: `http://localhost:8000/api`
- Handles CRUD operations, loading states, and error handling
- `submitLead()` action for form submissions

**Views**:
- `PageList.vue` - Dashboard grid showing all pages with edit/delete/preview actions
- `PageEditor.vue` - Main editor with 3-panel layout (blocks sidebar, canvas, properties panel)
- `PagePreview.vue` - Read-only preview of page
- `PublicPage.vue` - Public-facing page renderer (fetches by slug, only shows published pages)

**Block System**:
Located in `frontend/src/components/blocks/`. Each block is a Vue component that renders content from the `block.content` JSON object.

Available block types:
- `hero` - Hero section with title, subtitle, CTA button (HeroBlock.vue)
- `text` - Simple text block with title and paragraph (TextBlock.vue)
- `two-column-text-image` - Text left, image right (TwoColumnTextImage.vue)
- `two-column-image-text` - Image left, text right (TwoColumnImageText.vue)
- `form` - Lead capture form with configurable fields (FormBlock.vue)
- `footer` - Footer with company info, links, contacts (FooterBlock.vue)

**Block Structure**:
```javascript
{
  id: number,           // Unique identifier
  type: string,         // Block type (hero, text, form, etc)
  content: object,      // JSON content specific to block type
  styles: object,       // JSON with backgroundColor, textColor, padding
  position: object,     // JSON with layout info (currently unused)
  order: number         // Display order (updated on drag-drop)
}
```

**Editor Components**:
- `BlockEditor.vue` - Right panel for editing block properties (shows when block selected)
- `PageSettings.vue` - Right panel for editing page metadata (slug, SEO fields, publish status)

**Drag and Drop**:
- Uses `vuedraggable` library (wraps SortableJS)
- Applied to blocks in PageEditor.vue
- Updates `order` field on blocks after drag operations
- Footer block is always forced to bottom position

**Content Editing**:
- Blocks support `contenteditable` for inline text editing when in editor mode
- Block components receive `:editable="true"` prop in editor
- Changes reflected immediately in page.blocks array (Vue reactivity)

## Database Schema

### `pages` table
- `id` - Primary key
- `title` - Page title shown in editor
- `slug` - URL-friendly identifier (auto-generated from title, must be unique)
- `meta_title` - SEO meta title
- `meta_description` - SEO meta description
- `is_published` - Boolean, only published pages accessible via public URL
- `created_at`, `updated_at` - Timestamps

### `blocks` table
- `id` - Primary key
- `page_id` - Foreign key to pages (cascade delete)
- `type` - Block type string
- `content` - JSON field with block-specific data
- `styles` - JSON field with backgroundColor, textColor, padding
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
- `metadata` - JSON field for extra form data
- `created_at`, `updated_at` - Timestamps

## Development Workflow

### Adding New Block Types

1. Create new Vue component in `frontend/src/components/blocks/`
2. Add block type to `blockTypes` array in PageEditor.vue (line ~188)
3. Add default content structure in `getDefaultContent()` function (line ~240)
4. Map component in `getBlockComponent()` function (line ~291)
5. Block content structure should match what the component expects in props

### Updating Page Content

When updating a page via API, the backend deletes ALL existing blocks and recreates them from the payload. This means:
- Block IDs are regenerated on each save
- Frontend generates temporary IDs (using `Date.now()`) for new blocks
- On save, blocks are sent as array in page payload
- Backend creates new block records with proper IDs

### CORS Configuration

CORS middleware in `backend/public/index.php` allows all origins (`*`). For production, restrict to specific frontend domain.

### Slug Generation

Slugs are auto-generated from page title in PageEditor.vue (line ~346):
- Converts to lowercase
- Replaces non-alphanumeric with hyphens
- Backend ensures uniqueness by appending counter if slug exists

### Image Uploads

Upload endpoint exists at `POST /api/upload/image` but implementation details in UploadController.php should be verified for file storage location and validation.

## Key Patterns

- **JSON Storage**: Block content/styles stored as JSON in database, allows flexible schema per block type
- **Cascade Deletes**: Deleting a page automatically deletes all blocks (database constraint)
- **Optimistic Updates**: Frontend updates local state before API confirmation for better UX
- **Component Reuse**: Same block components used in editor, preview, and public views with different props
- **Eloquent Relationships**: Always eager load blocks with `->with('blocks')` to avoid N+1 queries
- ricorda che in esercizio siamo nella sottocartella ELPB