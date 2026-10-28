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
}
