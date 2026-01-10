<?php
/**
 * Configurazione Standalone Renderer
 */

// Error reporting (disabilitato in produzione)
if (isset($_ENV['DEBUG']) && $_ENV['DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Rome');

// Costanti utili
define('RENDERER_VERSION', '1.0.0');
define('RENDERER_PATH', __DIR__);
