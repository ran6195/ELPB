<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Guida alla pubblicazione su un nuovo sito (pagina del frontend) e download del renderer.
 * La guida e gli zip sono file statici del frontend: queste rotte servono solo
 * a lasciarne traccia nel registro attività (RecordActivity), come il calendario dei lead.
 */
class GuideController
{
    /** Pacchetti distribuiti dalla guida (scripts/build-renderer-packages.sh) */
    public const RENDERER_PACKAGES = ['renderer-lp-elpb-nolegal.zip', 'renderer-lp-elpb.zip'];

    /** GET /api/guides/publishing: apertura della guida */
    public function view(Request $request, Response $response)
    {
        return $response->withStatus(204);
    }

    /** POST /api/renderer/downloads  { file, version }: download di un pacchetto del renderer */
    public function logRendererDownload(Request $request, Response $response)
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        $file = $data['file'] ?? null;
        $version = $data['version'] ?? null;

        if (!in_array($file, self::RENDERER_PACKAGES, true)
            || ($version !== null && !(is_string($version) && preg_match('/^\d+\.\d+\.\d+$/', $version)))) {
            $response->getBody()->write(json_encode(['error' => 'Pacchetto o versione non validi']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        return $response->withStatus(204);
    }
}
