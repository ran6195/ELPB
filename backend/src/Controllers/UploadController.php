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

            $imageUrl = 'http://localhost:8000/uploads/images/' . $filename;

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
}
