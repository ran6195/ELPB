<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use App\Storage\LocalMediaStorage;
use App\Storage\MediaStorage;
use App\Storage\MediaStorageFactory;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

/**
 * Logica della libreria media: validazione, salvataggio su storage,
 * miniature, eliminazione e ricerca degli utilizzi nelle pagine.
 */
class MediaService
{
    /** MIME reale (rilevato con finfo) → [tipo, estensione] */
    public const ALLOWED = [
        'image/jpeg'      => ['image', 'jpg'],
        'image/png'       => ['image', 'png'],
        'image/gif'       => ['image', 'gif'],
        'image/webp'      => ['image', 'webp'],
        'image/avif'      => ['image', 'avif'],
        'video/mp4'       => ['video', 'mp4'],
        'video/webm'      => ['video', 'webm'],
        'video/quicktime' => ['video', 'mov'],
    ];

    public const THUMB_SIZE = 400;

    private const UPLOAD_ERRORS = [
        UPLOAD_ERR_INI_SIZE   => 'File troppo grande (supera upload_max_filesize in php.ini)',
        UPLOAD_ERR_FORM_SIZE  => 'File troppo grande (supera MAX_FILE_SIZE del form)',
        UPLOAD_ERR_PARTIAL    => 'File caricato solo parzialmente',
        UPLOAD_ERR_NO_FILE    => 'Nessun file inviato',
        UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante',
        UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco',
        UPLOAD_ERR_EXTENSION  => 'Upload bloccato da un\'estensione PHP',
    ];

    private MediaStorage $storage;
    private Logger $logger;

    public function __construct(?MediaStorage $storage = null)
    {
        $this->storage = $storage ?? MediaStorageFactory::default();
        $this->logger = new Logger('upload', 'UPLOAD_LOG_LEVEL');
    }

    /**
     * Carica un file nella libreria del proprietario (azienda o utente).
     *
     * @param string|null $expectedType 'image' | 'video' per limitare il tipo accettato
     * @param array $meta  Metadati opzionali dal client per i video: width, height, duration
     * @param UploadedFileInterface|null $poster Miniatura del video catturata nel browser
     */
    public function upload(
        UploadedFileInterface $file,
        User $user,
        ?string $expectedType = null,
        array $meta = [],
        ?UploadedFileInterface $poster = null
    ): Media {
        $this->logger->debug('media_received', [
            'filename'   => $file->getClientFilename(),
            'size'       => $file->getSize(),
            'mime'       => $file->getClientMediaType(),
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max'   => ini_get('post_max_size'),
        ]);

        $tmp = $this->moveToTemp($file);
        $thumbTmp = null;
        $storedPaths = [];

        try {
            $mime = $this->detectMime($tmp);
            if (!isset(self::ALLOWED[$mime])) {
                $this->logger->warning('media_type_rejected', ['mime' => $mime, 'client_mime' => $file->getClientMediaType()]);
                throw new MediaUploadException('Tipo file non supportato (' . $mime . '). Immagini: JPEG, PNG, GIF, WebP, AVIF. Video: MP4, WebM, MOV.');
            }

            [$type, $ext] = self::ALLOWED[$mime];
            if ($expectedType !== null && $type !== $expectedType) {
                throw new MediaUploadException($expectedType === 'image'
                    ? 'Il file non è un\'immagine valida'
                    : 'Il file non è un video valido');
            }

            $width = $height = $duration = null;
            if ($type === 'image') {
                if ($mime === 'image/jpeg') {
                    $this->fixJpegOrientation($tmp);
                }
                $info = @getimagesize($tmp);
                if ($info === false) {
                    throw new MediaUploadException('Immagine danneggiata o non leggibile');
                }
                [$width, $height] = $info;
            } else {
                $width = $this->positiveInt($meta['width'] ?? null);
                $height = $this->positiveInt($meta['height'] ?? null);
                $duration = $this->positiveInt($meta['duration'] ?? null);
            }

            $name = bin2hex(random_bytes(8));
            $folder = Media::ownerFolder($user) . '/' . $type . 's/' . date('Y/m');
            $size = filesize($tmp);

            // Miniatura (prima di spostare l'originale nello storage)
            $thumbTmp = $type === 'image'
                ? $this->makeThumbnail($tmp, $mime, (int) $width, (int) $height)
                : $this->posterThumbnail($poster);

            $path = $this->storage->putFile($tmp, $folder . '/' . $name . '.' . $ext, $mime);
            $storedPaths[] = $path;

            $thumbPath = null;
            if ($thumbTmp !== null) {
                [$thumbFile, $thumbExt, $thumbMime] = $thumbTmp;
                $thumbPath = $this->storage->putFile($thumbFile, $folder . '/thumbs/' . $name . '.' . $thumbExt, $thumbMime);
                $storedPaths[] = $thumbPath;
            }

            $media = Media::create([
                'company_id'     => $user->company_id,
                'user_id'        => $user->id,
                'type'           => $type,
                'disk'           => $this->storage->name(),
                'path'           => $path,
                'thumbnail_path' => $thumbPath,
                'original_name'  => $this->cleanName($file->getClientFilename()),
                'mime_type'      => $mime,
                'size'           => $size,
                'width'          => $width,
                'height'         => $height,
                'duration'       => $duration,
            ]);

            $this->logger->info('media_saved', ['id' => $media->id, 'path' => $path, 'user_id' => $user->id]);

            return $media;
        } catch (Throwable $e) {
            // Rollback dei file già salvati
            foreach ($storedPaths as $p) {
                $this->storage->delete($p);
            }
            if ($e instanceof MediaUploadException) {
                throw $e;
            }
            $this->logger->error('media_save_failed', ['exception' => $e->getMessage()]);
            throw new MediaUploadException('Impossibile salvare il file: ' . $e->getMessage(), 500);
        } finally {
            foreach ([$tmp, $thumbTmp[0] ?? null] as $leftover) {
                if ($leftover !== null && is_file($leftover)) {
                    @unlink($leftover);
                }
            }
        }
    }

