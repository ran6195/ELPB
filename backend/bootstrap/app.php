<?php

/**
 * Crea l'applicazione Slim (ambiente, database, middleware, rotte) senza eseguirla:
 * la usano public/index.php e i test.
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\RecordActivity;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Timezone (senza questo PHP usa UTC di default e i timestamp dei leads
// vengono salvati sfasati rispetto all'ora italiana)
date_default_timezone_set('Europe/Rome');

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

// Middleware: l'ultimo aggiunto è il più esterno. Ordine di esecuzione:
// CORS → RecordActivity → ErrorMiddleware → routing → captureRoute → rotta.
// RecordActivity sta fuori da ErrorMiddleware per vedere anche gli errori già
// trasformati in risposta; captureRoute (dopo il routing) gli passa la rotta.
$app->add([RecordActivity::class, 'captureRoute']);
$app->addRoutingMiddleware();

// Add error middleware
$app->addErrorMiddleware(
    $_ENV['APP_DEBUG'] === 'true',
    true,
    true
);

$app->add(new RecordActivity());

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

require __DIR__ . '/../config/routes.php';

return $app;
