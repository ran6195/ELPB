<?php

namespace App\Audit;

use App\Models\AuditLog;

/**
 * Testi leggibili del registro attività. Nel database ci sono solo codici stabili
 * (pattern della rotta, tipo.evento, categoria, esito): le etichette si calcolano
 * alla lettura, quindi cambiarle non richiede migrazioni.
 *
 * Ogni nuova rotta in config/routes.php va aggiunta a ROUTES (lo verifica AuditCatalogTest).
 */
class AuditCatalog
{
    public const CATEGORIES = [
        Auditor::VIEW => 'Consultazione',
        Auditor::ACTION => 'Azione',
        Auditor::CHANGE => 'Modifica',
        Auditor::ACCESS => 'Accesso',
        Auditor::PUBLISH => 'Pubblicazione',
        Auditor::SYSTEM => 'Sistema',
        Auditor::EXPORT => 'Esportazione',
    ];

    public const OUTCOMES = [
        'ok' => 'Riuscita',
        'invalid' => 'Dati non validi',
        'denied' => 'Negata',
        'not_found' => 'Non trovato',
        'expired' => 'Scaduta',
        'throttled' => 'Troppi tentativi',
        'error' => 'Errore',
    ];

    public const ROLES = [
        'admin' => 'Amministratore',
        'company' => 'Responsabile aziendale',
        'user' => 'Utente',
    ];

    public const CHANNELS = [
        'api' => 'Applicazione',
        'console' => 'Script',
        'job' => 'Elaborazione in coda',
    ];

