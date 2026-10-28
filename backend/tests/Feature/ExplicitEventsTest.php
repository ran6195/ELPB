<?php

namespace Tests\Feature;

use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Eventi espliciti: accessi, pubblicazione, assegnazione, lead ricevuto, script.
 */
class ExplicitEventsTest extends TestCase
{
    private function byAction(string $action): array
    {
        return array_values(array_filter($this->auditEntries(), fn($e) => $e->action === $action));
    }

    public function test_login_riuscito(): void
    {
        $user = $this->createUser(['email' => 'entra@example.test']);

        $this->request('POST', '/api/auth/login', ['email' => 'entra@example.test', 'password' => 'password-test']);

        $entries = $this->auditEntries();
        $login = current(array_filter($entries, fn($e) => $e->action === 'login'));
        $request = current(array_filter($entries, fn($e) => $e->action === 'POST /api/auth/login'));
        $this->assertSame(['access', 'ok', $user->id, 'entra@example.test'], [$login->category, $login->outcome, $login->user_id, $login->subject_label]);
        // Dopo il login l'autore della richiesta è l'utente entrato
        $this->assertSame($user->id, $request->user_id);
    }

    public function test_login_fallito_con_utente_noto_e_sconosciuto(): void
    {
        $user = $this->createUser(['email' => 'noto@example.test']);

        $this->request('POST', '/api/auth/login', ['email' => 'noto@example.test', 'password' => 'sbagliata']);
        $this->request('POST', '/api/auth/login', ['email' => 'nessuno@example.test', 'password' => 'x']);

        [$known, $unknown] = array_values(array_filter($this->auditEntries(), fn($e) => $e->action === 'login_failed'));
        $this->assertSame(['denied', null, $user->id], [$known->outcome, $known->user_id, $known->subject_id]);
        $this->assertSame(['email' => 'noto@example.test', 'known_user' => true], $known->properties);
        $this->assertSame(['email' => 'nessuno@example.test', 'known_user' => false], $unknown->properties);
        $this->assertNull($unknown->subject_id);
    }

    public function test_logout(): void
    {
        $user = $this->createUser();

        $this->assertSame(204, $this->request('POST', '/api/auth/logout', null, [], $user)->getStatusCode());

        $this->assertSame($user->id, $this->byAction('logout')[0]->user_id);
    }

    public function test_registrazione_pubblica_chiusa_e_utenti_creati_solo_dall_admin(): void
    {
        $data = ['name' => 'Intruso', 'email' => 'intruso@example.test', 'password' => 'abcdef12', 'role' => 'admin'];
        $this->assertSame(401, $this->request('POST', '/api/auth/register', $data)->getStatusCode());
        $this->assertSame(403, $this->request('POST', '/api/auth/register', $data, [], $this->createUser(['role' => 'company']))->getStatusCode());
        $this->assertSame(0, \App\Models\User::where('email', 'intruso@example.test')->count());

        $admin = $this->createUser(['role' => 'admin', 'name' => 'Amministratore']);
        $created = $this->request('POST', '/api/auth/register', ['name' => 'Nuovo', 'email' => 'nuovo@example.test', 'password' => 'abcdef12'], [], $admin);
        $this->assertSame(201, $created->getStatusCode());

        // La creazione ha come autore l'admin, non l'utente creato
        [$change] = $this->byAction('user.created');
        $this->assertSame([$admin->id, 'nuovo@example.test'], [$change->user_id, $change->subject_label]);
    }

    public function test_pubblicazione_e_ritiro_della_pagina(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage(['is_published' => 0, 'title' => 'Offerta', 'user_id' => $admin->id]);

        $this->request('PUT', "/api/pages/$pageId", ['is_published' => true], [], $admin);
        $this->request('PUT', "/api/pages/$pageId", ['is_published' => true], [], $admin); // già pubblicata
        $this->request('PUT', "/api/pages/$pageId", ['is_published' => false], [], $admin);

        $events = array_values(array_filter($this->auditEntries(), fn($e) => $e->category === 'publish'));
        $this->assertSame(['page.published', 'page.unpublished'], array_map(fn($e) => $e->action, $events));
        $this->assertSame(['Offerta', $pageId], [$events[0]->subject_label, $events[0]->page_id]);
    }

