<?php
/**
 * Verifica prerequisiti del Registro attività sul server (sola lettura).
 *
 * Per hosting senza SSH (Aruba/Plesk): attività pianificata "Esegui uno script PHP",
 * output con "Esegui ora". Plesk tronca l'output: in cima solo i problemi, il resoconto
 * completo è in storage/logs/check-audit-requirements.txt. Non scrive nulla sul database.
 *
 * Si usa due volte:
 * 1. dopo l'SQL in phpMyAdmin e il caricamento di vendor/, PRIMA del backend: devono
 *    risultare ok PHP, pacchetti e tabella; le classi dell'applicazione mancano ancora
 * 2. dopo il deploy del backend: tutto ok
 *
 * Non include config/database.php (che dal registro in poi richiede illuminate/events):
 * la connessione si apre qui, così lo script funziona anche con il backend vecchio.
 *
 * Usage: php scripts/check-audit-requirements.php
 *
 * @created 2026-10-27
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
date_default_timezone_set('Europe/Rome');

use Illuminate\Database\Capsule\Manager as Capsule;

$errors = 0;
$warnings = 0;
$problems = [];

ob_start();

function check(bool $ok, string $label, string $hint = '', bool $blocking = true): void
{
    global $errors, $warnings, $problems;
    if ($ok) {
        echo "✓ $label\n";
        return;
    }
    $line = ($blocking ? '✗ ' : '! ') . $label . ($hint ? " — $hint" : '');
    $blocking ? $errors++ : $warnings++;
    $problems[] = $line;
    echo "$line\n";
}

// 1. PHP ---------------------------------------------------------------------
echo "[PHP " . PHP_VERSION . "]\n";
check(PHP_VERSION_ID >= 80100, 'PHP 8.1 o superiore (richiesto da illuminate 10)');
check(function_exists('random_bytes'), 'random_bytes (ID delle richieste)');
echo "\n";

// 2. Pacchetti in vendor/ ------------------------------------------------------
echo "[vendor/]\n";
foreach ([
    'Illuminate\\Events\\Dispatcher' => 'illuminate/events',
    'Illuminate\\Bus\\Dispatcher' => 'illuminate/bus',
    'Illuminate\\Pipeline\\Pipeline' => 'illuminate/pipeline',
    'Illuminate\\Container\\Container' => 'illuminate/container',
    'Illuminate\\Database\\DatabaseTransactionsManager' => 'illuminate/database 10',
] as $class => $package) {
    check(class_exists($class), "Pacchetto $package", 'caricare la cartella vendor/ aggiornata');
}
echo "\n";

// 3. File del backend (dopo il deploy) ---------------------------------------
echo "[Backend]\n";
$deployed = true;
foreach ([
    'App\\Audit\\Auditor', 'App\\Audit\\AuditCatalog', 'App\\Middleware\\RecordActivity',
    'App\\Controllers\\AuditLogController', 'App\\Models\\AuditLog',
] as $class) {
    $deployed = $deployed && class_exists($class);
}
check($deployed, 'Classi del registro attività', 'normale prima del deploy del backend', false);
check(is_file(__DIR__ . '/../bootstrap/app.php') && is_file(__DIR__ . '/../config/routes.php'),
    'bootstrap/app.php e config/routes.php', 'normale prima del deploy del backend', false);
echo "\n";

// 4. Database -----------------------------------------------------------------
echo "[Database " . ($_ENV['DB_DATABASE'] ?? '?') . "]\n";
try {
    $capsule = new Capsule;
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? '',
        'username' => $_ENV['DB_USERNAME'] ?? '',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ]);
    $capsule->setAsGlobal();
    $db = Capsule::connection();
    $db->getPdo();
    check(true, 'Connessione al database');

    $schema = Capsule::schema();
    $hasTable = $schema->hasTable('audit_logs');
    check($hasTable, 'Tabella audit_logs', 'eseguire backend/database/sql/registro_attivita.sql in phpMyAdmin');
    if ($hasTable) {
        $columns = ['id', 'created_at', 'request_id', 'channel', 'category', 'action', 'user_id', 'user_name',
            'user_email', 'user_role', 'company_id', 'page_id', 'subject_type', 'subject_id', 'subject_label',
            'method', 'url', 'status', 'outcome', 'duration_ms', 'ip', 'user_agent', 'properties'];
        $missing = array_values(array_diff($columns, $schema->getColumnListing('audit_logs')));
        check(!$missing, 'Colonne di audit_logs', 'mancano: ' . implode(', ', $missing));

        $engine = $db->selectOne('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', ['audit_logs']);
        check(($engine->engine ?? '') === 'InnoDB', 'audit_logs in InnoDB', 'motore: ' . ($engine->engine ?? '?'), false);

        $precision = $db->selectOne("SELECT DATETIME_PRECISION AS p FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'created_at'");
        check((int) ($precision->p ?? 0) === 3, 'created_at con i millisecondi', 'datetime(3) atteso', false);

        $count = $db->table('audit_logs')->count();
        $last = $db->table('audit_logs')->max('created_at');
        echo "  voci nel registro: $count" . ($last ? " (ultima: $last)" : '') . "\n";
    }
} catch (\Throwable $e) {
    check(false, 'Connessione al database', $e->getMessage());
}
echo "\n";

// Riepilogo --------------------------------------------------------------------
$detail = ob_get_clean();
$summary = $errors === 0
    ? ($warnings === 0 ? "✅ Registro attività: tutto pronto\n" : "✅ Registro attività: nessun errore bloccante ($warnings avvisi)\n")
    : "❌ Registro attività: $errors problemi bloccanti\n";
echo $summary;
foreach ($problems as $line) {
    echo "$line\n";
}

$logDir = __DIR__ . '/../storage/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    $reportFile = realpath($logDir) . '/check-audit-requirements.txt';
    file_put_contents($reportFile, date('Y-m-d H:i:s') . "\n" . $summary . "\n" . $detail);
    echo "\nResoconto completo: storage/logs/check-audit-requirements.txt\n";
} else {
    echo "\n" . $detail;
}

exit($errors === 0 ? 0 : 1);
