<?php

namespace App\Controllers;

use App\Models\Lead;
use App\Models\Page;
use App\Models\User;
use App\Services\TrackingService;
use App\Utils\Hostname;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Visite senza cookie e registro dei renderer (fasi 2-3 domini).
 */
class TrackController
{
    /** Giorni considerati per le visite mostrate nelle impostazioni della pagina */
    private const PAGE_VIEWS_DAYS = 30;

    /**
     * POST /api/track/view  { page_id, host? }  (pubblico, beacon dei renderer v2.1)
     * Il beacon invia text/plain per evitare la richiesta preliminare CORS.
     * Risponde sempre 204: il browser non legge la risposta.
     */
    public function view(Request $request, Response $response)
    {
        $data = json_decode((string) $request->getBody(), true);
        if (is_array($data) && !empty($data['page_id']) && is_numeric($data['page_id'])) {
            $host = isset($data['host']) && is_string($data['host']) ? substr($data['host'], 0, 253) : null;
            TrackingService::recordView($request, (int) $data['page_id'], $host);
        }
        return $response->withStatus(204);
    }

    /**
     * GET /api/pages/{id}/renderers
     * Dove viene mostrata la pagina (registro renderer) e visite per sito negli ultimi 30 giorni.
     */
    public function pageRenderers(Request $request, Response $response, array $args)
    {
        $user = $request->getAttribute('user');
        $page = Page::with('domains:id,hostname')->find($args['id']);

        if (!$page) {
            return $this->json($response, ['error' => 'Page not found'], 404);
        }
        if (!$user || !$user->canViewPage($page)) {
            return $this->json($response, ['error' => 'Forbidden'], 403);
        }

        $associated = $page->domains->pluck('hostname')->all();

        $hits = Capsule::table('renderer_hits')
            ->where('page_id', $page->id)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn($hit) => $this->presentHit($hit, $associated));

        $since = date('Y-m-d', strtotime('-' . (self::PAGE_VIEWS_DAYS - 1) . ' days'));
        $views = Capsule::table('page_views_daily')
            ->where('page_id', $page->id)
            ->where('day', '>=', $since)
            ->selectRaw('host, SUM(views) as views, SUM(visitors) as visitors')
            ->groupBy('host')
            ->orderByDesc('views')
            ->get()
            ->map(fn($row) => [
                'host' => $row->host,
                'display_host' => $row->host !== '' ? Hostname::display($row->host) : '',
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
            ]);

