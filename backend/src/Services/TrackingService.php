<?php

namespace App\Services;

use App\Models\Page;
use App\Utils\Hostname;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Registro dei renderer che scaricano le pagine e visite senza cookie.
 *
 * Renderer v2.1 inviano X-ELPB-Host, X-ELPB-Url e X-ELPB-Renderer ("standalone/2.1.0").
 * Quelli vecchi non mandano nulla: restano riconoscibili da IP del server e user agent
 * (cURL dello standalone senza user agent, Joomla con il proprio).
 *
 * Visite: un hash di sale giornaliero + IP + user agent + pagina + sito conta i visitatori
 * del giorno. Il sale viene sostituito ogni giorno e quello vecchio cancellato, insieme
 * agli hash: dal giorno dopo non resta nulla che identifichi un visitatore.
 */
class TrackingService
{
    /** Versione dei renderer distribuiti: le precedenti risultano da aggiornare */
    public const CURRENT_RENDERER_VERSION = '2.1.0';

    /** Aggregati giornalieri conservati per 16 mesi, come GA4 */
    private const RETENTION_MONTHS = 16;

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|preview|facebookexternalhit|curl|wget|python|java\/|go-http|monitor|uptime/i';

    /**
     * Reti di bot e servizi di IA che scaricano le pagine direttamente dall'API
     * (non sono renderer). Non includere le reti cloud generiche (es. Google Cloud
     * 35.208.0.0/12): ci sono anche siti dei clienti (SiteGround).
     */
    private const BOT_NETWORKS = [
        '160.79.104.0/21',  // Anthropic (Claude), verificato via RDAP
        '66.249.64.0/19',   // Googlebot
        '157.55.39.0/24',   // Bingbot
        '207.46.13.0/24',   // Bingbot
        '40.77.167.0/24',   // Bingbot
        '66.220.144.0/20',  // Meta (facebookexternalhit)
        '69.63.176.0/20',   // Meta
        '173.252.64.0/18',  // Meta
    ];

    /** User agent di bot, crawler, assistenti IA e strumenti da riga di comando */
    private const AUTOMATED_AGENT = '/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|preview|facebookexternalhit|curl|wget|python|java\/|go-http|monitor|uptime|claude|anthropic|chatgpt|openai|perplexity|ccbot|bytespider|postman|insomnia|httpie/i';

    /** Renderer senza versione (precedente alla 2.1) o con una versione superata */
    public static function isOutdated(?string $version): bool
    {
        return $version === null || version_compare($version, self::CURRENT_RENDERER_VERSION, '<');
    }

    /**
     * Richiesta automatica (bot, assistente IA, strumento) e non di un renderer.
     * Si valuta alla lettura, quindi vale anche per le righe già registrate.
     * I renderer 2.1 si dichiarano (versione): non sono mai automatici.
     */
    public static function isAutomated(?string $version, ?string $ip, ?string $userAgent): bool
    {
        if ($version !== null) {
            return false;
        }
        if ($userAgent !== null && $userAgent !== '' && stripos($userAgent, 'joomla') === false
            && preg_match(self::AUTOMATED_AGENT, $userAgent)) {
            return true;
        }
        return $ip !== null && self::inNetworks($ip, self::BOT_NETWORKS);
    }

