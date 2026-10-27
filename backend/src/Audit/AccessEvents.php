<?php

namespace App\Audit;

use App\Models\User;

/**
 * Accessi all'applicazione (categoria access, record = utente, etichetta = email).
 * Con JWT non c'è una sessione: il logout lo segnala il frontend prima di cancellare il token.
 */
class AccessEvents
{
    public static function login(User $user): void
    {
        // Dopo il login l'autore della richiesta è l'utente appena entrato
        Auditor::get()->setUser($user);
        self::record('login', $user, 'ok');
    }

    public static function loginFailed(string $email, ?User $knownUser): void
    {
        Auditor::get()->record(Auditor::ACCESS, 'login_failed', [
            'user' => null,
            'company_id' => $knownUser?->company_id,
            'subject_type' => $knownUser ? 'user' : null,
            'subject_id' => $knownUser?->id,
            'subject_label' => $knownUser?->email,
            'outcome' => 'denied',
            'properties' => ['email' => mb_substr($email, 0, 255), 'known_user' => $knownUser !== null],
        ]);
    }

    public static function logout(User $user): void
    {
        self::record('logout', $user, 'ok');
    }

    private static function record(string $action, User $user, string $outcome): void
    {
        Auditor::get()->record(Auditor::ACCESS, $action, [
            'user' => $user,
            'company_id' => $user->company_id,
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'subject_label' => $user->email,
            'outcome' => $outcome,
        ]);
    }
}
