<?php
/**
 * Migration: storico della pubblicazione delle pagine (base della fatturazione)
 *
 * page_publications: un periodo per ogni tratto in cui una pagina è stata pubblicata
 * (pubblicata e non archiviata) per una stessa azienda. Senza FK verso pages: lo storico
 * resta anche dopo l'eliminazione definitiva della pagina.
 *
 * Usage: php backend/database/migrations/create_page_publications_table.php
 * Equivalente SQL per phpMyAdmin: backend/database/sql/storico_pubblicazione.sql
 *
 * @created 2026-11-14
 */

require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

require __DIR__ . '/../../config/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

echo "Creazione tabella page_publications...\n";

try {
    if (!Capsule::schema()->hasTable('page_publications')) {
        Capsule::schema()->create('page_publications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->string('start_source', 20);
            $table->string('end_reason', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['page_id', 'ended_at']);
            $table->index(['company_id', 'started_at']);
            $table->index(['started_at', 'ended_at']);
        });
        echo "✓ Tabella page_publications creata\n";
    } else {
        echo "⊘ Tabella page_publications già esistente\n";
    }

    echo "\n✅ Migration completata\n";
} catch (\Exception $e) {
    echo "\n❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
