<?php
/**
 * Wrapper per testare legal-pages.php con errori visibili
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simula parametri se non presenti
if (!isset($_GET['slug'])) $_GET['slug'] = 'replica-aran';
if (!isset($_GET['type'])) $_GET['type'] = 'privacy';

echo "<!-- Debug: Esecuzione legal-pages.php con slug=" . $_GET['slug'] . " type=" . $_GET['type'] . " -->\n";

try {
    require __DIR__ . '/legal-pages.php';
} catch (Throwable $e) {
    echo "<h1>ERRORE CATTURATO</h1>";
    echo "<pre>";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Messaggio: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Riga: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    echo "</pre>";
}
