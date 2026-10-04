<?php

namespace App\Storage;

/**
 * Astrazione dello storage dei media.
 *
 * I percorsi ($path) sono sempre relativi alla radice del disco
 * (es. "media/c3/images/2026/10/abc.jpg"), mai assoluti.
 * Un nuovo backend (es. S3-compatibile) si aggiunge implementando
 * questa interfaccia e registrandolo in MediaStorageFactory.
 */
interface MediaStorage
{
    /** Nome del disco, salvato nella colonna media.disk */
    public function name(): string;

    /** Copia/sposta un file locale nello storage. Ritorna il path salvato. */
    public function putFile(string $sourcePath, string $path, string $mimeType): string;

    public function delete(string $path): bool;

    public function exists(string $path): bool;

    /** Dimensione in byte, null se il file non esiste */
    public function size(string $path): ?int;

    /** URL pubblico assoluto del file */
    public function url(string $path): string;
}
