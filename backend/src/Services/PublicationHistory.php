<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Storico della pubblicazione delle pagine, base della fatturazione per pagina pubblicata.
 *
 * Un periodo (tabella page_publications) copre un tratto in cui la pagina è stata online
 * per una stessa azienda. Online = pubblicata e non archiviata: sono le sole pagine che
 * `/api/page/{slug}` restituisce ai renderer. Il periodo si apre alla pubblicazione e si
 * chiude al ritiro, all'archiviazione, all'eliminazione definitiva o al passaggio della
 * pagina a un'altra azienda (che apre subito un nuovo periodo per la nuova azienda).
 *
 * Gli agganci sono nel modello Page, quindi valgono per qualunque rotta o script che
 * salvi la pagina con Eloquent. Gli aggiornamenti di massa (query builder) non passano
 * dal modello: il riallineamento (reconcile) li recupera.
 */
class PublicationHistory
{
    public const TABLE = 'page_publications';

    /** Origine della data di inizio */
    public const SOURCE_LIVE = 'live';             // registrata al momento della pubblicazione
    public const SOURCE_AUDIT = 'audit_log';       // ultima pubblicazione nel registro attività
    public const SOURCE_FIRST_VIEW = 'first_view'; // primo giorno con visite registrate
    public const SOURCE_FIRST_HIT = 'first_hit';   // prima richiesta di un renderer
    public const SOURCE_BACKFILL = 'backfill';     // nessuna traccia: momento del recupero

    /** Motivo della chiusura */
    public const END_UNPUBLISHED = 'unpublished';
    public const END_ARCHIVED = 'archived';
    public const END_DELETED = 'deleted';
    public const END_REASSIGNED = 'reassigned';
    public const END_RECONCILED = 'reconciled';

    /** Eventi del registro attività dopo i quali la pagina è online per l'azienda attuale */
    private const AUDIT_START_ACTIONS = ['page.published', 'page.restored', 'page.reassigned'];

    public static function isLive(Page $page): bool
    {
        return (bool) $page->is_published && !$page->trashed();
    }

    /**
     * Allinea lo storico allo stato attuale della pagina (dagli agganci del modello).
     * Non solleva mai eccezioni: il salvataggio della pagina non deve fallire per lo storico;
     * le pagine rimaste indietro le trova il riallineamento.
     */
    public static function sync(Page $page, string $endReason, ?bool $live = null): void
    {
        try {
            self::apply($page->id, $page->company_id, $live ?? self::isLive($page), $endReason, self::now());
        } catch (\Throwable $e) {
            error_log("PublicationHistory: storico della pagina #{$page->id} non aggiornato: " . $e->getMessage());
        }
    }

