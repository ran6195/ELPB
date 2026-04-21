<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View class for the Landing Pages component
 */
class LandingPagesViewLandingPages extends HtmlView
{
    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  mixed
     */
    public function display($tpl = null)
    {
        // Set the toolbar
        $this->addToolbar();

        // Get component parameters
        $this->params = ComponentHelper::getParams('com_landingpages');

        // Display the template
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar
     *
     * @return  void
     */
    protected function addToolbar()
    {
        ToolbarHelper::title(Text::_('COM_LANDINGPAGES'), 'generic');

        if (JFactory::getUser()->authorise('core.admin', 'com_landingpages'))
        {
            ToolbarHelper::preferences('com_landingpages');
        }
    }
}
