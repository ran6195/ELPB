<?php

namespace App\Middleware;

use App\Audit\Auditor;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

/**
 * Registra ogni richiesta all'API: chi, che cosa (pattern della rotta), su quale record,
 * con quale esito e in quanto tempo. La voce va nel buffer di Auditor, scritto da
 * public/index.php dopo l'invio della risposta.
 *
 * È il middleware più esterno dopo CORS (vede anche gli errori trasformati in risposta
 * da ErrorMiddleware); la rotta gliela passa captureRoute(), che gira dopo il routing.
 */
class RecordActivity implements MiddlewareInterface
{
    /** Rotte tecniche o troppo frequenti: non si registrano */
    private const SKIP_ROUTES = [
        'POST /api/track/view',          // beacon delle visite (pubblico, a ogni visualizzazione)
        'POST /api/pages/check-slug',    // controllo dello slug mentre si scrive
        'GET /api/media/{id}/file',      // file per l'editor immagini
        'GET /api/admin/audit-logs/new', // diretta del registro stesso
        'GET /api/renderers/new',        // diretta della scheda Renderer (ogni 4 secondi)
        'GET /api/stats/leads',          // grafici della scheda Statistiche: basta la voce di
        'GET /api/stats/views',          // GET /api/stats/summary, una per apertura o periodo
        'GET /api/stats/conversion',
    ];

    /** Input non registrato: dati personali dei visitatori o contenuto completo della pagina */
    private const NO_INPUT_ROUTES = [
        'POST /api/leads',
        'POST /api/leads/exports',       // elenco degli id: il riepilogo è nella voce lead.exported
        'PUT /api/pages/{id}',
        'POST /api/pages/import',
    ];

    /** Salvataggi dell'editor (autosalvataggio): una voce ogni 10 minuti per utente e pagina */
    public const PAGE_SAVE_ROUTE = 'PUT /api/pages/{id}';

    /** Record coinvolto dedotto dal prefisso della rotta ({id}) */
    private const SUBJECT_ROUTES = [
        '/api/company/pages/{id}' => 'page',
        '/api/pages/{id}' => 'page',
        '/api/media-folders/{id}' => 'media_folder',
        '/api/media/{id}' => 'media',
        '/api/domains/{id}' => 'domain',
        '/api/leads/{id}' => 'lead',
        '/api/admin/users/{id}' => 'user',
        '/api/company/users/{id}' => 'user',
        '/api/admin/companies/{id}' => 'company',
    ];

    /** Corpi più grandi non vengono letti (es. import di pagine) */
    private const MAX_BODY = 65536;

    private static ?array $route = null;

    /** Middleware interno (dopo il routing): passa pattern e parametri della rotta */
    public static function captureRoute(Request $request, RequestHandler $handler): Response
    {
        $route = RouteContext::fromRequest($request)->getRoute();
        if ($route) {
            self::$route = ['pattern' => $route->getPattern(), 'args' => $route->getArguments()];
        }
        return $handler->handle($request);
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $start = microtime(true);
        self::$route = null;
        $auditor = Auditor::get();
        $server = $request->getServerParams();
        $auditor->beginRequest('api', $server['REMOTE_ADDR'] ?? null, $request->getHeaderLine('User-Agent'));
        $auditor->setUser(null);

        $method = strtoupper($request->getMethod());
        $blocksBefore = $this->blockTypesBefore($request, $method);

        $response = $handler->handle($request);

        try {
            $this->record($auditor, $request, $response, $method, $server['REQUEST_TIME_FLOAT'] ?? $start, $blocksBefore);
        } catch (\Throwable $e) {
            error_log('RecordActivity: richiesta non registrata: ' . $e->getMessage());
        }

        return $response;
    }

    private function record(Auditor $auditor, Request $request, Response $response, string $method, float $start, ?array $blocksBefore): void
    {
        if ($method === 'HEAD' || $method === 'OPTIONS' || !self::$route) {
            return;
        }

        $action = $method . ' ' . self::$route['pattern'];
        $user = $auditor->user();
        if (in_array($action, self::SKIP_ROUTES, true) || ($method === 'GET' && !$user)) {
            return; // le GET anonime sono le pagine servite ai renderer
        }

        $status = $response->getStatusCode();
        $args = self::$route['args'];
        $properties = [];
        if ($args) {
            $properties['params'] = Auditor::clean($args);
        }

        if ($method !== 'GET') {
            if (in_array($action, self::NO_INPUT_ROUTES, true)) {
                $properties += $this->summary($request, $action, $blocksBefore);
            } else {
                $input = $this->input($request);
                if ($input !== null) {
                    $properties['input'] = $input;
                }
            }
        }

        $outcome = self::outcome($status);
        if ($status >= 400) {
            $error = $this->errorMessage($response);
            if ($error !== null) {
                $properties['error'] = $error;
            }
        }

        $data = [
            'method' => $method,
            'url' => (string) $request->getUri(),
            'status' => $status,
            'outcome' => $outcome,
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            'properties' => $properties,
            'at' => $start,
            'resolve_subject' => true,
        ];

        $subjectType = $this->subjectType(self::$route['pattern']);
        if ($subjectType && isset($args['id']) && ctype_digit((string) $args['id'])) {
            $data['subject_type'] = $subjectType;
            $data['subject_id'] = (int) $args['id'];
        }
        if ($action === self::PAGE_SAVE_ROUTE) {
            $data['coalesce'] = true;
        }

        if (isset($properties['lead_page_id'])) {
            $data['page_id'] = $properties['lead_page_id'];
        }

        $auditor->record($method === 'GET' ? Auditor::VIEW : Auditor::ACTION, $action, $data);
    }

