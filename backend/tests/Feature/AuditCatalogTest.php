<?php

namespace Tests\Feature;

use App\Audit\AuditCatalog;
use App\Audit\Auditor;
use App\Models\AuditLog;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Catalogo delle etichette e pulizia automatica del registro.
 */
class AuditCatalogTest extends TestCase
{
    public function test_ogni_rotta_dell_api_ha_una_descrizione(): void
    {
        $missing = [];
        foreach ($this->app->getRouteCollector()->getRoutes() as $route) {
            foreach ($route->getMethods() as $method) {
                $action = $method . ' ' . $route->getPattern();
                if ($method !== 'OPTIONS' && !isset(AuditCatalog::ROUTES[$action])) {
                    $missing[] = $action;
                }
            }
        }

        $this->assertSame([], $missing, 'Rotte da aggiungere a AuditCatalog::ROUTES');
    }

    public function test_descrizioni_per_categoria(): void
    {
        $cases = [
            ['change', 'page.updated', null, null, 'Pagina modificata'],
            ['change', 'page.deleted', null, null, 'Pagina archiviata'],
            ['change', 'page.force_deleted', null, null, 'Pagina eliminata definitivamente'],
            ['change', 'domain.deleted', null, null, 'Dominio eliminato'],
            ['change', 'media_folder.created', null, null, 'Cartella media creata'],
            ['change', 'page.domains_updated', null, null, 'Domini della pagina aggiornati'],
            ['publish', 'page.published', null, null, 'Pagina pubblicata'],
            ['access', 'login_failed', null, null, 'Accesso non riuscito'],
            ['system', 'lead.received', null, null, 'Lead ricevuto'],
            ['action', 'PUT /api/pages/{id}', 'PUT', 'http://x/api/pages/5', 'Salvataggio pagina'],
            ['view', 'GET /api/nuova', 'GET', 'http://x/api/nuova?p=1', 'GET /api/nuova'],
        ];

        foreach ($cases as [$category, $action, $method, $url, $expected]) {
            $log = new AuditLog(['category' => $category, 'action' => $action, 'method' => $method, 'url' => $url]);
            $this->assertSame($expected, AuditCatalog::describe($log), $action);
        }
    }

    public function test_etichette_di_codici_sconosciuti_restano_il_codice(): void
    {
        $this->assertSame('Negata', AuditCatalog::outcome('denied'));
        $this->assertSame('nuovo_esito', AuditCatalog::outcome('nuovo_esito'));
        $this->assertSame('Responsabile aziendale', AuditCatalog::role('company'));
        $this->assertSame('', AuditCatalog::role(null));
    }

    public function test_pulizia_automatica_dopo_la_scrittura(): void
    {
        Capsule::table('audit_logs')->insert([
            ['created_at' => date('Y-m-d H:i:s', strtotime('-25 months')), 'channel' => 'api', 'category' => 'view', 'action' => 'vecchia'],
            ['created_at' => date('Y-m-d H:i:s', strtotime('-1 month')), 'channel' => 'api', 'category' => 'view', 'action' => 'recente'],
        ]);

        Auditor::get()->record(Auditor::VIEW, 'nuova');
        Auditor::get()->flush();

        $this->assertSame(['recente', 'nuova', 'audit_prune'], AuditLog::orderBy('id')->pluck('action')->all());
        $prune = AuditLog::where('action', 'audit_prune')->first();
        $this->assertSame(['system', null], [$prune->category, $prune->user_id]);
        $this->assertEquals(['deleted' => 1, 'months' => 24], $prune->properties);
    }

    public function test_senza_voci_vecchie_nessuna_pulizia(): void
    {
        Auditor::get()->record(Auditor::VIEW, 'nuova');
        Auditor::get()->flush();

        $this->assertSame(['nuova'], AuditLog::pluck('action')->all());
    }
}
