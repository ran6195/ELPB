<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

JLoader::register('BlockRenderer', JPATH_COMPONENT . '/helpers/blockrenderer.php');

// Check if this is a thank you page redirect
$app = Factory::getApplication();
$showThankYou = $app->input->get('thankyou', 0, 'int');

$showInTemplate = $this->params->get('show_in_template', 0);

// Get page styles
$pageBackgroundColor = $this->page['styles']['backgroundColor'] ?? '#FFFFFF';
$pageFontFamily = $this->page['styles']['fontFamily'] ?? '';
$containerWidth = $this->page['styles']['containerWidth'] ?? 'max-w-7xl';

// Set font family in BlockRenderer
if (!empty($pageFontFamily)) {
    BlockRenderer::setFontFamily($pageFontFamily);
}

// Set page slug for legal pages links
$pageSlug = $this->page['slug'] ?? '';
if (!empty($pageSlug)) {
    BlockRenderer::setPageSlug($pageSlug);
}

// Set hasLegalInfo based on presence of legal_info data
$hasLegalInfo = !empty($this->page['legal_info']) && is_array($this->page['legal_info']) && count($this->page['legal_info']) > 0;
BlockRenderer::setHasLegalInfo($hasLegalInfo);

// Set API URL for legal pages links
// Get from component params or use default based on current domain
$apiUrl = $this->params->get('api_url', '');
if (empty($apiUrl)) {
    // Build default API URL based on current domain
    $uri = \Joomla\CMS\Uri\Uri::getInstance();
    $apiUrl = $uri->toString(['scheme', 'host', 'port']) . '/api';
}
BlockRenderer::setApiUrl($apiUrl);

// Raccogli tutti i font unici usati nei blocchi
$allFonts = [];

// Aggiungi font globale della pagina se presente
if (!empty($pageFontFamily)) {
    $allFonts[$pageFontFamily] = true;
}

// Raccogli font da ogni blocco
if (!empty($this->blocks)) {
    foreach ($this->blocks as $block) {
        $blockFontFamily = $block['styles']['fontFamily'] ?? '';
        if (!empty($blockFontFamily)) {
            $allFonts[$blockFontFamily] = true;
        }
    }
}

// Converti in array di font unici
$uniqueFonts = array_keys($allFonts);

// Google Tag Manager
$trackingSettings = $this->page['tracking_settings'] ?? [];
$gtmEnabled = $trackingSettings['gtm_enabled'] ?? false;
$gtmId = $trackingSettings['gtm_id'] ?? '';

// If not showing in template, output full HTML structure
if (!$showInTemplate):
?>
<!DOCTYPE html>
<html lang="<?php echo Factory::getApplication()->getLanguage()->getTag(); ?>" style="scroll-behavior: smooth">
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

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->page['meta_title'] ?? $this->page['title'] ?? ''; ?></title>

    <?php if (!empty($this->page['meta_description'])): ?>
    <meta name="description" content="<?php echo htmlspecialchars($this->page['meta_description']); ?>">
    <?php endif; ?>
    <meta name="robots" content="noindex">

    <!-- TailwindCSS CDN (for styling) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <!-- Component CSS -->
    <link rel="stylesheet" href="<?php echo \Joomla\CMS\Uri\Uri::root(); ?>components/com_landingpages/assets/css/landingpage.css">

    <?php if (!empty($uniqueFonts)): ?>
    <!-- Google Fonts - Carica tutti i font unici usati nella pagina e nei blocchi -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <?php foreach ($uniqueFonts as $font): ?>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($font); ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Cookie Consent CSS -->
    <link href="/cookieconsent/cookieconsent.css" rel="stylesheet" />

    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-family: <?php echo !empty($pageFontFamily) ? '"' . htmlspecialchars($pageFontFamily) . '", ' : ''; ?>system-ui, -apple-system, sans-serif;
            background-color: <?php echo htmlspecialchars($pageBackgroundColor); ?>;
        }

        .landing-page-container {
            min-height: 100vh;
        }

        /* Stili per heading - semibold di default */
        h1, h2, h3 {
            font-weight: 600;
        }
        /* Font size per heading dentro contenuto Quill (.prose) — Tailwind CDN resetta font-size a inherit */
        .prose h1 { font-size: 2.25em; font-weight: 700; line-height: 1.2;  margin-top: 0.5em; margin-bottom: 0.5em; }
        .prose h2 { font-size: 1.75em; font-weight: 700; line-height: 1.25; margin-top: 0.5em; margin-bottom: 0.5em; }
        .prose h3 { font-size: 1.35em; font-weight: 600; line-height: 1.3;  margin-top: 0.5em; margin-bottom: 0.5em; }
        .prose p  { margin-bottom: 0.75em; }
        .prose p:last-child { margin-bottom: 0; }
        .prose ul, .prose ol { padding-left: 1.5em; margin-bottom: 0.75em; }
        .prose li { margin-bottom: 0.25em; }
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
</head>
<body>
    <?php if ($gtmEnabled && !empty($gtmId)): ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($gtmId); ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php endif; ?>
<?php endif; ?>

<?php if ($showInTemplate && !empty($uniqueFonts)): ?>
<!-- Google Fonts for template mode - Carica tutti i font unici usati nella pagina e nei blocchi -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php foreach ($uniqueFonts as $font): ?>
<link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($font); ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endforeach; ?>
<?php endif; ?>

<div class="landing-page-container container-width-<?php echo htmlspecialchars($containerWidth); ?>" <?php if ($showInTemplate && !empty($pageFontFamily)): ?>style="font-family: '<?php echo htmlspecialchars($pageFontFamily); ?>', system-ui, -apple-system, sans-serif;"<?php endif; ?>>
    <?php if ($showThankYou): ?>
        <!-- Thank You Message -->
        <div class="min-h-screen flex items-center justify-center px-4">
            <div class="max-w-md w-full text-center">
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
        </div>
    <?php elseif (!empty($this->blocks)): ?>
        <?php foreach ($this->blocks as $block): ?>
            <?php $anchorAttr = !empty($block['content']['anchor']) ? ' id="' . htmlspecialchars($block['content']['anchor']) . '"' : ''; ?>
            <div<?php echo $anchorAttr; ?>><?php echo BlockRenderer::render($block); ?></div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="container mx-auto py-20 px-4 text-center">
            <p class="text-gray-500">No content available for this page.</p>
        </div>
    <?php endif; ?>

    <?php
    // Render Quick Contacts (floating buttons)
    $quickContacts = $this->page['quick_contacts'] ?? $this->page['quickContacts'] ?? null;
    if ($quickContacts) {
        echo BlockRenderer::render([
            'type' => 'quick-contact',
            'content' => $quickContacts,
            'styles' => [],
            'position' => []
        ]);
    }
    ?>
</div>

<!-- Swiper JS -->
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

<?php if (!$showInTemplate): ?>
</body>
</html>
<?php endif; ?>
