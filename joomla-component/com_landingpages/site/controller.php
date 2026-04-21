<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

JLoader::register('LandingPagesHelper', JPATH_COMPONENT . '/helpers/landingpages.php');

/**
 * Landing Pages Controller
 */
class LandingPagesController extends BaseController
{
    /**
     * The default view
     *
     * @var string
     */
    protected $default_view = 'page';

    /**
     * Method to display a view
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters
     *
     * @return  BaseController  This object to support chaining
     */
    public function display($cachable = false, $urlparams = [])
    {
        $cachable = true;

        $safeurlparams = [
            'slug' => 'STRING',
            'id' => 'INT',
            'limit' => 'UINT',
            'limitstart' => 'UINT',
            'return' => 'BASE64',
        ];

        parent::display($cachable, $safeurlparams);

        return $this;
    }

    /**
     * Submit lead form
     *
     * @return  void
     */
    public function submitLead()
    {
        // Check for request forgeries
        \Joomla\CMS\Session\Session::checkToken() or jexit('Invalid Token');

        $app = Factory::getApplication();
        $input = $app->input;

        // Get form data
        $data = [
            'page_id' => $input->getInt('page_id', 0),
            'name' => $input->getString('name', ''),
            'email' => $input->getString('email', ''),
            'phone' => $input->getString('phone', ''),
            'message' => $input->getString('message', ''),
        ];

        // Validate email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $app->enqueueMessage('Please provide a valid email address', 'error');
            $app->redirect($_SERVER['HTTP_REFERER'] ?? '/');
            return;
        }

        // Submit to API
        $result = LandingPagesHelper::submitLead($data);

        if ($result) {
            $app->enqueueMessage('Thank you! Your message has been submitted successfully.', 'success');
        } else {
            $app->enqueueMessage('Sorry, there was an error submitting your message. Please try again.', 'error');
        }

        // Redirect back
        $app->redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    /**
     * Display legal page (Privacy, Condizioni d'uso, Cookie Policy)
     *
     * URL: index.php?option=com_landingpages&task=displayLegal&slug={slug}&type={type}
     *
     * @return  void
     * @since   2.0.8
     */
    public function displayLegal()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Get parameters
        $slug = $input->getString('slug', '');
        $type = $input->getString('type', '');

        // Validazione slug e type
        if (empty($slug) || empty($type)) {
            throw new Exception('Parametri mancanti: slug e type sono obbligatori', 404);
        }

        // Sanitize slug (security: prevent path traversal)
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        $type = preg_replace('/[^a-z]/', '', strtolower($type));

        if (empty($slug) || empty($type)) {
            throw new Exception('Parametri non validi', 404);
        }

        // Whitelist type (only allowed legal page types)
        $allowedTypes = ['privacy', 'condizioni', 'cookies'];
        if (!in_array($type, $allowedTypes, true)) {
            throw new Exception('Tipo di pagina non valido. Tipi ammessi: ' . implode(', ', $allowedTypes), 404);
        }

        // Path traversal prevention
        if (strpos($slug, '..') !== false || strpos($slug, '/') !== false || strpos($slug, '\\') !== false) {
            throw new Exception('Slug non valido', 400);
        }

        // Fetch page data from API
        $pageData = LandingPagesHelper::getPageBySlug($slug);

        if (!$pageData) {
            throw new Exception('Pagina non trovata', 404);
        }

        // Check if page has legal_info
        $legalInfo = $pageData['legal_info'] ?? null;

        if (!$legalInfo || empty($legalInfo)) {
            throw new Exception('Informazioni legali non configurate per questa pagina', 404);
        }

        // Load LegalTemplateProcessor
        require_once JPATH_ROOT . '/components/com_landingpages/helpers/LegalTemplateProcessor.php';

        try {
            // Validate legal_info
            $processor = new LegalTemplateProcessor(JPATH_ROOT . '/components/com_landingpages/templates/legal');
            $processor->validate($legalInfo);

            // Render legal template
            $legalContent = $processor->render($type, $legalInfo);
        } catch (InvalidArgumentException $e) {
            throw new Exception('Informazioni legali incomplete: ' . $e->getMessage(), 400);
        } catch (Exception $e) {
            throw new Exception('Errore durante il rendering: ' . $e->getMessage(), 500);
        }

