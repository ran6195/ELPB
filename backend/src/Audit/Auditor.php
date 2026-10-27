<?php

namespace App\Audit;

use App\Models\Company;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Registro attività: raccoglie le voci in memoria e le scrive con un solo INSERT
 * multiplo alla fine (flush, dopo l'invio della risposta). Non deve mai rallentare
 * né rompere l'applicazione: ogni errore di scrittura finisce solo in error_log.
 *
 * Istanza unica per processo: Auditor::get().
 */
class Auditor
{
    public const VIEW = 'view';
    public const ACTION = 'action';
    public const CHANGE = 'change';
    public const ACCESS = 'access';
    public const PUBLISH = 'publish';
    public const SYSTEM = 'system';

    /** Oltre questo numero di voci in attesa si scrive subito (script lunghi) */
    private const BUFFER_LIMIT = 200;
    private const INSERT_CHUNK = 100;

    private const SECRET_KEYS = '/password|token|secret|signature|api_key|recovery_code|authorization/i';
    private const SECRET_QUERY = ['signature', 'token', 'code', 'state'];
    private const MASK = '•••';
    private const MAX_DEPTH = 3;
    private const MAX_TEXT = 2000;

    private static ?Auditor $instance = null;

    private array $buffer = [];
    /** Voci "creato" già nel buffer, per unire le modifiche fatte subito dopo la creazione */
    private array $createdIndex = [];
    private ?array $request = null;
    private array $sources = [];
    private bool $enabled = true;
    private ?User $user = null;
    private array $context = ['company_id' => null, 'page_id' => null];

    public static function get(): Auditor
    {
        return self::$instance ??= new Auditor();
    }

    /** Ricomincia da zero (test e inizio di ogni richiesta) */
    public static function reset(): void
    {
        self::$instance = new Auditor();
    }

    /** Inizia una richiesta: genera il request_id (ULID) e salva il contesto */
    public function beginRequest(string $channel, ?string $ip, ?string $userAgent): string
    {
        $this->request = [
            'id' => self::ulid(),
            'channel' => $channel,
            'ip' => $ip !== null && $ip !== '' ? substr($ip, 0, 45) : null,
            'user_agent' => $userAgent !== null && $userAgent !== '' ? mb_substr($userAgent, 0, 250) : null,
            'staff_view' => false,
        ];
        return $this->request['id'];
    }

    public function requestId(): ?string
    {
        return $this->request['id'] ?? null;
    }

    /** Un admin sta guardando o modificando un'area di un'altra azienda */
    public function markStaffView(): void
    {
        if ($this->request) {
            $this->request['staff_view'] = true;
        }
    }

    /** Utente autenticato della richiesta (lo imposta AuthMiddleware) */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    /** Azienda e pagina a cui si riferisce la richiesta, se note */
    public function setContext(?int $companyId, ?int $pageId = null): void
    {
        $this->context = ['company_id' => $companyId, 'page_id' => $pageId];
    }

    /** Script e comandi annidati: la voce prende il canale della sorgente più interna */
    public function enterSource(string $channel, string $name): void
    {
        $this->sources[] = ['channel' => $channel, 'name' => $name];
    }

    public function leaveSource(): void
    {
        array_pop($this->sources);
    }

    /** Sospende la registrazione (dati di prova, operazioni tecniche) */
    public function withoutAuditing(callable $callback)
    {
        $previous = $this->enabled;
        $this->enabled = false;
        try {
            return $callback();
        } finally {
            $this->enabled = $previous;
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Aggiunge una voce al buffer.
     *
     * $data: user (autore esplicito, null = sistema; se manca la chiave l'utente della
     * richiesta), company_id, page_id, subject_type, subject_id, subject_label, method,
     * url, status, outcome, duration_ms, properties, at (timestamp float).
     */
    public function record(string $category, string $action, array $data = []): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $this->buffer[] = $this->buildEntry($category, $action, $data);
            if (count($this->buffer) > self::BUFFER_LIMIT) {
                $this->flush();
            }
        } catch (\Throwable $e) {
            error_log('Auditor: voce non registrata (' . $action . '): ' . $e->getMessage());
        }
    }

    /**
     * Modifica di un modello (trait Auditable). Registrata solo dopo il commit:
     * le modifiche annullate da un rollback non compaiono.
     */
    public function modelChanged(Model $model, string $event): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $ignored = array_merge(['created_at', 'updated_at'], method_exists($model, 'auditIgnored') ? $model->auditIgnored() : []);

            if ($event === 'updated') {
                $changes = array_diff_key($model->getChanges(), array_flip($ignored));
                if (!$changes) {
                    return;
                }
                $properties = ['old' => [], 'new' => []];
                foreach ($changes as $key => $value) {
                    $properties['old'][$key] = self::decodeJson($model->getRawOriginal($key));
                    $properties['new'][$key] = self::decodeJson($value);
                }
            } elseif ($event === 'deleted') {
                $properties = ['old' => self::decodeAll(array_diff_key($model->getRawOriginal(), array_flip($ignored)))];
            } else {
                $properties = ['new' => self::decodeAll(array_diff_key($model->getAttributes(), array_flip($ignored)))];
            }

            $type = method_exists($model, 'auditType') ? $model->auditType() : self::snake(class_basename($model));
            $data = [
                'company_id' => $model instanceof Company ? $model->getKey() : $model->getAttribute('company_id'),
                'page_id' => $model instanceof Page ? $model->getKey() : $model->getAttribute('page_id'),
                'subject_type' => $type,
                'subject_id' => $model->getKey(),
                'subject_label' => method_exists($model, 'auditLabel') ? $model->auditLabel() : null,
                'properties' => $properties,
                'at' => microtime(true),
            ];
            $key = $type . '#' . $model->getKey();

            $model->getConnection()->afterCommit(function () use ($event, $type, $data, $key) {
                // Colonne impostate subito dopo la creazione: un solo evento "creato"
                if ($event === 'updated' && isset($this->createdIndex[$key], $this->buffer[$this->createdIndex[$key]])) {
                    $entry = &$this->buffer[$this->createdIndex[$key]];
                    $entry['properties']['new'] = array_merge($entry['properties']['new'] ?? [], self::clean($data['properties']['new']));
                    return;
                }
                $this->record(self::CHANGE, $type . '.' . $event, $data);
                if ($event === 'created') {
                    $this->createdIndex[$key] = array_key_last($this->buffer);
                }
            });
        } catch (\Throwable $e) {
            error_log('Auditor: modifica non registrata (' . get_class($model) . ' ' . $event . '): ' . $e->getMessage());
        }
    }

    /** Voci in attesa di scrittura (per i test) */
    public function pending(): array
    {
        return $this->buffer;
    }

    /** Scrive le voci in attesa. Non solleva mai eccezioni. */
    public function flush(): void
    {
        $entries = $this->buffer;
        $this->buffer = [];
        $this->createdIndex = [];
        if (!$entries) {
            return;
        }

        try {
            $entries = $this->resolveSubjects($entries);
            $entries = array_values(array_filter($entries, fn($entry) => !$entry['_coalesce'] || !$this->mergeIntoRecentSave($entry)));
            foreach (array_chunk($entries, self::INSERT_CHUNK) as $chunk) {
                Capsule::table('audit_logs')->insert(array_map([self::class, 'toRow'], $chunk));
            }
        } catch (\Throwable $e) {
            error_log('Auditor: ' . count($entries) . ' voci non scritte: ' . $e->getMessage());
        }
    }

    /** Tabella, colonna dell'etichetta e colonna della pagina per ogni tipo di record */
    private const SUBJECT_SOURCES = [
        'page' => ['pages', 'title', null],
        'lead' => ['leads', null, 'page_id'],
        'media' => ['media', 'original_name', null],
        'media_folder' => ['media_folders', 'name', null],
        'domain' => ['domains', 'hostname', null],
        'company' => ['companies', 'name', null],
        'user' => ['users', 'email', null],
    ];

    /** Finestra in cui i salvataggi della stessa pagina dallo stesso utente diventano una voce */
    public const SAVE_WINDOW_MINUTES = 10;

    /**
     * Voci delle richieste: azienda, pagina ed etichetta del record coinvolto, con una
     * query per tipo di record (si fa nel flush, dopo l'invio della risposta).
     * Un admin che agisce su un'azienda diversa dalla propria è una "consultazione staff".
     */
    private function resolveSubjects(array $entries): array
    {
        $ids = [];
        foreach ($entries as $entry) {
            if ($entry['_resolve'] && $entry['subject_id'] && isset(self::SUBJECT_SOURCES[$entry['subject_type']])) {
                $ids[$entry['subject_type']][] = $entry['subject_id'];
            }
        }

        $found = [];
        foreach ($ids as $type => $list) {
            [$table, $labelColumn, $pageColumn] = self::SUBJECT_SOURCES[$type];
            $columns = array_filter(['id', $labelColumn, $pageColumn]);
            if ($type !== 'lead') {
                $columns[] = $type === 'company' ? 'id as company_id' : 'company_id';
            }
            $found[$type] = Capsule::table($table)->whereIn('id', array_unique($list))->get($columns)->keyBy('id');
        }

        $pageIds = [];
        foreach ($entries as &$entry) {
            $row = $entry['_resolve'] && $entry['subject_id'] ? ($found[$entry['subject_type']][$entry['subject_id']] ?? null) : null;
            if ($row) {
                [, $labelColumn, $pageColumn] = self::SUBJECT_SOURCES[$entry['subject_type']];
                if ($entry['subject_type'] === 'page') {
                    $entry['page_id'] = $row->id;
                } elseif ($pageColumn) {
                    $entry['page_id'] = $row->$pageColumn;
                }
                if (isset($row->company_id) || $entry['subject_type'] === 'page') {
                    $entry['company_id'] = $row->company_id;
                }
                if ($labelColumn && $entry['subject_label'] === null && $row->$labelColumn !== null) {
                    $entry['subject_label'] = mb_substr((string) $row->$labelColumn, 0, 255);
                }
            }
            if ($entry['_resolve'] && $entry['page_id'] && ($entry['company_id'] === null || $entry['subject_type'] === 'lead')) {
                $pageIds[] = $entry['page_id'];
            }
        }
        unset($entry);

        // Lead e voci con solo la pagina: l'azienda è quella della pagina
        $pageCompanies = $pageIds ? Capsule::table('pages')->whereIn('id', array_unique($pageIds))->pluck('company_id', 'id') : collect();
        $staffRequests = [];
        foreach ($entries as &$entry) {
            if ($entry['_resolve'] && $entry['page_id'] && $pageCompanies->has($entry['page_id'])) {
                $entry['company_id'] = $pageCompanies[$entry['page_id']];
            }
            if ($entry['_resolve'] && $entry['user_role'] === 'admin' && $entry['company_id'] !== null
                && (int) $entry['company_id'] !== (int) $entry['_user_company'] && $entry['request_id']) {
                $staffRequests[$entry['request_id']] = true;
            }
        }
        unset($entry);

        foreach ($entries as &$entry) {
            if (isset($staffRequests[$entry['request_id']])) {
                $entry['properties']['staff_view'] = true;
            }
        }
        unset($entry);

        return $entries;
    }

    /**
     * Autosalvataggio dell'editor: se lo stesso utente ha già salvato la stessa pagina negli
     * ultimi SAVE_WINDOW_MINUTES minuti, il salvataggio si somma a quella voce invece di
     * crearne una nuova. È l'unico caso in cui una voce viene aggiornata: solo il contatore,
     * l'ora dell'ultimo salvataggio e i blocchi aggiunti/tolti, con il Query Builder.
     * Restituisce true se la voce è stata unita.
     */
    private function mergeIntoRecentSave(array $entry): bool
    {
        if (!$entry['user_id'] || !$entry['subject_id']) {
            return false;
        }
        $recent = Capsule::table('audit_logs')
            ->where('action', $entry['action'])
            ->where('user_id', $entry['user_id'])
            ->where('subject_id', $entry['subject_id'])
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-' . self::SAVE_WINDOW_MINUTES . ' minutes', (int) $entry['created_at_ts'])))
            ->orderByDesc('id')
            ->first(['id', 'properties', 'status']);
        if (!$recent || (int) $recent->status >= 400 || $entry['status'] >= 400) {
            return false;
        }

        $old = json_decode((string) $recent->properties, true) ?: [];
        $new = $entry['properties'];
        $merged = $old;
        $merged['saves'] = ($old['saves'] ?? 1) + 1;
        $merged['last_saved_at'] = $entry['created_at'];
        $merged['blocks']['count'] = $new['blocks']['count'] ?? ($old['blocks']['count'] ?? null);
        foreach (['added', 'removed'] as $kind) {
            foreach ($new['blocks'][$kind] ?? [] as $type => $count) {
                $merged['blocks'][$kind][$type] = ($merged['blocks'][$kind][$type] ?? 0) + $count;
            }
        }

        Capsule::table('audit_logs')->where('id', $recent->id)->update([
            'properties' => json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
        ]);
        return true;
    }

    /**
     * Sanificazione: chiavi segrete mascherate, profondità massima 3, testi lunghi
     * troncati, oggetti convertiti in testo, file caricati registrati solo per nome.
     */
    public static function clean($data, int $depth = 1)
    {
        if (!is_array($data)) {
            return self::cleanScalar($data);
        }
        if ($depth > self::MAX_DEPTH) {
            return '[…]';
        }

        $clean = [];
        foreach ($data as $key => $value) {
            $clean[$key] = is_string($key) && preg_match(self::SECRET_KEYS, $key)
                ? self::MASK
                : self::clean($value, $depth + 1);
        }
        return $clean;
    }

    /** URL senza i parametri segreti della query (token, firme, ritorni OAuth) */
    public static function cleanUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['query'])) {
            return mb_substr($url, 0, 1000);
        }
        parse_str($parts['query'], $query);
        $query = array_diff_key($query, array_flip(self::SECRET_QUERY));
        $base = strtok($url, '?');
        return mb_substr($query ? $base . '?' . http_build_query($query) : $base, 0, 1000);
    }

    private function buildEntry(string $category, string $action, array $data): array
    {
        $user = array_key_exists('user', $data) ? $data['user'] : $this->user;

        $properties = isset($data['properties']) ? self::clean($data['properties']) : [];
        $source = end($this->sources) ?: null;
        if ($source) {
            $properties['source'] = $source['name'];
        }
        if (!empty($this->request['staff_view'])) {
            $properties['staff_view'] = true;
        }

        return [
            'created_at' => self::formatTime($at = $data['at'] ?? microtime(true)),
            'created_at_ts' => $at,
            'request_id' => $this->request['id'] ?? null,
            'channel' => $source['channel'] ?? $this->request['channel'] ?? (PHP_SAPI === 'cli' ? 'console' : 'api'),
            'category' => $category,
            'action' => mb_substr($action, 0, 100),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'company_id' => $data['company_id'] ?? $this->context['company_id'] ?? $user?->company_id,
            'page_id' => $data['page_id'] ?? $this->context['page_id'],
            'subject_type' => isset($data['subject_type']) ? mb_substr($data['subject_type'], 0, 40) : null,
            'subject_id' => $data['subject_id'] ?? null,
            'subject_label' => isset($data['subject_label']) ? mb_substr((string) $data['subject_label'], 0, 255) : null,
            'method' => $data['method'] ?? null,
            'url' => isset($data['url']) ? self::cleanUrl($data['url']) : null,
            'status' => $data['status'] ?? null,
            'outcome' => $data['outcome'] ?? null,
            'duration_ms' => isset($data['duration_ms']) ? max(0, (int) $data['duration_ms']) : null,
            'ip' => $this->request['ip'] ?? null,
            'user_agent' => $this->request['user_agent'] ?? null,
            'properties' => $properties,
            // Chiavi interne, tolte prima dell'INSERT
            '_resolve' => !empty($data['resolve_subject']),
            '_coalesce' => !empty($data['coalesce']),
            '_user_company' => $user?->company_id,
        ];
    }

    private static function toRow(array $entry): array
    {
        unset($entry['_resolve'], $entry['_coalesce'], $entry['_user_company'], $entry['created_at_ts']);
        $entry['properties'] = $entry['properties']
            ? json_encode($entry['properties'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)
            : null;
        return $entry;
    }

    private static function cleanScalar($value)
    {
        if ($value instanceof UploadedFileInterface) {
            return $value->getClientFilename();
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_object($value)) {
            $value = method_exists($value, '__toString') ? (string) $value : '[' . class_basename($value) . ']';
        }
        if (is_string($value) && mb_strlen($value) > self::MAX_TEXT) {
            return mb_substr($value, 0, self::MAX_TEXT) . '… [troncato]';
        }
        return $value;
    }

    /** Le colonne JSON (styles, settings...) si registrano come struttura, non come testo */
    private static function decodeJson($value)
    {
        if (is_string($value) && $value !== '' && ($value[0] === '{' || $value[0] === '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $value;
    }

    private static function decodeAll(array $values): array
    {
        return array_map([self::class, 'decodeJson'], $values);
    }

    /** Ora con millisecondi nel fuso dell'applicazione */
    private static function formatTime(float $timestamp): string
    {
        $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', $timestamp));
        $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return $date->format('Y-m-d H:i:s.v');
    }

    private static function snake(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    /** ULID: 48 bit di millisecondi + 80 bit casuali, base32 di Crockford (26 caratteri) */
    public static function ulid(): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $time = (int) floor(microtime(true) * 1000);
        $encoded = '';
        for ($i = 0; $i < 10; $i++) {
            $encoded = $alphabet[$time % 32] . $encoded;
            $time = intdiv($time, 32);
        }
        foreach (str_split(random_bytes(16)) as $i => $byte) {
            if ($i < 16) {
                $encoded .= $alphabet[ord($byte) % 32];
            }
        }
        return $encoded;
    }
}
