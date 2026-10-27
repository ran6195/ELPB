<?php

namespace Tests\Feature;

use App\Audit\Auditor;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Il registro non deve mai rompere né riordinare l'applicazione.
 */
class ResilienceTest extends TestCase
{
    public function test_senza_la_tabella_del_registro_l_api_funziona(): void
    {
        // Es. backend caricato prima dell'SQL in phpMyAdmin
        $admin = $this->createUser(['role' => 'admin', 'email' => 'admin-test@example.test']);
        $pageId = $this->createPage(['user_id' => $admin->id, 'title' => 'Prima']);
        Capsule::statement('RENAME TABLE audit_logs TO audit_logs_tmp');
        try {
            $this->assertSame(200, $this->request('GET', '/api/pages', null, [], $admin)->getStatusCode());
            $this->assertSame(200, $this->request('PUT', "/api/pages/$pageId", ['title' => 'Dopo'], [], $admin)->getStatusCode());
            $login = $this->request('POST', '/api/auth/login', ['email' => 'admin-test@example.test', 'password' => 'password-test']);
            $this->assertSame(200, $login->getStatusCode());

            Auditor::get()->flush(); // scrittura fallita: solo nel log, nessuna eccezione
            $this->assertSame('Dopo', Capsule::table('pages')->where('id', $pageId)->value('title'));
        } finally {
            Capsule::statement('RENAME TABLE audit_logs_tmp TO audit_logs');
        }
    }

    public function test_nell_elenco_la_richiesta_viene_dopo_le_sue_modifiche(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage(['user_id' => $admin->id, 'title' => 'Prima', 'is_published' => 0]);

        $this->request('PUT', "/api/pages/$pageId", ['title' => 'Dopo', 'is_published' => true], [], $admin);
        Auditor::get()->flush();
        $requestId = Capsule::table('audit_logs')->where('action', 'PUT /api/pages/{id}')->value('request_id');

        $rows = $this->json($this->request('GET', "/api/admin/audit-logs?richiesta=$requestId", null, [], $admin))['data'];

        // Dalla più recente: modifiche e pubblicazione, poi la richiesta che le ha avviate
        $this->assertSame('PUT /api/pages/{id}', end($rows)['action']);
        $this->assertEqualsCanonicalizing(['page.updated', 'page.published'], array_column(array_slice($rows, 0, -1), 'action'));
    }

    public function test_il_responsabile_sulla_propria_azienda_non_e_staff(): void
    {
        $companyId = $this->createCompany();
        $manager = $this->createUser(['role' => 'company', 'company_id' => $companyId]);
        $pageId = $this->createPage(['company_id' => $companyId]);

        $this->request('GET', "/api/pages/$pageId", null, [], $manager);

        [$entry] = $this->auditEntries();
        $this->assertArrayNotHasKey('staff_view', $entry->properties ?? []);
    }
}
