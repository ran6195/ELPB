<?php

namespace Tests\Feature;

use App\Services\TrackingService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Bot, assistenti IA e strumenti nel registro dei renderer: "automatici", non renderer da aggiornare.
 */
class RendererBotsTest extends TestCase
{
    public function test_versione_da_aggiornare_per_renderer(): void
    {
        $this->assertTrue(TrackingService::isOutdated(null, ''));
        $this->assertTrue(TrackingService::isOutdated('2.1.0', 'standalone'));
        $this->assertFalse(TrackingService::isOutdated('2.1.1', 'standalone'));
        // Joomla non ha la 2.1.1: la 2.1.0 è l'ultima
        $this->assertFalse(TrackingService::isOutdated('2.1.0', 'joomla'));
        // Renderer sconosciuto: confronto con la versione più recente
        $this->assertTrue(TrackingService::isOutdated('2.1.0', 'altro'));
    }

    public function test_classificazione_delle_richieste_automatiche(): void
    {
        $cases = [
            'Anthropic senza user agent' => [null, '160.79.106.25', null, true],
            'Anthropic con user agent' => [null, '160.79.106.23', 'Mozilla/5.0 (compatible)', true],
            'Googlebot' => [null, '66.249.66.1', null, true],
            'Google Cloud (sito su SiteGround)' => [null, '35.214.192.83', null, false],
            'Server Aruba dei clienti' => [null, '93.186.242.100', null, false],
            'Renderer 2.1 anche da una rete di bot' => ['2.1.0', '160.79.106.25', null, false],
            'curl da riga di comando' => [null, '151.42.160.32', 'curl/8.7.1', true],
            'Assistente IA dallo user agent' => [null, '10.0.0.1', 'Mozilla/5.0 Claude-User', true],
            'Joomla' => [null, '10.0.0.2', 'Joomla! - Open Source Content Management', false],
            'Connessione domestica con browser' => [null, '151.42.160.32', 'Mozilla/5.0 (Macintosh)', false],
            'IPv6' => [null, '2001:db8::1', null, false],
        ];
        foreach ($cases as $name => [$version, $ip, $ua, $expected]) {
            $this->assertSame($expected, TrackingService::isAutomated($version, $ip, $ua), $name);
        }
    }

    public function test_l_api_segna_i_bot_e_non_li_conta_da_aggiornare(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage();
        $now = date('Y-m-d H:i:s');
        Capsule::table('renderer_hits')->insert([
            ['page_id' => $pageId, 'host' => '', 'renderer' => 'standalone', 'version' => null, 'ip' => '160.79.106.25', 'user_agent' => null, 'hits' => 1, 'first_seen_at' => $now, 'last_seen_at' => $now],
            ['page_id' => $pageId, 'host' => '', 'renderer' => 'standalone', 'version' => null, 'ip' => '35.214.192.83', 'user_agent' => null, 'hits' => 39, 'first_seen_at' => $now, 'last_seen_at' => $now],
        ]);

        $rows = collect($this->json($this->request('GET', '/api/renderers', null, [], $admin))['data'])->keyBy('ip');
        $this->assertSame([true, false], [$rows['160.79.106.25']['is_bot'], $rows['160.79.106.25']['outdated']]);
        $this->assertSame([false, true], [$rows['35.214.192.83']['is_bot'], $rows['35.214.192.83']['outdated']]);

        $hits = collect($this->json($this->request('GET', "/api/pages/$pageId/renderers", null, [], $admin))['data']['hits'])->keyBy('ip');
        $this->assertTrue($hits['160.79.106.25']['is_bot']);
        $this->assertFalse($hits['35.214.192.83']['is_bot']);
    }
}
