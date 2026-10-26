<?php
/**
 * Migration: Domini associati alle landing page (fase 1)
 *
 * Associazione facoltativa: le pagine senza domini funzionano come prima.
 * - Tabella domains (anagrafica per azienda, hostname normalizzato)
 * - Tabella ponte domain_page (molti-a-molti)
 * - Colonne leads.origin_host, leads.domain_id, leads.origin_inferred
 *
 * Usage: php backend/database/migrations/create_domains_tables.php
 * Equivalente SQL per phpMyAdmin: backend/database/sql/domini_lp.sql
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

echo "Creazione tabelle domini...\n";

try {
    if (!Capsule::schema()->hasTable('domains')) {
        Capsule::schema()->create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('hostname', 253);
            $table->string('label', 100)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'hostname']);
            $table->index('hostname');
        });
        echo "✓ Tabella domains creata\n";
    } else {
        echo "⊘ Tabella domains già esistente\n";
    }

    if (!Capsule::schema()->hasTable('domain_page')) {
        Capsule::schema()->create('domain_page', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');

            $table->primary(['page_id', 'domain_id']);
            $table->index('domain_id');
        });
        echo "✓ Tabella domain_page creata\n";
    } else {
        echo "⊘ Tabella domain_page già esistente\n";
    }

    if (!Capsule::schema()->hasColumn('leads', 'origin_host')) {
        Capsule::schema()->table('leads', function (Blueprint $table) {
            $table->string('origin_host', 253)->nullable()->after('metadata');
            $table->foreignId('domain_id')->nullable()->after('origin_host')->constrained('domains')->onDelete('set null');
            $table->boolean('origin_inferred')->default(false)->after('domain_id');
        });
        echo "✓ Colonne leads.origin_host, domain_id, origin_inferred aggiunte\n";
    } else {
        echo "⊘ Colonne origine dei lead già presenti\n";
    }
} catch (Exception $e) {
    echo "✗ Errore durante la migration: " . $e->getMessage() . "\n";
    exit(1);
}
