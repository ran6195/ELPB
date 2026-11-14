<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Services\PublicationHistory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Storico della pubblicazione: un periodo per ogni tratto online di una pagina per
 * un'azienda, aperto e chiuso dagli agganci del modello Page; riallineamento e stima
 * dell'inizio per le pagine pubblicate prima dello storico.
 */
class PublicationHistoryTest extends TestCase
{
    public function test_pubblicazione_e_ritiro_aprono_e_chiudono_il_periodo(): void
    {
        [$manager, $pageId] = $this->companyPage(['is_published' => 0]);
        $this->attach($pageId, $this->domain($manager->company_id, 'cliente.it'));

        $this->request('PUT', "/api/pages/$pageId", ['is_published' => true], [], $manager);
        $periods = $this->periods($pageId);
        $this->assertCount(1, $periods);
        $this->assertNull($periods[0]->ended_at);
        $this->assertSame(PublicationHistory::SOURCE_LIVE, $periods[0]->start_source);
        $this->assertSame($manager->company_id, (int) $periods[0]->company_id);

        // Altri salvataggi della pagina pubblicata non toccano lo storico
        $this->request('PUT', "/api/pages/$pageId", ['title' => 'Nuovo titolo', 'is_published' => true], [], $manager);
        $this->assertCount(1, $this->periods($pageId));

        $this->request('PUT', "/api/pages/$pageId", ['is_published' => false], [], $manager);
        $periods = $this->periods($pageId);
        $this->assertCount(1, $periods);
        $this->assertNotNull($periods[0]->ended_at);
        $this->assertSame(PublicationHistory::END_UNPUBLISHED, $periods[0]->end_reason);

        // Una nuova pubblicazione apre un secondo periodo
        $this->request('PUT', "/api/pages/$pageId", ['is_published' => true], [], $manager);
        $periods = $this->periods($pageId);
        $this->assertCount(2, $periods);
        $this->assertNull($periods[1]->ended_at);
    }

    public function test_bozza_creata_non_apre_periodi(): void
    {
        $page = Page::create(['title' => 'Bozza', 'slug' => 'bozza-' . uniqid(), 'is_published' => false]);
        $this->assertCount(0, $this->periods($page->id));
    }

    public function test_archiviazione_chiude_ripristino_riapre_eliminazione_lascia_lo_storico(): void
    {
        [$manager, $pageId] = $this->companyPage(['is_published' => 0]);
        $page = Page::find($pageId);
        $page->update(['is_published' => true]);

        $this->assertSame(200, $this->request('DELETE', "/api/pages/$pageId", null, [], $manager)->getStatusCode());
        $periods = $this->periods($pageId);
        $this->assertSame(PublicationHistory::END_ARCHIVED, $periods[0]->end_reason);

        // Ripristinata ancora pubblicata: torna online, nuovo periodo
        $this->assertSame(200, $this->request('POST', "/api/pages/$pageId/restore", null, [], $manager)->getStatusCode());
        $periods = $this->periods($pageId);
        $this->assertCount(2, $periods);
        $this->assertNull($periods[1]->ended_at);

        // Archiviata ed eliminata definitivamente: i periodi restano per la fatturazione
        $this->request('DELETE', "/api/pages/$pageId", null, [], $manager);
        $this->assertSame(200, $this->request('DELETE', "/api/pages/$pageId/force", null, [], $manager)->getStatusCode());
        $this->assertNull(Capsule::table('pages')->where('id', $pageId)->first());
        $periods = $this->periods($pageId);
        $this->assertCount(2, $periods);
        $this->assertNotNull($periods[1]->ended_at);
    }

    public function test_passaggio_ad_altra_azienda_chiude_e_apre_per_la_nuova(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        [, $pageId] = $this->companyPage(['is_published' => 0]);
        Page::find($pageId)->update(['is_published' => true]);
        $otherCompany = $this->createCompany('Altra agenzia');
        $newOwner = $this->createUser(['company_id' => $otherCompany]);

        $response = $this->request('PUT', "/api/company/pages/$pageId/reassign", ['user_id' => $newOwner->id], [], $admin);
        $this->assertSame(200, $response->getStatusCode());

        $periods = $this->periods($pageId);
        $this->assertCount(2, $periods);
        $this->assertSame(PublicationHistory::END_REASSIGNED, $periods[0]->end_reason);
        $this->assertNull($periods[1]->ended_at);
        $this->assertSame($otherCompany, (int) $periods[1]->company_id);
    }

