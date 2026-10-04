<?php

namespace App\Storage;

use InvalidArgumentException;

/**
 * Crea il driver di storage in base alla configurazione .env.
 *
 * MEDIA_DISK      disco per i nuovi upload (default: local)
 * MEDIA_ROOT      radice del disco locale (default: backend/public/uploads)
 * MEDIA_BASE_URL  URL pubblico della radice (default: APP_URL/uploads)
 */
class MediaStorageFactory
{
    /** @var array<string, MediaStorage> */
    private static array $instances = [];

    /** Disco usato per i nuovi upload */
    public static function default(): MediaStorage
    {
        return self::disk($_ENV['MEDIA_DISK'] ?? 'local');
    }

    /** Disco specifico (per i file esistenti si usa media.disk) */
    public static function disk(string $name): MediaStorage
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = self::create($name);
        }
        return self::$instances[$name];
    }

    private static function create(string $name): MediaStorage
    {
        switch ($name) {
            case 'local':
                $root = $_ENV['MEDIA_ROOT'] ?? '';
                if ($root === '') {
                    $root = __DIR__ . '/../../public/uploads';
                }
                $baseUrl = $_ENV['MEDIA_BASE_URL'] ?? '';
                if ($baseUrl === '') {
                    $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/') . '/uploads';
                }
                return new LocalMediaStorage($root, $baseUrl);

            // case 's3': driver S3-compatibile (R2, Hetzner, B2...) — vedi docs/features/PIANO_MEDIA_LIBRARY.md

            default:
                throw new InvalidArgumentException('Disco media non supportato: ' . $name);
        }
    }
}