    /** Rotte dell'API: "METODO pattern" => descrizione */
    public const ROUTES = [
        'GET /' => 'Stato dell\'API',

        'POST /api/auth/login' => 'Accesso',
        'POST /api/auth/register' => 'Creazione utente',
        'GET /api/auth/me' => 'Profilo dell\'utente',
        'POST /api/auth/logout' => 'Uscita',
        'GET /api/auth/companies' => 'Elenco aziende',
        'GET /api/auth/users' => 'Elenco utenti',

        'POST /api/admin/companies' => 'Creazione azienda',
        'DELETE /api/admin/companies/{id}' => 'Eliminazione azienda',
        'PUT /api/admin/users/{id}' => 'Modifica utente',
        'DELETE /api/admin/users/{id}' => 'Eliminazione utente',

        'POST /api/company/users' => 'Creazione utente dell\'azienda',
        'DELETE /api/company/users/{id}' => 'Eliminazione utente dell\'azienda',
        'GET /api/company/users-with-pages' => 'Utenti dell\'azienda con le pagine',
        'PUT /api/company/pages/{id}/reassign' => 'Assegnazione pagina',

        'GET /api/pages' => 'Elenco pagine',
        'GET /api/pages/archived' => 'Pagine archiviate',
        'GET /api/pages/{id}' => 'Apertura pagina',
        'POST /api/pages' => 'Creazione pagina',
        'POST /api/pages/{id}/duplicate' => 'Duplicazione pagina',
        'POST /api/pages/{id}/restore' => 'Ripristino pagina',
        'POST /api/pages/check-slug' => 'Controllo indirizzo pagina',
        'DELETE /api/pages/{id}/force' => 'Eliminazione definitiva pagina',
        'GET /api/pages/{id}/export' => 'Esportazione pagina',
        'POST /api/pages/import' => 'Importazione pagina',
        'PUT /api/pages/{id}/legal-info' => 'Modifica dati legali',
        'POST /api/pages/{id}/legal-info' => 'Modifica dati legali',
        'PUT /api/pages/{id}/notification-settings' => 'Modifica notifiche email',
        'POST /api/pages/{id}/notification-settings' => 'Modifica notifiche email',
        'PUT /api/pages/{id}/domains' => 'Modifica domini della pagina',
        'POST /api/pages/{id}/domains' => 'Modifica domini della pagina',
        'GET /api/pages/{id}/domain-suggestions' => 'Domini suggeriti',
        'GET /api/pages/{id}/renderers' => 'Siti che mostrano la pagina',
        'GET /api/renderers' => 'Registro renderer',
        'GET /api/renderers/new' => 'Diretta dei renderer',
        'GET /api/stats/summary' => 'Statistiche: riepilogo',
        'GET /api/stats/leads' => 'Statistiche: lead',
        'GET /api/stats/views' => 'Statistiche: visite',
        'GET /api/stats/conversion' => 'Statistiche: conversione',
        'PUT /api/pages/{id}' => 'Salvataggio pagina',
        'DELETE /api/pages/{id}' => 'Archiviazione pagina',

        'POST /api/upload/image' => 'Caricamento immagine',
        'POST /api/upload/video' => 'Caricamento video',

        'GET /api/media' => 'Libreria media',
        'POST /api/media/move' => 'Spostamento media',
        'POST /api/media' => 'Caricamento media',
        'GET /api/media/{id}' => 'Dettaglio media',
        'PUT /api/media/{id}' => 'Modifica media',
        'POST /api/media/{id}' => 'Modifica media',
        'GET /api/media/{id}/usage' => 'Utilizzo media',
        'GET /api/media/{id}/file' => 'File media',
        'POST /api/media/{id}/versions' => 'Nuova versione immagine',
        'POST /api/media/{id}/restore' => 'Ripristino media',
        'DELETE /api/media/{id}/force' => 'Eliminazione definitiva media',
        'DELETE /api/media/{id}' => 'Archiviazione media',

        'GET /api/media-folders' => 'Cartelle media',
        'POST /api/media-folders' => 'Creazione cartella',
        'PUT /api/media-folders/{id}' => 'Modifica cartella',
        'POST /api/media-folders/{id}' => 'Modifica cartella',
        'DELETE /api/media-folders/{id}' => 'Eliminazione cartella',

        'GET /api/domains' => 'Elenco domini',
        'POST /api/domains' => 'Creazione dominio',
        'PUT /api/domains/{id}' => 'Modifica dominio',
        'POST /api/domains/{id}' => 'Modifica dominio',
        'DELETE /api/domains/{id}' => 'Eliminazione dominio',

        'GET /api/leads' => 'Elenco lead',
        'POST /api/leads/exports' => 'Esportazione lead',
        'GET /api/leads/calendar' => 'Calendario dei lead',
        'GET /api/leads/{id}' => 'Dettaglio lead',
        'DELETE /api/leads/{id}' => 'Eliminazione lead',
        'POST /api/leads/{id}/notes' => 'Nota sul lead',
        'PUT /api/leads/{id}/appointment' => 'Appuntamento del lead',

        'GET /api/page/{slug}' => 'Pagina pubblica',
        'POST /api/leads' => 'Invio modulo contatti',
        'POST /api/track/view' => 'Visita',

        'GET /api/admin/audit-logs' => 'Registro attività',
        'GET /api/admin/audit-logs/export' => 'Esportazione registro attività',
        'GET /api/admin/audit-logs/new' => 'Diretta registro attività',
    ];

    /** Tipi di record: codice => [nome, femminile] (per "Pagina creata" / "Dominio creato") */
    public const SUBJECTS = [
        'page' => ['Pagina', true],
        'lead' => ['Lead', false],
        'media' => ['File media', false],
        'media_folder' => ['Cartella media', true],
        'domain' => ['Dominio', false],
        'company' => ['Azienda', true],
        'user' => ['Utente', false],
    ];

    /** Eventi dei modelli: codice => [maschile, femminile] */
    private const MODEL_EVENTS = [
        'created' => ['creato', 'creata'],
        'updated' => ['modificato', 'modificata'],
        'deleted' => ['eliminato', 'eliminata'],
        'force_deleted' => ['eliminato definitivamente', 'eliminata definitivamente'],
        'restored' => ['ripristinato', 'ripristinata'],
    ];

    /** Modelli archiviabili: "deleted" è un'archiviazione */
    private const ARCHIVABLE = ['page', 'media'];

