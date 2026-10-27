<?php

namespace App\Audit;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registra nel registro attività creazione, modifica (campo per campo), eliminazione
 * e ripristino del modello.
 *
 * Il modello può definire:
 * - auditType(): codice del tipo di record (default: nome della classe in snake_case)
 * - auditLabel(): etichetta leggibile copiata nella voce (es. email, titolo)
 * - auditIgnored(): colonne da non registrare (oltre a created_at/updated_at)
 *
 * Attenzione: le query di massa (Model::where()->update()/delete(), sync() delle
 * relazioni) non passano dagli eventi e non finiscono nel registro. Sui modelli
 * auditati, quando serve traccia, aggiornare o cancellare un record alla volta
 * oppure registrare un evento esplicito con Auditor::record().
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(fn($model) => Auditor::get()->modelChanged($model, $event));
        }
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn($model) => Auditor::get()->modelChanged($model, 'restored'));
        }
    }
}
