<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Landing Pages Admin Controller
 */
class LandingPagesController extends BaseController
{
    /**
     * The default view
     *
     * @var string
     */
    protected $default_view = 'landingpages';

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
        parent::display($cachable, $urlparams);

        return $this;
    }
}