    public function test_pagina_creata_gia_pubblicata(): void
    {
        $user = $this->createUser();

        $this->request('POST', '/api/pages', ['title' => 'Subito online', 'slug' => 'subito', 'is_published' => true], [], $user);

        $this->assertCount(1, $this->byAction('page.published'));
    }

    public function test_pagina_assegnata_ad_altri_con_i_nomi(): void
    {
        $admin = $this->createUser(['role' => 'admin', 'name' => 'Amministratore']);
        $from = $this->createUser(['name' => 'Vecchio', 'company_id' => $this->createCompany('Prima srl')]);
        $to = $this->createUser(['name' => 'Nuovo', 'company_id' => $this->createCompany('Dopo srl')]);
        $pageId = $this->createPage(['user_id' => $from->id, 'company_id' => $from->company_id]);

        $this->request('PUT', "/api/company/pages/$pageId/reassign", ['user_id' => $to->id], [], $admin);

        [$event] = $this->byAction('page.reassigned');
        $this->assertSame('publish', $event->category);
        $this->assertSame(['Vecchio', 'Prima srl'], [$event->properties['from']['user'], $event->properties['from']['company']]);
        $this->assertSame(['Nuovo', 'Dopo srl'], [$event->properties['to']['user'], $event->properties['to']['company']]);
        $this->assertSame($to->company_id, $event->company_id);
    }

    public function test_lead_ricevuto_con_esito_della_notifica(): void
    {
        $companyId = $this->createCompany();
        $pageId = $this->createPage(['company_id' => $companyId]);

        $this->request('POST', '/api/leads', ['page_id' => $pageId, 'email' => 'v@example.test'], ['Origin' => 'https://www.cliente.it']);

        [$event] = $this->byAction('lead.received');
        $this->assertSame(['system', null, $pageId, $companyId, 'lead'], [$event->category, $event->user_id, $event->page_id, $event->company_id, $event->subject_type]);
        $this->assertSame(['origin_host' => 'cliente.it', 'notification' => 'not_configured'], $event->properties);
    }

