<?php

namespace App\Controllers;

use App\Models\Media;
use App\Services\MediaService;
use App\Services\MediaUploadException;
use App\Storage\LocalMediaStorage;
use App\Storage\MediaStorageFactory;
use Slim\Psr7\Stream;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Libreria media per cliente: ogni azienda vede e gestisce i propri media
 * (gli utenti senza azienda solo i propri, l'admin tutti).
 */
class MediaController
{
    private const PER_PAGE_DEFAULT = 40;
    private const PER_PAGE_MAX = 100;

    /** Relazioni incluse nelle risposte: autore e originale (anche se archiviato) */
    private function relations(): array
    {
        return [
            'user:id,name',
            'parent' => fn ($query) => $query->withTrashed()->select('id', 'original_name', 'disk', 'path', 'thumbnail_path', 'deleted_at'),
        ];
    }

    /**
     * GET /api/media?type=image|video&search=&company_id=&archived=1&page=1&per_page=40
     */
    public function index(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        $query = Media::visibleTo($user)->with($this->relations());

        if (!empty($params['archived'])) {
            $query->onlyTrashed();
        }
        if (in_array($params['type'] ?? '', ['image', 'video'], true)) {
            $query->where('type', $params['type']);
        }
        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', $search)
                  ->orWhere('alt_text', 'like', $search);
            });
        }
        // Filtro per azienda (solo admin, che altrimenti vede tutto)
        if ($user->isAdmin() && !empty($params['company_id'])) {
            $query->where('company_id', (int) $params['company_id']);
        }

        $perPage = min(self::PER_PAGE_MAX, max(1, (int) ($params['per_page'] ?? self::PER_PAGE_DEFAULT)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $total = (clone $query)->count();

        $items = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->json($response, [
            'data'      => $items,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    /**
     * POST /api/media  (multipart: file, poster?, width?, height?, duration?)
     */
    public function store(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $files = $request->getUploadedFiles();

        if (!isset($files['file'])) {
            return $this->json($response, ['error' => 'Nessun file inviato (campo "file")'], 400);
        }

        try {
            $media = (new MediaService())->upload(
                $files['file'],
                $user,
                null,
                (array) ($request->getParsedBody() ?? []),
                $files['poster'] ?? null
            );
            return $this->json($response, ['success' => true, 'data' => $media->load($this->relations())], 201);
        } catch (MediaUploadException $e) {
            return $this->json($response, ['error' => $e->getMessage()], $e->getStatus());
        }
    }

    /** GET /api/media/{id} */
    public function show(Request $request, Response $response, $args)
    {
        $media = $this->findVisible($request, $args['id'], true);
        if (!$media) {
            return $this->notFound($response);
        }
        return $this->json($response, ['data' => $media->load($this->relations())]);
    }

    /** PUT/POST /api/media/{id}  (alt_text, original_name) */
    public function update(Request $request, Response $response, $args)
    {
        $media = $this->findVisible($request, $args['id']);
        if (!$media) {
            return $this->notFound($response);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        if (!is_array($data)) {
            return $this->json($response, ['error' => 'Dati non validi'], 400);
        }

        if (array_key_exists('alt_text', $data)) {
            $alt = trim(strip_tags((string) $data['alt_text']));
            $media->alt_text = $alt === '' ? null : mb_substr($alt, 0, 255);
        }
        if (array_key_exists('original_name', $data)) {
            $name = trim(strip_tags((string) $data['original_name']));
            if ($name === '') {
                return $this->json($response, ['error' => 'Il nome non può essere vuoto'], 422);
            }
            $media->original_name = mb_substr($name, 0, 255);
        }

        $media->save();

        return $this->json($response, ['success' => true, 'data' => $media->load($this->relations())]);
    }

    /**
     * GET /api/media/{id}/file — contenuto del file servito dall'API.
     * Serve all'editor immagini: caricare l'originale dallo stesso endpoint
     * autenticato evita che il canvas venga bloccato dal CORS.
     */
    public function file(Request $request, Response $response, $args)
    {
        $media = $this->findVisible($request, $args['id'], true);
        if (!$media) {
            return $this->notFound($response);
        }

        $storage = MediaStorageFactory::disk($media->disk);
        $path = $storage instanceof LocalMediaStorage ? $storage->localPath($media->path) : null;
        if ($path === null) {
            return $this->notFound($response);
        }

        return $response
            ->withBody(new Stream(fopen($path, 'rb')))
            ->withHeader('Content-Type', $media->mime_type)
            ->withHeader('Content-Length', (string) filesize($path))
            ->withHeader('Cache-Control', 'private, max-age=300');
    }

    /**
     * POST /api/media/{id}/versions  (multipart: file, name?)
     * Salva un'immagine modificata come nuova versione dell'originale, che resta intatto.
     */
    public function storeVersion(Request $request, Response $response, $args)
    {
        $parent = $this->findVisible($request, $args['id']);
        if (!$parent) {
            return $this->notFound($response);
        }
        if ($parent->type !== 'image') {
            return $this->json($response, ['error' => 'Si possono modificare solo le immagini'], 422);
        }

        $files = $request->getUploadedFiles();
        if (!isset($files['file'])) {
            return $this->json($response, ['error' => 'Nessun file inviato (campo "file")'], 400);
        }

        $body = (array) ($request->getParsedBody() ?? []);
        $meta = isset($body['name']) ? ['name' => $body['name']] : [];

        try {
            $media = (new MediaService())->upload($files['file'], $request->getAttribute('user'), 'image', $meta, null, $parent);
            return $this->json($response, ['success' => true, 'data' => $media->load($this->relations())], 201);
        } catch (MediaUploadException $e) {
            return $this->json($response, ['error' => $e->getMessage()], $e->getStatus());
        }
    }

    /** GET /api/media/{id}/usage */
    public function usage(Request $request, Response $response, $args)
    {
        $media = $this->findVisible($request, $args['id'], true);
        if (!$media) {
            return $this->notFound($response);
        }
        return $this->json($response, ['data' => (new MediaService())->findUsage($media)]);
    }

    /**
     * DELETE /api/media/{id}  → archivia (soft delete).
     * Se il media è usato in qualche pagina risponde 409 con l'elenco,
     * a meno che non venga passato ?confirm=1.
     */
    public function delete(Request $request, Response $response, $args)
    {
        $media = $this->findVisible($request, $args['id']);
        if (!$media) {
            return $this->notFound($response);
        }

        $confirm = !empty($request->getQueryParams()['confirm']);
        if (!$confirm) {
            $usage = (new MediaService())->findUsage($media);
            if (!empty($usage)) {
                return $this->json($response, [
                    'error' => 'Il file è usato in una o più pagine',
                    'usage' => $usage,
                ], 409);
            }
        }

        $media->delete();

        return $this->json($response, ['success' => true]);
    }

    /** POST /api/media/{id}/restore */
    public function restore(Request $request, Response $response, $args)
    {
        $media = $this->findVisible($request, $args['id'], true);
        if (!$media || !$media->trashed()) {
            return $this->notFound($response);
        }

        $media->restore();

        return $this->json($response, ['success' => true, 'data' => $media]);
    }

    /** DELETE /api/media/{id}/force → elimina record e file (solo media archiviati) */
    public function forceDelete(Request $request, Response $response, $args)
    {
        $media = $this->findVisible($request, $args['id'], true);
        if (!$media) {
            return $this->notFound($response);
        }
        if (!$media->trashed()) {
            return $this->json($response, ['error' => 'Archivia il file prima di eliminarlo definitivamente'], 422);
        }

        // I file importati dai vecchi upload possono essere usati anche fuori da
        // questo database (pagine esportate, email, siti esterni): solo l'admin
        if ($media->legacy && !$request->getAttribute('user')->isAdmin()) {
            return $this->json($response, [
                'error' => 'Questo file proviene dai caricamenti precedenti alla libreria: può eliminarlo definitivamente solo un amministratore',
            ], 403);
        }

        // Mai cancellare fisicamente un file ancora usato da una pagina
        $service = new MediaService();
        $usage = $service->findUsage($media);
        if (!empty($usage)) {
            return $this->json($response, [
                'error' => 'Il file è ancora usato in ' . count($usage) . ' pagina/e: sostituiscilo nelle pagine prima di eliminarlo definitivamente',
                'usage' => $usage,
            ], 409);
        }

        $service->forceDelete($media);

        return $this->json($response, ['success' => true]);
    }

    private function findVisible(Request $request, $id, bool $withTrashed = false): ?Media
    {
        $query = Media::visibleTo($request->getAttribute('user'));
        if ($withTrashed) {
            $query->withTrashed();
        }
        return $query->find((int) $id);
    }

    private function notFound(Response $response): Response
    {
        return $this->json($response, ['error' => 'File non trovato'], 404);
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
