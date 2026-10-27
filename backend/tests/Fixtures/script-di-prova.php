<?php
/**
 * Script di prova per ScriptTest: si comporta come quelli di backend/scripts,
 * ma sul database dei test passato in ELPB_TEST_DB.
 */

require __DIR__ . '/../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->load();
$_ENV['DB_DATABASE'] = (string) getenv('ELPB_TEST_DB');
if (!str_ends_with($_ENV['DB_DATABASE'], '_test')) {
    fwrite(STDERR, "Database dei test non indicato\n");
    exit(2);
}
date_default_timezone_set('Europe/Rome');

require __DIR__ . '/../../config/database.php';
\App\Audit\ScriptAudit::start(basename(__FILE__, '.php'), $argv ?? []);

\App\Models\Company::create(['name' => 'Creata dallo script', 'email' => uniqid('script') . '@example.test']);

if (in_array('--fallisci', $argv, true)) {
    funzione_inesistente();
}
exit(0);
