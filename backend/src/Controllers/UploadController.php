<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\Logger;
use App\Services\MediaService;
use App\Services\MediaUploadException;

/**
 * Endpoint di upload storici (/api/upload/image|video) usati dall'editor.
 * Delegano a MediaService: ogni file caricato finisce anche nella libreria media
 * del cliente. La risposta mantiene il formato originale { success, url, filename }.
 */
class UploadController
{
    public function uploadImage(Request $request, Response $response)
    {
        return $this->handle($request, $response, 'image');
    }

    public function uploadVideo(Request $request, Response $response)
    {
        return $this->handle($request, $response, 'video');
    }

    private function handle(Request $request, Response $response, string $type)
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles[$type])) {
            (new Logger('upload', 'UPLOAD_LOG_LEVEL'))->warning($type . '_missing', ['reason' => 'campo "' . $type . '" assente nella richiesta']);
            $response->getBody()->write(json_encode(['error' => $type === 'image' ? 'No image uploaded' : 'No video uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $media = (new MediaService())->upload($uploadedFiles[$type], $request->getAttribute('user'), $type);

            $response->getBody()->write(json_encode([
                'success'  => true,
                'url'      => $media->url,
                'filename' => basename($media->path),
                'media'    => $media,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (MediaUploadException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($e->getStatus());
        }
    }
}
