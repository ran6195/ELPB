<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Initialize database connection
require_once __DIR__ . '/../../config/database.php';

echo "Adding recaptcha_settings column to pages table...\n";

try {
    // Check if column exists
    $hasColumn = Capsule::schema()->hasColumn('pages', 'recaptcha_settings');

    if (!$hasColumn) {
        Capsule::schema()->table('pages', function ($table) {
            $table->json('recaptcha_settings')->nullable()->after('styles');
        });
        echo "✓ Column recaptcha_settings added successfully\n";
    } else {
        echo "✓ Column recaptcha_settings already exists\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
