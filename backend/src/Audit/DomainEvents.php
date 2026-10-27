<?php

namespace App\Audit;

use App\Models\Page;

/**
 * Domini associati alle pagine: sync()/attach()/detach() non emettono eventi dei modelli,
 * quindi le associazioni si registrano qui (categoria change, record = pagina).
 */
class DomainEvents
{
    /**
     * @param string[] $attached hostname aggiunti
     * @param string[] $detached hostname tolti
     * @param string   $reason   motivo stabile: manual (impostazioni pagina), domain_created,
     *                           domain_deleted, page_reassigned
     */
    public static function pageDomainsChanged(Page $page, array $attached, array $detached, string $reason, array $extra = []): void
    {
        if (!$attached && !$detached) {
            return;
        }

        $properties = ['reason' => $reason];
        if ($attached) {
            $properties['attached'] = array_values($attached);
        }
        if ($detached) {
            $properties['detached'] = array_values($detached);
        }

        Auditor::get()->record(Auditor::CHANGE, 'page.domains_updated', [
            'company_id' => $page->company_id,
            'page_id' => $page->id,
            'subject_type' => 'page',
            'subject_id' => $page->id,
            'subject_label' => $page->title,
            'properties' => $properties + $extra,
        ]);
    }
}
