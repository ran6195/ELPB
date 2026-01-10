<?php

namespace App\Controllers;

use App\Models\Page;
use App\Models\User;
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
        $query = Page::with(['blocks', 'company', 'user']);

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
        $page = Page::with(['blocks', 'company', 'user'])->find($args['id']);

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

        // Validate required fields
        if (empty($data['title']) || empty($data['slug'])) {
            $response->getBody()->write(json_encode(['error' => 'Title and slug are required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Ensure unique slug
        $slug = $data['slug'];
        $counter = 1;
        while (Page::where('slug', $slug)->exists()) {
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

        // Update page
        $page->update([
            'title' => $data['title'] ?? $page->title,
            'slug' => $data['slug'] ?? $page->slug,
            'meta_title' => $data['meta_title'] ?? $page->meta_title,
            'meta_description' => $data['meta_description'] ?? $page->meta_description,
            'is_published' => $data['is_published'] ?? $page->is_published,
            'styles' => $data['styles'] ?? $page->styles,
            'recaptcha_settings' => $data['recaptcha_settings'] ?? $page->recaptcha_settings,
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

        $page->load('blocks');

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

        // Impedisce l'eliminazione di pagine pubblicate
        if ($page->is_published) {
            $response->getBody()->write(json_encode(['error' => 'Non puoi eliminare una pagina pubblicata. Prima devi rimuovere la pubblicazione.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
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

        $response->getBody()->write(json_encode($page));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Reassign page to another user (company manager)
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

        $page->user_id = $newUserId;
        $page->save();

        $page->load(['blocks', 'company', 'user']);

        $response->getBody()->write(json_encode([
            'success' => true,
            'page' => $page
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

        // Assicura che lo slug sia unico aggiungendo un suffisso numerico
        $slug = $baseSlug;
        $counter = 1;
        while (Page::where('slug', $slug)->exists()) {
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
}
