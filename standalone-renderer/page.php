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

    // Initialize renderer with API URL, page ID and slug
    $apiUrl = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000/api';
    $pageId = $page['id'] ?? 0;
    $pageSlug = $page['slug'] ?? '';
    $hasLegalInfo = !empty($page['legal_info']) && is_array($page['legal_info']) && count($page['legal_info']) > 0;
    $legalBaseUrl = $_ENV['LEGAL_BASE_URL'] ?? ''; // Base URL per pagine legali (opzionale, per renderer remoti)
    $renderer = new BlockRenderer($apiUrl, $pageId, $pageSlug, $hasLegalInfo, $legalBaseUrl);

    $customStyles = $page['styles'] ?? [];
    $pageBackgroundColor = $customStyles['backgroundColor'] ?? '#FFFFFF';
    $blockGap = $customStyles['blockGap'] ?? 0;
    $fontFamily = $customStyles['fontFamily'] ?? '';
    $roundedCorners = $customStyles['roundedCorners'] ?? true;
    $containerWidth = $customStyles['containerWidth'] ?? 'max-w-7xl';

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
    <meta name="robots" content="noindex">

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

    <!-- Cookie Consent CSS -->
    <link href="/cookieconsent/cookieconsent.css" rel="stylesheet" />

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
        /* Stili per heading - semibold di default */
        h1, h2, h3 {
            font-weight: 600;
        }
        /* Grassetto normale per paragrafi e liste */
        p strong, p b,
        li strong, li b {
            font-weight: 700 !important;
        }
        /* Grassetto extra per heading */
        h1 strong, h1 b,
        h2 strong, h2 b,
        h3 strong, h3 b {
            font-weight: 900 !important;
        }
        /* Assicura che grassetto generico funzioni */
        strong, b {
            font-weight: 700 !important;
        }
        em, i {
            font-style: italic !important;
        }
        u {
            text-decoration: underline !important;
        }
        s, del {
            text-decoration: line-through !important;
        }
        blockquote {
            border-left: 4px solid #d1d5db;
            padding-left: 1em;
            margin-left: 0;
            margin-bottom: 1em;
            font-style: italic;
            color: #6b7280;
        }
        pre.ql-syntax {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1em;
            border-radius: 0.375rem;
            overflow-x: auto;
            margin-bottom: 1em;
            font-family: 'Courier New', monospace;
        }
        code {
            background: #f3f4f6;
            padding: 0.2em 0.4em;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
            font-size: 0.875em;
        }
        /* Allineamento testo Quill */
        .ql-align-center {
            text-align: center !important;
        }
        .ql-align-right {
            text-align: right !important;
        }
        .ql-align-justify {
            text-align: justify !important;
        }
        /* Container width override - sovrascrive max-w-7xl nei blocchi */
        .container-width-max-w-4xl .max-w-7xl {
            max-width: 56rem !important; /* 896px */
        }
        .container-width-max-w-5xl .max-w-7xl {
            max-width: 64rem !important; /* 1024px */
        }
        .container-width-max-w-6xl .max-w-7xl {
            max-width: 72rem !important; /* 1152px */
        }
        .container-width-max-w-7xl .max-w-7xl {
            max-width: 80rem !important; /* 1280px - default */
        }
        .container-width-max-w-full .max-w-7xl {
            max-width: 100% !important;
        }
    </style>

    <?php if (!empty($page['styles']['custom_css'])): ?>
    <style>
        <?php echo $page['styles']['custom_css']; ?>
    </style>
    <?php endif; ?>
</head>
<body class="container-width-<?php echo htmlspecialchars($containerWidth); ?>">
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
            if (typeof Swiper === 'undefined') {
                console.error('Swiper library not loaded');
                return;
            }

            // Inizializza tutti gli Swiper
            document.querySelectorAll('.swiper').forEach(function(swiperEl) {
                const sliderId = swiperEl.getAttribute('id');
                if (!sliderId) return;

                // Ottieni le configurazioni dai data attributes
                const loop = swiperEl.dataset.loop === 'true';
                const autoplay = swiperEl.dataset.autoplay === 'true';
                const delay = parseInt(swiperEl.dataset.delay) || 3000;
                const slidesPerView = parseInt(swiperEl.dataset.slidesPerView) || 3;
                const slideGap = parseInt(swiperEl.dataset.slideGap) || 20;
                const showNavigation = swiperEl.dataset.showNavigation === 'true';
                const showPagination = swiperEl.dataset.showPagination === 'true';

                // Configurazione Swiper
                const config = {
                    slidesPerView: 1,
                    spaceBetween: slideGap,
                    loop: loop,
                    autoplay: autoplay ? {
                        delay: delay,
                        disableOnInteraction: false
                    } : false,
                    breakpoints: {
                        768: {
                            slidesPerView: slidesPerView,
                            spaceBetween: slideGap
                        }
                    }
                };

                // Aggiungi navigazione se abilitata
                if (showNavigation) {
                    config.navigation = {
                        nextEl: '#' + sliderId + '-next',
                        prevEl: '#' + sliderId + '-prev'
                    };
                }

                // Aggiungi paginazione se abilitata
                if (showPagination) {
                    config.pagination = {
                        el: '#' + sliderId + '-pagination',
                        clickable: true
                    };
                }

                // Inizializza Swiper
                new Swiper('#' + sliderId, config);
            });
        });
    </script>
</body>
</html>
    <?php
}

/**
 * Renderizza la thank-you page con header e footer della landing page originale
 */
