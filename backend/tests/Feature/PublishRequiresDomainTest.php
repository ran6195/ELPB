<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Una pagina si pubblica solo se associata ad almeno un dominio e, pubblicata,
 * non può perdere l'ultimo. Le pagine già pubblicate senza domini restano pubblicate.
 */
class PublishRequiresDomainTest extends TestCase
{
    public function test_pubblicazione_rifiutata_senza_domini_e_accettata_con_un_dominio(): void
    {
        [$manager, $pageId] = $this->companyPage(['is_published' => 0]);

        $response = $this->request('PUT', "/api/pages/$pageId", ['is_published' => true], [], $manager);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('domain_required', $this->json($response)['code']);
        $this->assertSame(0, (int) Capsule::table('pages')->where('id', $pageId)->value('is_published'));

        $this->attach($pageId, $this->domain($manager->company_id, 'cliente.it'));
        $response = $this->request('PUT', "/api/pages/$pageId", ['is_published' => true], [], $manager);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->json($response)['is_published']);
    }

    public function test_pagina_gia_pubblicata_senza_domini_si_salva_ancora(): void
    {
        [$manager, $pageId] = $this->companyPage(['is_published' => 1]);

        $response = $this->request('PUT', "/api/pages/$pageId", ['title' => 'Nuovo titolo', 'is_published' => true], [], $manager);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Nuovo titolo', $this->json($response)['title']);
    }

    public function test_pagina_pubblicata_non_perde_l_ultimo_dominio(): void
    {
        [$manager, $pageId] = $this->companyPage(['is_published' => 1]);
        $first = $this->domain($manager->company_id, 'primo.it');
        $second = $this->domain($manager->company_id, 'secondo.it');
        $this->attach($pageId, $first);
        $this->attach($pageId, $second);

        // Togliere un dominio su due va bene
        $response = $this->request('PUT', "/api/pages/$pageId/domains", ['domain_ids' => [$first]], [], $manager);
        $this->assertSame(200, $response->getStatusCode());

        // L'ultimo no
        $response = $this->request('PUT', "/api/pages/$pageId/domains", ['domain_ids' => []], [], $manager);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('domain_required', $this->json($response)['code']);
        $this->assertSame(1, Capsule::table('domain_page')->where('page_id', $pageId)->count());

        // Ritirata la pubblicazione si può togliere
        $this->request('PUT', "/api/pages/$pageId", ['is_published' => false], [], $manager);
        $response = $this->request('PUT', "/api/pages/$pageId/domains", ['domain_ids' => []], [], $manager);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_eliminazione_del_dominio_unico_di_una_pagina_pubblicata_rifiutata(): void
    {
        [$manager, $pageId] = $this->companyPage(['is_published' => 1, 'title' => 'Offerta']);
        $domainId = $this->domain($manager->company_id, 'cliente.it');
        $this->attach($pageId, $domainId);

        $response = $this->request('DELETE', "/api/domains/$domainId", null, [], $manager);
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame([['id' => $pageId, 'title' => 'Offerta']], $this->json($response)['pages']);
        $this->assertTrue(Capsule::table('domains')->where('id', $domainId)->exists());

        // Con un altro dominio associato l'eliminazione passa
        $this->attach($pageId, $this->domain($manager->company_id, 'altro.it'));
        $response = $this->request('DELETE', "/api/domains/$domainId", null, [], $manager);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_pagina_pubblicata_non_passa_ad_altra_azienda_perdendo_i_domini(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        [$manager, $pageId] = $this->companyPage(['is_published' => 1]);
        $this->attach($pageId, $this->domain($manager->company_id, 'cliente.it'));
        $other = $this->createUser(['company_id' => $this->createCompany('Altra srl')]);

        $response = $this->request('PUT', "/api/company/pages/$pageId/reassign", ['user_id' => $other->id], [], $admin);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame($manager->company_id, (int) Capsule::table('pages')->where('id', $pageId)->value('company_id'));

        // Nella stessa azienda i domini restano: assegnazione possibile
        $colleague = $this->createUser(['company_id' => $manager->company_id]);
        $response = $this->request('PUT', "/api/company/pages/$pageId/reassign", ['user_id' => $colleague->id], [], $admin);
        $this->assertSame(200, $response->getStatusCode());
    }

    /** @return array{0: User, 1: int} responsabile aziendale e pagina della sua azienda */
    private function companyPage(array $attributes): array
    {
        $manager = $this->createUser(['role' => 'company', 'company_id' => $this->createCompany()]);
        $pageId = $this->createPage(array_merge(['user_id' => $manager->id, 'company_id' => $manager->company_id], $attributes));
        return [$manager, $pageId];
    }

    private function domain(int $companyId, string $hostname): int
    {
        $now = date('Y-m-d H:i:s');
        return Capsule::table('domains')->insertGetId(['company_id' => $companyId, 'hostname' => $hostname, 'created_at' => $now, 'updated_at' => $now]);
    }

    private function attach(int $pageId, int $domainId): void
    {
        Capsule::table('domain_page')->insert(['page_id' => $pageId, 'domain_id' => $domainId]);
    }
}