    /** IPv4 in una delle reti CIDR indicate */
    private static function inNetworks(string $ip, array $networks): bool
    {
        $address = ip2long($ip);
        if ($address === false) {
            return false;
        }
        foreach ($networks as $network) {
            [$base, $bits] = explode('/', $network);
            $mask = $bits === '0' ? 0 : (~0 << (32 - (int) $bits)) & 0xFFFFFFFF;
            if (($address & $mask) === (ip2long($base) & $mask)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Registra la richiesta di una pagina da parte di un renderer. Non solleva mai eccezioni.
     */
    public static function recordRendererHit(Request $request, Page $page): void
    {
        try {
            $rawHost = trim($request->getHeaderLine('X-ELPB-Host'));
            $rendererHeader = trim($request->getHeaderLine('X-ELPB-Renderer'));

            if ($rawHost === '' && $rendererHeader === '' && self::isBrowserRequest($request)) {
                return; // anteprima dell'app o richiesta aperta a mano nel browser
            }

            [$renderer, $version] = self::parseRenderer($rendererHeader);
            $userAgent = substr($request->getHeaderLine('User-Agent'), 0, 255);
            if ($renderer === '') {
                // Renderer non aggiornato: lo standalone (cURL) non manda user agent
                $renderer = $userAgent === '' ? 'standalone' : (stripos($userAgent, 'joomla') !== false ? 'joomla' : 'altro');
            }

            $url = trim($request->getHeaderLine('X-ELPB-Url'));
            $url = preg_match('#^https?://#i', $url) ? substr($url, 0, 500) : null;
            $now = date('Y-m-d H:i:s');

            Capsule::connection()->statement(
                'INSERT INTO renderer_hits
                    (page_id, host, renderer, version, ip, user_agent, last_url, hits, first_seen_at, last_seen_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    hits = hits + 1,
                    version = VALUES(version),
                    user_agent = VALUES(user_agent),
                    last_url = COALESCE(VALUES(last_url), last_url),
                    last_seen_at = VALUES(last_seen_at)',
                [
                    $page->id,
                    self::host($rawHost),
                    $renderer,
                    $version,
                    self::clientIp($request),
                    $userAgent !== '' ? $userAgent : null,
                    $url,
                    $now,
                    $now,
                ]
            );
        } catch (\Throwable $e) {
            error_log('TrackingService: registro renderer non aggiornato: ' . $e->getMessage());
        }
    }

    /**
     * Conta una visita segnalata dal browser (beacon del renderer). Non solleva mai eccezioni.
     * Restituisce true se la visita è stata conteggiata.
     */
    public static function recordView(Request $request, int $pageId, ?string $bodyHost): bool
    {
        try {
            $userAgent = $request->getHeaderLine('User-Agent');
            if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent)) {
                return false;
            }

            $origin = $request->getHeaderLine('Origin');
            $source = ($origin !== '' && $origin !== 'null') ? $origin : (string) $bodyHost;
            if ($source === '' || Hostname::isAppHost($source)) {
                return false; // anteprima dell'applicazione
            }
            $host = self::host($source);

            $page = Page::where('id', $pageId)->where('is_published', true)->first();
            if (!$page) {
                return false;
            }

            $day = date('Y-m-d');
            $hash = hash('sha256', implode('|', [self::dailySalt($day), self::clientIp($request), $userAgent, $page->id, $host]));
            $newVisitor = Capsule::table('page_view_visitors')->insertOrIgnore(['day' => $day, 'visitor_hash' => $hash]) > 0;

            Capsule::connection()->statement(
                'INSERT INTO page_views_daily (page_id, host, day, views, visitors)
                 VALUES (?, ?, ?, 1, ?)
                 ON DUPLICATE KEY UPDATE views = views + 1, visitors = visitors + VALUES(visitors)',
                [$page->id, $host, $day, $newVisitor ? 1 : 0]
            );
            return true;
        } catch (\Throwable $e) {
            error_log('TrackingService: visita non registrata: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sale del giorno. Alla prima visita di un nuovo giorno cancella sali e hash
     * dei giorni precedenti e gli aggregati oltre i 16 mesi (niente attività pianificata).
     */
    private static function dailySalt(string $day): string
    {
        $salt = Capsule::table('tracking_salts')->where('day', $day)->value('salt');
        if ($salt) {
            return $salt;
        }

        $created = Capsule::table('tracking_salts')->insertOrIgnore([
            'day' => $day,
            'salt' => bin2hex(random_bytes(32)),
        ]);
        if ($created > 0) {
            Capsule::table('tracking_salts')->where('day', '<', $day)->delete();
            Capsule::table('page_view_visitors')->where('day', '<', $day)->delete();
            Capsule::table('page_views_daily')
                ->where('day', '<', date('Y-m-d', strtotime('-' . self::RETENTION_MONTHS . ' months')))
                ->delete();
        }

        return Capsule::table('tracking_salts')->where('day', $day)->value('salt');
    }

    /**
     * Le chiamate server-to-server dei renderer non hanno gli header Sec-Fetch-*
     * né un Referer/Origin dell'applicazione
     */
    private static function isBrowserRequest(Request $request): bool
    {
        return $request->hasHeader('Sec-Fetch-Mode')
            || Hostname::isAppHost($request->getHeaderLine('Origin'))
            || Hostname::isAppHost($request->getHeaderLine('Referer'));
    }

    /** "standalone/2.1.0" → ['standalone', '2.1.0'] */
    private static function parseRenderer(string $header): array
    {
        if (!preg_match('#^([a-z][a-z0-9_-]{0,19})(?:/([0-9A-Za-z.\-]{1,20}))?$#i', $header, $m)) {
            return ['', null];
        }
        return [strtolower($m[1]), $m[2] ?? null];
    }

    /** Hostname normalizzato; se non valido (es. localhost) l'host così com'è, senza porta */
    private static function host(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $host = Hostname::normalize($value);
        if ($host !== null) {
            return $host;
        }
        $raw = parse_url(preg_match('#://#', $value) ? $value : 'http://' . $value, PHP_URL_HOST);
        return substr(strtolower((string) $raw), 0, 253);
    }

    private static function clientIp(Request $request): string
    {
        return substr((string) ($request->getServerParams()['REMOTE_ADDR'] ?? ''), 0, 45);
    }
}
