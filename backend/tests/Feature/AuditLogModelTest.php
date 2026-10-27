<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Database\Capsule\Manager as Capsule;
use LogicException;
use Tests\TestCase;

/**
 * Le voci del registro sono immutabili; la pulizia toglie solo quelle oltre 24 mesi.
 */
class AuditLogModelTest extends TestCase
{
    private function insertEntry(string $createdAt): int
    {
        return Capsule::table('audit_logs')->insertGetId([
            'created_at' => $createdAt,
            'channel' => 'api',
            'category' => 'view',
            'action' => 'GET /api/pages',
        ]);
    }

    public function test_una_voce_non_si_modifica(): void
    {
        $entry = AuditLog::find($this->insertEntry(date('Y-m-d H:i:s')));

        $this->expectException(LogicException::class);
        $entry->update(['action' => 'altro']);
    }

    public function test_una_voce_non_si_elimina(): void
    {
        $entry = AuditLog::find($this->insertEntry(date('Y-m-d H:i:s')));

        try {
            $entry->delete();
            $this->fail('Eliminazione non bloccata');
        } catch (LogicException $e) {
            $this->assertSame(1, AuditLog::count());
        }
    }

    public function test_la_pulizia_toglie_solo_le_voci_oltre_24_mesi(): void
    {
        $this->insertEntry(date('Y-m-d H:i:s', strtotime('-25 months')));
        $this->insertEntry(date('Y-m-d H:i:s', strtotime('-24 months -1 day')));
        $recent = $this->insertEntry(date('Y-m-d H:i:s', strtotime('-23 months')));
        $today = $this->insertEntry(date('Y-m-d H:i:s'));

        $this->assertSame(2, AuditLog::prune());
        $this->assertSame([$recent, $today], AuditLog::orderBy('id')->pluck('id')->all());
    }

    public function test_la_data_conserva_i_millisecondi(): void
    {
        $entry = AuditLog::find($this->insertEntry('2026-10-27 10:15:30.123'));

        $this->assertSame('2026-10-27 10:15:30.123', $entry->toArray()['created_at']);
    }
}