    /** Periodo aperto della pagina (il più recente, se per errore ce ne fossero più d'uno) */
    public static function openPeriod(int $pageId): ?object
    {
        return Capsule::table(self::TABLE)
            ->where('page_id', $pageId)
            ->whereNull('ended_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Riallinea lo storico a tutte le pagine: apre il periodo delle pagine online che non
     * lo hanno (con la data d'inizio stimata da inferStart) e chiude quelli delle pagine
     * non più online o passate a un'altra azienda. Con $execute = false non scrive nulla.
     *
     * @return array<int, array{page_id:int, title:string, action:string, at:string, source?:string, reason?:string}>
     */
    public static function reconcile(bool $execute): array
    {
        $now = self::now();
        $actions = [];
        $pages = Page::withTrashed()->get(['id', 'title', 'is_published', 'company_id', 'deleted_at'])->keyBy('id');

        // Periodi aperti di pagine non più online, eliminate o di un'altra azienda
        $openPeriods = Capsule::table(self::TABLE)->whereNull('ended_at')->get();
        foreach ($openPeriods as $period) {
            $page = $pages->get($period->page_id);
            $live = $page && self::isLive($page);
            if ($live && (int) $period->company_id === (int) $page->company_id) {
                continue;
            }
            $reason = !$page ? self::END_DELETED : ($live ? self::END_REASSIGNED : self::END_RECONCILED);
            $actions[] = ['page_id' => (int) $period->page_id, 'title' => $page->title ?? '(eliminata)', 'action' => 'close', 'at' => $now, 'reason' => $reason];
            if ($execute) {
                Capsule::table(self::TABLE)->where('id', $period->id)
                    ->update(['ended_at' => $now, 'end_reason' => $reason, 'updated_at' => $now]);
            }
        }

        // Pagine online senza periodo aperto (dopo le chiusure sopra)
        foreach ($pages as $page) {
            if (!self::isLive($page)) {
                continue;
            }
            $open = $execute ? self::openPeriod($page->id) : self::openPeriodAfter($page->id, $actions);
            if ($open) {
                continue;
            }
            $closedNow = (bool) array_filter($actions, fn($a) => $a['page_id'] === $page->id && $a['action'] === 'close');
            [$startedAt, $source] = $closedNow ? [$now, self::SOURCE_BACKFILL] : self::inferStart($page);
            $actions[] = ['page_id' => $page->id, 'title' => $page->title, 'action' => 'open', 'at' => $startedAt, 'source' => $source];
            if ($execute) {
                self::insert($page->id, $page->company_id, $startedAt, $source, $now);
            }
        }

        return $actions;
    }

    /**
     * Inizio stimato della pubblicazione in corso di una pagina senza storico.
     *
     * 1. l'ultimo evento del registro attività dopo il quale la pagina è online per l'azienda
     *    attuale (pubblicata, ripristinata, riassegnata): dato esatto;
     * 2. altrimenti il primo segno che un sito l'ha mostrata: il primo giorno con visite o la
     *    prima richiesta di un renderer (esclusi bot), il più vecchio dei due. Entrambi si
     *    registrano solo per pagine pubblicate;
     * 3. altrimenti il momento del recupero.
     *
     * @return array{0:string, 1:string} [data e ora, origine]
     */
    public static function inferStart(Page $page): array
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('audit_logs')) {
            $event = Capsule::table('audit_logs')
                ->where('page_id', $page->id)
                ->whereIn('action', self::AUDIT_START_ACTIONS)
                ->max('created_at');
            if ($event) {
                return [substr((string) $event, 0, 19), self::SOURCE_AUDIT];
            }
        }

        $candidates = [];
        if ($schema->hasTable('page_views_daily')) {
            $day = Capsule::table('page_views_daily')->where('page_id', $page->id)->min('day');
            if ($day) {
                $candidates[] = [substr((string) $day, 0, 10) . ' 00:00:00', self::SOURCE_FIRST_VIEW];
            }
        }
        if ($schema->hasTable('renderer_hits')) {
            $hits = Capsule::table('renderer_hits')->where('page_id', $page->id)
                ->whereNotNull('first_seen_at')
                ->get(['version', 'ip', 'user_agent', 'first_seen_at'])
                ->reject(fn($hit) => TrackingService::isAutomated($hit->version, $hit->ip, $hit->user_agent));
            if ($hits->isNotEmpty()) {
                $candidates[] = [substr((string) $hits->min('first_seen_at'), 0, 19), self::SOURCE_FIRST_HIT];
            }
        }
        if ($candidates) {
            usort($candidates, fn($a, $b) => strcmp($a[0], $b[0]));
            return $candidates[0];
        }

        return [self::now(), self::SOURCE_BACKFILL];
    }

    /**
     * Chiude il periodo aperto se la pagina non è più online o ha cambiato azienda,
     * e ne apre uno se è online senza periodo aperto.
     */
    private static function apply(int $pageId, ?int $companyId, bool $live, string $endReason, string $now): void
    {
        $open = self::openPeriod($pageId);
        if ($open && (!$live || (int) $open->company_id !== (int) $companyId)) {
            Capsule::table(self::TABLE)->where('page_id', $pageId)->whereNull('ended_at')->update([
                'ended_at' => $now,
                'end_reason' => $live ? self::END_REASSIGNED : $endReason,
                'updated_at' => $now,
            ]);
            $open = null;
        }
        if ($live && !$open) {
            self::insert($pageId, $companyId, $now, self::SOURCE_LIVE, $now);
        }
    }

    private static function insert(int $pageId, ?int $companyId, string $startedAt, string $source, string $now): void
    {
        Capsule::table(self::TABLE)->insert([
            'page_id' => $pageId,
            'company_id' => $companyId,
            'started_at' => $startedAt,
            'start_source' => $source,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** Nella prova senza scritture: periodo aperto che resterebbe dopo le chiusure previste */
    private static function openPeriodAfter(int $pageId, array $actions): ?object
    {
        $willClose = (bool) array_filter($actions, fn($a) => $a['page_id'] === $pageId && $a['action'] === 'close');
        return $willClose ? null : self::openPeriod($pageId);
    }

    /** Ora italiana, come il resto del database */
    private static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
