<?php
/**
 * Script per aggiornare i blocchi slider con i nuovi campi
 * Esegui questo script se lo slider non viene renderizzato correttamente
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Models\Block;

echo "========================================\n";
echo "Fix Slider Blocks Script\n";
echo "========================================\n\n";

try {
    // Trova tutti i blocchi di tipo slider
    $sliderBlocks = Block::where('type', 'slider')->get();

    if ($sliderBlocks->isEmpty()) {
        echo "Nessun blocco slider trovato nel database.\n";
        exit(0);
    }

    echo "Trovati " . $sliderBlocks->count() . " blocchi slider.\n\n";

    $updated = 0;

    foreach ($sliderBlocks as $block) {
        $content = $block->content;
        $needsUpdate = false;

        // Aggiungi slideHeight se manca
        if (!isset($content['slideHeight'])) {
            $content['slideHeight'] = '';
            $needsUpdate = true;
            echo "- Aggiunto slideHeight a blocco ID {$block->id}\n";
        }

        // Aggiungi slideAspectRatio se manca
        if (!isset($content['slideAspectRatio'])) {
            $content['slideAspectRatio'] = 'square';
            $needsUpdate = true;
            echo "- Aggiunto slideAspectRatio a blocco ID {$block->id}\n";
        }

        // Aggiungi slideGap se manca
        if (!isset($content['slideGap'])) {
            $content['slideGap'] = 20;
            $needsUpdate = true;
            echo "- Aggiunto slideGap a blocco ID {$block->id}\n";
        }

        // Verifica che ogni slide abbia tutti i campi necessari
        if (isset($content['slides']) && is_array($content['slides'])) {
            foreach ($content['slides'] as $index => &$slide) {
                if (!isset($slide['image'])) {
                    $slide['image'] = 'https://placehold.co/800x800';
                    $needsUpdate = true;
                    echo "- Aggiunta immagine placeholder alla slide {$index} del blocco ID {$block->id}\n";
                }
                if (!isset($slide['alt'])) {
                    $slide['alt'] = "Slide " . ($index + 1);
                    $needsUpdate = true;
                }
                if (!isset($slide['title'])) {
                    $slide['title'] = '';
                    $needsUpdate = true;
                }
                if (!isset($slide['description'])) {
                    $slide['description'] = '';
                    $needsUpdate = true;
                }
            }
            $content['slides'] = $slide;
        }

        // Salva le modifiche se necessario
        if ($needsUpdate) {
            $block->content = $content;
            $block->save();
            $updated++;
            echo "✓ Blocco ID {$block->id} aggiornato\n\n";
        } else {
            echo "○ Blocco ID {$block->id} già aggiornato\n\n";
        }
    }

    echo "========================================\n";
    echo "Completato!\n";
    echo "Blocchi aggiornati: {$updated} / " . $sliderBlocks->count() . "\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
