<?php

namespace Tests\Feature;

use App\Audit\Auditor;
use App\Models\AuditLog;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Psr7\UploadedFile;
use Tests\Fixtures\AuditedCompany;
use Tests\TestCase;

class AuditorTest extends TestCase
{
    private Auditor $auditor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditor = Auditor::get();
    }

    public function test_i_segreti_sono_mascherati_anche_negli_array_annidati(): void
    {
        $clean = Auditor::clean([
            'email' => 'a@b.it',
            'password' => 'segreta',
            'recaptcha_settings' => ['site_key' => 'pubblica', 'secret_key' => 'privata'],
            'Authorization' => 'Bearer xyz',
            'nested' => ['api_key' => 'k', 'ok' => 1],
        ]);

        $this->assertSame('a@b.it', $clean['email']);
        $this->assertSame('•••', $clean['password']);
        $this->assertSame('pubblica', $clean['recaptcha_settings']['site_key']);
        $this->assertSame('•••', $clean['recaptcha_settings']['secret_key']);
        $this->assertSame('•••', $clean['Authorization']);
        $this->assertSame('•••', $clean['nested']['api_key']);
    }

    public function test_profondita_testi_lunghi_oggetti_e_file(): void
    {
        $file = new UploadedFile('/tmp/inesistente', 'foto.jpg', 'image/jpeg', 10, UPLOAD_ERR_OK);
        $clean = Auditor::clean([
            'l2' => ['l3' => ['l4' => ['x' => 1]]],
            'testo' => str_repeat('a', 2500),
            'data' => new \DateTime('2026-10-27 10:00:00'),
            'oggetto' => new \stdClass(),
            'file' => $file,
        ]);

        $this->assertSame('[…]', $clean['l2']['l3']['l4']);
        $this->assertSame(2000 + mb_strlen('… [troncato]'), mb_strlen($clean['testo']));
        $this->assertStringEndsWith('… [troncato]', $clean['testo']);
        $this->assertSame('2026-10-27 10:00:00', $clean['data']);
        $this->assertSame('[stdClass]', $clean['oggetto']);
        $this->assertSame('foto.jpg', $clean['file']);
    }

    public function test_url_senza_parametri_segreti(): void
    {
        $this->assertSame('https://x.it/api/a?page=2', Auditor::cleanUrl('https://x.it/api/a?page=2&token=abc&signature=s&code=c&state=st'));
        $this->assertSame('https://x.it/api/a', Auditor::cleanUrl('https://x.it/api/a?token=abc'));
    }

    public function test_la_voce_copia_utente_richiesta_e_millisecondi(): void
    {
        $companyId = Capsule::table('companies')->insertGetId(['name' => 'Azienda', 'email' => 'az@example.test']);
        $user = $this->createUser(['name' => 'Mario', 'email' => 'mario@example.test', 'role' => 'company', 'company_id' => $companyId]);
        $requestId = $this->auditor->beginRequest('api', '10.0.0.1', 'Browser di prova');
        $this->auditor->setUser($user);

        $this->auditor->record(Auditor::VIEW, 'GET /api/pages', ['at' => 1793095200.123456, 'properties' => ['token' => 'x']]);
        $this->assertSame(0, AuditLog::count(), 'Nessuna scrittura prima del flush');
        $this->auditor->flush();

        $entry = AuditLog::first();
        $this->assertSame($requestId, $entry->request_id);
        $this->assertSame(26, strlen($requestId));
        $this->assertSame(['api', 'view', 'GET /api/pages'], [$entry->channel, $entry->category, $entry->action]);
        $this->assertSame([$user->id, 'Mario', 'mario@example.test', 'company', $companyId], [$entry->user_id, $entry->user_name, $entry->user_email, $entry->user_role, $entry->company_id]);
        $this->assertSame(['10.0.0.1', 'Browser di prova'], [$entry->ip, $entry->user_agent]);
        $this->assertSame(['token' => '•••'], $entry->properties);
        $this->assertSame(date('Y-m-d H:i:s', 1793095200) . '.123', $entry->toArray()['created_at']);
    }

    public function test_autore_esplicito_null_significa_sistema(): void
    {
        $this->auditor->setUser($this->createUser());
        $this->auditor->record(Auditor::SYSTEM, 'audit_prune', ['user' => null]);
        $this->auditor->flush();

        $this->assertNull(AuditLog::first()->user_id);
    }

    public function test_un_errore_di_scrittura_non_si_propaga(): void
    {
        $this->auditor->record(Auditor::VIEW, 'ok');
        $this->auditor->record(Auditor::VIEW, 'rotta', ['status' => 'non-un-numero']);

        $this->auditor->flush();

        $this->assertSame([], $this->auditor->pending());
    }

    public function test_oltre_200_voci_scrive_subito(): void
    {
        for ($i = 0; $i < 201; $i++) {
            $this->auditor->record(Auditor::SYSTEM, 'riga');
        }

        $this->assertSame(201, AuditLog::count());
        $this->assertSame([], $this->auditor->pending());
    }

    public function test_modifica_registrata_campo_per_campo_con_lo_stesso_request_id(): void
    {
        $company = $this->auditor->withoutAuditing(fn() => AuditedCompany::create(['name' => 'Prima', 'email' => 'a@example.test']));
        $requestId = $this->auditor->beginRequest('api', '127.0.0.1', 'test');

        $company->update(['name' => 'Dopo', 'email' => 'a@example.test']);
        $this->auditor->flush();

        $entry = AuditLog::sole();
        $this->assertSame('company.updated', $entry->action);
        $this->assertSame('change', $entry->category);
        $this->assertSame($requestId, $entry->request_id);
        $this->assertEquals(['old' => ['name' => 'Prima'], 'new' => ['name' => 'Dopo']], $entry->properties);
        $this->assertSame(['company', $company->id, 'Dopo', $company->id], [$entry->subject_type, $entry->subject_id, $entry->subject_label, $entry->company_id]);
    }

    public function test_una_modifica_senza_cambiamenti_non_si_registra(): void
    {
        $company = $this->auditor->withoutAuditing(fn() => AuditedCompany::create(['name' => 'Uguale', 'email' => 'a@example.test']));

        $company->update(['name' => 'Uguale']);
        $this->auditor->flush();

        $this->assertSame(0, AuditLog::count());
    }

    public function test_creazione_seguita_da_modifica_produce_un_solo_evento(): void
    {
        $company = AuditedCompany::create(['name' => 'Nuova', 'email' => 'prima@example.test']);
        $company->email = 'dopo@example.test';
        $company->save();
        $this->auditor->flush();

        $entry = AuditLog::sole();
        $this->assertSame('company.created', $entry->action);
        $this->assertSame('Nuova', $entry->properties['new']['name']);
        $this->assertSame('dopo@example.test', $entry->properties['new']['email']);
    }

    public function test_le_modifiche_annullate_da_un_rollback_non_si_registrano(): void
    {
        try {
            Capsule::connection()->transaction(function () {
                AuditedCompany::create(['name' => 'Annullata', 'email' => 'x@example.test']);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $e) {
        }
        Capsule::connection()->transaction(fn() => AuditedCompany::create(['name' => 'Confermata', 'email' => 'y@example.test']));
        $this->auditor->flush();

        $this->assertSame(['Confermata'], AuditLog::pluck('subject_label')->all());
    }

    public function test_eliminazione_registra_i_valori_precedenti(): void
    {
        $company = $this->auditor->withoutAuditing(fn() => AuditedCompany::create(['name' => 'Da eliminare', 'email' => 'z@example.test']));

        $company->delete();
        $this->auditor->flush();

        $entry = AuditLog::sole();
        $this->assertSame('company.deleted', $entry->action);
        $this->assertSame('Da eliminare', $entry->properties['old']['name']);
    }

    public function test_sorgente_annidata_e_registrazione_sospesa(): void
    {
        $this->auditor->enterSource('console', 'backfill-lead-domains');
        $this->auditor->record(Auditor::SYSTEM, 'script');
        $this->auditor->leaveSource();
        $this->auditor->withoutAuditing(fn() => $this->auditor->record(Auditor::SYSTEM, 'ignorata'));
        $this->auditor->flush();

        $entry = AuditLog::sole();
        $this->assertSame('console', $entry->channel);
        $this->assertSame(['source' => 'backfill-lead-domains'], $entry->properties);
    }
}
