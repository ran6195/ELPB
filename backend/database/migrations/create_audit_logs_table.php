<?php
/**
 * Migration: registro attività (audit log)
 *
 * Chi ha fatto che cosa, quando, da dove e con quale esito. Nessuna foreign key:
 * le voci sopravvivono all'eliminazione di utenti, aziende e pagine (nome, email,
 * ruolo ed etichetta del record sono copiati nella voce). Date in ora italiana,
 * come il resto dell'applicazione.
 *
 * Usage: php backend/database/migrations/create_audit_logs_table.php
 * Equivalente SQL per phpMyAdmin: backend/database/sql/registro_attivita.sql
 *
 * @created 2026-10-27
 */

require __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

require __DIR__ . '/../../config/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

echo "Creazione tabella audit_logs...\n";

try {
    if (!Capsule::schema()->hasTable('audit_logs')) {
        Capsule::schema()->create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Millisecondi; per le richieste è l'ora di inizio
            $table->dateTime('created_at', 3);
            // ULID per richiesta: lega la voce della richiesta alle modifiche che ha prodotto
            $table->char('request_id', 26)->nullable();
            $table->string('channel', 10);
            $table->string('category', 10);
            $table->string('action', 100);

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role', 20)->nullable();

            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('page_id')->nullable();

            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();

            $table->string('method', 8)->nullable();
            $table->string('url', 1000)->nullable();
            $table->smallInteger('status')->unsigned()->nullable();
            $table->string('outcome', 12)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 250)->nullable();
            $table->json('properties')->nullable();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index('request_id');
        });
        echo "✓ Tabella audit_logs creata\n";
    } else {
        echo "⊘ Tabella audit_logs già esistente\n";
    }

    echo "\n✅ Migration completata\n";
} catch (\Exception $e) {
    echo "\n❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