    /** Eventi espliciti di modifica, pubblicazione, accesso e sistema */
    public const EVENTS = [
        'page.domains_updated' => 'Domini della pagina aggiornati',
        'page.published' => 'Pagina pubblicata',
        'page.unpublished' => 'Pagina ritirata',
        'page.reassigned' => 'Pagina assegnata',
        'login' => 'Accesso',
        'login_failed' => 'Accesso non riuscito',
        'logout' => 'Uscita',
        'lead.received' => 'Lead ricevuto',
        'lead.duplicate' => 'Lead doppio ignorato',
        'lead.exported' => 'Lead esportati',
        'script' => 'Script eseguito',
        'audit_prune' => 'Pulizia del registro',
    ];

    /** Motivo dell'aggiornamento dei domini di una pagina */
    public const DOMAIN_REASONS = [
        'manual' => 'dalle impostazioni della pagina',
        'domain_created' => 'nuovo dominio associato',
        'domain_deleted' => 'dominio eliminato',
        'page_reassigned' => 'pagina assegnata a un\'altra azienda',
    ];

    /** Esito della notifica email di un lead */
    public const NOTIFICATIONS = [
        'sent' => 'email inviata',
        'failed' => 'invio email non riuscito',
        'error' => 'errore nell\'invio email',
        'disabled' => 'notifiche disattivate',
        'not_configured' => 'notifiche non configurate',
        'no_page' => 'senza pagina',
    ];

    /** Descrizione della voce, scelta in base alla categoria */
    public static function describe(AuditLog $log): string
    {
        $action = (string) $log->action;

        if (isset(self::EVENTS[$action])) {
            return self::EVENTS[$action];
        }
        if ($log->category === Auditor::CHANGE && ($text = self::modelEvent($action))) {
            return $text;
        }
        if (isset(self::ROUTES[$action])) {
            return self::ROUTES[$action];
        }
        // Rotta non in catalogo: "METODO url"
        if ($log->method && $log->url) {
            return $log->method . ' ' . (parse_url($log->url, PHP_URL_PATH) ?: $log->url);
        }
        return $action;
    }

    /** "page.updated" → "Pagina modificata"; "page.deleted" → "Pagina archiviata" */
    public static function modelEvent(string $action): ?string
    {
        if (!preg_match('/^([a-z_]+)\.([a-z_]+)$/', $action, $m) || !isset(self::SUBJECTS[$m[1]], self::MODEL_EVENTS[$m[2]])) {
            return null;
        }
        [$name, $feminine] = self::SUBJECTS[$m[1]];
        $event = self::MODEL_EVENTS[$m[2]][$feminine ? 1 : 0];
        if ($m[2] === 'deleted' && in_array($m[1], self::ARCHIVABLE, true)) {
            $event = $feminine ? 'archiviata' : 'archiviato';
        }
        return $name . ' ' . $event;
    }

    public static function category(?string $code): string
    {
        return self::CATEGORIES[$code] ?? (string) $code;
    }

    public static function outcome(?string $code): string
    {
        return $code === null ? '' : (self::OUTCOMES[$code] ?? $code);
    }

    public static function role(?string $code): string
    {
        return $code === null ? '' : (self::ROLES[$code] ?? $code);
    }

    public static function channel(?string $code): string
    {
        return self::CHANNELS[$code] ?? (string) $code;
    }

    public static function subject(?string $type): string
    {
        return $type === null ? '' : (self::SUBJECTS[$type][0] ?? $type);
    }

    /** Tutte le etichette, per i filtri e le legende del frontend */
    public static function labels(): array
    {
        return [
            'categories' => self::CATEGORIES,
            'outcomes' => self::OUTCOMES,
            'roles' => self::ROLES,
            'channels' => self::CHANNELS,
            'subjects' => array_map(fn($subject) => $subject[0], self::SUBJECTS),
            'domain_reasons' => self::DOMAIN_REASONS,
            'notifications' => self::NOTIFICATIONS,
        ];
    }
}
