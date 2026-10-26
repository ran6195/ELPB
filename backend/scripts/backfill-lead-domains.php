<?php
/**
 * Stima il dominio di provenienza dei lead ricevuti prima che venisse registrato.
 *
 * Solo per le pagine associate a UN dominio: i loro lead senza origine prendono
 * quel dominio, marcati origin_inferred = 1 ("pagina vecchia, dato non certo").
 * Sono considerati solo i lead più vecchi del primo lead con origine registrata,
 * così i lead nuovi senza header Origin restano "origine sconosciuta".
 * Rieseguibile; non tocca i lead con origine già registrata.
 *
 * Usage:
 *   php scripts/backfill-lead-domains.php            # dry-run: mostra cosa verrebbe stimato
 *   php scripts/backfill-lead-domains.php --execute
 * Plesk: attività pianificata "Esegui uno script PHP", argomento --execute.
 * Il dettaglio va in storage/logs/backfill-lead-domains.txt (Plesk tronca l'output).
 *
 * Rollback:
 *   UPDATE leads SET domain_id = NULL, origin_host = NULL, origin_inferred = 0 WHERE origin_inferred = 1;
 *
 * @created 2026-10-26
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
date_default_timezone_set('Europe/Rome');

require __DIR__ . '/../config/database.php';

use App\Models\Lead;
use App\Models\Page;

$execute = in_array('--execute', $argv ?? [], true);

// Prima registrazione reale dell'origine: i lead successivi senza origine non si stimano
$firstMeasured = Lead::whereNotNull('origin_host')->where('origin_inferred', false)->min('created_at');

$pages = Page::withTrashed()->with('domains')->has('domains', '=', 1)->get();

$details = [];
$total = 0;
foreach ($pages as $page) {
    $domain = $page->domains->first();
    $query = Lead::where('page_id', $page->id)
        ->whereNull('origin_host')
        ->where('origin_inferred', false);
    if ($firstMeasured) {
        $query->where('created_at', '<', $firstMeasured);
    }
    $count = $query->count();
    if ($count === 0) {
        continue;
    }
    $total += $count;
    $details[] = sprintf('#%d %s → %s: %d lead', $page->id, $page->slug, $domain->hostname, $count);

    if ($execute) {
        $query->update([
            'origin_host' => $domain->hostname,
            'domain_id' => $domain->id,
            'origin_inferred' => true,
        ]);
    }
}

$summary = sprintf(
    "Stima dominio lead vecchi — %s\n%d lead su %d pagine con un solo dominio%s\n",
    $execute ? 'ESEGUITO' : 'DRY-RUN (nessuna scrittura, usa --execute)',
    $total,
    count($details),
    $firstMeasured ? " (solo lead prima del {$firstMeasured})" : ''
);

$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
@file_put_contents($logDir . '/backfill-lead-domains.txt', $summary . "\n" . implode("\n", $details) . "\n");

echo $summary;
echo implode("\n", $details) . ($details ? "\n" : '');
