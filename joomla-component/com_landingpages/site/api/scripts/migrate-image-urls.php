<?php
/**
 * Script per migrare gli URL delle immagini da localhost all'URL di produzione
 *
 * Uso: php scripts/migrate-image-urls.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize database
require __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as DB;

// Ottieni l'URL dell'applicazione dal .env
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';

echo "=== Migrazione URL Immagini ===\n";
echo "URL di destinazione: {$appUrl}\n\n";

// Trova tutte le pagine
$pages = DB::table('pages')->get();

echo "Trovate " . count($pages) . " pagine da controllare...\n\n";

$pagesUpdated = 0;
$blocksUpdated = 0;

foreach ($pages as $page) {
    $blocks = DB::table('blocks')->where('page_id', $page->id)->get();

    $pageHasChanges = false;

    foreach ($blocks as $block) {
        $content = json_decode($block->content, true);
        $contentChanged = false;

        // Funzione ricorsiva per sostituire URL in tutto il content
        $replaceUrls = function(&$value) use ($appUrl, &$contentChanged) {
            if (is_string($value) && str_contains($value, 'localhost:8000/uploads/images/')) {
                $value = str_replace('http://localhost:8000', $appUrl, $value);
                $contentChanged = true;
            } elseif (is_array($value)) {
                foreach ($value as &$item) {
                    if (is_string($item) && str_contains($item, 'localhost:8000/uploads/images/')) {
                        $item = str_replace('http://localhost:8000', $appUrl, $item);
                        $contentChanged = true;
                    } elseif (is_array($item)) {
                        // Ricorsione per array annidati (es. services array)
                        foreach ($item as &$subItem) {
                            if (is_string($subItem) && str_contains($subItem, 'localhost:8000/uploads/images/')) {
                                $subItem = str_replace('http://localhost:8000', $appUrl, $subItem);
                                $contentChanged = true;
                            }
                        }
                    }
                }
            }
        };

        // Applica la sostituzione
        array_walk_recursive($content, function(&$value) use ($appUrl, &$contentChanged) {
            if (is_string($value) && str_contains($value, 'localhost:8000/uploads/images/')) {
                $value = str_replace('http://localhost:8000', $appUrl, $value);
                $contentChanged = true;
            }
        });

        // Se il content è cambiato, aggiorna il blocco
        if ($contentChanged) {
            DB::table('blocks')
                ->where('id', $block->id)
                ->update(['content' => json_encode($content)]);

            $blocksUpdated++;
            $pageHasChanges = true;

            echo "  ✓ Aggiornato blocco #{$block->id} (tipo: {$block->type}) nella pagina \"{$page->title}\"\n";
        }
    }

    if ($pageHasChanges) {
        $pagesUpdated++;
    }
}

echo "\n=== Migrazione Completata ===\n";
echo "Pagine aggiornate: {$pagesUpdated}\n";
echo "Blocchi aggiornati: {$blocksUpdated}\n";

if ($blocksUpdated > 0) {
    echo "\n✓ Gli URL delle immagini sono stati aggiornati con successo!\n";
} else {
    echo "\n✓ Nessun URL da aggiornare trovato.\n";
}
