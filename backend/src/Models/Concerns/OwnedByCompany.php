<?php

namespace App\Models\Concerns;

use App\Models\User;

/**
 * Risorse della libreria media (file e cartelle) appartenenti a un'azienda,
 * oppure a un singolo utente se senza azienda.
 */
trait OwnedByCompany
{
    /**
     * Visibilità coerente con canViewPage: admin tutto, utenti con azienda
     * tutta l'azienda, utenti senza azienda solo le proprie risorse.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->company_id) {
            return $query->where('company_id', $user->company_id);
        }
        return $query->where('company_id', null)->where('user_id', $user->id);
    }

    /** Stesso proprietario: stessa azienda, oppure stesso utente se senza azienda */
    public function sameOwnerAs($other): bool
    {
        if ($this->company_id !== null || $other->company_id !== null) {
            return (int) $this->company_id === (int) $other->company_id;
        }
        return (int) $this->user_id === (int) $other->user_id;
    }
}
