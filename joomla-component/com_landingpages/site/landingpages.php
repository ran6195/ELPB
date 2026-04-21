<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Get an instance of the controller
$controller = BaseController::getInstance('LandingPages');

// Perform the Request task
$input = Factory::getApplication()->input;
$task = $input->getCmd('task', 'display');

$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
