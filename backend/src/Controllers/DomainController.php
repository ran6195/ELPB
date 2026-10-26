<?php

namespace App\Controllers;

use App\Models\Domain;
use App\Models\Page;
use App\Utils\Hostname;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Anagrafica dei domini (siti dei clienti) su cui sono pubblicate le landing page.
 * Tutti gli utenti vedono i domini della propria azienda per associarli alle pagine;
 * solo admin e responsabile aziendale possono crearli, modificarli ed eliminarli.
 */
class DomainController
{
    private const LABEL_MAX = 100;

    /**
     * GET /api/domains?company_id=  (company_id solo per admin)
     */
    public function index(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        $query = Domain::visibleTo($user)
            ->with(['company:id,name', 'pages:id,title,slug,is_published'])
            ->withCount('leads');

        if ($user->isAdmin() && !empty($params['company_id'])) {
            $query->where('company_id', (int) $params['company_id']);
        }

        $domains = $query->orderBy('hostname')->get();

        return $this->json($response, ['data' => $domains]);
    }

    /**
     * POST /api/domains  { hostname, label?, company_id? (solo admin), page_id? }
     * Con page_id il dominio prende il proprietario della pagina e le viene associato.
     */
    public function store(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        if (!$this->canManage($user)) {
            return $this->forbidden($response);
        }

        $data = json_decode($request->getBody()->getContents(), true) ?: [];

        $hostname = Hostname::normalize($data['hostname'] ?? '');
        if ($hostname === null) {
            return $this->invalid($response, $data['hostname'] ?? '');
        }

        $page = null;
        if (!empty($data['page_id'])) {
            $page = Page::find((int) $data['page_id']);
            if (!$page || !$user->canEditPage($page)) {
                return $this->json($response, ['error' => 'Pagina non trovata'], 404);
            }
            // Proprietario della pagina (per le pagine senza azienda, il suo autore)
            $companyId = $page->company_id;
            $ownerUserId = $page->company_id === null ? $page->user_id : $user->id;
        } elseif ($user->isAdmin() && !empty($data['company_id'])) {
            $companyId = (int) $data['company_id'];
            $ownerUserId = $user->id;
        } else {
            $companyId = $user->company_id;
            $ownerUserId = $user->id;
        }

        if (Domain::hostnameTaken($hostname, $companyId, $ownerUserId)) {
            return $this->json($response, ['error' => "Il dominio {$hostname} è già presente"], 409);
        }

        $domain = Domain::create([
            'company_id' => $companyId,
            'user_id'    => $ownerUserId,
            'hostname'   => $hostname,
            'label'      => $this->cleanLabel($data['label'] ?? null),
        ]);

        if ($page) {
            $page->domains()->syncWithoutDetaching([$domain->id]);
            $domain->linkExistingLeads($page->id);
        }

        return $this->json($response, ['success' => true, 'data' => $this->fresh($domain)], 201);
    }

    /**
     * PUT/POST /api/domains/{id}  { hostname?, label? }
     */
    public function update(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        if (!$this->canManage($user)) {
            return $this->forbidden($response);
        }

        $domain = Domain::visibleTo($user)->find((int) $args['id']);
        if (!$domain) {
            return $this->notFound($response);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        if (!is_array($data)) {
            return $this->json($response, ['error' => 'Dati non validi'], 400);
        }

        if (array_key_exists('hostname', $data)) {
            $hostname = Hostname::normalize($data['hostname']);
            if ($hostname === null) {
                return $this->invalid($response, $data['hostname']);
            }
            if (Domain::hostnameTaken($hostname, $domain->company_id, $domain->user_id, $domain->id)) {
                return $this->json($response, ['error' => "Il dominio {$hostname} è già presente"], 409);
            }
            $domain->hostname = $hostname;
        }

        if (array_key_exists('label', $data)) {
            $domain->label = $this->cleanLabel($data['label']);
        }

        $domain->save();

        return $this->json($response, ['success' => true, 'data' => $this->fresh($domain)]);
    }

    /**
     * DELETE /api/domains/{id}
     * Toglie il dominio dalle pagine; i lead restano con il loro origin_host.
     */
    public function delete(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        if (!$this->canManage($user)) {
            return $this->forbidden($response);
        }

        $domain = Domain::visibleTo($user)->find((int) $args['id']);
        if (!$domain) {
            return $this->notFound($response);
        }

        $domain->pages()->detach();
        $domain->delete();

        return $this->json($response, ['success' => true]);
    }

    private function canManage($user): bool
    {
        return $user && ($user->isAdmin() || $user->isCompany());
    }

    private function cleanLabel($label): ?string
    {
        $label = trim(preg_replace('/\s+/u', ' ', (string) $label));
        return $label === '' ? null : mb_substr($label, 0, self::LABEL_MAX);
    }

    private function fresh(Domain $domain): Domain
    {
        return $domain->fresh()
            ->load(['company:id,name', 'pages:id,title,slug,is_published'])
            ->loadCount('leads');
    }

    private function invalid(Response $response, $value): Response
    {
        return $this->json($response, ['error' => Hostname::invalidReason((string) $value)], 422);
    }

    private function forbidden(Response $response): Response
    {
        return $this->json($response, ['error' => 'Solo l\'amministratore o il responsabile aziendale possono gestire i domini'], 403);
    }

    private function notFound(Response $response): Response
    {
        return $this->json($response, ['error' => 'Dominio non trovato'], 404);
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
