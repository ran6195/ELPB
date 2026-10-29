<?php
/**
 * Configurazione Standalone Renderer
 */

// Error reporting (disabilitato in produzione)
if (isset($_ENV['DEBUG']) && $_ENV['DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Rome');

// Costanti utili
define('RENDERER_NAME', 'standalone');
define('RENDERER_VERSION', '2.1.1');
define('RENDERER_PATH', __DIR__);

/**
 * Header che identificano sito e renderer nelle chiamate al backend (dalla v2.1):
 * il backend registra dove viene mostrata ogni pagina. Query string esclusa dall'URL.
 */
function elpbRendererHeaders() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $clean = function ($value) {
        return preg_replace('/[\r\n]/', '', (string) $value);
    };

    return [
        'X-ELPB-Host: ' . $clean($host),
        'X-ELPB-Url: ' . $clean(($https ? 'https' : 'http') . '://' . $host . $path),
        'X-ELPB-Renderer: ' . RENDERER_NAME . '/' . RENDERER_VERSION,
    ];
}

/**
 * Script che segnala una visita al backend: una sola richiesta per pagina vista,
 * senza cookie né dati salvati nel browser. text/plain evita la richiesta preliminare CORS.
 */
function elpbViewBeacon($apiUrl, $pageId) {
    if (empty($pageId) || empty($apiUrl)) {
        return '';
    }
    $url = json_encode(rtrim($apiUrl, '/') . '/track/view', JSON_UNESCAPED_SLASHES);
    $id = (int) $pageId;

    return <<<HTML
<script>
(function () {
    try {
        if (navigator.webdriver) return;
        var data = JSON.stringify({ page_id: {$id}, host: location.hostname });
        var url = {$url};
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([data], { type: 'text/plain' }));
        } else {
            fetch(url, { method: 'POST', body: data, keepalive: true, mode: 'no-cors', headers: { 'Content-Type': 'text/plain' } });
        }
    } catch (e) {}
})();
</script>
HTML;
}
