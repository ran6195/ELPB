<?php

namespace Tests\Feature;

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

/**
 * Statistiche per Grafana: chiave in sola lettura, serie temporali con gli zeri, conversione.
 */
class StatsTest extends TestCase
{
    private const KEY = 'chiave-di-prova-delle-statistiche-0123456789';
    private ?string $previousKey;
    private ?string $previousAppUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousKey = $_ENV['STATS_API_KEY'] ?? null;
        $_ENV['STATS_API_KEY'] = self::KEY;
        // Indirizzo dell'applicazione: i lead arrivati da qui sono prove dall'anteprima
        $this->previousAppUrl = $_ENV['APP_URL'] ?? null;
        $_ENV['APP_URL'] = 'https://elpb.example.test';
    }

    protected function tearDown(): void
    {
        $_ENV['STATS_API_KEY'] = $this->previousKey;
        $_ENV['APP_URL'] = $this->previousAppUrl;
        parent::tearDown();
    }

    private function stats(string $uri): ResponseInterface
    {
        return $this->request('GET', $uri, null, ['Authorization' => 'Bearer ' . self::KEY]);
    }

    private function lead(?int $pageId, string $at, ?string $host = null): void
    {
        Capsule::table('leads')->insert([
            'page_id' => $pageId, 'email' => uniqid() . '@example.test', 'origin_host' => $host,
            'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    private function views(int $pageId, string $host, string $day, int $views, int $visitors): void
    {
        Capsule::table('page_views_daily')->insert(['page_id' => $pageId, 'host' => $host, 'day' => $day, 'views' => $views, 'visitors' => $visitors]);
    }

    public function test_accesso_solo_con_la_chiave(): void
    {
        $this->assertSame(401, $this->request('GET', '/api/stats/summary')->getStatusCode());
        $this->assertSame(401, $this->request('GET', '/api/stats/summary', null, ['Authorization' => 'Bearer sbagliata'])->getStatusCode());
        // Un token di un utente non apre le statistiche
        $this->assertSame(401, $this->request('GET', '/api/stats/summary', null, [], $this->createUser(['role' => 'admin']))->getStatusCode());
        $this->assertSame(200, $this->request('GET', '/api/stats/summary', null, ['X-Stats-Key' => self::KEY])->getStatusCode());
        $this->assertSame(200, $this->stats('/api/stats/summary')->getStatusCode());

        $_ENV['STATS_API_KEY'] = 'corta';
        $this->assertSame(503, $this->stats('/api/stats/summary')->getStatusCode());

        // Grafana interroga spesso: le statistiche non finiscono nel registro attività
        $this->assertSame([], array_filter($this->auditEntries(), fn($e) => str_starts_with($e->action, 'GET /api/stats')));
    }

    public function test_lead_per_giorno_con_gli_zeri_e_senza_l_anteprima(): void
    {
        $pageId = $this->createPage();
        $this->lead($pageId, '2026-10-01 09:00:00', 'cliente.it');
        $this->lead($pageId, '2026-10-01 18:30:00');
        $this->lead($pageId, '2026-10-03 10:00:00', 'cliente.it');
        $this->lead($pageId, '2026-10-02 10:00:00', 'elpb.example.test');
        $this->lead($pageId, '2026-10-04 00:00:00'); // escluso: "to" non è compreso

        $rows = $this->json($this->stats('/api/stats/leads?from=2026-10-01&to=2026-10-04'));

        $this->assertSame([2, 0, 1], array_column($rows, 'leads'));
        $this->assertSame(['Totale', 'Totale', 'Totale'], array_column($rows, 'series'));
        $this->assertSame((new \DateTimeImmutable('2026-10-01 00:00:00'))->format(DATE_ATOM), $rows[0]['time']);
    }

    public function test_lead_per_pagina_con_le_serie_oltre_il_limite_in_altre(): void
    {
        $a = $this->createPage(['title' => 'Offerta']);
        $b = $this->createPage(['title' => 'Promo']);
        $c = $this->createPage(['title' => 'Evento']);
        foreach ([$a, $a, $a, $b, $b, $c] as $pageId) {
            $this->lead($pageId, '2026-10-05 10:00:00');
        }

        $from = strtotime('2026-10-05 00:00:00') * 1000;
        $to = strtotime('2026-10-12 00:00:00') * 1000;
        $rows = $this->json($this->stats("/api/stats/leads?from=$from&to=$to&interval=week&group=page&limit=2"));

        $this->assertSame([
            ["Offerta (#$a)", (string) $a, 3],
            ["Promo (#$b)", (string) $b, 2],
            ['Altre', null, 1],
        ], array_map(fn($r) => [$r['series'], $r['series_id'], $r['leads']], $rows));
    }

    public function test_visite_e_conversione_per_pagina_e_per_sito(): void
    {
        $pageId = $this->createPage(['title' => 'Offerta']);
        $this->views($pageId, 'cliente.it', '2026-10-01', 120, 80);
        $this->views($pageId, '', '2026-10-02', 30, 20);
        $this->lead($pageId, '2026-10-01 10:00:00', 'cliente.it');
        $this->lead($pageId, '2026-10-02 10:00:00', 'cliente.it');

        $views = $this->json($this->stats('/api/stats/views?from=2026-10-01&to=2026-10-03'));
        $this->assertSame([[120, 80], [30, 20]], array_map(fn($r) => [$r['views'], $r['visitors']], $views));

        $byPage = $this->json($this->stats('/api/stats/conversion?from=2026-10-01&to=2026-10-03'));
        // assertEquals: nel JSON 2.0 torna come intero
        $this->assertEquals([["Offerta (#$pageId)", 2, 2, 150, 100, 2.0]], array_map(fn($r) => [$r['series'], $r['leads'], $r['leads_tracked'], $r['views'], $r['visitors'], $r['conversion_pct']], $byPage));

        $bySite = collect($this->json($this->stats('/api/stats/conversion?from=2026-10-01&to=2026-10-03&group=site')))->keyBy('series');
        $this->assertEquals([2, 80, 2.5], [$bySite['cliente.it']['leads'], $bySite['cliente.it']['visitors'], $bySite['cliente.it']['conversion_pct']]);
        $this->assertEquals([0, 20, 0.0], [$bySite['Sito sconosciuto']['leads'], $bySite['Sito sconosciuto']['visitors'], $bySite['Sito sconosciuto']['conversion_pct']]);
    }

    public function test_conversione_solo_con_i_lead_dei_siti_che_contano_le_visite(): void
    {
        $nuovo = $this->createPage(['title' => 'Nuovo']);
        $vecchio = $this->createPage(['title' => 'Vecchio']);
        // Renderer aggiornato su nuovo.it dal 3 ottobre: il lead del 2 non ha visite con cui confrontarsi
        $this->views($nuovo, 'nuovo.it', '2026-10-03', 100, 50);
        $this->lead($nuovo, '2026-10-02 10:00:00', 'nuovo.it');
        $this->lead($nuovo, '2026-10-03 10:00:00', 'nuovo.it');
        $this->lead($nuovo, '2026-10-04 10:00:00', 'altro.it'); // sito senza visite
        // Renderer vecchio: lead senza nessuna visita
        foreach (range(1, 5) as $i) {
            $this->lead($vecchio, '2026-10-03 10:00:00', 'vecchio.it');
        }

        $summary = $this->json($this->stats('/api/stats/summary?from=2026-10-01&to=2026-10-08'));
        $this->assertEquals([8, 1, 50, 2.0], [$summary['leads'], $summary['leads_tracked'], $summary['visitors'], $summary['conversion_pct']]);

        $byPage = collect($this->json($this->stats('/api/stats/conversion?from=2026-10-01&to=2026-10-08')))->keyBy('series_id');
        $this->assertEquals([3, 1, 50, 2.0], [$byPage[$nuovo]['leads'], $byPage[$nuovo]['leads_tracked'], $byPage[$nuovo]['visitors'], $byPage[$nuovo]['conversion_pct']]);
        $this->assertEquals([5, 0, 0, null], [$byPage[$vecchio]['leads'], $byPage[$vecchio]['leads_tracked'], $byPage[$vecchio]['visitors'], $byPage[$vecchio]['conversion_pct']]);

        // Senza visite nel periodo la conversione è null, non un numero gonfiato
        $empty = $this->json($this->stats('/api/stats/summary?from=2026-10-04&to=2026-10-08'));
        $this->assertEquals([1, 0, null], [$empty['leads'], $empty['leads_tracked'], $empty['conversion_pct']]);
    }

    public function test_riepilogo_con_il_periodo_precedente(): void
    {
        $pageId = $this->createPage();
        $this->lead($pageId, '2026-10-02 10:00:00', 'cliente.it');
        $this->lead($pageId, '2026-10-03 10:00:00', 'cliente.it');
        $this->lead($pageId, '2026-10-04 10:00:00', 'cliente.it');
        $this->lead($pageId, '2026-09-28 10:00:00'); // periodo precedente (25 settembre - 1 ottobre)
        $this->views($pageId, 'cliente.it', '2026-10-02', 200, 150);

        $summary = $this->json($this->stats('/api/stats/summary?from=2026-10-01&to=2026-10-08'));

        $this->assertEquals([3, 1, 200.0, 200, 150, 2.0], [
            $summary['leads'], $summary['leads_previous'], $summary['leads_change_pct'],
            $summary['views'], $summary['visitors'], $summary['conversion_pct'],
        ]);
    }

    public function test_periodo_senza_dati(): void
    {
        $rows = $this->json($this->stats('/api/stats/leads?from=2026-10-01&to=2026-10-03&group=site'));
        $this->assertSame([['Nessun dato', null, 0], ['Nessun dato', null, 0]], array_map(fn($r) => [$r['series'], $r['series_id'], $r['leads']], $rows));
    }

    public function test_parametri_non_validi(): void
    {
        $this->assertSame(400, $this->stats('/api/stats/views?interval=hour')->getStatusCode());
        $this->assertSame(400, $this->stats('/api/stats/leads?from=ieri')->getStatusCode());
        $this->assertSame(400, $this->stats('/api/stats/leads?from=2026-10-05&to=2026-10-01')->getStatusCode());
        $this->assertSame(400, $this->stats('/api/stats/leads?group=utente')->getStatusCode());
        $this->assertSame(400, $this->stats('/api/stats/leads?from=2020-01-01&to=2026-10-01&interval=hour')->getStatusCode());
    }
}