    /**
     * Genera la miniatura di un'immagine già presente nello storage locale
     * (usato dalla migrazione dei vecchi upload). Ritorna il path della miniatura.
     */
    public function generateThumbnail(Media $media): ?string
    {
        $storage = MediaStorageFactory::disk($media->disk);
        if ($media->type !== 'image' || !$storage instanceof LocalMediaStorage) {
            return null;
        }
        $source = $storage->localPath($media->path);
        if ($source === null) {
            return null;
        }

        $thumb = $this->makeThumbnail($source, $media->mime_type, (int) $media->width, (int) $media->height);
        if ($thumb === null) {
            return null;
        }

        [$thumbFile, $thumbExt, $thumbMime] = $thumb;
        $name = pathinfo($media->path, PATHINFO_FILENAME);
        $target = dirname($media->path) . '/thumbs/' . $name . '.' . $thumbExt;

        try {
            return $storage->putFile($thumbFile, $target, $thumbMime);
        } finally {
            if (is_file($thumbFile)) {
                @unlink($thumbFile);
            }
        }
    }

    /** Elimina definitivamente record e file (originale + miniatura) */
    public function forceDelete(Media $media): void
    {
        $storage = MediaStorageFactory::disk($media->disk);
        $storage->delete($media->path);
        if ($media->thumbnail_path) {
            $storage->delete($media->thumbnail_path);
        }
        $media->forceDelete();
        $this->logger->info('media_deleted', ['id' => $media->id, 'path' => $media->path]);
    }

    /**
     * Pagine/blocchi che usano il media. La ricerca avviene sul nome file
     * (casuale e univoco) perché nel JSON gli slash dell'URL sono escapati.
     *
     * @return array<int, array{page_id:int, title:string, slug:string, is_published:bool, archived:bool, blocks:array}>
     */
    public function findUsage(Media $media): array
    {
        $needle = '%' . basename($media->path) . '%';

        $rows = DB::table('blocks')
            ->join('pages', 'pages.id', '=', 'blocks.page_id')
            ->where('blocks.content', 'like', $needle)
            ->select('pages.id as page_id', 'pages.title', 'pages.slug', 'pages.is_published', 'pages.deleted_at', 'blocks.id as block_id', 'blocks.type as block_type')
            ->get();

        // Immagine allegata all'email di conferma (impostazioni notifiche)
        $settingsRows = DB::table('pages')
            ->where('notification_settings', 'like', $needle)
            ->select('id as page_id', 'title', 'slug', 'is_published', 'deleted_at')
            ->get();

        $pages = [];
        foreach ($rows as $row) {
            $pages[$row->page_id] ??= $this->usageEntry($row);
            $pages[$row->page_id]['blocks'][] = ['id' => $row->block_id, 'type' => $row->block_type];
        }
        foreach ($settingsRows as $row) {
            $pages[$row->page_id] ??= $this->usageEntry($row);
            $pages[$row->page_id]['blocks'][] = ['id' => null, 'type' => 'notification-settings'];
        }

        return array_values($pages);
    }

    /**
     * Percorso locale di un media a partire dal suo URL pubblico
     * (usato per allegare immagini alle email). Supporta anche i vecchi upload
     * in uploads/images/ non ancora presenti in tabella.
     */
    public static function localPathFromUrl(string $url): ?string
    {
        $filename = basename(parse_url($url, PHP_URL_PATH) ?? '');
        if ($filename === '') {
            return null;
        }

        $media = Media::where('path', 'like', '%/' . $filename)->first();
        if ($media) {
            $storage = MediaStorageFactory::disk($media->disk);
            if ($storage instanceof LocalMediaStorage) {
                return $storage->localPath($media->path);
            }
            return null;
        }

        $legacy = dirname(__DIR__, 2) . '/public/uploads/images/' . $filename;
        return is_file($legacy) ? $legacy : null;
    }

