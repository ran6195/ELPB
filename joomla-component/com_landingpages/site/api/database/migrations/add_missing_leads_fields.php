<?php

require __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// Connect to database
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'database'  => $_ENV['DB_DATABASE'] ?? 'landing_page_builder',
    'port'      => $_ENV['DB_PORT'] ?? '3306',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Updating leads table with missing fields...\n";

// Check if leads table exists
if (!Capsule::schema()->hasTable('leads')) {
    echo "⚠ Leads table doesn't exist yet. Run create_tables.php first.\n";
    exit(1);
}

// Add privacy_accepted field if not exists
if (!Capsule::schema()->hasColumn('leads', 'privacy_accepted')) {
    Capsule::schema()->table('leads', function (Blueprint $table) {
        $table->boolean('privacy_accepted')->default(false)->after('message');
    });
    echo "✓ Added privacy_accepted column to leads table\n";
} else {
    echo "⊘ privacy_accepted column already exists\n";
}

// Add page_published field if not exists
if (!Capsule::schema()->hasColumn('leads', 'page_published')) {
    Capsule::schema()->table('leads', function (Blueprint $table) {
        $table->boolean('page_published')->default(false)->after('privacy_accepted');
    });
    echo "✓ Added page_published column to leads table\n";
} else {
    echo "⊘ page_published column already exists\n";
}

// Update existing metadata column type if needed (from JSON to TEXT for compatibility)
echo "\n✅ Leads table update completed!\n";
