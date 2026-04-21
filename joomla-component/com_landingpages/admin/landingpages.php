<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check
if (!Factory::getUser()->authorise('core.manage', 'com_landingpages'))
{
    throw new \Exception(\Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Get an instance of the controller
$controller = BaseController::getInstance('LandingPages');

// Perform the Request task
$input = Factory::getApplication()->input;
$task = $input->getCmd('task', 'display');

$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
