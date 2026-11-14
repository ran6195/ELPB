<?php

namespace App\Controllers;

use App\Models\Company;
use App\Models\Page;
use App\Services\PublicationHistory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Storico della pubblicazione pagina per pagina (solo admin), base della fatturazione.
 * Comprende le pagine archiviate e quelle eliminate definitivamente che hanno uno storico.
 * Date in ora italiana, come nel database.
 */
class PublicationController
{
    /**
     * GET /api/admin/publications
     * { data: [{page, periods: [...]}], companies: {id: nome}, now }
     */
    public function index(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        if (!$user || !$user->isAdmin()) {
            return $this->json($response, ['error' => 'Forbidden'], 403);
        }

        $periods = Capsule::table(PublicationHistory::TABLE)
            ->orderBy('started_at')
            ->orderBy('id')
            ->get()
            ->groupBy('page_id');

        $pages = Page::withTrashed()
            ->with(['user:id,name', 'company:id,name'])
            ->whereIn('id', $periods->keys()->all())
            ->get(['id', 'title', 'slug', 'is_published', 'deleted_at', 'user_id', 'company_id'])
            ->keyBy('id');

        $companyIds = $periods->flatten()->pluck('company_id')->filter()->unique()->values()->all();
        $companies = Company::whereIn('id', $companyIds)->pluck('name', 'id');

        $data = $periods->map(function ($pagePeriods, $pageId) use ($pages) {
            $page = $pages->get($pageId);
            return [
                'page' => [
                    'id' => (int) $pageId,
                    'title' => $page->title ?? null,
                    'slug' => $page->slug ?? null,
                    'status' => !$page ? 'deleted' : ($page->trashed() ? 'archived' : ($page->is_published ? 'online' : 'draft')),
                    'owner' => $page->user->name ?? null,
                    'company_id' => $page->company_id ?? null,
                    'company' => $page->company->name ?? null,
                ],
                'periods' => $pagePeriods->map(fn($period) => [
                    'id' => (int) $period->id,
                    'company_id' => $period->company_id !== null ? (int) $period->company_id : null,
                    'started_at' => $period->started_at,
                    'ended_at' => $period->ended_at,
                    'start_source' => $period->start_source,
                    'end_reason' => $period->end_reason,
                ])->values(),
            ];
        })->values();

        return $this->json($response, [
            'data' => $data,
            'companies' => $companies,
            'now' => date('Y-m-d H:i:s'),
        ]);
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
