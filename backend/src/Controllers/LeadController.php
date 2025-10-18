<?php

namespace App\Controllers;

use App\Models\Lead;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LeadController
{
    public function store(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validate email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Valid email is required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Create lead
        $lead = Lead::create([
            'page_id' => $data['page_id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'metadata' => $data['metadata'] ?? []
        ]);

        $response->getBody()->write(json_encode([
            'message' => 'Lead saved successfully',
            'lead' => $lead
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
