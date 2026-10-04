<?php
/**
 * Migrazione dei vecchi upload (uploads/images, uploads/videos) nella libreria media.
 *
 * NON sposta, rinomina o modifica alcun file e NON tocca blocchi/pagine:
 * crea solo record nella tabella `media` che puntano ai file dove sono già,
 * quindi gli URL usati dalle LP in esercizio restano identici.
 *
 * Il proprietario di ogni file è dedotto dalle pagine che lo usano
 * (azienda/utente della pagina); i file non usati vengono assegnati all'admin.
 *
 * Usage:
 *   php scripts/migrate-existing-uploads.php                  # dry-run: solo report, nessuna scrittura
 *   php scripts/migrate-existing-uploads.php --execute        # crea i record media (+ miniature)
 *   php scripts/migrate-existing-uploads.php --execute --no-thumbnails
 *
 * Rieseguibile: i file che hanno già un record vengono saltati.
 * Rollback (i file originali non vengono mai toccati):
 *   DELETE FROM media WHERE legacy = 1;
 *   rm -r public/uploads/images/thumbs     ← le sole miniature create dallo script
 * NON usare l'eliminazione definitiva dalla libreria per il rollback: cancellerebbe i file originali.
 *
 * @created 2026-10-04
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
date_default_timezone_set('Europe/Rome');

require __DIR__ . '/../config/database.php';

use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use App\Storage\LocalMediaStorage;
use App\Storage\MediaStorageFactory;
use Illuminate\Database\Capsule\Manager as DB;

$execute = in_array('--execute', $argv, true);
$withThumbnails = !in_array('--no-thumbnails', $argv, true);

$storage = MediaStorageFactory::disk('local');
if (!$storage instanceof LocalMediaStorage) {
    fwrite(STDERR, "Il disco 'local' non è uno storage su filesystem\n");
    exit(1);
}
$service = new MediaService($storage);
$root = $storage->root();
$folders = ['images', 'videos'];

echo "========================================\n";
echo " Migrazione vecchi upload → libreria media\n";
echo " Modalità: " . ($execute ? "ESECUZIONE (scrive su DB)" : "DRY-RUN (nessuna scrittura)") . "\n";
echo " Radice:   $root\n";
echo "========================================\n\n";

if (!DB::schema()->hasTable('media') || !DB::schema()->hasColumn('media', 'legacy')) {
    fwrite(STDERR, "✗ Tabella media o colonna legacy mancanti: esegui prima le migration\n");
    fwrite(STDERR, "  php database/migrations/create_media_table.php\n");
    fwrite(STDERR, "  php database/migrations/add_legacy_to_media.php\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// 1. Riferimenti nelle pagine: filename → elenco utilizzi
// ---------------------------------------------------------------------------

/** Raccoglie ricorsivamente i riferimenti /uploads/(images|videos)/<file> */
function collectRefs($value, array &$found): void
{
    if (is_array($value)) {
        foreach ($value as $v) {
            collectRefs($v, $found);
        }
        return;
    }
    if (!is_string($value) || stripos($value, '/uploads/') === false) {
        return;
    }
    if (preg_match_all('#/uploads/(images|videos)/([A-Za-z0-9._-]+)#i', $value, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $found[strtolower($match[1]) . '/' . $match[2]] = true;
        }
    }
}

