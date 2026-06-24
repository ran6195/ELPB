<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\Logger;

class UploadController
{
    public function uploadImage(Request $request, Response $response)
    {
        $logger = new Logger('upload', 'UPLOAD_LOG_LEVEL');
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['image'])) {
            $logger->warning('image_missing', ['reason' => 'campo "image" assente nella richiesta']);
            $response->getBody()->write(json_encode(['error' => 'No image uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $uploadedFile = $uploadedFiles['image'];

        $logger->debug('image_received', [
            'filename'     => $uploadedFile->getClientFilename(),
            'size'         => $uploadedFile->getSize(),
            'mime'         => $uploadedFile->getClientMediaType(),
            'upload_max'   => ini_get('upload_max_filesize'),
            'post_max'     => ini_get('post_max_size'),
        ]);

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'File troppo grande (supera upload_max_filesize in php.ini)',
                UPLOAD_ERR_FORM_SIZE  => 'File troppo grande (supera MAX_FILE_SIZE del form)',
                UPLOAD_ERR_PARTIAL    => 'File caricato solo parzialmente',
                UPLOAD_ERR_NO_FILE    => 'Nessun file inviato',
                UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante',
                UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco',
                UPLOAD_ERR_EXTENSION  => 'Upload bloccato da un\'estensione PHP',
            ];
            $code = $uploadedFile->getError();
            $msg = $errorMessages[$code] ?? 'Errore upload sconosciuto (code: ' . $code . ')';
            $logger->warning('image_upload_error', ['code' => $code, 'message' => $msg]);
            $response->getBody()->write(json_encode(['error' => $msg]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate file type
        $mimeType = $uploadedFile->getClientMediaType();
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/heic', 'image/heif', 'image/bmp', 'image/tiff', 'image/svg+xml'];

        if (!in_array($mimeType, $allowedTypes)) {
            $logger->warning('image_type_rejected', ['mime' => $mimeType]);
            $response->getBody()->write(json_encode(['error' => 'Tipo file non supportato: ' . $mimeType . '. Usa JPEG, PNG, GIF, WebP, AVIF o SVG.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Generate unique filename
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $imagesDir = __DIR__ . '/../../public/uploads/images/';
        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }
        $uploadPath = $imagesDir . $filename;

        if (!is_writable($imagesDir)) {
            $logger->error('image_dir_not_writable', ['dir' => $imagesDir]);
            $response->getBody()->write(json_encode(['error' => 'Cartella di destinazione non scrivibile']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        try {
            $uploadedFile->moveTo($uploadPath);

            // Genera l'URL dell'immagine usando APP_URL dal .env
            $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
            $imageUrl = $baseUrl . '/uploads/images/' . $filename;

            $logger->info('image_saved', ['filename' => $filename, 'path' => $uploadPath]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'url' => $imageUrl,
                'filename' => $filename
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $logger->error('image_move_failed', ['path' => $uploadPath, 'exception' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['error' => 'Failed to save image: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function uploadVideo(Request $request, Response $response)
    {
        $logger = new Logger('upload', 'UPLOAD_LOG_LEVEL');
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['video'])) {
            $logger->warning('video_missing', ['reason' => 'campo "video" assente nella richiesta']);
            $response->getBody()->write(json_encode(['error' => 'No video uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $uploadedFile = $uploadedFiles['video'];

        $logger->debug('video_received', [
            'filename'     => $uploadedFile->getClientFilename(),
            'size'         => $uploadedFile->getSize(),
            'mime'         => $uploadedFile->getClientMediaType(),
            'upload_max'   => ini_get('upload_max_filesize'),
            'post_max'     => ini_get('post_max_size'),
        ]);

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $code = $uploadedFile->getError();
            $errorMessage = $errorMessages[$code] ?? 'Upload error';
            $logger->warning('video_upload_error', ['code' => $code, 'message' => $errorMessage]);
            $response->getBody()->write(json_encode(['error' => $errorMessage]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate file type
        $mimeType = $uploadedFile->getClientMediaType();
        $allowedTypes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'];

        if (!in_array($mimeType, $allowedTypes)) {
            $logger->warning('video_type_rejected', ['mime' => $mimeType]);
            $response->getBody()->write(json_encode(['error' => 'Invalid file type. Only video files are allowed (MP4, MOV, AVI, WebM).']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Generate unique filename
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;

        // Create videos directory if it doesn't exist
        $videosDir = __DIR__ . '/../../public/uploads/videos/';
        if (!is_dir($videosDir)) {
            mkdir($videosDir, 0755, true);
        }

        $uploadPath = $videosDir . $filename;

        if (!is_writable($videosDir)) {
            $logger->error('video_dir_not_writable', ['dir' => $videosDir]);
            $response->getBody()->write(json_encode(['error' => 'Cartella di destinazione non scrivibile']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        try {
            $uploadedFile->moveTo($uploadPath);

            // Genera l'URL del video usando APP_URL dal .env
            $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
            $videoUrl = $baseUrl . '/uploads/videos/' . $filename;

            $logger->info('video_saved', ['filename' => $filename, 'path' => $uploadPath]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'url' => $videoUrl,
                'filename' => $filename
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $logger->error('video_move_failed', ['path' => $uploadPath, 'exception' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['error' => 'Failed to save video: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