    private function createLead(int $pageId): int
    {
        return Capsule::table('leads')->insertGetId([
            'page_id' => $pageId, 'name' => 'Mario Rossi', 'email' => uniqid() . '@example.test',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function test_export_dei_lead_con_id_visibili_e_senza_dati_personali(): void
    {
        $companyId = $this->createCompany();
        $otherCompanyId = $this->createCompany('Altra');
        $pageId = $this->createPage(['company_id' => $companyId, 'title' => 'Offerta']);
        $otherPageId = $this->createPage(['company_id' => $otherCompanyId]);
        $own = [$this->createLead($pageId), $this->createLead($pageId)];
        $foreign = $this->createLead($otherPageId);
        $user = $this->createUser(['role' => 'company', 'company_id' => $companyId]);

        $response = $this->request('POST', '/api/leads/exports', [
            'lead_ids' => [...$own, $foreign],
            'panel' => 'company',
            'filters' => ['text' => 'mario.rossi@example.test', 'status' => 'published', 'site' => ''],
        ], [], $user);
        $this->assertSame(['logged' => 2], $this->json($response));

        [$event] = $this->byAction('lead.exported');
        $this->assertSame(['export', 'ok', $user->id, $companyId, $pageId, '2 lead'], [$event->category, $event->outcome, $event->user_id, $event->company_id, $event->page_id, $event->subject_label]);
        // assertEquals: il JSON di MySQL riordina le chiavi
        $this->assertEquals([
            'format' => 'csv',
            'panel' => 'company',
            'count' => 2,
            'refused' => 1,
            'lead_ids' => $own,
            'pages' => [['id' => $pageId, 'title' => 'Offerta', 'leads' => 2]],
            'filters' => ['status' => 'published', 'text_search' => true],
        ], $event->properties);

        // La voce della richiesta non contiene l'input, e nel registro non c'è la ricerca
        [$request] = $this->byAction('POST /api/leads/exports');
        $this->assertSame($event->request_id, $request->request_id);
        $this->assertStringNotContainsString('mario.rossi', json_encode(array_map(fn($e) => $e->toArray(), $this->auditEntries())));
    }

    public function test_export_dei_lead_di_piu_aziende_e_richiesta_non_valida(): void
    {
        $pageA = $this->createPage(['company_id' => $this->createCompany('A')]);
        $pageB = $this->createPage(['company_id' => $this->createCompany('B')]);
        $ids = [$this->createLead($pageA), $this->createLead($pageB)];
        $admin = $this->createUser(['role' => 'admin']);

        $this->request('POST', '/api/leads/exports', ['lead_ids' => $ids, 'panel' => 'admin'], [], $admin);

        [$event] = $this->byAction('lead.exported');
        $this->assertSame([null, null, 2, 2], [$event->company_id, $event->page_id, $event->properties['count'], $event->properties['companies']]);

        $this->assertSame(400, $this->request('POST', '/api/leads/exports', ['lead_ids' => []], [], $admin)->getStatusCode());
        $this->assertSame(401, $this->request('POST', '/api/leads/exports', ['lead_ids' => $ids])->getStatusCode());
    }

    public function test_apertura_di_un_lead_registrata_con_lead_pagina_e_azienda(): void
    {
        $companyId = $this->createCompany();
        $pageId = $this->createPage(['company_id' => $companyId]);
        $leadId = $this->createLead($pageId);
        $foreignId = $this->createLead($this->createPage(['company_id' => $this->createCompany('Altra')]));
        $user = $this->createUser(['role' => 'company', 'company_id' => $companyId]);

        $response = $this->request('GET', "/api/leads/$leadId", null, [], $user);
        $this->assertSame([200, $leadId], [$response->getStatusCode(), $this->json($response)['id']]);
        $this->assertSame(403, $this->request('GET', "/api/leads/$foreignId", null, [], $user)->getStatusCode());
        $this->assertSame(404, $this->request('GET', '/api/leads/999999', null, [], $user)->getStatusCode());

        [$ok, $denied, $missing] = $this->byAction('GET /api/leads/{id}');
        $this->assertSame(['view', 'ok', 'lead', $leadId, $pageId, $companyId], [$ok->category, $ok->outcome, $ok->subject_type, $ok->subject_id, $ok->page_id, $ok->company_id]);
        $this->assertSame(['denied', $foreignId], [$denied->outcome, $denied->subject_id]);
        $this->assertSame('not_found', $missing->outcome);
        // Nessun dato del visitatore nel registro
        $this->assertStringNotContainsString('Mario Rossi', json_encode(array_map(fn($e) => $e->toArray(), $this->auditEntries())));
    }

    public function test_consultazione_del_calendario_dei_lead(): void
    {
        $user = $this->createUser(['role' => 'admin']);

        $this->assertSame(204, $this->request('GET', '/api/leads/calendar?mese=2026-11&pannello=admin', null, [], $user)->getStatusCode());
        $this->assertSame(400, $this->request('GET', '/api/leads/calendar?mese=2026-13', null, [], $user)->getStatusCode());

        [$view, $invalid] = $this->byAction('GET /api/leads/calendar');
        $this->assertSame(['view', 'ok', $user->id], [$view->category, $view->outcome, $view->user_id]);
        $this->assertStringEndsWith('/api/leads/calendar?mese=2026-11&pannello=admin', $view->url);
        $this->assertSame('invalid', $invalid->outcome);
    }

    public function test_gli_script_si_registrano_come_system_sul_canale_console(): void
    {
        $this->runScript('--dry-run');
        $this->runScript('--fallisci');

        $entries = \App\Models\AuditLog::orderBy('id')->get();
        $scripts = $entries->where('action', 'script')->values();
        $this->assertCount(2, $scripts);
        $this->assertSame(['system', 'console', 'script-di-prova', 'ok'], [$scripts[0]->category, $scripts[0]->channel, $scripts[0]->subject_label, $scripts[0]->outcome]);
        $this->assertSame(['--dry-run'], $scripts[0]->properties['args']);
        $this->assertSame('error', $scripts[1]->outcome);
        $this->assertStringContainsString('funzione_inesistente', $scripts[1]->properties['error']);

        // Le modifiche fatte dallo script prendono canale e nome dello script
        $change = $entries->firstWhere('action', 'company.created');
        $this->assertSame(['console', 'script-di-prova', null], [$change->channel, $change->properties['source'], $change->user_id]);
    }

    private function runScript(string $arg): void
    {
        $database = Capsule::connection()->getDatabaseName();
        $command = sprintf('ELPB_TEST_DB=%s %s %s %s 2>&1', escapeshellarg($database), escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/../Fixtures/script-di-prova.php'), escapeshellarg($arg));
        exec($command, $output);
    }
}
