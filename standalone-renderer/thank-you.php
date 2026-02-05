<?php
/**
 * Thank You Page - Standalone Renderer
 *
 * Pagina di ringraziamento mostrata dopo l'invio di un form.
 * Se viene passato ?slug=..., mostra header e footer della landing page originale.
 * Altrimenti, mostra una pagina statica semplice.
 *
 * Pattern: segue legal-pages.php
 *
 * @version 2.0.0
 * @since 2026-01-24
 */

// Carica configurazione
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/BlockRenderer.php';

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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
    $url = rtrim($apiUrl, '/') . '/page/' . urlencode($slug);
    return httpGet($url);
}

// ========================================
// MAIN EXECUTION
// ========================================

loadEnv();

$slug = isset($_GET['slug']) ? sanitizeSlug($_GET['slug']) : '';
$hasDynamicContent = false;
$headerBlock = null;
$footerBlock = null;
$renderer = null;
$fonts = [];
$styles = [];
$pageTitle = 'Grazie per averci contattato';
$gtmEnabled = false;
$gtmId = '';

if (!empty($slug)) {
    try {
        $apiUrl = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000/api';
        $pageData = fetchPageData($slug, $apiUrl);

        if ($pageData && !isset($pageData['error'])) {
            $hasDynamicContent = true;

            $pageId = $pageData['id'] ?? 0;
            $hasLegalInfo = !empty($pageData['legal_info']);
            $legalBaseUrl = $_ENV['LEGAL_BASE_URL'] ?? '';
            $renderer = new BlockRenderer($apiUrl, $pageId, $slug, $hasLegalInfo, $legalBaseUrl);
            $blocks = $pageData['blocks'] ?? [];
            $styles = $pageData['styles'] ?? [];

            // Trova header e footer
            foreach ($blocks as $block) {
                if ($block['type'] === 'header') {
                    $headerBlock = $block;
                }
                if ($block['type'] === 'footer' || $block['type'] === 'legal-footer') {
                    $footerBlock = $block;
                }
            }

            // Raccogli font
            if (!empty($styles['fontFamily'])) {
                $fonts[] = $styles['fontFamily'];
            }
            foreach ($blocks as $block) {
                if (!empty($block['styles']['fontFamily'])) {
                    $fonts[] = $block['styles']['fontFamily'];
                }
            }
            $fonts = array_unique($fonts);

            // Titolo pagina
            $siteTitle = $pageData['title'] ?? '';
            if ($siteTitle) {
                $pageTitle = 'Grazie - ' . $siteTitle;
            }

            // GTM
            $trackingSettings = $pageData['tracking_settings'] ?? [];
            $gtmEnabled = $trackingSettings['gtm_enabled'] ?? false;
            $gtmId = $trackingSettings['gtm_id'] ?? '';
        }
    } catch (Exception $e) {
        error_log("thank-you.php: Errore caricamento pagina '{$slug}': " . $e->getMessage());
        // Fallback a pagina statica - non blocchiamo
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <?php if (!empty($fonts)): ?>
        <?php foreach ($fonts as $font): ?>
            <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($font); ?>:wght@300;400;600;700&display=swap" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Swiper CSS (per slider in header/footer) -->
    <?php if ($hasDynamicContent): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <?php endif; ?>

    <?php if ($gtmEnabled && !empty($gtmId)): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?php echo htmlspecialchars($gtmId); ?>');</script>
    <!-- End Google Tag Manager -->
    <?php endif; ?>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: <?php echo htmlspecialchars($styles['fontFamily'] ?? 'system-ui'); ?>, -apple-system, sans-serif;
        }

        <?php if ($hasDynamicContent):
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
        <?php endif; ?>
    </style>
</head>
<body class="<?php echo $hasDynamicContent ? '' : 'bg-gray-50'; ?>">

<?php if ($gtmEnabled && !empty($gtmId)): ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($gtmId); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php endif; ?>

<!-- Header della landing page originale -->
<?php if ($headerBlock && $renderer): ?>
    <?php echo $renderer->render($headerBlock); ?>
<?php endif; ?>

<!-- Messaggio di ringraziamento -->
<main class="<?php echo $hasDynamicContent ? 'py-20 px-4' : 'min-h-screen flex items-center justify-center px-4'; ?>">
    <div class="max-w-md w-full text-center <?php echo $hasDynamicContent ? 'mx-auto' : ''; ?>">
        <!-- Success Icon -->
        <div class="mb-6 inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full">
            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <!-- Thank You Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
            Grazie per averci contattato!
        </h1>

        <p class="text-lg text-gray-600 mb-8">
            La tua richiesta è stata inviata con successo. Ti risponderemo al più presto.
        </p>

        <!-- Back Button -->
        <button
            onclick="window.history.back()"
            class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-lg transition-colors"
        >
            Torna indietro
        </button>
    </div>
</main>

<!-- Footer della landing page originale -->
<?php if ($footerBlock && $renderer): ?>
    <?php echo $renderer->render($footerBlock); ?>
<?php endif; ?>

<?php if ($hasDynamicContent): ?>
<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Inizializza Swiper (se presente) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
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
<?php endif; ?>

</body>
</html>
