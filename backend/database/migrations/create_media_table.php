<?php
/**
 * Migration: Crea la tabella media (libreria media per cliente)
 *
 * Ogni record rappresenta un file (immagine o video) appartenente a una company
 * (o a un utente senza company). L'URL pubblico non viene salvato: è calcolato
 * da `disk` + `path` tramite il driver di storage configurato.
 *
 * Usage: php backend/database/migrations/create_media_table.php
 *
 * @created 2026-10-04
 */

require __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// Connect to database
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'database'  => $_ENV['DB_DATABASE'] ?? 'landing_page_builder',
    'port'      => $_ENV['DB_PORT'] ?? '3306',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Creazione tabella media...\n";

if (Capsule::schema()->hasTable('media')) {
    echo "⊘ Tabella media già esistente\n";
    exit(0);
}

try {
    Capsule::schema()->create('media', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
        $table->enum('type', ['image', 'video']);
        $table->string('disk', 20)->default('local');
        $table->string('path', 500);
        $table->string('thumbnail_path', 500)->nullable();
        $table->string('original_name');
        $table->string('mime_type', 100);
        $table->unsignedBigInteger('size');
        $table->unsignedInteger('width')->nullable();
        $table->unsignedInteger('height')->nullable();
        $table->unsignedInteger('duration')->nullable(); // secondi (video)
        $table->string('alt_text')->nullable();
        $table->foreignId('parent_id')->nullable()->constrained('media')->onDelete('set null');
        $table->timestamps();
        $table->softDeletes();

        $table->index(['company_id', 'type', 'created_at']);
        $table->index(['user_id', 'type']);
    });

    echo "✓ Tabella media creata con successo!\n";
} catch (Exception $e) {
    echo "✗ Errore durante la migration: " . $e->getMessage() . "\n";
    exit(1);
}
