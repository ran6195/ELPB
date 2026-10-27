<?php

namespace Tests\Feature;

use App\Audit\Auditor;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Registrazione delle richieste all'API (middleware RecordActivity).
 */
class RecordActivityTest extends TestCase
{
    public function test_la_consultazione_di_una_pagina_si_registra_con_azienda_e_pagina(): void
    {
        $companyId = $this->createCompany();
        $user = $this->createUser(['role' => 'company', 'company_id' => $companyId]);
        $pageId = $this->createPage(['title' => 'Promo autunno', 'company_id' => $companyId, 'user_id' => $user->id]);

        $response = $this->request('GET', "/api/pages/$pageId", null, ['User-Agent' => 'Browser di prova'], $user);
        $this->assertSame(200, $response->getStatusCode());

        [$entry] = $this->auditEntries();
        $this->assertSame(['view', 'GET /api/pages/{id}', 'api'], [$entry->category, $entry->action, $entry->channel]);
        $this->assertSame([$companyId, $pageId], [$entry->company_id, $entry->page_id]);
        $this->assertSame(['page', $pageId, 'Promo autunno'], [$entry->subject_type, $entry->subject_id, $entry->subject_label]);
        $this->assertSame([$user->id, 'GET', 200, 'ok'], [$entry->user_id, $entry->method, $entry->status, $entry->outcome]);
        $this->assertSame(['127.0.0.1', 'Browser di prova'], [$entry->ip, $entry->user_agent]);
        $this->assertSame(['params' => ['id' => (string) $pageId]], $entry->properties);
        $this->assertNotNull($entry->duration_ms);
        $this->assertSame(26, strlen($entry->request_id));
    }

    public function test_le_get_anonime_non_si_registrano(): void
    {
        $pageId = $this->createPage(['slug' => 'pubblica']);

        $this->assertSame(200, $this->request('GET', '/api/page/pubblica')->getStatusCode());
        $this->request('GET', '/api/pages'); // 401: anonima anche se rifiutata

        $this->assertSame([], $this->auditEntries());
    }

    public function test_rotte_tecniche_options_e_rotte_inesistenti_non_si_registrano(): void
    {
        $pageId = $this->createPage();
        $user = $this->createUser(['role' => 'admin']);

        $this->request('POST', '/api/track/view', json_encode(['page_id' => $pageId]));
        $this->request('POST', '/api/pages/check-slug', ['slug' => 'x'], [], $user);
        $this->request('OPTIONS', '/api/pages');
        $this->request('GET', '/api/inesistente', null, [], $user);

        $this->assertSame([], $this->auditEntries());
    }

    public function test_i_segreti_nell_input_sono_mascherati(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $this->request('POST', '/api/auth/register', ['name' => 'Nuovo', 'email' => 'nuovo@example.test', 'password' => 'segretissima'], [], $admin);

        $entries = $this->auditEntries();
        $entry = current(array_filter($entries, fn($e) => $e->category === 'action'));
        $this->assertStringNotContainsString('segretissima', json_encode(array_map(fn($e) => $e->toArray(), $entries)));
        $this->assertSame(['action', 'POST /api/auth/register'], [$entry->category, $entry->action]);
        $this->assertSame('•••', $entry->properties['input']['password']);
        $this->assertSame('nuovo@example.test', $entry->properties['input']['email']);
        $this->assertStringNotContainsString('segretissima', json_encode($entry->properties));
    }

    public function test_input_non_valido_registrato_con_esito_invalid_e_messaggio(): void
    {
        $response = $this->request('POST', '/api/auth/login', ['email' => 'chi@example.test']);
        $this->assertSame(400, $response->getStatusCode());

        [$entry] = $this->auditEntries();
        $this->assertSame([400, 'invalid'], [$entry->status, $entry->outcome]);
        $this->assertSame('Email and password are required', $entry->properties['error']);
        $this->assertNull($entry->user_id);
    }

