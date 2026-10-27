<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Consultazione del registro: solo admin, filtri, diretta, export.
 */
class AuditLogControllerTest extends TestCase
{
    private function entry(array $attributes): int
    {
        return Capsule::table('audit_logs')->insertGetId(array_merge([
            'created_at' => date('Y-m-d H:i:s.v'),
            'channel' => 'api',
            'category' => 'view',
            'action' => 'GET /api/pages',
        ], $attributes));
    }

    public function test_solo_l_admin_apre_il_registro(): void
    {
        $this->assertSame(401, $this->request('GET', '/api/admin/audit-logs')->getStatusCode());
        foreach (['user', 'company'] as $role) {
            $user = $this->createUser(['role' => $role, 'company_id' => $role === 'company' ? $this->createCompany() : null]);
            foreach (['/api/admin/audit-logs', '/api/admin/audit-logs/new', '/api/admin/audit-logs/export'] as $uri) {
                $this->assertSame(403, $this->request('GET', $uri, null, [], $user)->getStatusCode(), "$role $uri");
            }
        }
        $this->assertSame(200, $this->request('GET', '/api/admin/audit-logs', null, [], $this->createUser(['role' => 'admin']))->getStatusCode());
    }

    public function test_elenco_con_descrizioni_e_nomi(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $companyId = $this->createCompany('Rossi srl');
        $pageId = $this->createPage(['title' => 'Promo', 'company_id' => $companyId]);
        $this->entry(['category' => 'change', 'action' => 'page.updated', 'company_id' => $companyId, 'page_id' => $pageId,
            'subject_type' => 'page', 'subject_id' => $pageId, 'subject_label' => 'Promo', 'user_name' => 'Mario', 'user_role' => 'company',
            'properties' => json_encode(['old' => ['title' => 'A'], 'new' => ['title' => 'Promo']])]);
        $this->entry(['company_id' => 999, 'page_id' => 998]);

        $data = $this->json($this->request('GET', '/api/admin/audit-logs', null, [], $admin));

        [$deleted, $change] = $data['data'];
        $this->assertSame('Pagina modificata', $change['description']);
        $this->assertSame(['Rossi srl', 'Promo', 'Responsabile aziendale'], [$change['company']['name'], $change['page']['title'], $change['user']['role_label']]);
        $this->assertEquals(['old' => ['title' => 'A'], 'new' => ['title' => 'Promo']], $change['properties']);
        $this->assertSame(['Azienda #999', true, 'Pagina #998'], [$deleted['company']['name'], $deleted['company']['deleted'], $deleted['page']['title']]);
        $this->assertSame(2, $data['meta']['total']);
        $this->assertArrayHasKey('categories', $data['labels']);
    }

    public function test_filtri_e_filtri_non_validi_ignorati(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $companyId = $this->createCompany();
        $this->entry(['user_name' => 'Mario Rossi', 'user_email' => 'mario@example.test', 'company_id' => $companyId, 'ip' => '10.0.0.1', 'created_at' => '2026-10-20 23:30:00']);
        $this->entry(['user_name' => 'Anna', 'category' => 'change', 'action' => 'page.updated', 'outcome' => 'ok', 'subject_label' => 'Offerta Rossi', 'created_at' => '2026-10-21 00:10:00']);
        $this->entry(['user_name' => 'Luca', 'category' => 'action', 'outcome' => 'denied', 'request_id' => '01J0000000000000000000000A', 'created_at' => '2026-10-22 10:00:00']);

        $ids = fn(string $query) => array_map(fn($row) => $row['user']['name'] ?? null,
            $this->json($this->request('GET', '/api/admin/audit-logs?' . $query, null, [], $admin))['data']);

        $this->assertSame(['Anna', 'Mario Rossi'], $ids('utente=rossi'));           // nome, email ed etichetta del record
        $this->assertSame(['Mario Rossi'], $ids("azienda=$companyId"));
        $this->assertSame(['Anna'], $ids('categoria=change'));
        $this->assertSame(['Luca'], $ids('esito=denied'));
        $this->assertSame(['Mario Rossi'], $ids('al=2026-10-20'));                   // giorno intero in ora italiana
        $this->assertSame(['Luca', 'Anna'], $ids('dal=2026-10-21'));
        $this->assertSame(['Luca'], $ids('richiesta=01J0000000000000000000000A'));
        $this->assertSame(['Mario Rossi'], $ids('ip=10.0.0.1'));
        $this->assertSame(['Luca', 'Anna'], $ids('senza_consultazioni=1'));
        // Non validi: ignorati invece di dare errore
        $this->assertCount(3, $ids('categoria=inventata&esito=x&dal=2026-13-45&ip=nonip&azienda=abc&richiesta=corta'));
    }

    public function test_diretta_restituisce_solo_le_voci_successive_e_non_si_registra(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $first = $this->entry(['action' => 'prima']);
        $this->entry(['action' => 'seconda']);
        $this->entry(['action' => 'terza', 'category' => 'change']);

        $data = $this->json($this->request('GET', "/api/admin/audit-logs/new?dopo=$first", null, [], $admin));
        $this->assertSame(['terza', 'seconda'], array_column($data['data'], 'action'));
        $this->assertFalse($data['truncated']);

        $filtered = $this->json($this->request('GET', "/api/admin/audit-logs/new?dopo=$first&categoria=change", null, [], $admin));
        $this->assertSame(['terza'], array_column($filtered['data'], 'action'));

        // Le richieste della diretta non aggiungono voci
        $this->assertSame([], array_filter($this->auditEntries(), fn($e) => str_contains($e->action, 'audit-logs/new')));
        $this->assertSame(3, AuditLog::count());
    }

    public function test_diretta_troncata_oltre_200_voci(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        Capsule::table('audit_logs')->insert(array_map(fn($i) => [
            'created_at' => date('Y-m-d H:i:s'), 'channel' => 'api', 'category' => 'view', 'action' => "v$i",
        ], range(1, 205)));

        $data = $this->json($this->request('GET', '/api/admin/audit-logs/new?dopo=0', null, [], $admin));

        $this->assertCount(200, $data['data']);
        $this->assertTrue($data['truncated']);
    }

    public function test_export_csv_per_excel_con_gli_stessi_filtri(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $this->entry(['category' => 'change', 'action' => 'page.updated', 'user_name' => '=SOMMA(A1)', 'user_email' => 'mario@example.test']);
        $this->entry(['user_name' => 'Anna']);

        $response = $this->request('GET', '/api/admin/audit-logs/export?categoria=change', null, [], $admin);
        $csv = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment; filename="registro-attivita-', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringStartsWith("\xEF\xBB\xBF" . 'Data;Categoria;Descrizione', $csv);
        $this->assertStringContainsString('Modifica;"Pagina modificata";\'=SOMMA(A1);mario@example.test', $csv);
        $this->assertStringNotContainsString('Anna', $csv);
    }
}
