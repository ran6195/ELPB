<?php
/**
 * Migration: Cartelle della libreria media
 *
 * Le cartelle sono solo logiche (database): i file non vengono mai spostati
 * su disco, quindi gli URL usati dalle landing page restano invariati.
 * - Tabella media_folders (annidabili tramite parent_id, stessa visibilità dei media)
 * - Colonna media.folder_id (NULL = radice)
 *
 * Usage: php backend/database/migrations/create_media_folders_table.php
 *
 * @created 2026-10-04
 */

require __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

require __DIR__ . '/../../config/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

echo "Creazione cartelle media...\n";

try {
    if (!Capsule::schema()->hasTable('media_folders')) {
        Capsule::schema()->create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->onDelete('cascade');
            $table->string('name', 100);
            $table->timestamps();

            $table->index(['company_id', 'parent_id']);
        });
        echo "✓ Tabella media_folders creata\n";
    } else {
        echo "⊘ Tabella media_folders già esistente\n";
    }

    if (!Capsule::schema()->hasColumn('media', 'folder_id')) {
        Capsule::schema()->table('media', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('user_id')->constrained('media_folders')->onDelete('set null');
        });
        echo "✓ Colonna media.folder_id aggiunta\n";
    } else {
        echo "⊘ Colonna media.folder_id già presente\n";
    }
} catch (Exception $e) {
    echo "✗ Errore durante la migration: " . $e->getMessage() . "\n";
    exit(1);
}
