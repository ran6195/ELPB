<?php
/**
 * Migration: Aggiunge il flag `legacy` alla tabella media
 *
 * Marca i file importati dagli upload precedenti alla libreria media
 * (uploads/images, uploads/videos): possono essere usati da LP in esercizio
 * anche fuori da questo database, quindi l'eliminazione definitiva è
 * riservata all'admin.
 *
 * Usage: php backend/database/migrations/add_legacy_to_media.php
 *
 * @created 2026-10-04
 */

require __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

require __DIR__ . '/../../config/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Aggiunta campo legacy alla tabella media...\n";

if (Capsule::schema()->hasColumn('media', 'legacy')) {
    echo "⊘ Campo legacy già presente\n";
    exit(0);
}

try {
    Capsule::schema()->table('media', function ($table) {
        $table->boolean('legacy')->default(false)->after('parent_id');
    });
    echo "✓ Campo legacy aggiunto con successo!\n";
} catch (Exception $e) {
    echo "✗ Errore durante la migration: " . $e->getMessage() . "\n";
    exit(1);
}