function decodeJson($raw)
{
    if ($raw === null || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return $data === null ? $raw : $data; // stringa non JSON: analizzata così com'è
}

$pages = DB::table('pages')->get()->keyBy('id');
$usages = []; // "images/<file>" => [page_id => info]

$addUsage = function (string $key, $page, string $where) use (&$usages) {
    $usages[$key][$page->id] ??= [
        'page_id'      => (int) $page->id,
        'title'        => $page->title,
        'slug'         => $page->slug,
        'company_id'   => $page->company_id !== null ? (int) $page->company_id : null,
        'user_id'      => $page->user_id !== null ? (int) $page->user_id : null,
        'is_published' => (bool) $page->is_published,
        'archived'     => $page->deleted_at !== null,
        'where'        => [],
    ];
    $usages[$key][$page->id]['where'][$where] = true;
};

// Blocchi (content + styles)
DB::table('blocks')->select('id', 'page_id', 'type', 'content', 'styles')->orderBy('id')
    ->chunk(500, function ($blocks) use ($pages, $addUsage) {
        foreach ($blocks as $block) {
            $page = $pages[$block->page_id] ?? null;
            if (!$page) {
                continue;
            }
            $found = [];
            collectRefs(decodeJson($block->content), $found);
            collectRefs(decodeJson($block->styles), $found);
            foreach (array_keys($found) as $key) {
                $addUsage($key, $page, 'blocco ' . $block->type);
            }
        }
    });

// Campi JSON della pagina (stili, contatti rapidi, notifiche → allegato email, ...)
$pageJsonFields = array_values(array_filter(
    ['styles', 'quick_contacts', 'notification_settings', 'legal_info', 'tracking_settings', 'recaptcha_settings'],
    fn ($col) => DB::schema()->hasColumn('pages', $col)
));
foreach ($pages as $page) {
    foreach ($pageJsonFields as $col) {
        $found = [];
        collectRefs(decodeJson($page->$col), $found);
        foreach (array_keys($found) as $key) {
            $addUsage($key, $page, 'pagina.' . $col);
        }
    }
}

// ---------------------------------------------------------------------------
// 2. File su disco
// ---------------------------------------------------------------------------

$diskFiles = []; // "images/<file>" => absolute path
foreach ($folders as $folder) {
    $dir = $root . '/' . $folder;
    if (!is_dir($dir)) {
        continue;
    }
    foreach (scandir($dir) as $entry) {
        $abs = $dir . '/' . $entry;
        if ($entry[0] === '.' || !is_file($abs)) {
            continue; // salta dotfile (.htaccess) e sottocartelle (thumbs/)
        }
        $diskFiles[$folder . '/' . $entry] = $abs;
    }
}

$admin = User::where('role', 'admin')->orderBy('id')->first();
if (!$admin) {
    fwrite(STDERR, "✗ Nessun utente admin trovato: serve come proprietario dei file non usati\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// 3. Analisi e (opzionale) scrittura
// ---------------------------------------------------------------------------

/** Proprietario: azienda/utente più frequente tra le pagine attive (pubblicate prima) */
function chooseOwner(array $pageUsages, User $admin): array
{
    $candidates = array_filter($pageUsages, fn ($u) => !$u['archived']) ?: $pageUsages;
    $scores = [];
    foreach ($candidates as $u) {
        if ($u['company_id'] === null && $u['user_id'] === null) {
            continue;
        }
        $key = $u['company_id'] !== null ? 'c' . $u['company_id'] : 'u' . $u['user_id'];
        $scores[$key] ??= ['score' => 0, 'company_id' => $u['company_id'], 'user_id' => $u['user_id']];
        $scores[$key]['score'] += $u['is_published'] ? 10 : 1;
    }
    if (!$scores) {
        return ['company_id' => null, 'user_id' => $admin->id, 'reason' => 'pagine senza proprietario → admin'];
    }
    uasort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);
    $best = reset($scores);
    return ['company_id' => $best['company_id'], 'user_id' => $best['user_id'], 'reason' => 'pagine che lo usano'];
}

function ownerLabel(?int $companyId, ?int $userId): string
{
    static $companies = null, $users = null;
    $companies ??= DB::table('companies')->pluck('name', 'id')->all();
    $users ??= DB::table('users')->pluck('name', 'id')->all();
    if ($companyId !== null) {
        return 'azienda #' . $companyId . ' ' . ($companies[$companyId] ?? '?');
    }
    return 'utente #' . $userId . ' ' . ($users[$userId] ?? '?');
}

$report = [
    'generated_at' => date('c'),
    'mode'         => $execute ? 'execute' : 'dry-run',
    'root'         => $root,
    'summary'      => [],
    'imported'     => [],
    'already'      => [],
    'unused'       => [],
    'shared'       => [],
    'unsupported'  => [],
    'missing'      => [],
    'errors'       => [],
];

foreach ($diskFiles as $rel => $abs) {
    $pageUsages = array_values($usages[$rel] ?? []);

    if (Media::withTrashed()->where('disk', 'local')->where('path', $rel)->exists()) {
        $report['already'][] = $rel;
        continue;
    }

    $mime = $service->detectMime($abs);
    if (!isset(MediaService::ALLOWED[$mime])) {
        $report['unsupported'][] = ['file' => $rel, 'mime' => $mime, 'pages' => $pageUsages];
        continue;
    }
    [$type] = MediaService::ALLOWED[$mime];

    $owner = $pageUsages ? chooseOwner($pageUsages, $admin) : ['company_id' => null, 'user_id' => $admin->id, 'reason' => 'non usato → admin'];

    $ownerKeys = array_unique(array_map(
        fn ($u) => $u['company_id'] !== null ? 'c' . $u['company_id'] : 'u' . ($u['user_id'] ?? '-'),
        $pageUsages
    ));
    if (count($ownerKeys) > 1) {
        $report['shared'][] = [
            'file'   => $rel,
            'owner'  => ownerLabel($owner['company_id'], $owner['user_id']),
            'pages'  => array_map(fn ($u) => $u['title'] . ' (' . ($u['company_id'] !== null ? 'azienda #' . $u['company_id'] : 'utente #' . $u['user_id']) . ')', $pageUsages),
        ];
    }
    if (!$pageUsages) {
        $report['unused'][] = $rel;
    }

    $width = $height = null;
    // Dimensioni come le mostra il browser (EXIF Orientation applicato): l'originale non si tocca
    if ($type === 'image' && ($dims = MediaService::displayDimensions($abs, $mime))) {
        [$width, $height] = $dims;
    }

    $entry = [
        'file'      => $rel,
        'type'      => $type,
        'mime'      => $mime,
        'size'      => filesize($abs),
        'owner'     => ownerLabel($owner['company_id'], $owner['user_id']),
        'reason'    => $owner['reason'],
        'pages'     => count($pageUsages),
        'published' => count(array_filter($pageUsages, fn ($u) => $u['is_published'] && !$u['archived'])),
    ];

    if ($execute) {
        try {
            $media = new Media([
                'company_id'    => $owner['company_id'],
                'user_id'       => $owner['user_id'],
                'type'          => $type,
                'disk'          => 'local',
                'path'          => $rel,
                'original_name' => basename($rel),
                'mime_type'     => $mime,
                'size'          => filesize($abs),
                'width'         => $width,
                'height'        => $height,
                'legacy'        => true,
            ]);
            // Data di caricamento originale = data del file
            $media->created_at = date('Y-m-d H:i:s', filemtime($abs));
            $media->save();

            if ($withThumbnails && $type === 'image') {
                $thumb = $service->generateThumbnail($media);
                if ($thumb) {
                    $media->thumbnail_path = $thumb;
                    $media->save();
                    $entry['thumbnail'] = $thumb;
                }
            }
            $entry['media_id'] = $media->id;
        } catch (Throwable $e) {
            $report['errors'][] = ['file' => $rel, 'error' => $e->getMessage()];
            continue;
        }
    }

    $report['imported'][] = $entry;
}

// Riferimenti a file che non esistono su disco (immagini già rotte oggi)
foreach ($usages as $rel => $pageUsages) {
    if (!isset($diskFiles[$rel])) {
        $report['missing'][] = [
            'file'  => $rel,
            'pages' => array_map(fn ($u) => [
                'page_id'      => $u['page_id'],
                'title'        => $u['title'],
                'slug'         => $u['slug'],
                'is_published' => $u['is_published'],
                'archived'     => $u['archived'],
                'where'        => array_keys($u['where']),
            ], array_values($pageUsages)),
        ];
    }
}

$report['summary'] = [
    'files_on_disk'         => count($diskFiles),
    'to_import'             => count($report['imported']),
    'already_in_library'    => count($report['already']),
    'unused_to_admin'       => count($report['unused']),
    'shared_between_owners' => count($report['shared']),
    'unsupported_format'    => count($report['unsupported']),
    'missing_on_disk'       => count($report['missing']),
    'errors'                => count($report['errors']),
];

// ---------------------------------------------------------------------------
// 4. Output
// ---------------------------------------------------------------------------

$s = $report['summary'];
echo "RIEPILOGO\n";
echo "  File su disco:                 {$s['files_on_disk']}\n";
echo "  " . ($execute ? 'Importati' : 'Da importare') . ":                  {$s['to_import']}\n";
echo "  Già in libreria (saltati):     {$s['already_in_library']}\n";
echo "  Non usati (→ admin):           {$s['unused_to_admin']}\n";
echo "  Condivisi tra proprietari:     {$s['shared_between_owners']}\n";
echo "  Formato non supportato:        {$s['unsupported_format']}\n";
echo "  Citati ma assenti su disco:    {$s['missing_on_disk']}\n";
echo "  Errori:                        {$s['errors']}\n\n";

if ($report['imported']) {
    echo ($execute ? "IMPORTATI" : "DA IMPORTARE") . "\n";
    foreach ($report['imported'] as $e) {
        printf("  %-32s %-6s %8s  %-30s pagine:%d (pubbl.:%d)\n",
            $e['file'], $e['type'], number_format($e['size'] / 1024, 0) . 'KB', $e['owner'], $e['pages'], $e['published']);
    }
    echo "\n";
}
if ($report['shared']) {
    echo "CONDIVISI TRA PIÙ PROPRIETARI (assegnati a uno solo, le LP funzionano comunque)\n";
    foreach ($report['shared'] as $e) {
        echo "  {$e['file']} → {$e['owner']}\n    usato in: " . implode('; ', $e['pages']) . "\n";
    }
    echo "\n";
}
if ($report['unsupported']) {
    echo "FORMATO NON SUPPORTATO DALLA LIBRERIA (lasciati dove sono, non importati)\n";
    foreach ($report['unsupported'] as $e) {
        echo "  {$e['file']} ({$e['mime']}) — usato in " . count($e['pages']) . " pagina/e\n";
    }
    echo "\n";
}
if ($report['missing']) {
    echo "CITATI NELLE PAGINE MA ASSENTI SU DISCO (immagini/video già non visibili)\n";
    foreach ($report['missing'] as $e) {
        $list = array_map(fn ($p) => $p['title'] . ' [' . $p['slug'] . ']' . ($p['is_published'] ? ' PUBBLICATA' : '') . ($p['archived'] ? ' archiviata' : ''), $e['pages']);
        echo "  {$e['file']}\n    " . implode("\n    ", $list) . "\n";
    }
    echo "\n";
}
if ($report['errors']) {
    echo "ERRORI\n";
    foreach ($report['errors'] as $e) {
        echo "  {$e['file']}: {$e['error']}\n";
    }
    echo "\n";
}

$logDir = __DIR__ . '/../' . ($_ENV['MAIL_LOG_PATH'] ?? 'storage/logs');
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$reportFile = $logDir . '/media-migration-' . date('Ymd-His') . ($execute ? '' : '-dryrun') . '.json';
file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Report completo: " . realpath($reportFile) . "\n";

if (!$execute) {
    echo "\nNessuna modifica effettuata. Per importare: php scripts/migrate-existing-uploads.php --execute\n";
}
