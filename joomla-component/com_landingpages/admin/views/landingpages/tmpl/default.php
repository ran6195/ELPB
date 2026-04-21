<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$apiUrl = $this->params->get('api_url', 'Not configured');
$cacheEnabled = $this->params->get('cache_enabled', 1) ? 'Yes' : 'No';
$cacheTime = $this->params->get('cache_time', 300);
$showInTemplate = $this->params->get('show_in_template', 0) ? 'Yes' : 'No';
?>

<div class="row">
    <div class="col-md-12">
        <div id="j-main-container" class="j-main-container">

            <div class="alert alert-info">
                <h4 class="alert-heading">
                    <span class="icon-info-circle" aria-hidden="true"></span>
                    <?php echo Text::_('COM_LANDINGPAGES'); ?>
                </h4>
                <p><?php echo Text::_('COM_LANDINGPAGES_XML_DESCRIPTION'); ?></p>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3>Current Configuration</h3>
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th style="width: 30%">API URL</th>
                                <td><code><?php echo htmlspecialchars($apiUrl); ?></code></td>
                            </tr>
                            <tr>
                                <th>Cache Enabled</th>
                                <td><?php echo $cacheEnabled; ?></td>
                            </tr>
                            <tr>
                                <th>Cache Time</th>
                                <td><?php echo $cacheTime; ?> seconds</td>
                            </tr>
                            <tr>
                                <th>Show in Joomla Template</th>
                                <td><?php echo $showInTemplate; ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="text-muted">
                        <small>Click the "Options" button in the toolbar above to change these settings.</small>
                    </p>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h3>How to Use</h3>
                    <ol>
                        <li>
                            <strong>Configure API Connection:</strong> Click "Options" button above and set your Landing Page Builder API URL
                        </li>
                        <li>
                            <strong>Create Menu Items:</strong> Go to Menus → Your Menu → New
                        </li>
                        <li>
                            <strong>Select Menu Type:</strong> Choose "Landing Pages" → "Single Landing Page"
                        </li>
                        <li>
                            <strong>Enter Page Slug:</strong> Type the slug of your landing page (e.g., "my-landing-page")
                        </li>
                        <li>
                            <strong>Save & Visit:</strong> Save the menu item and visit your site to see the landing page
                        </li>
                    </ol>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h3>Quick Links</h3>
                    <ul>
                        <li>
                            <a href="index.php?option=com_menus&view=items" class="btn btn-sm btn-primary">
                                <span class="icon-menu" aria-hidden="true"></span>
                                Manage Menus
                            </a>
                        </li>
                        <li class="mt-2">
                            <a href="index.php?option=com_cache" class="btn btn-sm btn-secondary">
                                <span class="icon-refresh" aria-hidden="true"></span>
                                Clear Cache
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
