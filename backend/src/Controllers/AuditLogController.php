<?php

namespace App\Controllers;

use App\Audit\AuditCatalog;
use App\Models\AuditLog;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Stream;

/**
 * Consultazione del registro attività (solo admin): elenco con filtri, diretta ed export CSV.
 * Le date sono in ora italiana, come nel database.
 */
class AuditLogController
{
    private const PER_PAGE = 50;
    /** Diretta: voci massime per risposta (oltre, la pagina si ricarica) */
    private const LIVE_LIMIT = 200;
    /** Export: voci lette per volta */
    private const EXPORT_CHUNK = 500;

    /**
     * GET /api/admin/audit-logs?pagina=1&<filtri>
     */
    public function index(Request $request, Response $response)
    {
        if ($denied = $this->denyUnlessAdmin($request, $response)) {
            return $denied;
        }

        $params = $request->getQueryParams();
        $filters = $this->filters($params);
        $page = max(1, (int) ($params['pagina'] ?? 1));

        $query = $this->query($filters);
        $total = (clone $query)->count();
        $logs = $query->orderByDesc('created_at')->orderByDesc('id')
            ->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE)->get();

        return $this->json($response, [
            'data' => $this->present($logs),
            'meta' => [
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / self::PER_PAGE)),
                'last_id' => (int) AuditLog::max('id'),
            ],
            'filters' => $filters,
            'labels' => AuditCatalog::labels(),
        ]);
    }

    /**
     * GET /api/admin/audit-logs/new?dopo=<id>&<filtri>
     * Voci successive a <id> (diretta). Non finisce nel registro (RecordActivity::SKIP_ROUTES).
     */
    public function newer(Request $request, Response $response)
    {
        if ($denied = $this->denyUnlessAdmin($request, $response)) {
            return $denied;
        }

        $params = $request->getQueryParams();
        $after = max(0, (int) ($params['dopo'] ?? 0));

        $logs = $this->query($this->filters($params))
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(self::LIVE_LIMIT + 1)
            ->get();
        $truncated = $logs->count() > self::LIVE_LIMIT;

        return $this->json($response, [
            'data' => $this->present($logs->take(self::LIVE_LIMIT)->reverse()->values()),
            'last_id' => max($after, (int) AuditLog::max('id')),
            'truncated' => $truncated,
        ]);
    }

    /**
     * GET /api/admin/audit-logs/export?<filtri>
     * CSV per Excel: BOM UTF-8, separatore ";", date in ora italiana.
     */
    public function export(Request $request, Response $response)
    {
        if ($denied = $this->denyUnlessAdmin($request, $response)) {
            return $denied;
        }

        $filters = $this->filters($request->getQueryParams());
        // Su disco oltre 5 MB: l'export non tiene tutto il registro in memoria
        $handle = fopen('php://temp/maxmemory:5242880', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'Data', 'Categoria', 'Descrizione', 'Utente', 'Email', 'Ruolo', 'Azienda', 'Pagina', 'Record',
            'Canale', 'Metodo', 'URL', 'Status', 'Esito', 'Durata (ms)', 'IP', 'Richiesta', 'Dettagli',
        ], ';', '"', '');

        // Dalla più recente, a blocchi: id decrescenti senza OFFSET
        $lastId = null;
        do {
            $query = $this->query($filters)->orderByDesc('id')->limit(self::EXPORT_CHUNK);
            if ($lastId !== null) {
                $query->where('id', '<', $lastId);
            }
            $logs = $query->get();
            foreach ($this->present($logs) as $row) {
                fputcsv($handle, array_map([self::class, 'csvCell'], [
                    $row['created_at'],
                    $row['category_label'],
                    $row['description'],
                    $row['user']['name'] ?? '',
                    $row['user']['email'] ?? '',
                    $row['user']['role_label'] ?? '',
                    $row['company']['name'] ?? '',
                    $row['page']['title'] ?? '',
                    trim(($row['subject']['type_label'] ?? '') . ' ' . ($row['subject']['label'] ?? (isset($row['subject']['id']) ? '#' . $row['subject']['id'] : ''))),
                    $row['channel_label'],
                    $row['method'],
                    $row['url'],
                    $row['status'],
                    $row['outcome_label'],
                    $row['duration_ms'],
                    $row['ip'],
                    $row['request_id'],
                    $row['properties'] ? json_encode($row['properties'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                ]), ';', '"', '');
            }
            $lastId = $logs->last()?->id;
        } while ($logs->count() === self::EXPORT_CHUNK);

        rewind($handle);
        return $response
            ->withBody(new Stream($handle))
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="registro-attivita-' . date('Y-m-d-His') . '.csv"');
    }

    /**
     * Filtri dalla query string, validati uno per uno: quelli non validi si ignorano.
     */
    private function filters(array $params): array
    {
        $filters = [];

        foreach (['azienda' => 'company_id', 'pagina_id' => 'page_id'] as $param => $column) {
            if (isset($params[$param]) && ctype_digit((string) $params[$param]) && (int) $params[$param] > 0) {
                $filters[$column] = (int) $params[$param];
            }
        }
        if (isset($params['utente']) && is_string($params['utente'])) {
            $text = trim($params['utente']);
            if ($text !== '' && mb_strlen($text) <= 100) {
                $filters['user'] = $text;
            }
        }
        if (isset($params['categoria']) && isset(AuditCatalog::CATEGORIES[$params['categoria']])) {
            $filters['category'] = $params['categoria'];
        }
        if (isset($params['esito']) && isset(AuditCatalog::OUTCOMES[$params['esito']])) {
            $filters['outcome'] = $params['esito'];
        }
        foreach (['dal' => 'from', 'al' => 'to'] as $param => $key) {
            if (isset($params[$param]) && is_string($params[$param]) && self::isDay($params[$param])) {
                $filters[$key] = $params[$param];
            }
        }
        if (isset($params['richiesta']) && is_string($params['richiesta']) && preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $params['richiesta'])) {
            $filters['request_id'] = $params['richiesta'];
        }
        if (isset($params['ip']) && is_string($params['ip']) && filter_var($params['ip'], FILTER_VALIDATE_IP)) {
            $filters['ip'] = $params['ip'];
        }
        if (!empty($params['senza_consultazioni']) && $params['senza_consultazioni'] !== '0') {
            $filters['without_views'] = true;
        }

        return $filters;
    }

    private function query(array $filters): Builder
    {
        $query = AuditLog::query();

        foreach (['company_id', 'page_id', 'category', 'outcome', 'request_id', 'ip'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }
        if (isset($filters['user'])) {
            $like = '%' . addcslashes($filters['user'], '%_\\') . '%';
            $query->where(fn($q) => $q->where('user_name', 'like', $like)
                ->orWhere('user_email', 'like', $like)
                ->orWhere('subject_label', 'like', $like));
        }
        // Giorni in ora italiana, come le date salvate
        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from'] . ' 00:00:00');
        }
        if (isset($filters['to'])) {
            $query->where('created_at', '<', date('Y-m-d', strtotime($filters['to'] . ' +1 day')) . ' 00:00:00');
        }
        if (!empty($filters['without_views'])) {
            $query->where('category', '!=', 'view');
        }

        return $query;
    }

    /** Righe per il frontend: descrizione dal catalogo e nomi di aziende e pagine */
    private function present($logs): array
    {
        $companyIds = $logs->pluck('company_id')->filter()->unique()->values()->all();
        $pageIds = $logs->pluck('page_id')->filter()->unique()->values()->all();
        // Anche aziende eliminate e pagine archiviate: le voci non hanno foreign key
        $companies = $companyIds ? Capsule::table('companies')->whereIn('id', $companyIds)->pluck('name', 'id') : collect();
        $pages = $pageIds ? Capsule::table('pages')->whereIn('id', $pageIds)->get(['id', 'title', 'deleted_at'])->keyBy('id') : collect();

        return $logs->map(function (AuditLog $log) use ($companies, $pages) {
            $page = $log->page_id ? $pages->get($log->page_id) : null;
            return [
                'id' => $log->id,
                'created_at' => $log->toArray()['created_at'],
                'request_id' => $log->request_id,
                'channel' => $log->channel,
                'channel_label' => AuditCatalog::channel($log->channel),
                'category' => $log->category,
                'category_label' => AuditCatalog::category($log->category),
                'action' => $log->action,
                'description' => AuditCatalog::describe($log),
                'user' => $log->user_id || $log->user_email || $log->user_name ? [
                    'id' => $log->user_id,
                    'name' => $log->user_name,
                    'email' => $log->user_email,
                    'role' => $log->user_role,
                    'role_label' => AuditCatalog::role($log->user_role),
                ] : null,
                'company' => $log->company_id ? [
                    'id' => $log->company_id,
                    'name' => $companies[$log->company_id] ?? "Azienda #{$log->company_id}",
                    'deleted' => !$companies->has($log->company_id),
                ] : null,
                'page' => $log->page_id ? [
                    'id' => $log->page_id,
                    'title' => $page->title ?? "Pagina #{$log->page_id}",
                    'archived' => $page && $page->deleted_at !== null,
                    'deleted' => $page === null,
                ] : null,
                'subject' => $log->subject_type ? [
                    'type' => $log->subject_type,
                    'type_label' => AuditCatalog::subject($log->subject_type),
                    'id' => $log->subject_id,
                    'label' => $log->subject_label,
                ] : null,
                'method' => $log->method,
                'url' => $log->url,
                'status' => $log->status,
                'outcome' => $log->outcome,
                'outcome_label' => AuditCatalog::outcome($log->outcome),
                'duration_ms' => $log->duration_ms,
                'ip' => $log->ip,
                'user_agent' => $log->user_agent,
                'properties' => $log->properties,
            ];
        })->values()->all();
    }

    /** Celle che Excel leggerebbe come formula (=, +, -, @): precedute da un apostrofo */
    private static function csvCell($value)
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }
        return $value;
    }

    private static function isDay(string $value): bool
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function denyUnlessAdmin(Request $request, Response $response): ?Response
    {
        $user = $request->getAttribute('user');
        return $user && $user->isAdmin() ? null : $this->json($response, ['error' => 'Forbidden'], 403);
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
