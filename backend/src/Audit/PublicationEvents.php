<?php

namespace App\Audit;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Page;
use App\Models\User;

/**
 * Eventi importanti per il cliente, registrati in aggiunta alle modifiche automatiche:
 * pagina pubblicata o ritirata, pagina assegnata ad altri, lead ricevuto.
 */
class PublicationEvents
{
    /** Pubblicazione o ritiro (dal modello Page, qualunque sia la rotta che lo provoca) */
    public static function pagePublicationChanged(Page $page, bool $published): void
    {
        Auditor::get()->record(Auditor::PUBLISH, $published ? 'page.published' : 'page.unpublished', [
            ...self::pageSubject($page),
            'properties' => ['slug' => $page->slug],
        ]);
    }

    /** Proprietario e azienda precedenti e nuovi, con i nomi (le colonne della voce hanno solo gli id) */
    public static function pageReassigned(Page $page, ?User $fromUser, ?int $fromCompanyId, User $toUser): void
    {
        $companies = Company::whereIn('id', array_filter([$fromCompanyId, $toUser->company_id]))->pluck('name', 'id');
        Auditor::get()->record(Auditor::PUBLISH, 'page.reassigned', [
            ...self::pageSubject($page),
            'properties' => [
                'from' => ['user' => $fromUser?->name, 'user_id' => $fromUser?->id, 'company' => $companies[$fromCompanyId] ?? null, 'company_id' => $fromCompanyId],
                'to' => ['user' => $toUser->name, 'user_id' => $toUser->id, 'company' => $companies[$toUser->company_id] ?? null, 'company_id' => $toUser->company_id],
            ],
        ]);
    }

    /**
     * Lead ricevuto dal form pubblico: sito di provenienza ed esito della notifica email.
     * Nessun dato personale del visitatore.
     *
     * @param string $notification sent, failed, error, disabled, not_configured, no_page
     */
    public static function leadReceived(Lead $lead, ?Page $page, string $notification): void
    {
        Auditor::get()->record(Auditor::SYSTEM, 'lead.received', [
            'user' => null,
            ...($page ? self::pageSubject($page) : []),
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
            'subject_label' => null,
            'properties' => array_filter([
                'origin_host' => $lead->origin_host,
                'domain_id' => $lead->domain_id,
                'notification' => $notification,
            ], fn($value) => $value !== null),
        ]);
    }

    private static function pageSubject(Page $page): array
    {
        return [
            'company_id' => $page->company_id,
            'page_id' => $page->id,
            'subject_type' => 'page',
            'subject_id' => $page->id,
            'subject_label' => $page->title,
        ];
    }
}