    public function test_le_richieste_negate_si_registrano_con_esito_denied(): void
    {
        $user = $this->createUser(['role' => 'company', 'company_id' => $this->createCompany('Mia')]);
        $otherPage = $this->createPage(['company_id' => $this->createCompany('Altra')]);

        $this->assertSame(403, $this->request('GET', "/api/pages/$otherPage", null, [], $user)->getStatusCode());

        [$entry] = $this->auditEntries();
        $this->assertSame([403, 'denied', $user->id], [$entry->status, $entry->outcome, $entry->user_id]);
    }

    public function test_lead_dal_form_pubblico_senza_dati_personali(): void
    {
        $companyId = $this->createCompany();
        $pageId = $this->createPage(['company_id' => $companyId]);

        $response = $this->request('POST', '/api/leads', [
            'page_id' => $pageId, 'name' => 'Visitatore', 'email' => 'visitatore@example.test', 'phone' => '3331234567',
        ]);
        $this->assertSame(201, $response->getStatusCode());

        $entries = array_values(array_filter($this->auditEntries(), fn($e) => $e->category === 'action'));
        $this->assertCount(1, $entries);
        $this->assertSame([$pageId, $companyId], [$entries[0]->page_id, $entries[0]->company_id]);
        $this->assertStringNotContainsString('visitatore', json_encode($entries[0]->toArray()));
        $this->assertStringNotContainsString('3331234567', json_encode($entries[0]->toArray()));
    }

    public function test_admin_su_pagina_di_un_altra_azienda_e_consultazione_staff(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage(['company_id' => $this->createCompany()]);

        $this->request('GET', "/api/pages/$pageId", null, [], $admin);

        [$entry] = $this->auditEntries();
        $this->assertTrue($entry->properties['staff_view'] ?? false);
    }

    public function test_salvataggi_dell_editor_uniti_in_una_voce_ogni_10_minuti(): void
    {
        $user = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage(['user_id' => $user->id]);

        $this->request('PUT', "/api/pages/$pageId", ['title' => 'Pagina Test', 'blocks' => [['type' => 'hero'], ['type' => 'text']]], [], $user);
        Auditor::get()->flush();
        $this->request('PUT', "/api/pages/$pageId", ['title' => 'Pagina Test', 'blocks' => [['type' => 'hero'], ['type' => 'form']]], [], $user);

        $saves = array_values(array_filter($this->auditEntries(), fn($e) => $e->action === 'PUT /api/pages/{id}'));
        $this->assertCount(1, $saves);
        $properties = $saves[0]->properties;
        $this->assertSame(2, $properties['saves']);
        $this->assertSame(2, $properties['blocks']['count']);
        $this->assertEquals(['hero' => 1, 'text' => 1, 'form' => 1], $properties['blocks']['added']);
        $this->assertEquals(['text' => 1], $properties['blocks']['removed']);
        $this->assertArrayNotHasKey('input', $properties);
    }

    public function test_salvataggio_oltre_la_finestra_crea_una_nuova_voce(): void
    {
        $user = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage(['user_id' => $user->id]);

        $this->request('PUT', "/api/pages/$pageId", ['blocks' => []], [], $user);
        Auditor::get()->flush();
        Capsule::table('audit_logs')->update(['created_at' => date('Y-m-d H:i:s', strtotime('-11 minutes'))]);
        $this->request('PUT', "/api/pages/$pageId", ['blocks' => []], [], $user);

        $saves = array_filter($this->auditEntries(), fn($e) => $e->action === 'PUT /api/pages/{id}');
        $this->assertCount(2, $saves);
    }

    public function test_la_voce_della_richiesta_prende_l_ora_di_inizio(): void
    {
        $user = $this->createUser(['role' => 'admin']);
        $before = microtime(true);

        $this->request('GET', '/api/pages', null, [], $user);

        [$entry] = $this->auditEntries();
        $this->assertEqualsWithDelta($before, (float) $entry->created_at->format('U.v'), 1.0);
    }
}
