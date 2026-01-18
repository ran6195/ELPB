<?php
/**
 * Migration: Add tracking_settings field to pages table
 *
 * This adds a JSON field to store tracking codes like Google Tag Manager
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Initialize database connection
require_once __DIR__ . '/../../config/database.php';

echo "Adding tracking_settings column to pages table...\n";

try {
    // Check if column exists
    $hasColumn = Capsule::schema()->hasColumn('pages', 'tracking_settings');

    if (!$hasColumn) {
        Capsule::schema()->table('pages', function ($table) {
            $table->json('tracking_settings')->nullable()->after('recaptcha_settings');
        });
        echo "✓ Column tracking_settings added successfully\n";
    } else {
        echo "✓ Column tracking_settings already exists\n";
    }

    echo "\nMigration completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
