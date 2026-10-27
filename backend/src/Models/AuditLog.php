<?php

namespace App\Models;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Voce del registro attività. Immutabile: modifiche ed eliminazioni del singolo record
 * lanciano LogicException. L'unica cancellazione è la pulizia di massa (prune) delle
 * voci più vecchie di MONTHS mesi.
 *
 * Le voci si scrivono con App\Audit\Auditor (INSERT multiplo), non con questo modello.
 */
class AuditLog extends Model
{
    /** Mesi di conservazione */
    public const MONTHS = 24;

    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'properties' => 'array',
        'user_id' => 'integer',
        'company_id' => 'integer',
        'page_id' => 'integer',
        'subject_id' => 'integer',
        'status' => 'integer',
        'duration_ms' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Le voci del registro attività non si modificano');
        });
        static::deleting(function () {
            throw new LogicException('Le voci del registro attività non si eliminano');
        });
    }

    /** Data in ora italiana con i millisecondi (la colonna è datetime(3)) */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s.v');
    }

    /**
     * Cancella le voci oltre il periodo di conservazione, a blocchi per non tenere
     * la tabella bloccata a lungo. Restituisce il numero di voci cancellate.
     */
    public static function prune(): int
    {
        $limit = date('Y-m-d H:i:s', strtotime('-' . self::MONTHS . ' months'));
        $total = 0;
        do {
            // Query di massa: non passa dagli eventi del modello (che la bloccherebbero)
            $deleted = Capsule::table('audit_logs')->where('created_at', '<', $limit)->limit(1000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
