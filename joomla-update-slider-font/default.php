<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_landingpages
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

JLoader::register('BlockRenderer', JPATH_COMPONENT . '/helpers/blockrenderer.php');

$showInTemplate = $this->params->get('show_in_template', 0);

// Get page styles
$pageBackgroundColor = $this->page['styles']['backgroundColor'] ?? '#FFFFFF';
$pageFontFamily = $this->page['styles']['fontFamily'] ?? '';

// Set font family in BlockRenderer
if (!empty($pageFontFamily)) {
    BlockRenderer::setFontFamily($pageFontFamily);
}

// If not showing in template, output full HTML structure
if (!$showInTemplate):
?>
<!DOCTYPE html>
<html lang="<?php echo Factory::getApplication()->getLanguage()->getTag(); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->page['meta_title'] ?? $this->page['title'] ?? ''; ?></title>

    <?php if (!empty($this->page['meta_description'])): ?>
    <meta name="description" content="<?php echo htmlspecialchars($this->page['meta_description']); ?>">
    <?php endif; ?>

    <!-- TailwindCSS CDN (for styling) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Component CSS -->
    <link rel="stylesheet" href="<?php echo \Joomla\CMS\Uri\Uri::root(); ?>components/com_landingpages/assets/css/landingpage.css">

    <?php if (!empty($pageFontFamily)): ?>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($pageFontFamily); ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>

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
    </style>
</head>
<body>
<?php endif; ?>

<?php if ($showInTemplate && !empty($pageFontFamily)): ?>
<!-- Google Fonts for template mode -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($pageFontFamily); ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<?php endif; ?>

<div class="landing-page-container" <?php if ($showInTemplate && !empty($pageFontFamily)): ?>style="font-family: '<?php echo htmlspecialchars($pageFontFamily); ?>', system-ui, -apple-system, sans-serif;"<?php endif; ?>>
    <?php if (!empty($this->blocks)): ?>
        <?php foreach ($this->blocks as $block): ?>
            <?php echo BlockRenderer::render($block); ?>
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

<?php if (!$showInTemplate): ?>
</body>
</html>
<?php endif; ?>