function renderThankYouPage($page) {
    if (!$page) {
        // Fallback: pagina statica semplice senza header/footer
        ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Grazie per averci contattato</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-6 inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Grazie per averci contattato!</h1>
            <p class="text-lg text-gray-600 mb-8">La tua richiesta è stata inviata con successo. Ti risponderemo al più presto.</p>
            <button onclick="window.history.back()" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-lg transition-colors">Torna indietro</button>
        </div>
    </div>
</body>
</html>
        <?php
        return;
    }

    $blocks = $page['blocks'] ?? [];
    usort($blocks, function($a, $b) {
        return ($a['order'] ?? 0) - ($b['order'] ?? 0);
    });

    $apiUrl = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000/api';
    $pageId = $page['id'] ?? 0;
    $pageSlug = $page['slug'] ?? '';
    $hasLegalInfo = !empty($page['legal_info']) && is_array($page['legal_info']) && count($page['legal_info']) > 0;
    $legalBaseUrl = $_ENV['LEGAL_BASE_URL'] ?? '';
    $renderer = new BlockRenderer($apiUrl, $pageId, $pageSlug, $hasLegalInfo, $legalBaseUrl);

    $customStyles = $page['styles'] ?? [];
    $fontFamily = $customStyles['fontFamily'] ?? '';
    $containerWidth = $customStyles['containerWidth'] ?? 'max-w-7xl';

    // Trova header e footer
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

    // Raccogli font
    $allFonts = [];
    if (!empty($fontFamily)) {
        $allFonts[$fontFamily] = true;
    }
    foreach ($blocks as $block) {
        if (!empty($block['styles']['fontFamily'])) {
            $allFonts[$block['styles']['fontFamily']] = true;
        }
    }
    $uniqueFonts = array_keys($allFonts);

    $siteTitle = $page['title'] ?? '';
    $pageTitle = $siteTitle ? 'Grazie - ' . htmlspecialchars($siteTitle) : 'Grazie per averci contattato';

    // GTM
    $trackingSettings = $page['tracking_settings'] ?? [];
    $gtmEnabled = $trackingSettings['gtm_enabled'] ?? false;
    $gtmId = $trackingSettings['gtm_id'] ?? '';

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
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $pageTitle; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <?php if (!empty($uniqueFonts)): ?>
        <?php foreach ($uniqueFonts as $font): ?>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($font); ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        body {
            margin: 0;
            padding: 0;
            <?php if ($fontFamily): ?>
            font-family: '<?php echo htmlspecialchars($fontFamily); ?>', sans-serif;
            <?php endif; ?>
        }
        .container-width-max-w-4xl .max-w-7xl { max-width: 56rem !important; }
        .container-width-max-w-5xl .max-w-7xl { max-width: 64rem !important; }
        .container-width-max-w-6xl .max-w-7xl { max-width: 72rem !important; }
        .container-width-max-w-7xl .max-w-7xl { max-width: 80rem !important; }
        .container-width-max-w-full .max-w-7xl { max-width: 100% !important; }
    </style>
</head>
<body class="container-width-<?php echo htmlspecialchars($containerWidth); ?>">
    <?php if ($gtmEnabled && !empty($gtmId)): ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($gtmId); ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php endif; ?>

    <?php if ($headerBlock): ?>
        <div class="block-container"><?php echo $renderer->render($headerBlock); ?></div>
    <?php endif; ?>

    <main class="py-20 px-4">
        <div class="max-w-md w-full text-center mx-auto">
            <div class="mb-6 inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Grazie per averci contattato!</h1>
            <p class="text-lg text-gray-600 mb-8">La tua richiesta è stata inviata con successo. Ti risponderemo al più presto.</p>
            <button onclick="window.history.back()" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-lg transition-colors">Torna indietro</button>
        </div>
    </main>

    <?php if ($footerBlock): ?>
        <div class="block-container"><?php echo $renderer->render($footerBlock); ?></div>
    <?php endif; ?>

    <!-- URL cosmetico: mostra /{slug}/thank-you nella barra del browser -->
    <script>
        (function() {
            var slug = '<?php echo htmlspecialchars($pageSlug, ENT_QUOTES); ?>';
            if (slug && window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '/' + slug + '/thank-you');
            }
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.swiper').forEach(function(swiperEl) {
                const sliderId = swiperEl.getAttribute('id');
                if (!sliderId) return;
                const loop = swiperEl.dataset.loop === 'true';
                const autoplay = swiperEl.dataset.autoplay === 'true';
                const delay = parseInt(swiperEl.dataset.delay) || 3000;
                const slidesPerView = parseInt(swiperEl.dataset.slidesPerView) || 3;
                const slideGap = parseInt(swiperEl.dataset.slideGap) || 20;
                const showNavigation = swiperEl.dataset.showNavigation === 'true';
                const showPagination = swiperEl.dataset.showPagination === 'true';
                const config = {
                    slidesPerView: 1, spaceBetween: slideGap, loop: loop,
                    autoplay: autoplay ? { delay: delay, disableOnInteraction: false } : false,
                    breakpoints: { 768: { slidesPerView: slidesPerView, spaceBetween: slideGap } }
                };
                if (showNavigation) config.navigation = { nextEl: '#' + sliderId + '-next', prevEl: '#' + sliderId + '-prev' };
                if (showPagination) config.pagination = { el: '#' + sliderId + '-pagination', clickable: true };
                new Swiper('#' + sliderId, config);
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

    // Check per thank-you page mode (via query string ?_mode=thankyou)
    if (isset($_GET['_mode']) && $_GET['_mode'] === 'thankyou') {
        $slug = isset($_GET['slug']) ? sanitizeSlug($_GET['slug']) : '';
        $page = !empty($slug) ? fetchPage($slug) : null;
        renderThankYouPage($page);
        exit;
    }

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
