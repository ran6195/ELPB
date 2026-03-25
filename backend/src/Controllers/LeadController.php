<?php

namespace App\Controllers;

use App\Models\Lead;
use App\Models\Page;
use App\Services\EmailService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LeadController
{
    public function index(Request $request, Response $response)
    {
        // Get authenticated user from middleware
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // If admin, get all leads
        if ($user->role === 'admin') {
            $leads = Lead::with('page')->orderBy('created_at', 'desc')->get();
        }
        // If company or user, get only leads from their company's pages
        else if ($user->company_id) {
            $leads = Lead::with('page')
                ->whereHas('page', function($query) use ($user) {
                    $query->where('company_id', $user->company_id);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }
        // No company associated
        else {
            $leads = [];
        }

        $response->getBody()->write(json_encode($leads));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function addNote(Request $request, Response $response, $args)
    {
        $leadId = $args['id'];
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $noteText = trim($data['note'] ?? '');

        if (empty($noteText)) {
            $response->getBody()->write(json_encode(['error' => 'Note text is required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $lead = Lead::with('page')->find($leadId);
        if (!$lead) {
            $response->getBody()->write(json_encode(['error' => 'Lead not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Check permissions
        if ($user->role !== 'admin' && $user->company_id) {
            if (!$lead->page || $lead->page->company_id !== $user->company_id) {
                $response->getBody()->write(json_encode(['error' => 'Forbidden']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        }

        $metadata = $lead->metadata ?? [];
        $notes = $metadata['_notes'] ?? [];
        $notes[] = [
            'text' => $noteText,
            'timestamp' => date('c'),
            'author' => $user->name ?? $user->email
        ];
        $metadata['_notes'] = $notes;

        $lead->metadata = $metadata;
        $lead->save();

        $response->getBody()->write(json_encode(['message' => 'Note added', 'metadata' => $metadata]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateAppointment(Request $request, Response $response, $args)
    {
        $leadId = $args['id'];
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $lead = Lead::with('page')->find($leadId);
        if (!$lead) {
            $response->getBody()->write(json_encode(['error' => 'Lead not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        if ($user->role !== 'admin' && $user->company_id) {
            if (!$lead->page || $lead->page->company_id !== $user->company_id) {
                $response->getBody()->write(json_encode(['error' => 'Forbidden']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $date = trim($data['date'] ?? '');
        $time = trim($data['time'] ?? '');

        if (empty($date)) {
            $response->getBody()->write(json_encode(['error' => 'La data è obbligatoria']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $metadata = $lead->metadata ?? [];
        $metadata['_appointment'] = [
            'date' => $date,
            'time' => $time,
            'notes' => trim($data['notes'] ?? ''),
            'set_by' => $user->name ?? $user->email,
            'set_at' => date('c')
        ];

        $lead->metadata = $metadata;
        $lead->save();

        $response->getBody()->write(json_encode(['message' => 'Appuntamento salvato', 'metadata' => $metadata]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, $args)
    {
        $leadId = $args['id'];
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $lead = Lead::with('page')->find($leadId);
        if (!$lead) {
            $response->getBody()->write(json_encode(['error' => 'Lead not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Check permissions: admin can delete all, company can delete only their leads
        if ($user->role !== 'admin' && $user->company_id) {
            if (!$lead->page || $lead->page->company_id !== $user->company_id) {
                $response->getBody()->write(json_encode(['error' => 'Forbidden']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        }

        $lead->delete();

        $response->getBody()->write(json_encode(['message' => 'Lead deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validate email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Valid email is required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Get page and verify reCAPTCHA if enabled
        if (!empty($data['page_id'])) {
            $page = Page::find($data['page_id']);
            if ($page) {
                // Verify reCAPTCHA if enabled
                if ($page->recaptcha_settings &&
                    isset($page->recaptcha_settings['enabled']) &&
                    $page->recaptcha_settings['enabled'] === true) {

                    // Check if reCAPTCHA token is present
                    if (empty($data['recaptcha_token'])) {
                        $response->getBody()->write(json_encode(['error' => 'reCAPTCHA verification is required']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }

                    // Verify reCAPTCHA token
                    $secretKey = $page->recaptcha_settings['secret_key'] ?? '';
                    if (empty($secretKey)) {
                        $response->getBody()->write(json_encode(['error' => 'reCAPTCHA configuration error']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
                    }

                    $recaptchaValid = $this->verifyRecaptcha($data['recaptcha_token'], $secretKey);
                    if (!$recaptchaValid) {
                        $response->getBody()->write(json_encode(['error' => 'reCAPTCHA verification failed. Please try again.']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }
                }
            }
        }

        // Campi standard mappati a colonne dedicate
        $standardKeys = ['page_id', 'name', 'email', 'phone', 'message', 'recaptcha_token'];

        // Tutti i campi extra (campi custom del form avanzato) vanno in metadata
        $metadata = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $standardKeys)) {
                $metadata[$key] = $value;
            }
        }

        // Create lead
        $lead = Lead::create([
            'page_id' => $data['page_id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'metadata' => $metadata
        ]);

        // Invia notifica email (non bloccante)
        try {
            if (empty($lead->page_id)) {
                error_log("LeadController: Lead #{$lead->id} non ha page_id, skip notifica email");
            } else {
                $page = Page::with('user', 'company')->find($lead->page_id);

                if (!$page) {
                    error_log("LeadController: Pagina #{$lead->page_id} non trovata per lead #{$lead->id}");
                } elseif (empty($page->notification_settings)) {
                    error_log("LeadController: Notifiche non configurate per pagina #{$page->id} (slug: {$page->slug})");
                } elseif (empty($page->notification_settings['enabled'])) {
                    error_log("LeadController: Notifiche disabilitate per pagina #{$page->id} (slug: {$page->slug})");
                } else {
                    error_log("LeadController: Invio notifica email per lead #{$lead->id} pagina #{$page->id} (slug: {$page->slug})");
                    $emailService = new EmailService();
                    $result = $emailService->sendLeadNotification($lead, $page);

                    if ($result) {
                        error_log("LeadController: Email notifica lead #{$lead->id} inviata con successo");
                    } else {
                        error_log("LeadController: Email notifica lead #{$lead->id} FALLITA (controlla log EmailService)");
                    }

                    // Email di cortesia al cliente
                    $emailService->sendLeadConfirmation($lead, $page);
                }
            }
        } catch (\Exception $e) {
            // Log errore ma non bloccare risposta
            error_log("LeadController: Errore invio email notifica lead #{$lead->id}: " . $e->getMessage());
            error_log("LeadController: Stack trace: " . $e->getTraceAsString());
        }

        $response->getBody()->write(json_encode([
            'message' => 'Lead saved successfully',
            'lead' => $lead
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Verify reCAPTCHA token with Google API
     */
    private function verifyRecaptcha($token, $secretKey)
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $token
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            return false;
        }

        $responseData = json_decode($result, true);
        return isset($responseData['success']) && $responseData['success'] === true;
    }
}
