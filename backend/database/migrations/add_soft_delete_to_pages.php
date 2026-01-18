<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Initialize database
require_once __DIR__ . '/../../config/database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Adding soft delete to pages table...\n";

try {
    // Check if column already exists
    $hasColumn = Capsule::schema()->hasColumn('pages', 'deleted_at');

    if ($hasColumn) {
        echo "Column 'deleted_at' already exists in pages table.\n";
        exit(0);
    }

    // Add deleted_at column
    Capsule::schema()->table('pages', function ($table) {
        $table->timestamp('deleted_at')->nullable()->after('updated_at');
    });

    echo "Successfully added 'deleted_at' column to pages table.\n";
    echo "Soft delete enabled for pages.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