    public function test_inizio_stimato_registro_poi_prima_visita_poi_adesso(): void
    {
        // Pagine pubblicate prima dello storico: inserite senza passare dal modello
        $fromAudit = $this->createPage();
        $fromViews = $this->createPage();
        $noTrace = $this->createPage();

        Capsule::table('audit_logs')->insert([
            'created_at' => '2026-11-03 10:15:00.000', 'channel' => 'api', 'category' => 'publish',
            'action' => 'page.published', 'page_id' => $fromAudit,
        ]);
        // Il registro vince anche su visite più vecchie (la pagina può essere stata ritirata e ripubblicata)
        $this->view($fromAudit, '2026-10-29');

        $this->view($fromViews, '2026-11-02');
        $this->view($fromViews, '2026-10-30');
        // Richiesta di un bot più vecchia: non conta
        $this->hit($fromViews, '2026-10-20 08:00:00', '66.249.66.1', null);
        // Richiesta di un renderer più vecchia delle visite: vince lei
        $this->hit($fromViews, '2026-10-28 18:30:00', '93.186.242.100', '2.1.1');

        $this->assertSame(['2026-11-03 10:15:00', PublicationHistory::SOURCE_AUDIT], PublicationHistory::inferStart(Page::find($fromAudit)));
        $this->assertSame(['2026-10-28 18:30:00', PublicationHistory::SOURCE_FIRST_HIT], PublicationHistory::inferStart(Page::find($fromViews)));

        Capsule::table('renderer_hits')->where('page_id', $fromViews)->delete();
        $this->assertSame(['2026-10-30 00:00:00', PublicationHistory::SOURCE_FIRST_VIEW], PublicationHistory::inferStart(Page::find($fromViews)));

        [, $source] = PublicationHistory::inferStart(Page::find($noTrace));
        $this->assertSame(PublicationHistory::SOURCE_BACKFILL, $source);
    }

    public function test_riallineamento_prova_senza_scritture_poi_apre_e_chiude(): void
    {
        $live = $this->createPage(['is_published' => 1]);
        $this->view($live, '2026-10-30');
        $draft = $this->createPage(['is_published' => 0]);
        // Periodo rimasto aperto di una pagina ritirata senza passare dal modello
        Capsule::table('page_publications')->insert([
            'page_id' => $draft, 'started_at' => '2026-11-01 09:00:00', 'start_source' => 'live',
        ]);
        // Periodo di una pagina eliminata del tutto
        Capsule::table('page_publications')->insert([
            'page_id' => 999999, 'started_at' => '2026-11-01 09:00:00', 'start_source' => 'live',
        ]);

        $actions = PublicationHistory::reconcile(false);
        $this->assertCount(3, $actions);
        $this->assertCount(0, $this->periods($live));
        $this->assertNull(PublicationHistory::openPeriod($draft)->ended_at);

        PublicationHistory::reconcile(true);
        $periods = $this->periods($live);
        $this->assertCount(1, $periods);
        $this->assertSame('2026-10-30 00:00:00', $periods[0]->started_at);
        $this->assertSame(PublicationHistory::SOURCE_FIRST_VIEW, $periods[0]->start_source);
        $this->assertSame(PublicationHistory::END_RECONCILED, $this->periods($draft)[0]->end_reason);
        $this->assertSame(PublicationHistory::END_DELETED, $this->periods(999999)[0]->end_reason);

        // Rieseguito non fa più nulla
        $this->assertSame([], PublicationHistory::reconcile(true));
    }

    private function periods(int $pageId): array
    {
        return Capsule::table('page_publications')->where('page_id', $pageId)->orderBy('id')->get()->all();
    }

    private function view(int $pageId, string $day): void
    {
        Capsule::table('page_views_daily')->insert(['page_id' => $pageId, 'host' => 'cliente.it', 'day' => $day, 'views' => 3, 'visitors' => 2]);
    }

    private function hit(int $pageId, string $at, string $ip, ?string $version): void
    {
        Capsule::table('renderer_hits')->insert([
            'page_id' => $pageId, 'host' => $version ? 'cliente.it' : '', 'renderer' => 'standalone',
            'version' => $version, 'ip' => $ip, 'hits' => 1, 'first_seen_at' => $at, 'last_seen_at' => $at,
        ]);
    }

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
