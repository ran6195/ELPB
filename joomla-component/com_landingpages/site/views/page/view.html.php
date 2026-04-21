<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

JLoader::register('LandingPagesHelper', JPATH_COMPONENT . '/helpers/landingpages.php');

/**
 * HTML Page View class for the Landing Pages component
 */
class LandingPagesViewPage extends HtmlView
{
    /**
     * Page data
     *
     * @var mixed
     */
    protected $page;

    /**
     * Blocks data
     *
     * @var array
     */
    protected $blocks;

    /**
     * Component parameters
     *
     * @var \Joomla\Registry\Registry
     */
    protected $params;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        // Get model
        $model = $this->getModel();

        // Get data from model
        $this->page = $model->getPage();
        $this->blocks = $model->getBlocks();
        $this->params = ComponentHelper::getParams('com_landingpages');

        // Check if page exists
        if (!$this->page) {
            throw new \Exception('Page not found', 404);
        }

        // Set page metadata
        $this->setMetadata();

        // If not showing in template, force component format (no Joomla chrome)
        $app = Factory::getApplication();
        if (!$this->params->get('show_in_template', 0)) {
            $app->input->set('tmpl', 'component');

            // Disable template rendering completely
            $document = Factory::getDocument();
            if (method_exists($document, 'setBase')) {
                $document->setBase('');
            }
        } else {
            // Load CSS if in template mode
            $this->addStylesheet();
        }

        parent::display($tpl);
    }

    /**
     * Set page metadata for SEO
     *
     * @return  void
     */
    protected function setMetadata()
    {
        $document = Factory::getDocument();
        $app = Factory::getApplication();

        // Set page title
        $metaTitle = $this->page['meta_title'] ?? $this->page['title'] ?? '';
        if (!empty($metaTitle)) {
            $document->setTitle($metaTitle);
        }

        // Set meta description
        $metaDescription = $this->page['meta_description'] ?? '';
        if (!empty($metaDescription)) {
            $document->setDescription($metaDescription);
        }

        // Set canonical URL
        $slug = $this->page['slug'] ?? '';
        if (!empty($slug)) {
            $uri = \Joomla\CMS\Uri\Uri::getInstance();
            $canonical = $uri->toString(['scheme', 'host', 'port']) .
                        \Joomla\CMS\Router\Route::_('index.php?option=com_landingpages&view=page&slug=' . $slug);
            $document->addHeadLink($canonical, 'canonical');
        }

        // Open Graph tags
        if (!empty($metaTitle)) {
            $document->setMetaData('og:title', $metaTitle);
        }
        if (!empty($metaDescription)) {
            $document->setMetaData('og:description', $metaDescription);
        }
        $document->setMetaData('og:type', 'website');
    }

    /**
     * Add component stylesheet
     *
     * @return  void
     */
    protected function addStylesheet()
    {
        $document = Factory::getDocument();
        $document->addStyleSheet(\Joomla\CMS\Uri\Uri::root() . 'components/com_landingpages/assets/css/landingpage.css');
    }
}
