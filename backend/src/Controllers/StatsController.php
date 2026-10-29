<?php

namespace App\Controllers;

use App\Services\TrackingService;
use App\Utils\Hostname;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Statistiche aggregate per Grafana (plugin Infinity), protette da StatsKeyMiddleware.
 * Solo numeri e andamenti: nessun dato personale dei lead.
 *
 * Parametri comuni:
 *   from, to   epoch in millisecondi (${__from}, ${__to} di Grafana) oppure AAAA-MM-GG;
 *              predefinito: ultimi 30 giorni. "to" è escluso.
 *   interval   hour, day, week (da lunedì), month; predefinito day
 *   group      none, page, site, company; predefinito none
 *   limit      serie mostrate (le altre diventano "Altre"), 1-50, predefinito 10
 *
 * Le serie temporali sono in formato lungo (time, series, valori), con gli zeri per i
 * periodi senza dati: in Grafana si separano con la trasformazione "Partition by values".
 * I lead arrivati dall'anteprima dell'applicazione non vengono contati.
 *
 * Conversione = lead / visitatori, ma solo con i lead "tracciati": quelli di una pagina e di
 * un sito che contavano già le visite il giorno del lead (renderer 2.1 o successivo). Gli altri
 * lead non hanno visite con cui confrontarli e gonfierebbero la percentuale.
 */
class StatsController
{
    private const INTERVALS = ['hour', 'day', 'week', 'month'];
    private const GROUPS = ['none', 'page', 'site', 'company'];
    private const MAX_POINTS = 20000;
    private const OTHERS = '__others__';
    private const EMPTY = '__empty__';

    private DateTimeZone $tz;

    public function __construct()
    {
        $this->tz = new DateTimeZone(date_default_timezone_get());
    }

    /**
     * GET /api/stats/summary?from&to
     * Un riepilogo per i pannelli "Stat": lead, visite e conversione nel periodo, con il
     * confronto con il periodo precedente di uguale durata, più lo stato attuale dei siti.
     */
    public function summary(Request $request, Response $response)
    {
        try {
            [$from, $to] = $this->range($request->getQueryParams());
        } catch (\InvalidArgumentException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        }

        $previousFrom = $from->sub($from->diff($to));
        $leads = (int) $this->leadsQuery($from, $to)->count();
        $tracked = (int) $this->trackedLeadsQuery($from, $to)->count();
        $previousLeads = (int) $this->leadsQuery($previousFrom, $from)->count();
        $views = $this->viewsQuery($from, $to)
            ->selectRaw('COALESCE(SUM(v.views), 0) as views, COALESCE(SUM(v.visitors), 0) as visitors')
            ->first();

        // Siti e renderer visti nel periodo, esclusi anteprima e richieste automatiche
        $hits = Capsule::table('renderer_hits as h')
            ->join('pages as p', 'p.id', '=', 'h.page_id')
            ->whereNull('p.deleted_at')
            ->where('h.last_seen_at', '>=', $this->sql($from))
            ->where('h.last_seen_at', '<', $this->sql($to))
            ->get(['h.host', 'h.version', 'h.ip', 'h.user_agent'])
            ->reject(fn($hit) => Hostname::isAppHost($hit->host)
                || TrackingService::isAutomated($hit->version, $hit->ip, $hit->user_agent));

        return $this->json($response, [
            'from' => $from->format(DATE_ATOM),
            'to' => $to->format(DATE_ATOM),
            'leads' => $leads,
            'leads_previous' => $previousLeads,
            'leads_change_pct' => $previousLeads > 0 ? round(($leads - $previousLeads) / $previousLeads * 100, 1) : null,
            'views' => (int) $views->views,
            'visitors' => (int) $views->visitors,
            'leads_tracked' => $tracked,
            'conversion_pct' => self::rate($tracked, (int) $views->visitors),
            'published_pages' => Capsule::table('pages')->whereNull('deleted_at')->where('is_published', 1)->count(),
            'active_sites' => $hits->where('host', '!=', '')->pluck('host')->unique()->count(),
            'outdated_renderers' => $hits->filter(fn($hit) => TrackingService::isOutdated($hit->version))->count(),
        ]);
    }

    /**
     * GET /api/stats/leads?from&to&interval&group&limit
     * Lead per periodo: [{time, series, series_id, leads}]
     */
    public function leads(Request $request, Response $response)
    {
        try {
            $params = $request->getQueryParams();
            [$from, $to] = $this->range($params);
            $interval = $this->choice($params, 'interval', self::INTERVALS, 'day');
            $group = $this->choice($params, 'group', self::GROUPS, 'none');
        } catch (\InvalidArgumentException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        }

        $rows = $this->leadsQuery($from, $to)
            ->selectRaw($this->bucketSql('l.created_at', $interval) . ' as bucket')
            ->selectRaw($this->leadSeriesSql($group) . ' as series_id')
            ->selectRaw('COUNT(*) as leads')
            ->groupBy('bucket', 'series_id')
            ->get();

        return $this->timeSeries($response, $rows, ['leads'], $from, $to, $interval, $group, $this->limit($params));
    }

