<?php

namespace App\Storage;

use RuntimeException;

/**
 * Storage su filesystem locale, servito direttamente da Apache
 * (default: backend/public/uploads/ → APP_URL/uploads/).
 */
class LocalMediaStorage implements MediaStorage
{
    private string $root;
    private string $baseUrl;

    public function __construct(string $root, string $baseUrl)
    {
        $this->root = rtrim($root, '/');
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function name(): string
    {
        return 'local';
    }

    public function putFile(string $sourcePath, string $path, string $mimeType): string
    {
        $path = $this->normalize($path);
        $target = $this->absolute($path);
        $dir = dirname($target);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Impossibile creare la cartella: ' . $dir);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('Cartella di destinazione non scrivibile: ' . $dir);
        }

        // rename() fallisce tra filesystem diversi (es. /tmp su altro mount): fallback a copy
        if (!@rename($sourcePath, $target)) {
            if (!copy($sourcePath, $target)) {
                throw new RuntimeException('Impossibile salvare il file: ' . $target);
            }
            @unlink($sourcePath);
        }
        @chmod($target, 0644);

        return $path;
    }

    public function delete(string $path): bool
    {
        $target = $this->absolute($this->normalize($path));
        return is_file($target) ? unlink($target) : false;
    }

    public function exists(string $path): bool
    {
        return is_file($this->absolute($this->normalize($path)));
    }

    public function size(string $path): ?int
    {
        $target = $this->absolute($this->normalize($path));
        return is_file($target) ? filesize($target) : null;
    }

    public function url(string $path): string
    {
        $segments = array_map('rawurlencode', explode('/', $this->normalize($path)));
        return $this->baseUrl . '/' . implode('/', $segments);
    }

    /** Percorso assoluto sul filesystem (es. per allegati email), null se non esiste */
    public function localPath(string $path): ?string
    {
        $target = $this->absolute($this->normalize($path));
        return is_file($target) ? $target : null;
    }

    private function absolute(string $path): string
    {
        return $this->root . '/' . $path;
    }

    /** Impedisce path traversal e path assoluti */
    private function normalize(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('Percorso media non valido: ' . $path);
            }
        }
        return $path;
    }
}