        return $this->json($response, ['data' => [
            'hits' => $hits,
            'views' => $views,
            'views_days' => self::PAGE_VIEWS_DAYS,
            'first_view_day' => Capsule::table('page_views_daily')->where('page_id', $page->id)->min('day'),
        ]]);
    }

    /**
     * GET /api/renderers
     * Panoramica dei siti che mostrano le pagine visibili all'utente, con renderer e versione.
     * I renderer non aggiornati (senza sito) sono raggruppati per IP del server.
     */
    public function index(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        $rows = Capsule::table('renderer_hits as h')
            ->join('pages as p', 'p.id', '=', 'h.page_id')
            ->whereNull('p.deleted_at')
            ->where(fn($query) => $this->restrictToVisiblePages($query, $user))
            ->selectRaw("h.host, h.renderer, h.version,
                CASE WHEN h.host = '' THEN h.ip ELSE '' END as ip,
                COUNT(DISTINCT h.page_id) as pages_count,
                GROUP_CONCAT(DISTINCT h.page_id) as page_ids,
                SUM(h.hits) as hits,
                MIN(h.first_seen_at) as first_seen_at,
                MAX(h.last_seen_at) as last_seen_at")
            ->groupBy('h.host', 'h.renderer', 'h.version', Capsule::raw("CASE WHEN h.host = '' THEN h.ip ELSE '' END"))
            ->orderByDesc('last_seen_at')
            ->get();

        $pageIds = $rows->flatMap(fn($row) => explode(',', (string) $row->page_ids))->filter()->unique()->values()->all();
        $pages = Page::with('domains:id,hostname')->whereIn('id', $pageIds)->get(['id', 'title', 'slug'])->keyBy('id');
        $leadHosts = $this->leadHostsByPage($pageIds);

        $rows = $rows
            ->map(fn($row) => [
                'host' => $row->host,
                'display_host' => $row->host !== '' ? Hostname::display($row->host) : '',
                'renderer' => $row->renderer,
                'version' => $row->version,
                'outdated' => TrackingService::isOutdated($row->version),
                'is_app' => Hostname::isAppHost($row->host),
                'ip' => $row->ip !== '' ? $row->ip : null,
                'pages_count' => (int) $row->pages_count,
                'pages' => $this->pagesOf($row->page_ids, $pages),
                // Renderer non aggiornati: il sito si deduce da domini associati e contatti delle pagine
                'likely_hosts' => $row->host === '' ? $this->likelyHosts($row->page_ids, $pages, $leadHosts) : [],
                'hits' => (int) $row->hits,
                'first_seen_at' => $row->first_seen_at,
                'last_seen_at' => $row->last_seen_at,
            ]);

        return $this->json($response, [
            'data' => $rows,
            'current_version' => TrackingService::CURRENT_RENDERER_VERSION,
        ]);
    }

    private function pagesOf(?string $pageIds, $pages): array
    {
        return collect(explode(',', (string) $pageIds))
            ->map(fn($id) => $pages->get((int) $id))
            ->filter()
            ->map(fn($page) => ['id' => $page->id, 'title' => $page->title, 'slug' => $page->slug])
            ->sortBy('title')
            ->values()
            ->all();
    }

    /** Siti probabili: domini associati alle pagine e siti da cui sono arrivati i loro contatti */
    private function likelyHosts(?string $pageIds, $pages, $leadHosts): array
    {
        $hosts = [];
        foreach (explode(',', (string) $pageIds) as $id) {
            $page = $pages->get((int) $id);
            foreach ($page ? $page->domains : [] as $domain) {
                $hosts[$domain->hostname]['domain'] = true;
            }
            foreach ($leadHosts->get((int) $id, []) as $host) {
                $hosts[$host]['leads'] = true;
            }
        }

        return collect($hosts)
            ->map(fn($sources, $host) => [
                'host' => $host,
                'display_host' => Hostname::display($host),
                'from_domain' => !empty($sources['domain']),
                'from_leads' => !empty($sources['leads']),
            ])
            ->sortBy('host')
            ->values()
            ->all();
    }

    /** Siti misurati (non stimati) da cui sono arrivati i contatti, per pagina */
    private function leadHostsByPage(array $pageIds)
    {
        if (!$pageIds) {
            return collect();
        }
        return Lead::whereIn('page_id', $pageIds)
            ->whereNotNull('origin_host')
            ->where('origin_inferred', false)
            ->distinct()
            ->get(['page_id', 'origin_host'])
            ->reject(fn($lead) => Hostname::isAppHost($lead->origin_host))
            ->groupBy('page_id')
            ->map(fn($leads) => $leads->pluck('origin_host')->all());
    }

    private function presentHit(object $hit, array $associated): array
    {
        return [
            'host' => $hit->host,
            'display_host' => $hit->host !== '' ? Hostname::display($hit->host) : '',
            'renderer' => $hit->renderer,
            'version' => $hit->version,
            'outdated' => TrackingService::isOutdated($hit->version),
            'associated' => $hit->host !== '' && in_array($hit->host, $associated, true),
            'is_app' => Hostname::isAppHost($hit->host),
            // L'IP serve solo a riconoscere i renderer non aggiornati
            'ip' => $hit->host === '' ? $hit->ip : null,
            'last_url' => $hit->last_url,
            'hits' => (int) $hit->hits,
            'first_seen_at' => $hit->first_seen_at,
            'last_seen_at' => $hit->last_seen_at,
        ];
    }

    /** Stessa visibilità dell'elenco pagine: admin tutto, responsabile l'azienda, utente le sue */
    private function restrictToVisiblePages($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->isCompany()) {
            return $query->where('p.company_id', $user->company_id);
        }
        return $query->where('p.user_id', $user->id);
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
