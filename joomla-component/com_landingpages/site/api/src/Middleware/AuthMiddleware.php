<?php

namespace App\Middleware;

use App\Models\User;
use App\Utils\JWTHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Prova a leggere l'header Authorization in diversi modi
        // (necessario per compatibilità con Apache + FastCGI/mod_php)
        $authHeader = $request->getHeaderLine('Authorization');

        // Fallback 1: Controlla $_SERVER['HTTP_AUTHORIZATION']
        if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Fallback 2: Controlla REDIRECT_HTTP_AUTHORIZATION (mod_rewrite)
        if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (empty($authHeader)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'No authorization token provided'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // Estrai il token (formato: "Bearer <token>")
        $token = str_replace('Bearer ', '', $authHeader);

        $decoded = JWTHandler::decode($token);

        if (!$decoded) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // Carica l'utente dal database
        $user = User::find($decoded->id);

        if (!$user) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'User not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // Aggiungi l'utente alla richiesta
        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }
}
