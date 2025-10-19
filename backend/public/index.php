<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize database
require __DIR__ . '/../config/database.php';

// Create Slim app
$app = AppFactory::create();

// Set base path for subfolder deployment
// Change this to match your server path (e.g., '/ELPB/backend/public')
// For local development, comment this line or set to ''
$basePath = $_ENV['BASE_PATH'] ?? '';
if (!empty($basePath)) {
    $app->setBasePath($basePath);
}

// Add error middleware
$app->addErrorMiddleware(
    $_ENV['APP_DEBUG'] === 'true',
    true,
    true
);

// Add CORS middleware
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Handle OPTIONS requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

// Routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'Landing Page Builder API']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Pages routes
$app->get('/api/pages', '\App\Controllers\PageController:index');
$app->get('/api/pages/{id}', '\App\Controllers\PageController:show');
$app->post('/api/pages', '\App\Controllers\PageController:store');
$app->put('/api/pages/{id}', '\App\Controllers\PageController:update');
$app->delete('/api/pages/{id}', '\App\Controllers\PageController:delete');

// Public page route (by slug)
$app->get('/api/page/{slug}', '\App\Controllers\PageController:showBySlug');

// Leads routes
$app->post('/api/leads', '\App\Controllers\LeadController:store');

// Upload routes
$app->post('/api/upload/image', '\App\Controllers\UploadController:uploadImage');

// Debug routes (rimuovere in produzione dopo il debug)
$app->get('/api/debug/database', '\App\Controllers\DebugController:checkDatabase');
$app->post('/api/debug/test-update', '\App\Controllers\DebugController:testUpdate');

$app->run();
