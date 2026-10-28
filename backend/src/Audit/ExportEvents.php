<?php

namespace App\Audit;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Esportazioni di dati personali (categoria export). Il file dei lead si crea nel browser:
 * il frontend segnala l'export con POST /api/leads/exports prima del download.
 * Si registrano quanti e quali lead (id), da quali pagine e con quali filtri, mai i loro dati.
 */
class ExportEvents
{
    /** Id dei lead salvati nella voce: oltre, solo conteggio e intervallo */
    public const MAX_LEAD_IDS = 1000;

    /** Pagine elencate nella voce, dalla più esportata */
    private const MAX_PAGES = 20;

    /**
     * @param Collection $leads    lead esportati visibili all'utente, con la pagina (anche archiviata)
     * @param int        $refused  id inviati ma non visibili all'utente (o inesistenti)
     * @param array      $filters  filtri dell'elenco al momento dell'export
     */
    public static function leadsExported(User $user, Collection $leads, int $refused, string $panel, array $filters): void
    {
        $ids = $leads->pluck('id')->sort()->values();
        $pages = $leads->groupBy('page_id')
            ->map(fn($group) => ['id' => $group->first()->page_id, 'title' => $group->first()->page?->title, 'leads' => $group->count()])
            ->sortByDesc('leads')
            ->values();
        $companyIds = $leads->map(fn($lead) => $lead->page?->company_id)->unique()->values();

        Auditor::get()->record(Auditor::EXPORT, 'lead.exported', [
            'user' => $user,
            // Azienda e pagina solo se l'export ne riguarda una sola
            'company_id' => $companyIds->count() === 1 ? $companyIds->first() : null,
            'page_id' => $pages->count() === 1 ? $pages->first()['id'] : null,
            'subject_type' => 'lead',
            'subject_label' => $ids->count() === 1 ? '1 lead' : $ids->count() . ' lead',
            'outcome' => 'ok',
            'properties' => array_filter([
                'format' => 'csv',
                'panel' => $panel,
                'count' => $ids->count(),
                'refused' => $refused ?: null,
                'lead_ids' => $ids->count() <= self::MAX_LEAD_IDS ? $ids->all() : null,
                'lead_id_range' => $ids->count() > self::MAX_LEAD_IDS ? [$ids->first(), $ids->last()] : null,
                'companies' => $companyIds->count() > 1 ? $companyIds->count() : null,
                'pages' => $pages->take(self::MAX_PAGES)->all(),
                'pages_total' => $pages->count() > self::MAX_PAGES ? $pages->count() : null,
                'filters' => $filters ?: null,
            ], fn($value) => $value !== null),
        ]);
    }
}
