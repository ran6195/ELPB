<?php
/**
 * Standalone Legal Pages Renderer
 *
 * Questo script renderizza le pagine legali (Privacy, Condizioni d'uso, Cookie Policy)
 * utilizzando i template condivisi e i dati salvati nel database.
 *
 * Uso:
 * - legal-pages.php/homepage/privacy
 * - legal-pages.php/homepage/condizioni
 * - legal-pages.php/homepage/cookies
 *
 * @version 1.0.0
 * @since 2026-02-01
 */

// Carica configurazione
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/BlockRenderer.php';
require_once __DIR__ . '/../menu-legali/LegalTemplateProcessor.php';

/**
 * Carica le variabili d'ambiente dal file .env
 */
function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

/**
 * Sanitizza lo slug
 */
function sanitizeSlug($slug) {
    return preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
}

/**
 * Fa una richiesta HTTP GET
 */
function httpGet($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo per sviluppo
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Errore cURL: " . $error);
    }

    if ($httpCode !== 200) {
        throw new Exception("Errore HTTP: " . $httpCode);
    }

    return json_decode($response, true);
}

/**
 * Recupera i dati della pagina dall'API
 */
function fetchPageData($slug, $apiUrl) {
    // API_BASE_URL termina già con /api, quindi aggiungi solo /page/{slug}
    $url = rtrim($apiUrl, '/') . '/page/' . urlencode($slug);
    return httpGet($url);
}

/**
 * Mostra pagina 404
 */
function show404($message = 'Pagina non trovata') {
    header("HTTP/1.0 404 Not Found");
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Pagina non trovata</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                background: #f7f7f7;
                color: #333;
            }
            .error-container {
                text-align: center;
                padding: 2rem;
            }
            h1 {
                font-size: 6rem;
                margin: 0;
                color: #e74c3c;
            }
            p {
                font-size: 1.25rem;
                margin: 1rem 0;
            }
            a {
                color: #3498db;
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>404</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <p><a href="/">← Torna alla homepage</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Parsing URL: /legal/{slug}/{type} o ?slug={slug}&type={type}
 */
function parseLegalUrl() {
    // Prova da query string (compatibilità server senza PATH_INFO)
    if (isset($_GET['slug']) && isset($_GET['type'])) {
        return [
            'slug' => sanitizeSlug($_GET['slug']),
            'type' => sanitizeSlug($_GET['type'])
        ];
    }

    // Prova da PATH_INFO
    if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
        $pathInfo = trim($_SERVER['PATH_INFO'], '/');
        $parts = explode('/', $pathInfo);

        if (count($parts) === 2) {
            return [
                'slug' => sanitizeSlug($parts[0]),
                'type' => sanitizeSlug($parts[1])
            ];
        }
    }

    return null;
}

// ========================================
// MAIN EXECUTION
// ========================================

