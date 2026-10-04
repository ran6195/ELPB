<?php
/**
 * Ripara orientamento di miniature e dimensioni dei media immagine caricati prima
 * della gestione completa dell'EXIF Orientation (es. foto con orientamento 5/7).
 *
 * - Media della libreria (legacy = 0) con EXIF ancora presente: l'originale viene
 *   raddrizzato fisicamente (il browser lo mostrava già così, l'aspetto non cambia).
 * - Media legacy: l'originale NON viene toccato, si rigenerano solo miniatura e dimensioni.
 *
 * Usage:
 *   php scripts/repair-media-orientation.php            # dry-run: elenca cosa verrebbe riparato
 *   php scripts/repair-media-orientation.php --execute
 *
 * @created 2026-10-04
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
date_default_timezone_set('Europe/Rome');

require __DIR__ . '/../config/database.php';

use App\Models\Media;
use App\Services\MediaService;
use App\Storage\LocalMediaStorage;
use App\Storage\MediaStorageFactory;

$execute = in_array('--execute', $argv, true);
$service = new MediaService();
$fix = new ReflectionMethod($service, 'fixJpegOrientation');
$fix->setAccessible(true);

echo "Riparazione orientamento media — " . ($execute ? "ESECUZIONE" : "DRY-RUN (nessuna scrittura)") . "\n\n";

$toRepair = 0;
foreach (Media::withTrashed()->where('type', 'image')->where('mime_type', 'image/jpeg')->orderBy('id')->get() as $media) {
    $storage = MediaStorageFactory::disk($media->disk);
    $path = $storage instanceof LocalMediaStorage ? $storage->localPath($media->path) : null;
    if ($path === null) {
        continue;
    }

    $orientation = MediaService::exifOrientation($path, $media->mime_type);
    if ($orientation === 1) {
        continue;
    }
    $toRepair++;

    echo sprintf("#%d %s  EXIF %d  %s\n", $media->id, $media->path, $orientation,
        $media->legacy ? 'legacy: solo miniatura e dimensioni' : 'originale raddrizzato + miniatura');

    if (!$execute) {
        continue;
    }

    if (!$media->legacy) {
        $fix->invoke($service, $path);
        clearstatcache(true, $path);
        $media->size = filesize($path);
    }

    [$media->width, $media->height] = MediaService::displayDimensions($path, $media->mime_type);

    $oldThumb = $media->thumbnail_path;
    $newThumb = $service->generateThumbnail($media);
    if ($oldThumb && $oldThumb !== $newThumb) {
        $storage->delete($oldThumb);
    }
    $media->thumbnail_path = $newThumb;
    $media->save();
}

echo "\n" . ($toRepair ? "$toRepair media " . ($execute ? 'riparati' : 'da riparare') : 'Nessun media da riparare') . "\n";
if (!$execute && $toRepair) {
    echo "Per applicare: php scripts/repair-media-orientation.php --execute\n";
}
