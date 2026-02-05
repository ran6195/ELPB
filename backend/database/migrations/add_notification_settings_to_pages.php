<?php
/**
 * Migration: Aggiunge campo notification_settings alla tabella pages
 *
 * Campo JSON per memorizzare le configurazioni delle notifiche email:
 * - enabled: bool (attiva/disattiva notifiche)
 * - additional_emails: string (email aggiuntive separate da virgola)
 *
 * Usage: php backend/database/migrations/add_notification_settings_to_pages.php
 *
 * @created 2026-02-05
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->safeLoad();

// Database configuration
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'port'      => $_ENV['DB_PORT'] ?? '3306',
    'database'  => $_ENV['DB_DATABASE'] ?? 'landing_page_builder',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    echo "Aggiunta campo notification_settings alla tabella pages...\n";

    Capsule::schema()->table('pages', function ($table) {
        $table->json('notification_settings')->nullable()->after('legal_info');
    });

    echo "✓ Campo notification_settings aggiunto con successo!\n";
    echo "\nStruttura campo JSON:\n";
    echo "{\n";
    echo "  \"enabled\": true|false,\n";
    echo "  \"additional_emails\": \"email1@example.com, email2@example.com\"\n";
    echo "}\n";

} catch (Exception $e) {
    echo "✗ Errore durante la migration: " . $e->getMessage() . "\n";
    exit(1);
}