    /**
     * GET /api/stats/views?from&to&interval&group&limit
     * Visite e visitatori per periodo (dai conteggi giornalieri, quindi niente "hour"):
     * [{time, series, series_id, views, visitors}]. "visitors" è la somma dei visitatori
     * unici di ogni giorno.
     */
    public function views(Request $request, Response $response)
    {
        try {
            $params = $request->getQueryParams();
            [$from, $to] = $this->range($params);
            $interval = $this->choice($params, 'interval', ['day', 'week', 'month'], 'day');
            $group = $this->choice($params, 'group', self::GROUPS, 'none');
        } catch (\InvalidArgumentException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        }

        $rows = $this->viewsQuery($from, $to)
            ->selectRaw($this->bucketSql('v.day', $interval) . ' as bucket')
            ->selectRaw($this->viewSeriesSql($group) . ' as series_id')
            ->selectRaw('SUM(v.views) as views, SUM(v.visitors) as visitors')
            ->groupBy('bucket', 'series_id')
            ->get();

        return $this->timeSeries($response, $rows, ['views', 'visitors'], $from, $to, $interval, $group, $this->limit($params));
    }

    /**
     * GET /api/stats/conversion?from&to&group
     * Tabella per pagina, sito o azienda: [{series, series_id, leads, leads_tracked, views, visitors,
     * conversion_pct}], dalla più alta per lead. Conversione = lead tracciati / visitatori
     * (null senza visitatori).
     * Per sito i renderer precedenti alla 2.1 non comunicano il sito delle visite ("Sito sconosciuto").
     */
    public function conversion(Request $request, Response $response)
    {
        try {
            $params = $request->getQueryParams();
            [$from, $to] = $this->range($params);
            $group = $this->choice($params, 'group', ['page', 'site', 'company'], 'page');
        } catch (\InvalidArgumentException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        }

        $leads = $this->leadsQuery($from, $to)
            ->selectRaw($this->leadSeriesSql($group) . ' as series_id, COUNT(*) as leads')
            ->groupBy('series_id')
            ->pluck('leads', 'series_id');
        $tracked = $this->trackedLeadsQuery($from, $to)
            ->selectRaw($this->leadSeriesSql($group) . ' as series_id, COUNT(*) as leads')
            ->groupBy('series_id')
            ->pluck('leads', 'series_id');
        $views = $this->viewsQuery($from, $to)
            ->selectRaw($this->viewSeriesSql($group) . ' as series_id, SUM(v.views) as views, SUM(v.visitors) as visitors')
            ->groupBy('series_id')
            ->get()
            ->keyBy('series_id');

        $ids = collect($leads->keys())->merge($views->keys())->map(fn($id) => (string) $id)->unique()->values();
        $labels = $this->labels($group, $ids->all());

        $rows = $ids->map(function ($id) use ($leads, $tracked, $views, $labels) {
            $visitors = (int) ($views[$id]->visitors ?? 0);
            return [
                'series' => $labels[$id],
                'series_id' => $id,
                'leads' => (int) ($leads[$id] ?? 0),
                'leads_tracked' => (int) ($tracked[$id] ?? 0),
                'views' => (int) ($views[$id]->views ?? 0),
                'visitors' => $visitors,
                'conversion_pct' => self::rate((int) ($tracked[$id] ?? 0), $visitors),
            ];
        })
            ->sortBy([['leads', 'desc'], ['visitors', 'desc'], ['series', 'asc']])
            ->values();

        return $this->json($response, $rows);
    }

    // --- Query ---------------------------------------------------------------

    /** Lead del periodo (anche di pagine archiviate o eliminate), senza quelli dall'anteprima */
    private function leadsQuery(DateTimeImmutable $from, DateTimeImmutable $to)
    {
        $query = Capsule::table('leads as l')
            ->leftJoin('pages as p', 'p.id', '=', 'l.page_id')
            ->where('l.created_at', '>=', $this->sql($from))
            ->where('l.created_at', '<', $this->sql($to));

        $appHost = Hostname::normalize($_ENV['APP_URL'] ?? '');
        if ($appHost) {
            $query->where(fn($q) => $q->whereNull('l.origin_host')->orWhere('l.origin_host', '!=', $appHost));
        }
        return $query;
    }

