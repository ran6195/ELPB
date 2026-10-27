<?php

namespace Tests;

use App\Models\User;
use App\Utils\JWTHandler;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Base dei test feature: crea l'applicazione e svuota il database dei test prima di ogni test.
 */
abstract class TestCase extends BaseTestCase
{
    protected App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = require __DIR__ . '/../bootstrap/app.php';
        // Ogni test ha un dispatcher nuovo: i modelli devono registrare di nuovo i loro eventi
        Model::clearBootedModels();

        $database = Capsule::connection()->getDatabaseName();
        if (!str_ends_with($database, '_test')) {
            $this->fail("I test girano solo su un database *_test (trovato: $database)");
        }
        $this->truncateAll();
    }

    /** Esegue una richiesta sull'applicazione; $body viene inviato come JSON */
    protected function request(string $method, string $uri, $body = null, array $headers = [], ?User $as = null): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri, ['REMOTE_ADDR' => '127.0.0.1']);
        if ($body !== null) {
            $request = $request
                ->withBody((new StreamFactory())->createStream(is_string($body) ? $body : json_encode($body)))
                ->withHeader('Content-Type', 'application/json');
        }
        if ($as) {
            $request = $request->withHeader('Authorization', 'Bearer ' . JWTHandler::encode(['id' => $as->id]));
        }
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        return $this->app->handle($request);
    }

    protected function json(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true) ?? [];
    }

    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Utente Test',
            'email' => 'utente' . uniqid() . '@example.test',
            'password' => password_hash('password-test', PASSWORD_DEFAULT),
            'role' => 'user',
        ], $attributes));
    }

    private function truncateAll(): void
    {
        $connection = Capsule::connection();
        $connection->statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($connection->select('SHOW TABLES') as $row) {
            $connection->table(array_values((array) $row)[0])->truncate();
        }
        $connection->statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
