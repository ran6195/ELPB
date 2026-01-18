<?php
/**
 * Standalone Landing Page Renderer
 *
 * Questo script recupera e renderizza una landing page pubblicata
 * tramite chiamate API al backend.
 *
 * Uso:
 * - page.php?slug=homepage
 * - page.php/homepage (con URL rewriting)
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
 * Ottiene lo slug dalla richiesta
 */
function getSlug() {
    // Prova da query string
    if (isset($_GET['slug']) && !empty($_GET['slug'])) {
        return sanitizeSlug($_GET['slug']);
    }

    // Prova da PATH_INFO
    if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
        $slug = trim($_SERVER['PATH_INFO'], '/');
        return sanitizeSlug($slug);
    }

    // Usa default se configurato
    if (isset($_ENV['DEFAULT_SLUG']) && !empty($_ENV['DEFAULT_SLUG'])) {
        return sanitizeSlug($_ENV['DEFAULT_SLUG']);
    }

    return null;
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
function fetchPage($slug) {
    $apiUrl = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000/api';
    $url = rtrim($apiUrl, '/') . '/page/' . urlencode($slug);

    try {
        $data = httpGet($url);
        return $data;
    } catch (Exception $e) {
        if ($_ENV['DEBUG'] === 'true') {
            throw $e;
        }
        return null;
    }
}

/**
 * Renderizza la pagina
 */
function renderPage($page) {
    if (!$page) {
        http_response_code(404);
        echo '<!DOCTYPE html><html><head><title>Pagina non trovata</title></head><body>';
        echo '<h1>Pagina non trovata</h1>';
        echo '<p>La pagina richiesta non esiste o non è pubblicata.</p>';
        echo '</body></html>';
        return;
    }

    // Ordina i blocchi per position.order
    $blocks = $page['blocks'] ?? [];
    usort($blocks, function($a, $b) {
        return ($a['order'] ?? 0) - ($b['order'] ?? 0);
    });

    // Initialize renderer with API URL and page ID
    $apiUrl = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000/api';
    $pageId = $page['id'] ?? 0;
    $renderer = new BlockRenderer($apiUrl, $pageId);

    $customStyles = $page['styles'] ?? [];
    $pageBackgroundColor = $customStyles['backgroundColor'] ?? '#FFFFFF';
    $blockGap = $customStyles['blockGap'] ?? 0;
    $fontFamily = $customStyles['fontFamily'] ?? '';
    $roundedCorners = $customStyles['roundedCorners'] ?? true;

    // Raccogli tutti i font unici usati nei blocchi
    $blocks = $page['blocks'] ?? [];
    $allFonts = [];

    // Aggiungi font globale della pagina se presente
    if (!empty($fontFamily)) {
        $allFonts[$fontFamily] = true;
    }

    // Raccogli font da ogni blocco
    foreach ($blocks as $block) {
        $blockFontFamily = $block['styles']['fontFamily'] ?? '';
        if (!empty($blockFontFamily)) {
            $allFonts[$blockFontFamily] = true;
        }
    }

    // Converti in array di font unici
    $uniqueFonts = array_keys($allFonts);

    // Meta tags
    $metaTitle = htmlspecialchars($page['meta_title'] ?? $page['title'] ?? 'Landing Page');
    $metaDescription = htmlspecialchars($page['meta_description'] ?? '');

    // Google Tag Manager
    $trackingSettings = $page['tracking_settings'] ?? [];
    $gtmEnabled = $trackingSettings['gtm_enabled'] ?? false;
    $gtmId = $trackingSettings['gtm_id'] ?? '';

    // Inizia output
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php if ($gtmEnabled && !empty($gtmId)): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?php echo htmlspecialchars($gtmId); ?>');</script>
    <!-- End Google Tag Manager -->
    <?php endif; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $metaTitle; ?></title>
    <?php if ($metaDescription): ?>
    <meta name="description" content="<?php echo $metaDescription; ?>">
    <?php endif; ?>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Swiper CSS per slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <!-- Google Fonts - Carica tutti i font unici usati nella pagina e nei blocchi -->
    <?php if (!empty($uniqueFonts)): ?>
        <?php foreach ($uniqueFonts as $font): ?>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($font); ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: <?php echo htmlspecialchars($pageBackgroundColor); ?>;
            <?php if ($fontFamily): ?>
            font-family: '<?php echo htmlspecialchars($fontFamily); ?>', sans-serif;
            <?php endif; ?>
        }
        .block-container {
            margin-bottom: <?php echo (int)$blockGap; ?>px;
        }
        .block-container:last-child {
            margin-bottom: 0;
        }
    </style>

    <?php if (!empty($page['styles']['custom_css'])): ?>
    <style>
        <?php echo $page['styles']['custom_css']; ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <?php if ($gtmEnabled && !empty($gtmId)): ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($gtmId); ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php endif; ?>

    <?php
    // Renderizza tutti i blocchi
    foreach ($blocks as $block) {
        echo '<div class="block-container">';
        echo $renderer->render($block, $roundedCorners);
        echo '</div>';
    }

    // Renderizza Quick Contacts (floating buttons)
    $quickContacts = $page['quick_contacts'] ?? $page['quickContacts'] ?? null;
    if ($quickContacts) {
        echo $renderer->render([
            'type' => 'quick-contact',
            'content' => $quickContacts,
            'styles' => [],
            'position' => []
        ], $roundedCorners);
    }
    ?>

    <!-- Swiper JS per slider -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Script per inizializzare gli slider -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inizializza tutti gli Swiper
            document.querySelectorAll('.swiper').forEach(function(swiperEl) {
                const sliderId = swiperEl.getAttribute('id');
                if (!sliderId) return;

                // Ottieni le configurazioni dall'elemento
                const autoplay = swiperEl.dataset.autoplay === 'true';
                const loop = swiperEl.dataset.loop === 'true';
                const speed = parseInt(swiperEl.dataset.speed) || 300;
                const delay = parseInt(swiperEl.dataset.delay) || 3000;

                new Swiper('#' + sliderId, {
                    loop: loop,
                    autoplay: autoplay ? {
                        delay: delay,
                        disableOnInteraction: false,
                    } : false,
                    speed: speed,
                    pagination: {
                        el: '#' + sliderId + ' .swiper-pagination',
                        clickable: true,
                    },
                    navigation: {
                        nextEl: '#' + sliderId + ' .swiper-button-next',
                        prevEl: '#' + sliderId + ' .swiper-button-prev',
                    },
                });
            });
        });
    </script>
</body>
</html>
    <?php
}

// Main execution
try {
    loadEnv();

    $slug = getSlug();

    if (!$slug) {
        throw new Exception("Slug non specificato. Usa ?slug=your-page o configura DEFAULT_SLUG nel file .env");
    }

    $page = fetchPage($slug);
    renderPage($page);

} catch (Exception $e) {
    if ($_ENV['DEBUG'] === 'true') {
        http_response_code(500);
        echo '<!DOCTYPE html><html><head><title>Errore</title></head><body>';
        echo '<h1>Errore</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    } else {
        http_response_code(500);
        echo '<!DOCTYPE html><html><head><title>Errore</title></head><body>';
        echo '<h1>Si è verificato un errore</h1>';
        echo '<p>Impossibile caricare la pagina richiesta.</p>';
        echo '</body></html>';
    }
}
