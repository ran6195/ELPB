<?php

namespace App\Controllers;

use App\Models\Page;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PageController
{
    public function index(Request $request, Response $response)
    {
        $pages = Page::with('blocks')->orderBy('created_at', 'desc')->get();

        $response->getBody()->write(json_encode($pages));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args)
    {
        $page = Page::with('blocks')->find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($page));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response)
    {
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

        // Create page
        $page = Page::create([
            'title' => $data['title'],
            'slug' => $slug,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'is_published' => $data['is_published'] ?? false,
            'styles' => $data['styles'] ?? null
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
        $page = Page::find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = json_decode($request->getBody()->getContents(), true);

        // Update page
        $page->update([
            'title' => $data['title'] ?? $page->title,
            'slug' => $data['slug'] ?? $page->slug,
            'meta_title' => $data['meta_title'] ?? $page->meta_title,
            'meta_description' => $data['meta_description'] ?? $page->meta_description,
            'is_published' => $data['is_published'] ?? $page->is_published,
            'styles' => $data['styles'] ?? $page->styles
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
        $page = Page::find($args['id']);

        if (!$page) {
            $response->getBody()->write(json_encode(['error' => 'Page not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
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
}