    /**
     * Lead tracciati: pagina e sito contavano le visite dal giorno del lead o da prima.
     * Il primo giorno è cercato su tutto lo storico delle visite (16 mesi), non solo nel periodo.
     */
    private function trackedLeadsQuery(DateTimeImmutable $from, DateTimeImmutable $to)
    {
        $firstDays = Capsule::table('page_views_daily')
            ->selectRaw('page_id, host, MIN(day) as first_day')
            ->groupBy('page_id', 'host');

        return $this->leadsQuery($from, $to)
            ->joinSub($firstDays, 't', fn($join) => $join->on('t.page_id', '=', 'l.page_id')->on('t.host', '=', 'l.origin_host'))
            ->whereRaw('DATE(l.created_at) >= t.first_day');
    }

    /** Visite giornaliere dei giorni compresi nel periodo */
    private function viewsQuery(DateTimeImmutable $from, DateTimeImmutable $to)
    {
        // "to" a mezzanotte esclude quel giorno, a metà giornata lo comprende
        $lastDay = $to->format('H:i:s') === '00:00:00' ? $to : $to->modify('+1 day');
        return Capsule::table('page_views_daily as v')
            ->leftJoin('pages as p', 'p.id', '=', 'v.page_id')
            ->where('v.day', '>=', $from->format('Y-m-d'))
            ->where('v.day', '<', $lastDay->format('Y-m-d'));
    }

    /** Inizio del periodo (ora italiana, come le date del database) */
    private function bucketSql(string $column, string $interval): string
    {
        return match ($interval) {
            'hour' => "DATE_FORMAT($column, '%Y-%m-%d %H:00:00')",
            'day' => "DATE_FORMAT($column, '%Y-%m-%d 00:00:00')",
            'week' => "DATE_FORMAT(DATE_SUB(DATE($column), INTERVAL WEEKDAY($column) DAY), '%Y-%m-%d 00:00:00')",
            'month' => "DATE_FORMAT($column, '%Y-%m-01 00:00:00')",
        };
    }

    private function leadSeriesSql(string $group): string
    {
        return match ($group) {
            'none' => "'all'",
            'page' => "COALESCE(l.page_id, '')",
            'site' => "COALESCE(l.origin_host, '')",
            'company' => "COALESCE(p.company_id, '')",
        };
    }

    private function viewSeriesSql(string $group): string
    {
        return match ($group) {
            'none' => "'all'",
            'page' => 'v.page_id',
            'site' => 'v.host',
            'company' => "COALESCE(p.company_id, '')",
        };
    }

    // --- Serie temporali -----------------------------------------------------

    /**
     * Formato lungo con gli zeri: le prime $limit serie per totale, le altre sommate in "Altre".
     *
     * @param string[] $fields colonne dei valori
     */
    private function timeSeries(Response $response, $rows, array $fields, DateTimeImmutable $from, DateTimeImmutable $to, string $interval, string $group, int $limit): Response
    {
        $first = $fields[0];
        $totals = [];
        foreach ($rows as $row) {
            $totals[(string) $row->series_id] = ($totals[(string) $row->series_id] ?? 0) + (int) $row->$first;
        }
        arsort($totals);
        // Le chiavi numeriche degli array PHP diventano interi: le serie restano stringhe
        $kept = array_map('strval', array_slice(array_keys($totals), 0, $limit));
        $hasOthers = count($totals) > $limit;

        // Nessun dato: una sola serie a zero, così il grafico mostra comunque l'asse del tempo
        $series = $kept ?: [$group === 'none' ? 'all' : self::EMPTY];
        if ($hasOthers) {
            $series[] = self::OTHERS;
        }

        $buckets = $this->buckets($from, $to, $interval);
        if (count($buckets) * count($series) > self::MAX_POINTS) {
            return $this->json($response, ['error' => 'Troppi punti: scegliere un intervallo più ampio o un periodo più breve'], 400);
        }

        $values = [];
        foreach ($rows as $row) {
            $id = in_array((string) $row->series_id, $kept, true) ? (string) $row->series_id : self::OTHERS;
            foreach ($fields as $field) {
                $values[$row->bucket][$id][$field] = ($values[$row->bucket][$id][$field] ?? 0) + (int) $row->$field;
            }
        }

        $labels = $this->labels($group, array_diff($series, [self::OTHERS, self::EMPTY]));
        $labels[self::OTHERS] = 'Altre';
        $labels[self::EMPTY] = 'Nessun dato';

        $result = [];
        foreach ($buckets as $key => $time) {
            foreach ($series as $id) {
                $point = ['time' => $time, 'series' => $labels[$id], 'series_id' => in_array($id, [self::OTHERS, self::EMPTY], true) ? null : $id];
                foreach ($fields as $field) {
                    $point[$field] = $values[$key][$id][$field] ?? 0;
                }
                $result[] = $point;
            }
        }

        return $this->json($response, $result);
    }

