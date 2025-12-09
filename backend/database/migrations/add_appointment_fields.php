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

echo "Updating leads table with appointment fields...\n";

// Check if leads table exists
if (!Capsule::schema()->hasTable('leads')) {
    echo "⚠ Leads table doesn't exist yet. Run create_tables.php first.\n";
    exit(1);
}

// Add appointment_requested field if not exists
if (!Capsule::schema()->hasColumn('leads', 'appointment_requested')) {
    Capsule::schema()->table('leads', function (Blueprint $table) {
        $table->boolean('appointment_requested')->default(false)->after('page_published');
    });
    echo "✓ Added appointment_requested column to leads table\n";
} else {
    echo "⊘ appointment_requested column already exists\n";
}

// Add appointment_datetime field if not exists
if (!Capsule::schema()->hasColumn('leads', 'appointment_datetime')) {
    Capsule::schema()->table('leads', function (Blueprint $table) {
        $table->dateTime('appointment_datetime')->nullable()->after('appointment_requested');
    });
    echo "✓ Added appointment_datetime column to leads table\n";
} else {
    echo "⊘ appointment_datetime column already exists\n";
}

echo "\n✅ Leads table appointment fields update completed!\n";
