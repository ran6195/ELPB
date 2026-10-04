<?php

namespace App\Services;

use RuntimeException;

/**
 * Errore di upload/gestione media con status HTTP da restituire al client.
 */
class MediaUploadException extends RuntimeException
{
    private int $status;

    public function __construct(string $message, int $status = 400)
    {
        parent::__construct($message);
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
