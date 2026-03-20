<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Middleware\AuthMiddleware;

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
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Expose-Headers', 'Content-Disposition');
});

// Handle OPTIONS requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

// Routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'Landing Page Builder API - Authentication Enabled']));
    return $response->withHeader('Content-Type', 'application/json');
});

// ===== AUTH ROUTES (pubbliche) =====
$app->post('/api/auth/login', '\App\Controllers\AuthController:login');
$app->post('/api/auth/register', '\App\Controllers\AuthController:register');

// ===== PROTECTED ROUTES (richiedono autenticazione) =====
// Auth routes protette
$app->get('/api/auth/me', '\App\Controllers\AuthController:me')->add(AuthMiddleware::class);
$app->get('/api/auth/companies', '\App\Controllers\AuthController:getCompanies')->add(AuthMiddleware::class);
$app->get('/api/auth/users', '\App\Controllers\AuthController:getUsers')->add(AuthMiddleware::class);

// Admin routes (solo admin)
$app->post('/api/admin/companies', '\App\Controllers\AuthController:createCompany')->add(AuthMiddleware::class);
$app->delete('/api/admin/companies/{id}', '\App\Controllers\AuthController:deleteCompany')->add(AuthMiddleware::class);
$app->put('/api/admin/users/{id}', '\App\Controllers\AuthController:updateUser')->add(AuthMiddleware::class);
$app->delete('/api/admin/users/{id}', '\App\Controllers\AuthController:deleteUser')->add(AuthMiddleware::class);

// Company routes (solo company manager)
$app->post('/api/company/users', '\App\Controllers\AuthController:createUserInCompany')->add(AuthMiddleware::class);
$app->delete('/api/company/users/{id}', '\App\Controllers\AuthController:deleteUserFromCompany')->add(AuthMiddleware::class);
$app->get('/api/company/users-with-pages', '\App\Controllers\AuthController:getUsersWithPagesCount')->add(AuthMiddleware::class);
$app->put('/api/company/pages/{id}/reassign', '\App\Controllers\PageController:reassignPage')->add(AuthMiddleware::class);

// Pages routes (tutte protette)
$app->get('/api/pages', '\App\Controllers\PageController:index')->add(AuthMiddleware::class);
$app->get('/api/pages/archived', '\App\Controllers\PageController:archived')->add(AuthMiddleware::class);
$app->get('/api/pages/{id}', '\App\Controllers\PageController:show')->add(AuthMiddleware::class);
$app->post('/api/pages', '\App\Controllers\PageController:store')->add(AuthMiddleware::class);
$app->post('/api/pages/{id}/duplicate', '\App\Controllers\PageController:duplicate')->add(AuthMiddleware::class);
$app->post('/api/pages/{id}/restore', '\App\Controllers\PageController:restore')->add(AuthMiddleware::class);
$app->post('/api/pages/check-slug', '\App\Controllers\PageController:checkSlug')->add(AuthMiddleware::class);
$app->delete('/api/pages/{id}/force', '\App\Controllers\PageController:forceDelete')->add(AuthMiddleware::class);
$app->get('/api/pages/{id}/export', '\App\Controllers\PageController:export')->add(AuthMiddleware::class);
$app->post('/api/pages/import', '\App\Controllers\PageController:import')->add(AuthMiddleware::class);
$app->put('/api/pages/{id}/legal-info', '\App\Controllers\PageController:updateLegalInfo')->add(AuthMiddleware::class);
$app->post('/api/pages/{id}/legal-info', '\App\Controllers\PageController:updateLegalInfo')->add(AuthMiddleware::class); // POST alternativo per compatibilità Apache
$app->put('/api/pages/{id}/notification-settings', '\App\Controllers\PageController:updateNotificationSettings')->add(AuthMiddleware::class);
$app->post('/api/pages/{id}/notification-settings', '\App\Controllers\PageController:updateNotificationSettings')->add(AuthMiddleware::class); // POST alternativo per compatibilità Apache
$app->put('/api/pages/{id}', '\App\Controllers\PageController:update')->add(AuthMiddleware::class);
$app->delete('/api/pages/{id}', '\App\Controllers\PageController:delete')->add(AuthMiddleware::class);

// Upload routes (protette)
$app->post('/api/upload/image', '\App\Controllers\UploadController:uploadImage')->add(AuthMiddleware::class);
$app->post('/api/upload/video', '\App\Controllers\UploadController:uploadVideo')->add(AuthMiddleware::class);

// Leads routes (protette - solo per admin)
$app->get('/api/leads', '\App\Controllers\LeadController:index')->add(AuthMiddleware::class);
$app->delete('/api/leads/{id}', '\App\Controllers\LeadController:delete')->add(AuthMiddleware::class);

// ===== PUBLIC ROUTES (accessibili senza auth) =====
// Public page route (by slug) - Le LP pubblicate devono essere visibili a tutti
$app->get('/api/page/{slug}', '\App\Controllers\PageController:showBySlug');

// Leads routes - Form submission pubblica
$app->post('/api/leads', '\App\Controllers\LeadController:store');

$app->run();
