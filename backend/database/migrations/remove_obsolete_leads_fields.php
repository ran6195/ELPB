<?php
/**
 * Migration: Remove obsolete fields from leads table
 *
 * Removes fields that were added in previous migrations but are no longer used:
 * - privacy_accepted (validation removed)
 * - page_published (not needed)
 * - appointment_requested (feature removed)
 * - appointment_datetime (feature removed)
 *
 * Run this migration if you have these fields in your database
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// Load .env
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Setup database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'database'  => $_ENV['DB_DATABASE'] ?? 'landing_page_builder',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Removing obsolete fields from leads table...\n\n";

// Check if leads table exists
if (!Capsule::schema()->hasTable('leads')) {
    echo "⚠ Leads table doesn't exist. Nothing to do.\n";
    exit(0);
}

$fieldsRemoved = 0;

// Remove privacy_accepted field if exists
if (Capsule::schema()->hasColumn('leads', 'privacy_accepted')) {
    Capsule::schema()->table('leads', function (Blueprint $table) {
        $table->dropColumn('privacy_accepted');
    });
    echo "✓ Removed privacy_accepted column\n";
    $fieldsRemoved++;
} else {
    echo "⊘ privacy_accepted column doesn't exist\n";
}

// Remove page_published field if exists
if (Capsule::schema()->hasColumn('leads', 'page_published')) {
    Capsule::schema()->table('leads', function (Blueprint $table) {
        $table->dropColumn('page_published');
    });
    echo "✓ Removed page_published column\n";
    $fieldsRemoved++;
} else {
    echo "⊘ page_published column doesn't exist\n";
}

// Remove appointment_requested field if exists
if (Capsule::schema()->hasColumn('leads', 'appointment_requested')) {
    Capsule::schema()->table('leads', function (Blueprint $table) {
        $table->dropColumn('appointment_requested');
    });
    echo "✓ Removed appointment_requested column\n";
    $fieldsRemoved++;
} else {
    echo "⊘ appointment_requested column doesn't exist\n";
}

// Remove appointment_datetime field if exists
if (Capsule::schema()->hasColumn('leads', 'appointment_datetime')) {
    Capsule::schema()->table('leads', function (Blueprint $table) {
        $table->dropColumn('appointment_datetime');
    });
    echo "✓ Removed appointment_datetime column\n";
    $fieldsRemoved++;
} else {
    echo "⊘ appointment_datetime column doesn't exist\n";
}

echo "\n";
if ($fieldsRemoved > 0) {
    echo "✅ Migration completed! Removed $fieldsRemoved obsolete field(s).\n";
} else {
    echo "✅ No obsolete fields found. Database is already clean.\n";
}

echo "\nCurrent leads table structure:\n";
$columns = Capsule::schema()->getColumnListing('leads');
foreach ($columns as $column) {
    echo "  - $column\n";
}
