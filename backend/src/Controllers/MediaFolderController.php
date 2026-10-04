<?php

namespace App\Controllers;

use App\Models\Media;
use App\Models\MediaFolder;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Cartelle logiche della libreria media. Spostare file o cartelle cambia solo
 * il database: i file restano dove sono e gli URL nelle pagine non cambiano.
 */
class MediaFolderController
{
    private const NAME_MAX = 100;

    /**
     * GET /api/media-folders?company_id=  → elenco piatto (il frontend costruisce l'albero)
     */
    public function index(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        $query = MediaFolder::visibleTo($user)
            ->with('company:id,name')
            ->withCount('media');

        if ($user->isAdmin() && !empty($params['company_id'])) {
            $query->where('company_id', (int) $params['company_id']);
        }

        $folders = $query->orderBy('name')->get();

        return $this->json($response, ['data' => $folders]);
    }

    /**
     * POST /api/media-folders  { name, parent_id?, company_id? (solo admin, in radice) }
     */
    public function store(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $data = json_decode($request->getBody()->getContents(), true) ?: [];

        $name = $this->cleanName($data['name'] ?? '');
        if ($name === '') {
            return $this->json($response, ['error' => 'Inserisci un nome per la cartella'], 422);
        }

        $parent = null;
        if (!empty($data['parent_id'])) {
            $parent = MediaFolder::visibleTo($user)->find((int) $data['parent_id']);
            if (!$parent) {
                return $this->json($response, ['error' => 'Cartella superiore non trovata'], 404);
            }
        }

        // Proprietario: quello della cartella superiore; in radice l'azienda dell'utente
        // (l'admin può creare cartelle in radice per un'azienda specifica)
        if ($parent) {
            $companyId = $parent->company_id;
            $ownerUserId = $parent->company_id === null ? $parent->user_id : $user->id;
        } elseif ($user->isAdmin() && !empty($data['company_id'])) {
            $companyId = (int) $data['company_id'];
            $ownerUserId = $user->id;
        } else {
            $companyId = $user->company_id;
            $ownerUserId = $user->id;
        }

        if ($this->nameTaken($name, $parent ? $parent->id : null, $companyId, $ownerUserId)) {
            return $this->json($response, ['error' => 'Esiste già una cartella con questo nome'], 422);
        }

        $folder = MediaFolder::create([
            'company_id' => $companyId,
            'user_id'    => $ownerUserId,
            'parent_id'  => $parent ? $parent->id : null,
            'name'       => $name,
        ]);

        return $this->json($response, ['success' => true, 'data' => $this->fresh($folder)], 201);
    }

    /**
     * PUT/POST /api/media-folders/{id}  { name?, parent_id? (null = radice) }
     */
    public function update(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $folder = MediaFolder::visibleTo($user)->find((int) $args['id']);
        if (!$folder) {
            return $this->notFound($response);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        if (!is_array($data)) {
            return $this->json($response, ['error' => 'Dati non validi'], 400);
        }

        $name = array_key_exists('name', $data) ? $this->cleanName($data['name']) : $folder->name;
        if ($name === '') {
            return $this->json($response, ['error' => 'Il nome non può essere vuoto'], 422);
        }

        $parentId = $folder->parent_id;
        if (array_key_exists('parent_id', $data)) {
            $parentId = $data['parent_id'] ? (int) $data['parent_id'] : null;
            if ($parentId !== null) {
                $parent = MediaFolder::visibleTo($user)->find($parentId);
                if (!$parent) {
                    return $this->json($response, ['error' => 'Cartella di destinazione non trovata'], 404);
                }
                if (!$parent->sameOwnerAs($folder)) {
                    return $this->json($response, ['error' => 'La cartella di destinazione appartiene a un\'altra azienda'], 422);
                }
                if ($folder->containsFolder($parentId)) {
                    return $this->json($response, ['error' => 'Non puoi spostare una cartella dentro se stessa'], 422);
                }
            }
        }

        if ($this->nameTaken($name, $parentId, $folder->company_id, $folder->user_id, $folder->id)) {
            return $this->json($response, ['error' => 'Esiste già una cartella con questo nome'], 422);
        }

        $folder->name = $name;
        $folder->parent_id = $parentId;
        $folder->save();

        return $this->json($response, ['success' => true, 'data' => $this->fresh($folder)]);
    }

    /**
     * DELETE /api/media-folders/{id}
     * Non cancella nulla: file e sottocartelle risalgono alla cartella superiore.
     */
    public function delete(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $folder = MediaFolder::visibleTo($user)->find((int) $args['id']);
        if (!$folder) {
            return $this->notFound($response);
        }

        $moved = DB::connection()->transaction(function () use ($folder) {
            $target = $folder->parent_id;

            // Sottocartelle: in caso di nome già presente nella destinazione si aggiunge un suffisso
            foreach ($folder->children()->get() as $child) {
                $name = $child->name;
                $suffix = 2;
                while ($this->nameTaken($name, $target, $child->company_id, $child->user_id, $child->id)) {
                    $name = $child->name . ' (' . $suffix++ . ')';
                }
                $child->name = $name;
                $child->parent_id = $target;
                $child->save();
            }

            // Anche i file archiviati, così non restano agganciati a una cartella eliminata
            $count = Media::withTrashed()->where('folder_id', $folder->id)->update(['folder_id' => $target]);
            $folder->delete();

            return $count;
        });

        return $this->json($response, ['success' => true, 'moved_media' => $moved]);
    }

    private function nameTaken(string $name, ?int $parentId, ?int $companyId, ?int $userId, ?int $exceptId = null): bool
    {
        $query = MediaFolder::whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        } else {
            $query->whereNull('company_id')->where('user_id', $userId);
        }
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        return $query->exists();
    }

    private function cleanName($name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', strip_tags((string) $name)));
        return mb_substr($name, 0, self::NAME_MAX);
    }

    private function fresh(MediaFolder $folder): MediaFolder
    {
        return MediaFolder::with('company:id,name')->withCount('media')->find($folder->id);
    }

    private function notFound(Response $response): Response
    {
        return $this->json($response, ['error' => 'Cartella non trovata'], 404);
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
