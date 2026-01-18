<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UploadController
{
    public function uploadImage(Request $request, Response $response)
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['image'])) {
            $response->getBody()->write(json_encode(['error' => 'No image uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $uploadedFile = $uploadedFiles['image'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'Upload error']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate file type
        $mimeType = $uploadedFile->getClientMediaType();
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mimeType, $allowedTypes)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid file type. Only images are allowed.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Generate unique filename
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $uploadPath = __DIR__ . '/../../public/uploads/images/' . $filename;

        try {
            $uploadedFile->moveTo($uploadPath);

            // Genera l'URL dell'immagine usando APP_URL dal .env
            $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
            $imageUrl = $baseUrl . '/uploads/images/' . $filename;

            $response->getBody()->write(json_encode([
                'success' => true,
                'url' => $imageUrl,
                'filename' => $filename
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to save image: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function uploadVideo(Request $request, Response $response)
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['video'])) {
            $response->getBody()->write(json_encode(['error' => 'No video uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $uploadedFile = $uploadedFiles['video'];

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
            $errorMessage = $errorMessages[$uploadedFile->getError()] ?? 'Upload error';
            $response->getBody()->write(json_encode(['error' => $errorMessage]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate file type
        $mimeType = $uploadedFile->getClientMediaType();
        $allowedTypes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'];

        if (!in_array($mimeType, $allowedTypes)) {
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

        try {
            $uploadedFile->moveTo($uploadPath);

            // Genera l'URL del video usando APP_URL dal .env
            $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
            $videoUrl = $baseUrl . '/uploads/videos/' . $filename;

            $response->getBody()->write(json_encode([
                'success' => true,
                'url' => $videoUrl,
                'filename' => $filename
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to save video: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