    private function usageEntry(object $row): array
    {
        return [
            'page_id'      => (int) $row->page_id,
            'title'        => $row->title,
            'slug'         => $row->slug,
            'is_published' => (bool) $row->is_published,
            'archived'     => $row->deleted_at !== null,
            'blocks'       => [],
        ];
    }

    private function moveToTemp(UploadedFileInterface $file): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $code = $file->getError();
            $msg = self::UPLOAD_ERRORS[$code] ?? 'Errore upload sconosciuto (code: ' . $code . ')';
            $this->logger->warning('media_upload_error', ['code' => $code, 'message' => $msg]);
            throw new MediaUploadException($msg);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'media_');
        if ($tmp === false) {
            throw new MediaUploadException('Impossibile creare il file temporaneo', 500);
        }
        @unlink($tmp); // moveTo() richiede che la destinazione non esista
        $file->moveTo($tmp);

        return $tmp;
    }

    public function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path) ?: 'application/octet-stream';

        // Alcune versioni di libmagic riconoscono MP4/MOV in modo generico
        if ($mime === 'application/octet-stream' || $mime === 'video/x-m4v') {
            $head = (string) file_get_contents($path, false, null, 0, 12);
            if (substr($head, 4, 4) === 'ftyp') {
                $brand = substr($head, 8, 4);
                return $brand === 'qt  ' ? 'video/quicktime' : 'video/mp4';
            }
        }

        return $mime;
    }

    /** Ruota fisicamente le foto JPEG da smartphone secondo il tag EXIF Orientation */
    private function fixJpegOrientation(string $path): void
    {
        if (!function_exists('exif_read_data')) {
            return;
        }
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $angles = [3 => 180, 6 => -90, 8 => 90];
        if (!isset($angles[$orientation])) {
            return;
        }

        try {
            $img = @imagecreatefromjpeg($path);
            if (!$img) {
                return;
            }
            $rotated = imagerotate($img, $angles[$orientation], 0);
            imagedestroy($img);
            if ($rotated) {
                imagejpeg($rotated, $path, 90);
                imagedestroy($rotated);
            }
        } catch (Throwable $e) {
            $this->logger->warning('exif_rotation_failed', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Crea una miniatura (lato maggiore THUMB_SIZE). Ritorna [tmpPath, ext, mime]
     * oppure null se l'immagine è già piccola o GD non riesce a leggerla.
     */
    private function makeThumbnail(string $path, string $mime, int $width, int $height): ?array
    {
        if ($width <= self::THUMB_SIZE && $height <= self::THUMB_SIZE) {
            return null;
        }

        $loaders = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png'  => 'imagecreatefrompng',
            'image/gif'  => 'imagecreatefromgif',
            'image/webp' => 'imagecreatefromwebp',
            'image/avif' => 'imagecreatefromavif',
        ];
        $loader = $loaders[$mime] ?? null;
        if (!$loader || !function_exists($loader)) {
            return null;
        }

        try {
            $src = @$loader($path);
            if (!$src) {
                return null;
            }

            $ratio = min(self::THUMB_SIZE / $width, self::THUMB_SIZE / $height);
            $tw = max(1, (int) round($width * $ratio));
            $th = max(1, (int) round($height * $ratio));

            $dst = imagecreatetruecolor($tw, $th);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $width, $height);
            imagedestroy($src);

            $out = tempnam(sys_get_temp_dir(), 'thumb_');
            if (function_exists('imagewebp')) {
                imagewebp($dst, $out, 80);
                $result = [$out, 'webp', 'image/webp'];
            } else {
                imagepng($dst, $out, 6);
                $result = [$out, 'png', 'image/png'];
            }
            imagedestroy($dst);

            return $result;
        } catch (Throwable $e) {
            // Es. memoria insufficiente per immagini enormi: si procede senza miniatura
            $this->logger->warning('thumbnail_failed', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /** Miniatura video inviata dal client: validata come immagine e ridimensionata */
    private function posterThumbnail(?UploadedFileInterface $poster): ?array
    {
        if ($poster === null || $poster->getError() !== UPLOAD_ERR_OK) {
            return null;
        }

        try {
            $tmp = $this->moveToTemp($poster);
            $mime = $this->detectMime($tmp);
            $info = @getimagesize($tmp);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $info === false) {
                @unlink($tmp);
                return null;
            }

            $thumb = $this->makeThumbnail($tmp, $mime, $info[0], $info[1]);
            if ($thumb !== null) {
                @unlink($tmp);
                return $thumb;
            }
            return [$tmp, self::ALLOWED[$mime][1], $mime];
        } catch (Throwable $e) {
            $this->logger->warning('poster_failed', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    private function cleanName(?string $name): string
    {
        $name = trim(strip_tags((string) $name));
        return $name === '' ? 'file' : mb_substr($name, 0, 255);
    }

    private function positiveInt($value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $int === false ? null : $int;
    }
}
