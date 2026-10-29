<?php

namespace Tests\Feature;

use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Diretta della scheda Renderer: solo le righe con accessi recenti, fuori dal registro attività.
 */
class RendererLiveTest extends TestCase
{
    private function hit(int $pageId, string $host, string $at, int $hits = 1): void
    {
        Capsule::table('renderer_hits')->insert([
            'page_id' => $pageId, 'host' => $host, 'renderer' => 'standalone', 'version' => '2.1.0',
            'ip' => '93.186.242.100', 'hits' => $hits, 'first_seen_at' => $at, 'last_seen_at' => $at,
        ]);
    }

    public function test_la_diretta_restituisce_solo_le_righe_aggiornate_e_non_va_nel_registro(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage();
        $this->hit($pageId, 'vecchio.it', '2026-10-01 10:00:00');
        $this->hit($pageId, 'nuovo.it', '2026-10-28 12:00:05', 7);

        $full = $this->json($this->request('GET', '/api/renderers', null, [], $admin));
        $this->assertSame('2026-10-28 12:00:05', $full['last_seen_at']);
        $this->assertCount(2, $full['data']);

        // Stesso secondo dell'ultimo accesso compreso: il frontend sostituisce la riga
        $live = $this->json($this->request('GET', '/api/renderers/new?dopo=2026-10-28%2012:00:05', null, [], $admin));
        $this->assertSame(['nuovo.it'], array_column($live['data'], 'host'));
        $this->assertSame([7, '2026-10-28 12:00:05'], [$live['data'][0]['hits'], $live['last_seen_at']]);

        $this->assertSame([], $this->json($this->request('GET', '/api/renderers/new?dopo=2026-10-28%2012:00:06', null, [], $admin))['data']);
        $this->assertSame(400, $this->request('GET', '/api/renderers/new?dopo=ieri', null, [], $admin)->getStatusCode());

        $actions = array_map(fn($e) => $e->action, $this->auditEntries());
        $this->assertContains('GET /api/renderers', $actions);
        $this->assertNotContains('GET /api/renderers/new', $actions);
    }

    public function test_la_diretta_rispetta_la_visibilita_delle_pagine(): void
    {
        $companyId = $this->createCompany();
        $own = $this->createPage(['company_id' => $companyId]);
        $foreign = $this->createPage(['company_id' => $this->createCompany('Altra')]);
        $this->hit($own, 'mio.it', '2026-10-28 12:00:00');
        $this->hit($foreign, 'altrui.it', '2026-10-28 12:00:00');
        $manager = $this->createUser(['role' => 'company', 'company_id' => $companyId]);

        $live = $this->json($this->request('GET', '/api/renderers/new?dopo=2026-10-28%2011:00:00', null, [], $manager));
        $this->assertSame(['mio.it'], array_column($live['data'], 'host'));
    }

    public function test_il_renderer_aggiornato_assorbe_la_riga_vecchia_con_lo_stesso_ip(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage();
        $other = $this->createPage();
        $row = fn($page, $host, $version, $ip, $hits, $first, $last) => Capsule::table('renderer_hits')->insert([
            'page_id' => $page, 'host' => $host, 'renderer' => 'standalone', 'version' => $version,
            'ip' => $ip, 'hits' => $hits, 'first_seen_at' => $first, 'last_seen_at' => $last,
        ]);
        // Prima dell'aggiornamento (senza sito) e dopo, dallo stesso server
        $row($pageId, '', null, '93.186.242.100', 40, '2026-09-01 08:00:00', '2026-10-27 09:00:00');
        $row($pageId, 'cliente.it', '2.1.1', '93.186.242.100', 5, '2026-10-27 10:00:00', '2026-10-28 12:00:00');
        // Server diverso: è un'altra installazione, resta da aggiornare
        $row($pageId, '', null, '80.88.87.10', 3, '2026-09-01 08:00:00', '2026-10-28 11:00:00');
        // Due siti aggiornati sullo stesso IP: non si sa quale fosse, la riga vecchia resta
        $row($other, '', null, '93.186.242.100', 9, '2026-09-01 08:00:00', '2026-10-27 09:00:00');
        $row($other, 'uno.it', '2.1.1', '93.186.242.100', 1, '2026-10-27 10:00:00', '2026-10-28 12:00:00');
        $row($other, 'due.it', '2.1.1', '93.186.242.100', 1, '2026-10-27 10:00:00', '2026-10-28 12:00:00');

        $rows = collect($this->json($this->request('GET', '/api/renderers', null, [], $admin))['data'])
            ->where('page.id', $pageId)->keyBy('ip');
        $this->assertCount(2, $rows);
        $merged = $rows['93.186.242.100'];
        $this->assertSame(['cliente.it', 45, false, '2026-09-01 08:00:00'],
            [$merged['host'], $merged['hits'], $merged['outdated'], $merged['first_seen_at']]);
        $this->assertSame([['host' => '', 'renderer' => 'standalone', 'ip' => '93.186.242.100', 'hits' => 40]], $merged['merged']);
        $this->assertTrue($rows['80.88.87.10']['outdated']);

        // La diretta manda la riga unita, con le righe assorbite da togliere
        $live = $this->json($this->request('GET', '/api/renderers/new?dopo=2026-10-28%2012:00:00', null, [], $admin))['data'];
        $this->assertSame(45, collect($live)->firstWhere('host', 'cliente.it')['hits']);

        $page = $this->json($this->request('GET', "/api/pages/{$pageId}/renderers", null, [], $admin))['data']['hits'];
        $this->assertSame([45, 40], [$page[0]['hits'], $page[0]['merged_hits']]);
        $this->assertCount(2, $page);

        // Da aggiornare: il server diverso e la riga vecchia della pagina con due siti
        $summary = $this->json($this->request('GET', '/api/stats/summary?from=2026-10-01&to=2026-10-29', null, [], $admin));
        $this->assertSame(2, $summary['outdated_renderers']);
    }
}
