<?php

/**
 * Bootstrap dei test: stesso .env dello sviluppo, ma database con il suffisso _test
 * (da creare con tests/create-test-db.sh). Le variabili impostate qui hanno la
 * precedenza su .env perché Dotenv in bootstrap/app.php non sovrascrive quelle esistenti.
 */

require __DIR__ . '/../vendor/autoload.php';

$env = Dotenv\Dotenv::parse(file_get_contents(__DIR__ . '/../.env'));
$testDatabase = ($env['DB_DATABASE'] ?? 'landing_page_builder') . '_test';

foreach (['DB_DATABASE' => $testDatabase, 'APP_DEBUG' => 'false', 'BASE_PATH' => ''] as $name => $value) {
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
    putenv("$name=$value");
}
