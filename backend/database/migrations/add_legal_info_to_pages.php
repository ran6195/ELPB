<?php
/**
 * Migration: Add legal_info field to pages table
 *
 * Adds a JSON field to store legal information (company data, VAT, etc.)
 * for generating legal pages (Privacy Policy, Cookie Policy, etc.)
 *
 * Usage: php backend/database/migrations/add_legal_info_to_pages.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables from backend directory
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->safeLoad();

// Database configuration
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'],
    'database'  => $_ENV['DB_DATABASE'],
    'username'  => $_ENV['DB_USERNAME'],
    'password'  => $_ENV['DB_PASSWORD'],
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    $schema = $capsule->schema();

    // Check if column already exists
    if (!$schema->hasColumn('pages', 'legal_info')) {
        $schema->table('pages', function ($table) {
            $table->json('legal_info')->nullable()->after('tracking_settings');
        });
        echo "✅ Column 'legal_info' added successfully to 'pages' table.\n";
    } else {
        echo "ℹ️  Column 'legal_info' already exists in 'pages' table.\n";
    }

    echo "\n📝 Migration completed successfully!\n";
    echo "\nLegal info field structure:\n";
    echo "- ragioneSociale: string\n";
    echo "- indirizzo: string\n";
    echo "- cap: string\n";
    echo "- citta: string\n";
    echo "- provincia: string\n";
    echo "- email: string\n";
    echo "- telefono: string\n";
    echo "- sitoWeb: string (optional)\n";
    echo "- nomeSito: string (optional)\n";
    echo "- amministratore: string\n";
    echo "- piva: string\n";
    echo "- codiceFiscale: string\n";
    echo "- gestoreDati: string (Edysma, FM)\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
