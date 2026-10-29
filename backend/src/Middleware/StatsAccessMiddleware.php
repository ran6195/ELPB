<?php

namespace App\Middleware;

use App\Audit\Auditor;
use App\Models\User;
use App\Utils\JWTHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Accesso alle statistiche (/api/stats/*) in due modi:
 * - chiave in sola lettura per Grafana: STATS_API_KEY nel .env (almeno 32 caratteri),
 *   come "Authorization: Bearer <chiave>" o nell'header X-Stats-Key; vede tutti i dati;
 * - token di un utente del pannello, con un ruolo tra STATS_USER_ROLES (.env, separati
 *   da virgola, predefinito "admin"); vede solo le pagine visibili a lui (StatsController).
 * Senza chiave configurata né utente valido le statistiche sono disattivate (503).
 */
class StatsAccessMiddleware
{
    public const MIN_KEY_LENGTH = 32;

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $key = (string) ($_ENV['STATS_API_KEY'] ?? '');
        $key = strlen($key) >= self::MIN_KEY_LENGTH ? $key : null;

        $given = $request->getHeaderLine('X-Stats-Key');
        if ($given === '') {
            // Come AuthMiddleware: con Apache + FastCGI l'header può arrivare solo in $_SERVER
            $authorization = $request->getHeaderLine('Authorization')
                ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
            if (str_starts_with($authorization, 'Bearer ')) {
                $given = substr($authorization, 7);
            }
        }

        if ($key !== null && $given !== '' && hash_equals($key, $given)) {
            return $handler->handle($request);
        }

        $user = $given !== '' ? self::user($given) : null;
        if ($user) {
            Auditor::get()->setUser($user);
            if (!in_array($user->role, self::allowedRoles(), true)) {
                return self::error(403, 'Statistiche non disponibili per questo utente');
            }
            return $handler->handle($request->withAttribute('user', $user));
        }

        if ($key === null) {
            return self::error(503, 'Statistiche non attive: STATS_API_KEY non configurata');
        }
        return self::error(401, 'Chiave delle statistiche mancante o non valida');
    }

    /** Ruoli del pannello che vedono le statistiche */
    public static function allowedRoles(): array
    {
        $roles = array_filter(array_map('trim', explode(',', (string) ($_ENV['STATS_USER_ROLES'] ?? 'admin'))));
        return $roles ?: ['admin'];
    }

    private static function user(string $token): ?User
    {
        $decoded = JWTHandler::decode($token);
        return $decoded && isset($decoded->id) ? User::find($decoded->id) : null;
    }

    private static function error(int $status, string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
