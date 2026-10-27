<?php
/**
 * Verifica prerequisiti della Libreria Media sul server (sola lettura).
 *
 * Pensato per hosting senza SSH (es. Aruba/Plesk): si lancia come attività
 * pianificata "Esegui uno script PHP" e si legge l'output con "Esegui ora".
 * Plesk tronca l'output: in cima vengono stampati solo i problemi, il
 * resoconto completo è salvato in storage/logs/check-media-requirements.txt.
 * Non scrive nulla su database; l'unico file scritto è il resoconto.
 *
 * Controlla: versione PHP ed estensioni (GD con WebP, fileinfo, exif),
 * file del backend caricati, tabelle/colonne del database, motore InnoDB,
 * cartelle scrivibili, APP_URL e URL pubblico dei media.
 *
 * Usage: php scripts/check-media-requirements.php
 *
 * @created 2026-10-06
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
date_default_timezone_set('Europe/Rome');

use Illuminate\Database\Capsule\Manager as DB;

$errors = 0;
$warnings = 0;
$problems = [];

// Il dettaglio viene bufferizzato e stampato dopo il riepilogo dei problemi
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
check(PHP_VERSION_ID >= 80000, 'PHP 8.0 o superiore', 'scegliere la stessa versione PHP del sito nell\'attività pianificata');
check(extension_loaded('pdo_mysql'), 'Estensione pdo_mysql');
check(class_exists('finfo'), 'Estensione fileinfo (rilevamento MIME reale)');
check(extension_loaded('gd'), 'Estensione GD (miniature)');
check(function_exists('imagewebp'), 'GD con supporto WebP in scrittura (miniature .webp)');
check(function_exists('imagecreatefromwebp'), 'GD con supporto WebP in lettura', '', false);
check(function_exists('exif_read_data'), 'Estensione exif (orientamento foto smartphone)', 'senza, le foto ruotate restano storte nelle miniature', false);
echo "  upload_max_filesize=" . ini_get('upload_max_filesize') . "  post_max_size=" . ini_get('post_max_size')
    . "  (valori della CLI: quelli del sito possono differire, vedi .user.ini)\n\n";

// 2. File del backend --------------------------------------------------------
echo "[File backend]\n";
foreach ([
    'App\\Controllers\\MediaController',
    'App\\Controllers\\MediaFolderController',
    'App\\Models\\Media',
    'App\\Models\\MediaFolder',
    'App\\Models\\Concerns\\OwnedByCompany',
    'App\\Services\\MediaService',
    'App\\Services\\MediaUploadException',
    'App\\Storage\\MediaStorage',
    'App\\Storage\\LocalMediaStorage',
    'App\\Storage\\MediaStorageFactory',
] as $class) {
    $exists = class_exists($class) || interface_exists($class) || trait_exists($class);
    check($exists, $class, 'file non caricato');
}
echo "\n";

// 3. Database ----------------------------------------------------------------
echo "[Database " . ($_ENV['DB_DATABASE'] ?? '?') . "]\n";
try {
    require __DIR__ . '/../config/database.php';
    \App\Audit\ScriptAudit::start(basename(__FILE__, '.php'), $argv ?? []); // registro attività
    DB::connection()->getPdo();
    check(true, 'Connessione al database');

    $engines = DB::select("SHOW TABLE STATUS WHERE Name IN ('companies', 'users')");
    foreach ($engines as $t) {
        check(strcasecmp($t->Engine, 'InnoDB') === 0, "Tabella {$t->Name} in InnoDB (attuale: {$t->Engine})", 'le chiavi esterne richiedono InnoDB');
    }

    $schema = DB::schema();
    check($schema->hasTable('media'), 'Tabella media', 'eseguire database/sql/media_library.sql');
    check($schema->hasTable('media') && $schema->hasColumn('media', 'legacy'), 'Colonna media.legacy');
    check($schema->hasTable('media_folders'), 'Tabella media_folders');
    check($schema->hasTable('media') && $schema->hasColumn('media', 'folder_id'), 'Colonna media.folder_id');

    if ($schema->hasTable('media')) {
        $total = DB::table('media')->count();
        $legacy = $schema->hasColumn('media', 'legacy') ? DB::table('media')->where('legacy', 1)->count() : 0;
        echo "  Record media: $total (di cui legacy: $legacy)\n";
    }
} catch (Throwable $e) {
    check(false, 'Connessione al database', $e->getMessage());
}
echo "\n";

// 4. Cartelle e URL ----------------------------------------------------------
echo "[Storage]\n";
$appUrl = $_ENV['APP_URL'] ?? '';
check($appUrl !== '' && !str_contains($appUrl, 'localhost'), "APP_URL = " . ($appUrl ?: '(vuoto)'), 'serve per gli URL pubblici dei media');

try {
    $storage = App\Storage\MediaStorageFactory::disk($_ENV['MEDIA_DISK'] ?? 'local');
    echo "  Disco: " . $storage->name() . "\n";
    echo "  URL di esempio: " . $storage->url('media/c1/images/2026/10/esempio.jpg') . "\n";
    if ($storage instanceof App\Storage\LocalMediaStorage) {
        $root = $storage->root();
        check(is_dir($root) && is_writable($root), "Radice upload scrivibile: $root", 'permessi 775 sulla cartella uploads');
        $media = $root . '/media';
        check(!is_dir($media) || is_writable($media), "Cartella uploads/media scrivibile (viene creata al primo upload)");
        foreach (['images', 'videos'] as $legacyDir) {
            if (is_dir("$root/$legacyDir")) {
                check(is_writable("$root/$legacyDir"), "uploads/$legacyDir scrivibile (miniature dei vecchi upload)", '', false);
            }
        }
    }
} catch (Throwable $e) {
    check(false, 'Storage media', $e->getMessage());
}

$logDir = __DIR__ . '/../' . ($_ENV['MAIL_LOG_PATH'] ?? 'storage/logs');
check(is_dir($logDir) ? is_writable($logDir) : is_writable(dirname($logDir)), 'Cartella log scrivibile (report migrazione)', $logDir, false);

$detail = ob_get_clean();

$summary = $errors === 0
    ? "OK: nessun errore bloccante ($warnings avvisi)\n"
    : "$errors errori bloccanti, $warnings avvisi\n";
foreach ($problems as $line) {
    $summary .= "$line\n";
}

if (is_dir($logDir) || @mkdir($logDir, 0755, true)) {
    $reportFile = realpath($logDir) . '/check-media-requirements.txt';
    $saved = @file_put_contents($reportFile, date('Y-m-d H:i:s') . "\n" . $summary . "\n" . $detail) !== false;
} else {
    $saved = false;
}
$summary .= $saved ? "Dettaglio: $reportFile\n" : "Dettaglio non salvato: cartella log non scrivibile\n";

echo $summary . "\n" . $detail;

exit($errors === 0 ? 0 : 1);