    /** Inizi dei periodi nell'intervallo: ['2026-10-27 00:00:00' => '2026-10-27T00:00:00+01:00', …] */
    private function buckets(DateTimeImmutable $from, DateTimeImmutable $to, string $interval): array
    {
        $cursor = match ($interval) {
            'hour' => $from->setTime((int) $from->format('H'), 0),
            'day' => $from->setTime(0, 0),
            'week' => $from->setTime(0, 0)->modify('-' . ((int) $from->format('N') - 1) . ' days'),
            'month' => $from->modify('first day of this month')->setTime(0, 0),
        };
        $step = ['hour' => '+1 hour', 'day' => '+1 day', 'week' => '+1 week', 'month' => '+1 month'][$interval];

        $buckets = [];
        while ($cursor < $to && count($buckets) <= self::MAX_POINTS) {
            $buckets[$cursor->format('Y-m-d H:i:s')] = $cursor->format(DATE_ATOM);
            $cursor = $cursor->modify($step);
        }
        return $buckets;
    }

    /** Nomi leggibili e univoci delle serie */
    private function labels(string $group, array $ids): array
    {
        $ids = array_map('strval', array_values($ids));
        $labels = [];

        if ($group === 'none') {
            return array_fill_keys($ids, 'Totale');
        }
        if ($group === 'site') {
            foreach ($ids as $id) {
                $labels[$id] = $id === '' ? 'Sito sconosciuto' : Hostname::display($id);
            }
            return $labels;
        }

        $numeric = array_filter($ids, fn($id) => $id !== '');
        if ($group === 'page') {
            $pages = Capsule::table('pages')->whereIn('id', $numeric)->get(['id', 'title', 'deleted_at'])->keyBy('id');
            foreach ($ids as $id) {
                $page = $pages[$id] ?? null;
                // Con l'id: due pagine con lo stesso titolo restano serie separate in Grafana
                $labels[$id] = $page
                    ? $page->title . ' (#' . $id . ')' . ($page->deleted_at ? ' – archiviata' : '')
                    : 'Pagina eliminata' . ($id !== '' ? ' (#' . $id . ')' : '');
            }
            return $labels;
        }

        $companies = Capsule::table('companies')->whereIn('id', $numeric)->pluck('name', 'id');
        foreach ($ids as $id) {
            $labels[$id] = $id === '' ? 'Senza azienda' : ($companies[$id] ?? 'Azienda #' . $id);
        }
        return $labels;
    }

    // --- Parametri -----------------------------------------------------------

    /** @return DateTimeImmutable[] [from, to] */
    private function range(array $params): array
    {
        $to = isset($params['to']) && $params['to'] !== '' ? $this->parseTime($params['to'], 'to') : new DateTimeImmutable('now', $this->tz);
        $from = isset($params['from']) && $params['from'] !== '' ? $this->parseTime($params['from'], 'from') : $to->modify('-30 days');

        if ($from >= $to) {
            throw new \InvalidArgumentException('from deve essere precedente a to');
        }
        if ($from < $to->modify('-5 years')) {
            throw new \InvalidArgumentException('Periodo troppo lungo: al massimo 5 anni');
        }
        return [$from, $to];
    }

    private function parseTime($value, string $name): DateTimeImmutable
    {
        $value = (string) $value;
        if (ctype_digit($value)) {
            // Epoch in millisecondi (Grafana) o in secondi
            $seconds = strlen($value) > 11 ? intdiv((int) $value, 1000) : (int) $value;
            return (new DateTimeImmutable('@' . $seconds))->setTimezone($this->tz);
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->tz);
        if ($date && $date->format('Y-m-d') === $value) {
            return $date;
        }
        throw new \InvalidArgumentException("$name deve essere un epoch in millisecondi o una data AAAA-MM-GG");
    }

    private function choice(array $params, string $name, array $allowed, string $default): string
    {
        $value = $params[$name] ?? '';
        if ($value === '') {
            return $default;
        }
        if (!in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException("$name deve essere uno tra: " . implode(', ', $allowed));
        }
        return $value;
    }

    private function limit(array $params): int
    {
        return max(1, min(50, (int) ($params['limit'] ?? 10) ?: 10));
    }

    private function sql(DateTimeImmutable $time): string
    {
        return $time->setTimezone($this->tz)->format('Y-m-d H:i:s');
    }

    private static function rate(int $leads, int $visitors): ?float
    {
        return $visitors > 0 ? round($leads / $visitors * 100, 2) : null;
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