    /** Esito dallo status HTTP (codice stabile, il testo è nel catalogo) */
    public static function outcome(int $status): string
    {
        return match (true) {
            $status >= 500 => 'error',
            $status === 401, $status === 403 => 'denied',
            $status === 404 => 'not_found',
            $status === 400, $status === 422 => 'invalid',
            $status === 429 => 'throttled',
            $status >= 400 => 'error',
            default => 'ok',
        };
    }

    private function subjectType(string $pattern): ?string
    {
        foreach (self::SUBJECT_ROUTES as $prefix => $type) {
            if (str_starts_with($pattern, $prefix)) {
                return $type;
            }
        }
        return null;
    }

    /** Input senza segreti; file caricati solo per nome */
    private function input(Request $request): ?array
    {
        $input = $request->getParsedBody();
        if (!is_array($input)) {
            $body = $this->body($request);
            if (is_string($body) && $body !== '') {
                $decoded = json_decode($body, true);
                $input = is_array($decoded) ? $decoded : null;
            } elseif (is_int($body)) {
                $input = ['_corpo' => "[$body byte, non registrato]"];
            }
        }

        $files = array_map(
            fn($file) => is_array($file) ? array_map(fn($f) => $f->getClientFilename(), $file) : $file->getClientFilename(),
            $request->getUploadedFiles()
        );

        $input = array_merge($input ?? [], $files);
        unset($input['_token'], $input['_method']);
        return $input ? Auditor::clean($input) : null;
    }

    /** Corpo della richiesta come testo, o la sua dimensione se troppo grande */
    private function body(Request $request)
    {
        $stream = $request->getBody();
        $size = $stream->getSize();
        if ($size !== null && $size > self::MAX_BODY) {
            return $size;
        }
        return (string) $stream; // __toString riparte dall'inizio dello stream
    }

    /** Riepilogo al posto dell'input per le rotte senza input registrato */
    private function summary(Request $request, string $action, ?array $blocksBefore): array
    {
        $body = $this->body($request);
        if (is_int($body)) {
            return ['_corpo' => "[$body byte, non registrato]"];
        }
        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            return [];
        }

        if ($action === 'POST /api/leads') {
            return isset($data['page_id']) && is_numeric($data['page_id']) ? ['lead_page_id' => (int) $data['page_id']] : [];
        }
        if ($action === 'POST /api/pages/import') {
            return ['import' => ['title' => mb_substr((string) ($data['page']['title'] ?? $data['title'] ?? ''), 0, 200), 'blocks' => count($data['blocks'] ?? $data['page']['blocks'] ?? [])]];
        }

        // Salvataggio della pagina: blocchi aggiunti e tolti per tipo
        if (!isset($data['blocks']) || !is_array($data['blocks'])) {
            return [];
        }
        $after = array_count_values(array_filter(array_map(fn($b) => is_array($b) ? ($b['type'] ?? null) : null, $data['blocks']), 'is_string'));
        $summary = ['blocks' => ['count' => array_sum($after)]];
        if ($blocksBefore !== null) {
            $added = self::diffCounts($after, $blocksBefore);
            $removed = self::diffCounts($blocksBefore, $after);
            if ($added) {
                $summary['blocks']['added'] = $added;
            }
            if ($removed) {
                $summary['blocks']['removed'] = $removed;
            }
        }
        return $summary;
    }

    /** Tipi di blocco della pagina prima del salvataggio (solo per il salvataggio dell'editor) */
    private function blockTypesBefore(Request $request, string $method): ?array
    {
        if ($method !== 'PUT' || !preg_match('#/api/pages/(\d+)$#', $request->getUri()->getPath(), $m)) {
            return null;
        }
        try {
            return array_count_values(Capsule::table('blocks')->where('page_id', (int) $m[1])->pluck('type')->all());
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function diffCounts(array $a, array $b): array
    {
        $diff = [];
        foreach ($a as $type => $count) {
            $delta = $count - ($b[$type] ?? 0);
            if ($delta > 0) {
                $diff[$type] = $delta;
            }
        }
        ksort($diff);
        return $diff;
    }

    /** Messaggio di errore della risposta JSON (message/error), breve */
    private function errorMessage(Response $response): ?string
    {
        $body = $response->getBody();
        if ($body->getSize() !== null && $body->getSize() > self::MAX_BODY) {
            return null;
        }
        $data = json_decode((string) $body, true);
        $message = is_array($data) ? ($data['message'] ?? $data['error'] ?? null) : null;
        return is_string($message) ? mb_substr($message, 0, 300) : null;
    }
}
