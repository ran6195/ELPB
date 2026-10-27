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
