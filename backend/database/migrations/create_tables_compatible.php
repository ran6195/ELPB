<?php

require __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// Connect to MySQL without database to create it first
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'port'      => $_ENV['DB_PORT'] ?? '3306',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Create database if not exists
$dbName = $_ENV['DB_DATABASE'];
Capsule::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
Capsule::statement("USE `{$dbName}`");

echo "Creating tables...\n";

// Drop tables if exists (for development)
Capsule::schema()->dropIfExists('leads');
Capsule::schema()->dropIfExists('blocks');
Capsule::schema()->dropIfExists('pages');

// Create pages table
Capsule::schema()->create('pages', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    $table->boolean('is_published')->default(false);
    $table->text('styles')->nullable(); // Usato TEXT invece di JSON per compatibilità con MySQL < 5.7.8
    $table->timestamps();
});

echo "✓ Pages table created\n";

// Create blocks table
Capsule::schema()->create('blocks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
    $table->string('type'); // hero, text, image, form, cta, video, testimonial, footer
    $table->text('content')->nullable(); // Usato TEXT invece di JSON per compatibilità
    $table->text('styles')->nullable(); // Usato TEXT invece di JSON per compatibilità
    $table->text('position')->nullable(); // Usato TEXT invece di JSON per compatibilità
    $table->integer('order')->default(0); // ordine di visualizzazione
    $table->timestamps();
});

echo "✓ Blocks table created\n";

// Create leads table
Capsule::schema()->create('leads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->nullable()->constrained('pages')->onDelete('set null');
    $table->string('name')->nullable();
    $table->string('email');
    $table->string('phone')->nullable();
    $table->text('message')->nullable();
    $table->text('metadata')->nullable(); // Usato TEXT invece di JSON per compatibilità
    $table->timestamps();
});

echo "✓ Leads table created\n";

// Insert sample data
echo "\nInserting sample data...\n";

$pageId = Capsule::table('pages')->insertGetId([
    'title' => 'Homepage Demo',
    'slug' => 'homepage-demo',
    'meta_title' => 'Landing Page Demo - Il tuo prodotto',
    'meta_description' => 'Scopri il nostro prodotto fantastico',
    'is_published' => true,
    'styles' => json_encode([
        'backgroundColor' => '#FFFFFF'
    ]),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);

Capsule::table('blocks')->insert([
    [
        'page_id' => $pageId,
        'type' => 'hero',
        'content' => json_encode([
            'title' => 'Il Tuo Prodotto Rivoluzionario',
            'subtitle' => 'La soluzione che stavi cercando',
            'buttonText' => 'Scopri di più',
            'buttonLink' => '#form',
            'backgroundImage' => ''
        ]),
        'styles' => json_encode([
            'backgroundColor' => '#4F46E5',
            'textColor' => '#FFFFFF',
            'padding' => '80px 20px'
        ]),
        'position' => json_encode(['x' => 0, 'y' => 0, 'width' => 12, 'height' => 400]),
        'order' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'page_id' => $pageId,
        'type' => 'text',
        'content' => json_encode([
            'title' => 'Perché sceglierci',
            'text' => 'Offriamo la migliore soluzione sul mercato con caratteristiche uniche e un supporto eccezionale.'
        ]),
        'styles' => json_encode([
            'backgroundColor' => '#FFFFFF',
            'textColor' => '#1F2937',
            'padding' => '60px 20px'
        ]),
        'position' => json_encode(['x' => 0, 'y' => 400, 'width' => 12, 'height' => 200]),
        'order' => 2,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'page_id' => $pageId,
        'type' => 'form',
        'content' => json_encode([
            'title' => 'Richiedi Informazioni',
            'fields' => [
                ['name' => 'name', 'label' => 'Nome', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['name' => 'phone', 'label' => 'Telefono', 'type' => 'tel', 'required' => false],
                ['name' => 'message', 'label' => 'Messaggio', 'type' => 'textarea', 'required' => false]
            ],
            'buttonText' => 'Invia Richiesta'
        ]),
        'styles' => json_encode([
            'backgroundColor' => '#F3F4F6',
            'textColor' => '#1F2937',
            'padding' => '60px 20px'
        ]),
        'position' => json_encode(['x' => 0, 'y' => 600, 'width' => 12, 'height' => 400]),
        'order' => 3,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]
]);

echo "✓ Sample page and blocks inserted\n";

echo "\n✅ Database setup completed!\n";
