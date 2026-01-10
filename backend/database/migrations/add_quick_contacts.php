<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Initialize database connection
require_once __DIR__ . '/../../config/database.php';

echo "Adding quick_contacts column to pages table...\n";

try {
    // Check if column exists
    $hasColumn = Capsule::schema()->hasColumn('pages', 'quick_contacts');

    if (!$hasColumn) {
        Capsule::schema()->table('pages', function ($table) {
            $table->json('quick_contacts')->nullable()->after('recaptcha_settings');
        });
        echo "✓ Column quick_contacts added successfully\n";
    } else {
        echo "✓ Column quick_contacts already exists\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
