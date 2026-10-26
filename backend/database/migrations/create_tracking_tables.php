<?php
/**
 * Migration: Renderer v2.1 e visite senza cookie (fasi 2-3 domini)
 *
 * - renderer_hits: chi scarica le pagine (sito, renderer, versione, IP del server)
 * - page_views_daily: visite e visitatori per pagina, sito e giorno (16 mesi)
 * - page_view_visitors: hash anonimi del giorno corrente
 * - tracking_salts: sale giornaliero degli hash
 *
 * Usage: php backend/database/migrations/create_tracking_tables.php
 * Equivalente SQL per phpMyAdmin: backend/database/sql/renderer_visite.sql
 *
 * @created 2026-10-26
 */

require __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

require __DIR__ . '/../../config/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

echo "Creazione tabelle renderer e visite...\n";

try {
    if (!Capsule::schema()->hasTable('renderer_hits')) {
        Capsule::schema()->create('renderer_hits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            // Stringhe vuote invece di NULL: l'indice univoco non considera uguali due NULL
            $table->string('host', 253)->default('');
            $table->string('renderer', 20)->default('');
            $table->string('version', 20)->nullable();
            $table->string('ip', 45)->default('');
            $table->string('user_agent', 255)->nullable();
            $table->string('last_url', 500)->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->unique(['page_id', 'host', 'renderer', 'ip'], 'renderer_hits_unique');
            $table->index('host');
        });
        echo "✓ Tabella renderer_hits creata\n";
    } else {
        echo "⊘ Tabella renderer_hits già esistente\n";
    }

    if (!Capsule::schema()->hasTable('page_views_daily')) {
        Capsule::schema()->create('page_views_daily', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->string('host', 253)->default('');
            $table->date('day');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('visitors')->default(0);

            $table->primary(['page_id', 'host', 'day']);
            $table->index('day');
        });
        echo "✓ Tabella page_views_daily creata\n";
    } else {
        echo "⊘ Tabella page_views_daily già esistente\n";
    }

    if (!Capsule::schema()->hasTable('page_view_visitors')) {
        Capsule::schema()->create('page_view_visitors', function (Blueprint $table) {
            $table->date('day');
            $table->char('visitor_hash', 64);

            $table->primary(['day', 'visitor_hash']);
        });
        echo "✓ Tabella page_view_visitors creata\n";
    } else {
        echo "⊘ Tabella page_view_visitors già esistente\n";
    }

    if (!Capsule::schema()->hasTable('tracking_salts')) {
        Capsule::schema()->create('tracking_salts', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->char('salt', 64);
        });
        echo "✓ Tabella tracking_salts creata\n";
    } else {
        echo "⊘ Tabella tracking_salts già esistente\n";
    }

    echo "\n✅ Migration completata\n";
} catch (\Exception $e) {
    echo "\n❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