try {
    // Carica variabili d'ambiente
    loadEnv();

    // Ottieni API URL dalla configurazione (stessa variabile usata da page.php)
    $apiUrl = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000/api';

    // Parsing URL
    $params = parseLegalUrl();

    if (!$params) {
        show404('URL non valido. Formato atteso: /legal/{slug}/{tipo}');
    }

    $slug = $params['slug'];
    $type = $params['type'];

    // Validazione tipo (whitelist)
    $allowedTypes = ['privacy', 'condizioni', 'cookies'];
    if (!in_array($type, $allowedTypes, true)) {
        show404('Tipo di pagina non valido. Tipi ammessi: ' . implode(', ', $allowedTypes));
    }

    // Validazione slug (path traversal prevention)
    if (str_contains($slug, '..') || str_contains($slug, '/') || str_contains($slug, '\\')) {
        show404('Slug non valido');
    }

    // Fetch dati pagina dall'API (restituisce direttamente l'oggetto page)
    $pageData = fetchPageData($slug, $apiUrl);

    if (!$pageData || isset($pageData['error'])) {
        show404($pageData['error'] ?? 'Pagina non trovata');
    }

    // Verifica che la pagina abbia legal_info
    $legalInfo = $pageData['legal_info'] ?? null;

    if (!$legalInfo || empty($legalInfo)) {
        show404('Informazioni legali non configurate per questa pagina');
    }

    // Componi campi derivati per il processore
    // Il database salva indirizzo in campi separati, il processore si aspetta indirizzoCompleto
    if (empty($legalInfo['indirizzoCompleto'])) {
        $parts = array_filter([
            $legalInfo['indirizzo'] ?? '',
            $legalInfo['cap'] ?? '',
            $legalInfo['citta'] ?? '',
            !empty($legalInfo['provincia']) ? '(' . $legalInfo['provincia'] . ')' : ''
        ]);
        $legalInfo['indirizzoCompleto'] = implode(' ', $parts);
    }
    // Il database salva email, il processore si aspetta emailContatto
    if (empty($legalInfo['emailContatto'])) {
        $legalInfo['emailContatto'] = $legalInfo['email'] ?? '';
    }

    // Valida legal_info con LegalTemplateProcessor
    $processor = new LegalTemplateProcessor(__DIR__ . '/../menu-legali');
    try {
        $processor->validate($legalInfo);
    } catch (InvalidArgumentException $e) {
        // Log warning ma non bloccare - il render funziona anche senza tutti i campi
        error_log("Legal pages warning: " . $e->getMessage());
    }

    // Renderizza il template della pagina legale
    $legalContent = $processor->render($type, $legalInfo);

    // Renderizza header e footer della landing page originale
    $pageId = $pageData['id'] ?? 0;
    $hasLegalInfo = !empty($legalInfo);
    $legalBaseUrl = $_ENV['LEGAL_BASE_URL'] ?? '';
    $renderer = new BlockRenderer($apiUrl, $pageId, $slug, $hasLegalInfo, $legalBaseUrl);
    $blocks = $pageData['blocks'] ?? [];
    $styles = $pageData['styles'] ?? [];

    // Trova blocchi header e footer
    $headerBlock = null;
    $footerBlock = null;

    foreach ($blocks as $block) {
        if ($block['type'] === 'header') {
            $headerBlock = $block;
        }
        if ($block['type'] === 'footer' || $block['type'] === 'legal-footer') {
            $footerBlock = $block;
        }
    }

    // Raccogli tutti i font usati
    $fonts = [];
    if (isset($styles['fontFamily']) && !empty($styles['fontFamily'])) {
        $fonts[] = $styles['fontFamily'];
    }

    foreach ($blocks as $block) {
        if (isset($block['styles']['fontFamily']) && !empty($block['styles']['fontFamily'])) {
            $fonts[] = $block['styles']['fontFamily'];
        }
    }

    $fonts = array_unique($fonts);

    // Mapping nomi pagine per titolo
    $pageTypeNames = [
        'privacy' => 'Privacy Policy',
        'condizioni' => "Condizioni d'uso",
        'cookies' => 'Cookie Policy'
    ];

    $pageTitle = $pageTypeNames[$type] ?? 'Pagina Legale';
    $siteTitle = $pageData['title'] ?? 'Sito Web';

} catch (Exception $e) {
    error_log("Errore legal-pages.php: " . $e->getMessage());
    show404('Errore durante il caricamento della pagina');
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($pageTitle . ' - ' . $siteTitle); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <?php if (!empty($fonts)): ?>
        <?php foreach ($fonts as $font): ?>
            <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($font); ?>:wght@300;400;600;700&display=swap" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Swiper CSS (per slider nel footer, se presenti) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <style>
        body {
            font-family: <?php echo $styles['fontFamily'] ?? 'Inter'; ?>, sans-serif;
        }

        /* Container larghezza personalizzata */
        <?php
        $containerWidth = $styles['containerWidth'] ?? 'max-w-6xl';
        $containerMaxWidth = [
            'max-w-4xl' => '56rem',
            'max-w-5xl' => '64rem',
            'max-w-6xl' => '72rem',
            'max-w-7xl' => '80rem',
            'max-w-full' => '100%'
        ][$containerWidth] ?? '72rem';
        ?>
        .container-width-custom {
            max-width: <?php echo $containerMaxWidth; ?>;
        }

        /* Stile contenuto legale */
        .legal-content {
            background: white;
            padding: 3rem 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            line-height: 1.8;
        }

        .legal-content h1, .legal-content h2, .legal-content h3 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .legal-content p, .legal-content ol, .legal-content ul {
            margin-bottom: 1rem;
        }

        .legal-content a {
            color: #3b82f6;
            text-decoration: underline;
        }

        .legal-content a:hover {
            color: #2563eb;
        }
    </style>
</head>
<body>

<!-- Header della landing page originale -->
<?php if ($headerBlock): ?>
    <?php echo $renderer->render($headerBlock); ?>
<?php endif; ?>

<!-- Contenuto pagina legale -->
<main class="py-12 px-4">
    <div class="container mx-auto container-width-custom">
        <h1 class="text-4xl font-bold mb-8 text-center"><?php echo htmlspecialchars($pageTitle); ?></h1>

        <div class="legal-content">
            <?php echo $legalContent; ?>
        </div>
    </div>
</main>

<!-- Footer della landing page originale -->
<?php if ($footerBlock): ?>
    <?php echo $renderer->render($footerBlock); ?>
<?php endif; ?>

<!-- Swiper JS (per slider) -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Inizializza Swiper (se presente) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza tutti gli slider Swiper presenti nella pagina
    document.querySelectorAll('.swiper[data-swiper-config]').forEach(function(swiperElement) {
        try {
            const config = JSON.parse(swiperElement.getAttribute('data-swiper-config'));
            new Swiper(swiperElement, config);
        } catch (e) {
            console.error('Errore inizializzazione Swiper:', e);
        }
    });
});
</script>

</body>
</html>
