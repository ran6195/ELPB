<?php
/**
 * Apre lo storico della pubblicazione per le pagine già online e lo riallinea.
 *
 * Da eseguire una volta dopo il rilascio dello storico (le pagine pubblicate prima non
 * hanno un periodo aperto) e quando serve un controllo: è rieseguibile e non tocca i
 * periodi già corretti.
 *
 * - Pagine online senza periodo aperto: apre il periodo con l'inizio stimato
 *   (PublicationHistory::inferStart): ultimo evento nel registro attività, altrimenti
 *   il primo giorno con visite o la prima richiesta di un renderer, altrimenti adesso.
 * - Periodi aperti di pagine ritirate, archiviate, eliminate o passate a un'altra
 *   azienda: li chiude adesso.
 *
 * Usage:
 *   php scripts/backfill-publication-history.php            # prova: mostra cosa farebbe
 *   php scripts/backfill-publication-history.php --execute
 * Plesk: attività pianificata "Esegui uno script PHP", argomento --execute.
 * Il dettaglio va in storage/logs/backfill-publication-history.txt (Plesk tronca l'output).
 *
 * Rollback del primo recupero (i periodi registrati dal vivo restano):
 *   DELETE FROM page_publications WHERE start_source IN ('audit_log', 'first_view', 'first_hit', 'backfill');
 *
 * @created 2026-11-14
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
date_default_timezone_set('Europe/Rome');

require __DIR__ . '/../config/database.php';
\App\Audit\ScriptAudit::start(basename(__FILE__, '.php'), $argv ?? []); // registro attività

use App\Services\PublicationHistory;
use Illuminate\Database\Capsule\Manager as Capsule;

$execute = in_array('--execute', $argv ?? [], true);

if (!Capsule::schema()->hasTable(PublicationHistory::TABLE)) {
    echo "✗ Tabella " . PublicationHistory::TABLE . " assente: eseguire prima database/sql/storico_pubblicazione.sql\n";
    exit(1);
}

$actions = PublicationHistory::reconcile($execute);

$sources = [
    PublicationHistory::SOURCE_AUDIT => 'registro attività',
    PublicationHistory::SOURCE_FIRST_VIEW => 'primo giorno con visite',
    PublicationHistory::SOURCE_FIRST_HIT => 'prima richiesta di un renderer',
    PublicationHistory::SOURCE_BACKFILL => 'nessuna traccia, adesso',
];
$opened = array_filter($actions, fn($a) => $a['action'] === 'open');
$closed = array_filter($actions, fn($a) => $a['action'] === 'close');

$bySource = [];
foreach ($opened as $action) {
    $bySource[$action['source']] = ($bySource[$action['source']] ?? 0) + 1;
}

$summary = sprintf(
    "%s: %d periodi da aprire, %d da chiudere%s\n",
    $execute ? 'ESEGUITO' : 'PROVA (nessuna scrittura, --execute per applicare)',
    count($opened),
    count($closed),
    $bySource ? ' (' . implode(', ', array_map(fn($s, $n) => ($sources[$s] ?? $s) . ": $n", array_keys($bySource), $bySource)) . ')' : ''
);

$details = [];
foreach ($opened as $action) {
    $details[] = sprintf('APRE   #%d %s: dal %s (%s)', $action['page_id'], $action['title'], $action['at'], $sources[$action['source']] ?? $action['source']);
}
foreach ($closed as $action) {
    $details[] = sprintf('CHIUDE #%d %s: al %s (%s)', $action['page_id'], $action['title'], $action['at'], $action['reason']);
}

echo $summary;
echo $details ? implode("\n", $details) . "\n" : "Niente da fare: lo storico è allineato.\n";

$logDir = __DIR__ . '/../storage/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    file_put_contents($logDir . '/backfill-publication-history.txt', date('Y-m-d H:i:s') . "\n" . $summary . implode("\n", $details) . "\n");
}