        // Set variables for view
        $this->setVariable('pageData', $pageData);
        $this->setVariable('legalContent', $legalContent);
        $this->setVariable('legalType', $type);
        $this->setVariable('slug', $slug);

        // Render the legal page layout
        $this->renderLegalPage($pageData, $legalContent, $type, $slug);
    }

    /**
     * Set a variable for later use
     *
     * @param string $name Variable name
     * @param mixed $value Variable value
     * @return void
     */
    private function setVariable($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * Render legal page with header/footer from original landing page
     *
     * @param array $pageData Page data from API
     * @param string $legalContent Rendered legal content (HTML)
     * @param string $type Legal page type (privacy|condizioni|cookies)
     * @param string $slug Page slug
     * @return void
     */
    private function renderLegalPage($pageData, $legalContent, $type, $slug)
    {
        // Load BlockRenderer
        require_once JPATH_ROOT . '/components/com_landingpages/helpers/blockrenderer.php';

        $apiUrl = LandingPagesHelper::getApiUrl();
        $renderer = new BlockRenderer($apiUrl, $slug);
        $renderer->setHasLegalInfo(!empty($pageData['legal_info']));

        $blocks = $pageData['blocks'] ?? [];
        $styles = $pageData['styles'] ?? [];

        // Find header and footer blocks
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

        // Collect fonts
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

        // Page type names
        $pageTypeNames = [
            'privacy' => 'Privacy Policy',
            'condizioni' => "Condizioni d'uso",
            'cookies' => 'Cookie Policy'
        ];

        $pageTitle = $pageTypeNames[$type] ?? 'Pagina Legale';
        $siteTitle = $pageData['title'] ?? 'Sito Web';

        // Set document title
        $document = Factory::getDocument();
        $document->setTitle($pageTitle . ' - ' . $siteTitle);

        // Add meta robots noindex
        $document->setMetaData('robots', 'noindex, nofollow');

        // Add Google Fonts
        foreach ($fonts as $font) {
            $document->addStyleSheet('https://fonts.googleapis.com/css2?family=' . urlencode($font) . ':wght@300;400;600;700&display=swap');
        }

        // Add Swiper CSS
        $document->addStyleSheet('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');

        // Add Tailwind CSS
        $document->addScript('https://cdn.tailwindcss.com');

        // Add Swiper JS
        $document->addScript('https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js');

        // Custom styles
        $containerWidth = $styles['containerWidth'] ?? 'max-w-6xl';
        $containerMaxWidth = [
            'max-w-4xl' => '56rem',
            'max-w-5xl' => '64rem',
            'max-w-6xl' => '72rem',
            'max-w-7xl' => '80rem',
            'max-w-full' => '100%'
        ][$containerWidth] ?? '72rem';

        $customCss = "
        body {
            font-family: " . ($styles['fontFamily'] ?? 'Inter') . ", sans-serif;
        }

        .container-width-custom {
            max-width: {$containerMaxWidth};
        }

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
        ";

        $document->addStyleDeclaration($customCss);

        // Swiper initialization script
        $swiperScript = "
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
        ";

        $document->addScriptDeclaration($swiperScript);

        // Render HTML output
        echo '<!-- Header della landing page originale -->';
        if ($headerBlock) {
            echo $renderer->renderBlock($headerBlock);
        }

        echo '<main class="py-12 px-4">';
        echo '<div class="container mx-auto container-width-custom">';
        echo '<h1 class="text-4xl font-bold mb-8 text-center">' . htmlspecialchars($pageTitle) . '</h1>';
        echo '<div class="legal-content">';
        echo $legalContent;
        echo '</div>';
        echo '</div>';
        echo '</main>';

        echo '<!-- Footer della landing page originale -->';
        if ($footerBlock) {
            echo $renderer->renderBlock($footerBlock);
        }

        // Exit to prevent default view rendering
        jexit();
    }
}
