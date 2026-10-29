<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Accesso alle statistiche per Grafana (/api/stats/*) con una chiave in sola lettura,
 * separata dagli utenti: STATS_API_KEY nel .env, almeno 32 caratteri.
 * La chiave arriva come "Authorization: Bearer <chiave>" oppure nell'header X-Stats-Key.
 * Senza chiave configurata le statistiche sono disattivate (503).
 */
class StatsKeyMiddleware
{
    public const MIN_KEY_LENGTH = 32;

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $expected = (string) ($_ENV['STATS_API_KEY'] ?? '');
        if (strlen($expected) < self::MIN_KEY_LENGTH) {
            return self::error(503, 'Statistiche non attive: STATS_API_KEY non configurata');
        }

        $given = $request->getHeaderLine('X-Stats-Key');
        if ($given === '') {
            // Come AuthMiddleware: con Apache + FastCGI l'header può arrivare solo in $_SERVER
            $authorization = $request->getHeaderLine('Authorization')
                ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
            if (str_starts_with($authorization, 'Bearer ')) {
                $given = substr($authorization, 7);
            }
        }

        if ($given === '' || !hash_equals($expected, $given)) {
            return self::error(401, 'Chiave delle statistiche mancante o non valida');
        }

        return $handler->handle($request);
    }

    private static function error(int $status, string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
