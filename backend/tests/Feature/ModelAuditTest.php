<?php

namespace Tests\Feature;

use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Modifiche ai modelli registrate dal trait Auditable e associazioni dei domini.
 */
class ModelAuditTest extends TestCase
{
    private function changes(string $action): array
    {
        return array_values(array_filter($this->auditEntries(), fn($e) => $e->action === $action));
    }

    public function test_modifica_della_pagina_campo_per_campo_con_il_request_id_della_richiesta(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage(['title' => 'Vecchio titolo', 'user_id' => $admin->id]);

        $this->request('PUT', "/api/pages/$pageId", ['title' => 'Nuovo titolo'], [], $admin);

        $entries = $this->auditEntries();
        $request = current(array_filter($entries, fn($e) => $e->action === 'PUT /api/pages/{id}'));
        $change = current(array_filter($entries, fn($e) => $e->action === 'page.updated'));

        $this->assertSame('change', $change->category);
        $this->assertSame('Vecchio titolo', $change->properties['old']['title']);
        $this->assertSame('Nuovo titolo', $change->properties['new']['title']);
        $this->assertSame([$pageId, 'Nuovo titolo'], [$change->page_id, $change->subject_label]);
        $this->assertSame($request->request_id, $change->request_id);
        // La richiesta inizia prima delle modifiche che produce
        $this->assertLessThanOrEqual($change->created_at->format('U.v'), $request->created_at->format('U.v'));
    }

    public function test_creare_una_pagina_produce_un_solo_evento_di_creazione(): void
    {
        $user = $this->createUser();

        $response = $this->request('POST', '/api/pages', ['title' => 'Nuova', 'slug' => 'nuova'], [], $user);
        $this->assertSame(201, $response->getStatusCode());

        $this->assertCount(1, $this->changes('page.created'));
        $this->assertSame([], $this->changes('page.updated'));
    }

    public function test_archiviazione_ripristino_ed_eliminazione_definitiva(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage(['is_published' => 0, 'user_id' => $admin->id]);

        $this->request('DELETE', "/api/pages/$pageId", null, [], $admin);
        $this->request('POST', "/api/pages/$pageId/restore", null, [], $admin);
        $this->request('DELETE', "/api/pages/$pageId", null, [], $admin);
        $this->request('DELETE', "/api/pages/$pageId/force", null, [], $admin);

        $actions = array_map(fn($e) => $e->action, array_filter($this->auditEntries(), fn($e) => $e->category === 'change'));
        $this->assertSame(['page.deleted', 'page.restored', 'page.deleted', 'page.force_deleted'], array_values($actions));
    }

    public function test_cambio_password_registrato_ma_mascherato(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $user = $this->createUser(['name' => 'Rossi']);

        $this->request('PUT', "/api/admin/users/{$user->id}", ['password' => 'nuova-password-123'], [], $admin);

        [$change] = $this->changes('user.updated');
        $this->assertSame('•••', $change->properties['new']['password']);
        $this->assertSame('•••', $change->properties['old']['password']);
        $this->assertSame($user->email, $change->subject_label);
        $this->assertSame('•••', $this->changes('PUT /api/admin/users/{id}')[0]->properties['input']['password']);
    }

    public function test_lead_creato_senza_dati_personali(): void
    {
        $pageId = $this->createPage(['company_id' => $this->createCompany()]);

        $this->request('POST', '/api/leads', ['page_id' => $pageId, 'name' => 'Bianchi', 'email' => 'bianchi@example.test', 'phone' => '3330000000']);

        [$change] = $this->changes('lead.created');
        $this->assertSame($pageId, $change->page_id);
        $this->assertArrayNotHasKey('email', $change->properties['new']);
        $this->assertStringNotContainsString('Bianchi', json_encode($change->toArray()));
    }

    public function test_domini_associati_alla_pagina(): void
    {
        $companyId = $this->createCompany();
        $manager = $this->createUser(['role' => 'company', 'company_id' => $companyId]);
        $pageId = $this->createPage(['company_id' => $companyId, 'user_id' => $manager->id]);
        $now = date('Y-m-d H:i:s');
        $first = Capsule::table('domains')->insertGetId(['company_id' => $companyId, 'hostname' => 'primo.it', 'created_at' => $now, 'updated_at' => $now]);
        $second = Capsule::table('domains')->insertGetId(['company_id' => $companyId, 'hostname' => 'secondo.it', 'created_at' => $now, 'updated_at' => $now]);

        $this->request('PUT', "/api/pages/$pageId/domains", ['domain_ids' => [$first]], [], $manager);
        $this->request('PUT', "/api/pages/$pageId/domains", ['domain_ids' => [$second]], [], $manager);
        $this->request('PUT', "/api/pages/$pageId/domains", ['domain_ids' => [$second]], [], $manager); // nessun cambiamento

        $changes = $this->changes('page.domains_updated');
        $this->assertCount(2, $changes);
        $this->assertSame(['reason' => 'manual', 'attached' => ['primo.it']], $changes[0]->properties);
        $this->assertEquals(['reason' => 'manual', 'attached' => ['secondo.it'], 'detached' => ['primo.it']], $changes[1]->properties);
        $this->assertSame([$companyId, $pageId], [$changes[1]->company_id, $changes[1]->page_id]);
    }

    public function test_i_blocchi_non_sono_registrati_uno_per_uno(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $pageId = $this->createPage(['user_id' => $admin->id]);

        $this->request('PUT', "/api/pages/$pageId", ['blocks' => [['type' => 'hero', 'content' => []]]], [], $admin);

        $this->assertSame([], array_filter($this->auditEntries(), fn($e) => str_starts_with($e->action, 'block.')));
    }
}
