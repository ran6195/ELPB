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

echo "Creating authentication tables...\n";

// Create companies table
if (!Capsule::schema()->hasTable('companies')) {
    Capsule::schema()->create('companies', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });
    echo "✓ Companies table created\n";
} else {
    echo "⊘ Companies table already exists\n";
}

// Create users table
if (!Capsule::schema()->hasTable('users')) {
    Capsule::schema()->create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->enum('role', ['admin', 'company', 'user'])->default('user');
        $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
        $table->timestamps();
    });
    echo "✓ Users table created\n";
} else {
    echo "⊘ Users table already exists\n";
}

// Add company_id and user_id to pages table if not exists
if (Capsule::schema()->hasTable('pages')) {
    if (!Capsule::schema()->hasColumn('pages', 'company_id')) {
        Capsule::schema()->table('pages', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
        });
        echo "✓ Added company_id to pages table\n";
    }

    if (!Capsule::schema()->hasColumn('pages', 'user_id')) {
        Capsule::schema()->table('pages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('company_id')->constrained('users')->onDelete('set null');
        });
        echo "✓ Added user_id to pages table\n";
    }
} else {
    echo "⚠ Pages table doesn't exist yet. Run create_tables.php first.\n";
}

// Insert default admin user
$adminExists = Capsule::table('users')->where('email', 'admin@example.com')->exists();

if (!$adminExists) {
    Capsule::table('users')->insert([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'company_id' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    echo "✓ Default admin user created (admin@example.com / admin123)\n";
} else {
    echo "⊘ Admin user already exists\n";
}

// Insert demo company
$companyExists = Capsule::table('companies')->where('email', 'demo@company.com')->exists();

if (!$companyExists) {
    $companyId = Capsule::table('companies')->insertGetId([
        'name' => 'Demo Company',
        'email' => 'demo@company.com',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // Insert demo company user
    Capsule::table('users')->insert([
        'name' => 'Company Manager',
        'email' => 'company@example.com',
        'password' => password_hash('company123', PASSWORD_DEFAULT),
        'role' => 'company',
        'company_id' => $companyId,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // Insert demo user
    Capsule::table('users')->insert([
        'name' => 'Regular User',
        'email' => 'user@example.com',
        'password' => password_hash('user123', PASSWORD_DEFAULT),
        'role' => 'user',
        'company_id' => $companyId,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    echo "✓ Demo company and users created\n";
    echo "  - Company Manager: company@example.com / company123\n";
    echo "  - Regular User: user@example.com / user123\n";
} else {
    echo "⊘ Demo company already exists\n";
}

echo "\n✅ Authentication tables setup completed!\n";
echo "\nDefault credentials:\n";
echo "- Admin: admin@example.com / admin123\n";
echo "- Company: company@example.com / company123\n";
echo "- User: user@example.com / user123\n";
