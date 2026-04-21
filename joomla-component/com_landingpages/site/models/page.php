<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

JLoader::register('LandingPagesHelper', JPATH_COMPONENT . '/helpers/landingpages.php');

/**
 * Landing Page Model
 */
class LandingPagesModelPage extends BaseDatabaseModel
{
    /**
     * Page data
     *
     * @var mixed
     */
    protected $page = null;

    /**
     * Get page data by slug
     *
     * @param   string  $slug  Page slug
     *
     * @return  mixed   Page data or false
     */
    public function getPage($slug = null)
    {
        if ($this->page === null) {
            if ($slug === null) {
                $app = Factory::getApplication();
                $slug = $app->input->getString('slug', '');
            }

            if (empty($slug)) {
                return false;
            }

            $this->page = LandingPagesHelper::getPageBySlug($slug);

            // Check if page is published
            if ($this->page && isset($this->page['is_published']) && !$this->page['is_published']) {
                Factory::getApplication()->enqueueMessage(
                    'This page is not published',
                    'warning'
                );
                return false;
            }
        }

        return $this->page;
    }

    /**
     * Get page blocks sorted by order
     *
     * @return  array
     */
    public function getBlocks()
    {
        $page = $this->getPage();

        if (!$page || !isset($page['blocks'])) {
            return [];
        }

        $blocks = $page['blocks'];

        // Sort by order
        usort($blocks, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        return $blocks;
    }

    /**
     * Get page metadata
     *
     * @return  array
     */
    public function getMetadata()
    {
        $page = $this->getPage();

        if (!$page) {
            return [
                'title' => '',
                'meta_title' => '',
                'meta_description' => '',
            ];
        }

        return [
            'title' => $page['title'] ?? '',
            'meta_title' => $page['meta_title'] ?? $page['title'] ?? '',
            'meta_description' => $page['meta_description'] ?? '',
        ];
    }
}
