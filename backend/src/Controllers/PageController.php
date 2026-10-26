<?php

namespace App\Controllers;

use App\Models\Domain;
use App\Models\Lead;
use App\Models\Page;
use App\Models\User;
use App\Services\TrackingService;
use App\Utils\Hostname;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PageController
{
    public function index(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Filtra le pagine in base al ruolo
        $query = Page::with(['blocks', 'company', 'user', 'domains'])->withCount('leads');

        if ($user->isAdmin()) {
            // Admin vede tutte le pagine
            $pages = $query->orderBy('created_at', 'desc')->get();
        } elseif ($user->isCompany()) {
            // Company vede solo le pagine della sua azienda
            $pages = $query->where('company_id', $user->company_id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // User vede solo le sue pagine
            $pages = $query->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $response->getBody()->write(json_encode($pages));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::with(['blocks', 'company', 'user', 'domains'])->find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Controlla i permessi
        if ($user && !$user->canViewPage($page)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $response->getBody()->write(json_encode($page));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        try {
            // Validate required fields
            if (empty($data['title']) || empty($data['slug'])) {
                $response->getBody()->write(json_encode(['error' => 'Title and slug are required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Ensure unique slug (check also soft-deleted pages)
            $slug = $data['slug'];
            $counter = 1;
            while (Page::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $data['slug'] . '-' . $counter;
                $counter++;
            }

            // Create page con company_id e user_id
            $page = Page::create([
                'title' => $data['title'],
                'slug' => $slug,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'is_published' => $data['is_published'] ?? false,
                'styles' => $data['styles'] ?? null,
                'recaptcha_settings' => $data['recaptcha_settings'] ?? null,
                'tracking_settings' => $data['tracking_settings'] ?? null,
                'quick_contacts' => $data['quickContacts'] ?? $data['quick_contacts'] ?? null,
                'company_id' => $user->company_id,
                'user_id' => $user->id
            ]);

            // Create blocks if provided
            if (!empty($data['blocks'])) {
                foreach ($data['blocks'] as $blockData) {
                    $page->blocks()->create([
                        'type' => $blockData['type'],
                        'content' => $blockData['content'] ?? [],
                        'styles' => $blockData['styles'] ?? [],
                        'position' => $blockData['position'] ?? [],
                        'order' => $blockData['order'] ?? 0
                    ]);
                }
            }

            $page->load('blocks');

            $response->getBody()->write(json_encode($page));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function update(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Controlla i permessi
        if (!$user || !$user->canEditPage($page)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        // Ensure unique slug if it's being changed (check also soft-deleted pages)
        $newSlug = $data['slug'] ?? $page->slug;
        if ($newSlug !== $page->slug) {
            $slug = $newSlug;
            $counter = 1;
            while (Page::withTrashed()->where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
                $slug = $newSlug . '-' . $counter;
                $counter++;
            }
            $data['slug'] = $slug;
        }

        // Update page
        $page->update([
            'title' => $data['title'] ?? $page->title,
            'slug' => $data['slug'] ?? $page->slug,
            'meta_title' => $data['meta_title'] ?? $page->meta_title,
            'meta_description' => $data['meta_description'] ?? $page->meta_description,
            'is_published' => $data['is_published'] ?? $page->is_published,
            'styles' => $data['styles'] ?? $page->styles,
            'recaptcha_settings' => $data['recaptcha_settings'] ?? $page->recaptcha_settings,
            'tracking_settings' => $data['tracking_settings'] ?? $page->tracking_settings,
            'quick_contacts' => $data['quickContacts'] ?? $data['quick_contacts'] ?? $page->quick_contacts
        ]);

        // Update blocks if provided
        if (isset($data['blocks'])) {
            // Delete existing blocks
            $page->blocks()->delete();

            // Create new blocks
            foreach ($data['blocks'] as $blockData) {
                $page->blocks()->create([
                    'type' => $blockData['type'],
                    'content' => $blockData['content'] ?? [],
                    'styles' => $blockData['styles'] ?? [],
                    'position' => $blockData['position'] ?? [],
                    'order' => $blockData['order'] ?? 0
                ]);
            }
        }

        $page->load(['blocks', 'domains']);

        $response->getBody()->write(json_encode($page));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Controlla i permessi
        if (!$user || !$user->canEditPage($page)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $page->delete();

        $response->getBody()->write(json_encode(['message' => 'Page deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function showBySlug(Request $request, Response $response, array $args)
    {
        $page = Page::with('blocks')
            ->where('slug', $args['slug'])
            ->where('is_published', true)
            ->first();

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found or not published']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Endpoint pubblico: niente segreti né dati interni (secret_key reCAPTCHA,
        // destinatari delle notifiche, proprietario)
        $data = $page->toArray();
        unset($data['notification_settings'], $data['user_id'], $data['company_id'], $data['deleted_at']);
        if (is_array($data['recaptcha_settings'] ?? null)) {
            unset($data['recaptcha_settings']['secret_key']);
        }

        // Registro dei renderer (sito, versione): non blocca mai la risposta.
        // class_exists: durante l'upload FTP questo file può arrivare prima del servizio
        if (class_exists(TrackingService::class)) {
            TrackingService::recordRendererHit($request, $page);
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Reassign page to another user (admin: qualsiasi utente, anche di un'altra azienda;
     * company manager: solo utenti della propria azienda). L'azienda segue il nuovo proprietario.
     */
    public function reassignPage(Request $request, Response $response, array $args)
    {
        $currentUser = $request->getAttribute('user');
        $pageId = $args['id'];
        $page = Page::find($pageId);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Solo company manager può riassegnare pagine della sua company
        if (!$currentUser || (!$currentUser->isCompany() && !$currentUser->isAdmin())) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Verifica che la pagina appartenga alla company del manager
        if ($currentUser->isCompany() && $page->company_id !== $currentUser->company_id) {
            $response->getBody()->write(json_encode(['error' => 'Page does not belong to your company']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['user_id']) || $data['user_id'] === null) {
            $response->getBody()->write(json_encode(['error' => 'user_id is required and cannot be null']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $newUserId = $data['user_id'];
        $newUser = User::find($newUserId);

        if (!$newUser) {
            $response->getBody()->write(json_encode(['error' => 'Target user not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Verifica che il nuovo utente appartenga alla stessa company
        if ($currentUser->isCompany() && $newUser->company_id !== $currentUser->company_id) {
            $response->getBody()->write(json_encode(['error' => 'Target user does not belong to your company']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // L'azienda segue il nuovo proprietario (l'admin può spostare pagine tra aziende;
        // il responsabile aziendale resta nella propria, verificato sopra)
        $page->user_id = $newUserId;
        $page->company_id = $newUser->company_id;
        $page->save();

        // Domini e origine dei lead dell'azienda precedente non valgono per la nuova
        $removedDomains = [];
        foreach ($page->domains()->get() as $domain) {
            if (!$domain->sameOwnerAs($page)) {
                $removedDomains[] = $domain->hostname;
                $page->domains()->detach($domain->id);
                Lead::where('page_id', $page->id)->where('domain_id', $domain->id)->update(['domain_id' => null]);
            }
        }

        $page->load(['blocks', 'company', 'user', 'domains']);

        $response->getBody()->write(json_encode([
            'success' => true,
            'page' => $page,
            'removed_domains' => $removedDomains
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Duplicate a page with all its blocks
     */
    public function duplicate(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $originalPage = Page::with('blocks')->find($args['id']);

        if (!$originalPage) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Controlla i permessi - l'utente deve poter visualizzare la pagina per duplicarla
        if (!$user || !$user->canViewPage($originalPage)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Genera il nuovo titolo
        $newTitle = "Copia di " . $originalPage->title;

        // Genera lo slug base dal nuovo titolo
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $newTitle)));

        // Assicura che lo slug sia unico aggiungendo un suffisso numerico (check also soft-deleted pages)
        $slug = $baseSlug;
        $counter = 1;
        while (Page::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Crea la nuova pagina (sempre non pubblicata)
        $newPage = Page::create([
            'title' => $newTitle,
            'slug' => $slug,
            'meta_title' => $originalPage->meta_title,
            'meta_description' => $originalPage->meta_description,
            'is_published' => false,  // Sempre non pubblicata per sicurezza
            'styles' => $originalPage->styles,
            'recaptcha_settings' => $originalPage->recaptcha_settings,
            'tracking_settings' => $originalPage->tracking_settings,
            'quick_contacts' => $originalPage->quickContacts ?? $originalPage->quick_contacts ?? null,
            'company_id' => $originalPage->company_id,
            'user_id' => $user->id  // La copia appartiene all'utente che la duplica
        ]);

        // Duplica tutti i blocchi
        foreach ($originalPage->blocks as $originalBlock) {
            $newPage->blocks()->create([
                'type' => $originalBlock->type,
                'content' => $originalBlock->content,
                'styles' => $originalBlock->styles,
                'position' => $originalBlock->position,
                'order' => $originalBlock->order
            ]);
        }

        // Ricarica la pagina con tutti i blocchi e le relazioni
        $newPage->load(['blocks', 'company', 'user']);

        $response->getBody()->write(json_encode($newPage));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Get archived (soft deleted) pages
     */
    public function archived(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        $query = Page::onlyTrashed()->with(['blocks', 'company', 'user']);

        // Filtra per permessi come in index()
        if ($user->isUser()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isCompany()) {
            $query->where('company_id', $user->company_id);
        }
        // Admin vede tutto

        $pages = $query->orderBy('deleted_at', 'desc')->get();

        $response->getBody()->write(json_encode($pages));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Restore archived page
     */
    public function restore(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::onlyTrashed()->find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Archived page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Verifica permessi
        if (!$user || !$user->canEditPage($page)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $page->restore();

        // Ricarica la pagina ripristinata
        $page->load(['blocks', 'company', 'user', 'domains']);

        $response->getBody()->write(json_encode([
            'message' => 'Page restored successfully',
            'page' => $page
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Check if slug is available
     */
    public function checkSlug(Request $request, Response $response)
    {
        // Slim potrebbe non parsare automaticamente il JSON body
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);

        // Fallback a getParsedBody se json_decode fallisce
        if (!$data) {
            $data = $request->getParsedBody();
        }

        $slug = $data['slug'] ?? '';
        $pageId = $data['page_id'] ?? null;

        // Valida slug
        if (empty($slug)) {
            $response->getBody()->write(json_encode([
                'available' => false,
                'message' => 'Lo slug non può essere vuoto'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Valida formato slug: solo lettere, numeri e trattini
        if (!preg_match('/^[a-zA-Z0-9-]+$/', $slug)) {
            $response->getBody()->write(json_encode([
                'available' => false,
                'message' => 'Lo slug può contenere solo lettere, numeri e trattini'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Normalizza slug (converte in lowercase)
        $normalizedSlug = strtolower(trim($slug, '-'));

        // Verifica se lo slug esiste già (include pagine archiviate con withTrashed)
        $query = Page::withTrashed()->where('slug', $normalizedSlug);

        // Se stiamo modificando una pagina esistente, escludila dal controllo
        if ($pageId) {
            $query->where('id', '!=', $pageId);
        }

        $exists = $query->exists();

        if ($exists) {
            $response->getBody()->write(json_encode([
                'available' => false,
                'message' => 'Questo slug è già utilizzato da un\'altra pagina'
            ]));
        } else {
            $response->getBody()->write(json_encode([
                'available' => true,
                'message' => 'Slug disponibile'
            ]));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Permanently delete an archived page (force delete)
     */
    public function forceDelete(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::onlyTrashed()->find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Archived page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Verifica permessi
        if (!$user || !$user->canEditPage($page)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Elimina definitivamente la pagina (i blocchi vengono eliminati in cascade)
        $page->forceDelete();

        $response->getBody()->write(json_encode([
            'message' => 'Page permanently deleted'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Export page as JSON
     */
    public function export(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::with('blocks')->find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Verifica permessi
        if (!$user || !$user->canViewPage($page)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Prepara i dati da esportare (solo definizione pagina, senza relazioni utente)
        $exportData = [
            'version' => '1.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'styles' => $page->styles,
                'tracking_settings' => $page->tracking_settings,
                'recaptcha_settings' => $page->recaptcha_settings,
                'quick_contacts' => $page->quick_contacts,
                'blocks' => $page->blocks->map(function($block) {
                    return [
                        'type' => $block->type,
                        'content' => $block->content,
                        'styles' => $block->styles,
                        'position' => $block->position,
                        'order' => $block->order
                    ];
                })->toArray()
            ]
        ];

        // Genera nome file
        $filename = 'page-' . $page->slug . '-' . date('Ymd-His') . '.json';

        $response->getBody()->write(json_encode($exportData, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Import page from JSON
     */
    public function import(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        if (!$data || !isset($data['page'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid import data']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $pageData = $data['page'];

        try {
            // Valida che ci siano almeno title e slug
            if (empty($pageData['title']) || empty($pageData['slug'])) {
                $response->getBody()->write(json_encode(['error' => 'Title and slug are required']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Genera slug unico se esiste già (check also soft-deleted pages)
            $baseSlug = $pageData['slug'];
            $slug = $baseSlug;

            if (Page::withTrashed()->where('slug', $slug)->exists()) {
                // Slug esistente: aggiungi suffisso random
                $randomSuffix = substr(md5(uniqid()), 0, 6);
                $slug = $baseSlug . '-' . $randomSuffix;

                // Se anche con il random esiste (improbabile), usa contatore
                $counter = 1;
                while (Page::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $randomSuffix . '-' . $counter;
                    $counter++;
                }
            }

            // Crea la nuova pagina (proprietà dell'utente che importa, non pubblicata)
            $newPage = Page::create([
                'title' => $pageData['title'],
                'slug' => $slug,
                'meta_title' => $pageData['meta_title'] ?? null,
                'meta_description' => $pageData['meta_description'] ?? null,
                'is_published' => false,  // Sempre non pubblicata per sicurezza
                'styles' => $pageData['styles'] ?? null,
                'recaptcha_settings' => $pageData['recaptcha_settings'] ?? null,
                'tracking_settings' => $pageData['tracking_settings'] ?? null,
                'quick_contacts' => $pageData['quick_contacts'] ?? null,
                'company_id' => $user->company_id,
                'user_id' => $user->id  // Appartiene all'utente che importa
            ]);

            // Importa i blocchi
            if (!empty($pageData['blocks'])) {
                foreach ($pageData['blocks'] as $blockData) {
                    $newPage->blocks()->create([
                        'type' => $blockData['type'],
                        'content' => $blockData['content'] ?? [],
                        'styles' => $blockData['styles'] ?? [],
                        'position' => $blockData['position'] ?? [],
                        'order' => $blockData['order'] ?? 0
                    ]);
                }
            }

            // Ricarica con blocchi
            $newPage->load(['blocks', 'company', 'user']);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Page imported successfully',
                'page' => $newPage,
                'slug_changed' => $slug !== $baseSlug,
                'original_slug' => $baseSlug,
                'new_slug' => $slug
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Import failed',
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update legal information for a page
     */
    public function updateLegalInfo(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Controlla i permessi
        if (!$user->canEditPage($page)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        try {
            // Valida i campi obbligatori
            $requiredFields = [
                'ragioneSociale',
                'indirizzo',
                'cap',
                'citta',
                'provincia',
                'email',
                'telefono',
                'amministratore',
                'piva',
                'codiceFiscale',
                'gestoreDati'
            ];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode([
                        'error' => 'Validation failed',
                        'message' => "Il campo '$field' è obbligatorio"
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Salva i dati legali
            $page->legal_info = $data;
            $page->save();

            // Ricarica la pagina con le relazioni
            $page->load(['blocks', 'company', 'user', 'domains']);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Legal information saved successfully',
                'page' => $page
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to save legal information',
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update notification settings for a page
     */
    public function updateNotificationSettings(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Controlla i permessi
        if (!$user->canEditPage($page)) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        try {
            // Valida struttura dati
            if (!isset($data['enabled'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Validation failed',
                    'message' => "Il campo 'enabled' è obbligatorio"
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Valida owner_email se fornita
            if (!empty($data['owner_email']) && !filter_var($data['owner_email'], FILTER_VALIDATE_EMAIL)) {
                $response->getBody()->write(json_encode([
                    'error' => 'Validation failed',
                    'message' => "Email notifiche non valida: {$data['owner_email']}"
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Valida from_address notifiche se fornita
            if (!empty($data['from_address']) && !filter_var($data['from_address'], FILTER_VALIDATE_EMAIL)) {
                $response->getBody()->write(json_encode([
                    'error' => 'Validation failed',
                    'message' => "Indirizzo mittente non valido: {$data['from_address']}"
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Valida email aggiuntive se presenti
            if (!empty($data['additional_emails'])) {
                $emails = array_map('trim', explode(',', $data['additional_emails']));
                foreach ($emails as $email) {
                    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $response->getBody()->write(json_encode([
                            'error' => 'Validation failed',
                            'message' => "Email non valida: $email"
                        ]));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }
                }
            }

            // Salva le impostazioni notifiche
            $confirmationEmail = $data['confirmation_email'] ?? [];
            $page->notification_settings = [
                'enabled' => (bool) $data['enabled'],
                'owner_email' => $data['owner_email'] ?? '',
                'additional_emails' => $data['additional_emails'] ?? '',
                'from_name' => $data['from_name'] ?? '',
                'from_address' => $data['from_address'] ?? '',
                'confirmation_email' => [
                    'enabled' => (bool) ($confirmationEmail['enabled'] ?? false),
                    'subject' => $confirmationEmail['subject'] ?? '',
                    'body' => $confirmationEmail['body'] ?? '',
                    'from_name' => $confirmationEmail['from_name'] ?? '',
                    'from_address' => $confirmationEmail['from_address'] ?? '',
                    'header_color' => $confirmationEmail['header_color'] ?? '#667eea',
                    'header_color_end' => $confirmationEmail['header_color_end'] ?? '#764ba2',
                    'attachment_image' => $confirmationEmail['attachment_image'] ?? ''
                ]
            ];
            $page->save();

            // Ricarica la pagina con le relazioni
            $page->load(['blocks', 'company', 'user', 'domains']);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Notification settings saved successfully',
                'page' => $page
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to save notification settings',
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * PUT/POST /api/pages/{id}/domains  { domain_ids: [] }
     * Associazione facoltativa: un elenco vuoto riporta la pagina al comportamento di sempre.
     */
    public function updateDomains(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::find($args['id']);

        if (!$page) {
            return $this->jsonResponse($response, ['error' => 'Page not found'], 404);
        }
        if (!$user || !$user->canEditPage($page)) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $ids = $data['domain_ids'] ?? null;
        if (!is_array($ids)) {
            return $this->jsonResponse($response, ['error' => 'domain_ids deve essere un elenco'], 400);
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));

        // Solo domini visibili all'utente e dello stesso proprietario della pagina
        $domains = $ids ? Domain::visibleTo($user)->whereIn('id', $ids)->get() : collect();
        if ($domains->count() !== count($ids)) {
            return $this->jsonResponse($response, ['error' => 'Uno o più domini non esistono'], 404);
        }
        foreach ($domains as $domain) {
            if (!$domain->sameOwnerAs($page)) {
                return $this->jsonResponse($response, [
                    'error' => "Il dominio {$domain->hostname} appartiene a un'altra azienda"
                ], 422);
            }
        }

        $page->domains()->sync($ids);

        foreach ($domains as $domain) {
            $domain->linkExistingLeads($page->id);
        }

        return $this->jsonResponse($response, ['success' => true, 'data' => $page->domains()->get()]);
    }

    /**
     * GET /api/pages/{id}/domain-suggestions
     * Domini da cui la pagina ha ricevuto lead ma che non le sono ancora associati.
     */
    public function domainSuggestions(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::find($args['id']);

        if (!$page) {
            return $this->jsonResponse($response, ['error' => 'Page not found'], 404);
        }
        if (!$user || !$user->canViewPage($page)) {
            return $this->jsonResponse($response, ['error' => 'Forbidden'], 403);
        }

        $associated = $page->domains()->pluck('hostname')->all();

        $rows = Lead::where('page_id', $page->id)
            ->whereNotNull('origin_host')
            ->where('origin_inferred', false)
            ->selectRaw('origin_host, COUNT(*) as leads_count, MAX(created_at) as last_lead_at')
            ->groupBy('origin_host')
            ->orderByDesc('leads_count')
            ->get();

        $suggestions = $rows
            ->filter(fn($row) => !in_array($row->origin_host, $associated, true)
                && !Hostname::isAppHost($row->origin_host))
            ->map(function ($row) use ($user, $page) {
                // Se il dominio è già in anagrafica basta associarlo
                $existing = Domain::visibleTo($user)->where('hostname', $row->origin_host)->get()
                    ->first(fn($domain) => $domain->sameOwnerAs($page));
                return [
                    'hostname' => $row->origin_host,
                    'leads_count' => (int) $row->leads_count,
                    'last_lead_at' => $row->last_lead_at,
                    'domain_id' => $existing ? $existing->id : null,
                ];
            })
            ->values();

        return $this->jsonResponse($response, ['data' => $suggestions]);
    }

    private function jsonResponse(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
