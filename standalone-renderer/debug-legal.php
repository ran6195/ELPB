<?php
/**
 * Debug script per legal-pages.php
 * Testa ogni step del flusso per trovare dove fallisce
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug Legal Pages</h1>";
echo "<pre>";

// Step 1: Carica .env
echo "=== STEP 1: Carica .env ===\n";
$envPath = __DIR__ . '/.env';
echo "Path .env: $envPath\n";
echo "Esiste: " . (file_exists($envPath) ? 'SI' : 'NO') . "\n";

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

echo "\n=== STEP 2: Variabili .env ===\n";
echo "API_BASE_URL: " . ($_ENV['API_BASE_URL'] ?? 'NON DEFINITA') . "\n";
echo "API_URL: " . ($_ENV['API_URL'] ?? 'NON DEFINITA') . "\n";
echo "LEGAL_BASE_URL: " . ($_ENV['LEGAL_BASE_URL'] ?? 'NON DEFINITA') . "\n";
echo "DEBUG: " . ($_ENV['DEBUG'] ?? 'NON DEFINITA') . "\n";

// Step 3: Test API URL
$apiUrl = $_ENV['API_BASE_URL'] ?? 'http://localhost:8000/api';
echo "\n=== STEP 3: API URL ===\n";
echo "apiUrl usato: $apiUrl\n";

$testSlug = $_GET['slug'] ?? 'replica-aran';
$testType = $_GET['type'] ?? 'privacy';
echo "slug: $testSlug\n";
echo "type: $testType\n";

// Step 4: Costruisci URL API
$fullUrl = rtrim($apiUrl, '/') . '/page/' . urlencode($testSlug);
echo "\n=== STEP 4: URL API completo ===\n";
echo "URL: $fullUrl\n";

// Step 5: Test cURL
echo "\n=== STEP 5: Test cURL ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "cURL Error: " . ($error ?: 'nessuno') . "\n";
echo "Response length: " . strlen($response) . " bytes\n";

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    echo "JSON decode: " . ($data ? 'OK' : 'FALLITO') . "\n";

    if ($data) {
        echo "slug: " . ($data['slug'] ?? 'N/A') . "\n";
        echo "title: " . ($data['title'] ?? 'N/A') . "\n";
        echo "is_published: " . ($data['is_published'] ? 'true' : 'false') . "\n";
        echo "has error: " . (isset($data['error']) ? 'SI: ' . $data['error'] : 'NO') . "\n";

        // Step 6: legal_info
        echo "\n=== STEP 6: Legal Info ===\n";
        $legalInfo = $data['legal_info'] ?? null;
        echo "legal_info presente: " . ($legalInfo ? 'SI' : 'NO') . "\n";
        if ($legalInfo) {
            echo "tipo: " . gettype($legalInfo) . "\n";
            if (is_string($legalInfo)) {
                echo "ATTENZIONE: legal_info e' una stringa, non un array!\n";
                echo "Provo json_decode...\n";
                $decoded = json_decode($legalInfo, true);
                echo "json_decode: " . ($decoded ? 'OK' : 'FALLITO') . "\n";
                if ($decoded) {
                    $legalInfo = $decoded;
                }
            }
            if (is_array($legalInfo)) {
                echo "Campi: " . implode(', ', array_keys($legalInfo)) . "\n";
                echo "ragioneSociale: " . ($legalInfo['ragioneSociale'] ?? 'N/A') . "\n";
                echo "empty check: " . (empty($legalInfo) ? 'VUOTO' : 'NON VUOTO') . "\n";
            }
        }

        // Step 7: LegalTemplateProcessor
        echo "\n=== STEP 7: LegalTemplateProcessor ===\n";
        $processorPath = __DIR__ . '/../menu-legali/LegalTemplateProcessor.php';
        echo "Path: $processorPath\n";
        echo "Esiste: " . (file_exists($processorPath) ? 'SI' : 'NO') . "\n";

        if (file_exists($processorPath)) {
            require_once $processorPath;
            echo "Classe caricata: SI\n";

            $templateDir = __DIR__ . '/../menu-legali';
            echo "Template dir: $templateDir\n";
            echo "Template dir esiste: " . (is_dir($templateDir) ? 'SI' : 'NO') . "\n";

            $templateFile = $templateDir . '/' . $testType . '.php';
            echo "Template file ($testType): $templateFile\n";
            echo "Template file esiste: " . (file_exists($templateFile) ? 'SI' : 'NO') . "\n";

            if (is_array($legalInfo) && !empty($legalInfo)) {
                try {
                    $processor = new LegalTemplateProcessor($templateDir);
                    echo "Processor creato: SI\n";

                    // Test validate
                    try {
                        $processor->validate($legalInfo);
                        echo "Validate: OK\n";
                    } catch (Exception $e) {
                        echo "Validate FALLITO: " . $e->getMessage() . "\n";
                    }

                    // Test render
                    try {
                        $rendered = $processor->render($testType, $legalInfo);
                        echo "Render: OK (" . strlen($rendered) . " bytes)\n";
                    } catch (Exception $e) {
                        echo "Render FALLITO: " . $e->getMessage() . "\n";
                    }

                } catch (Exception $e) {
                    echo "Errore processor: " . $e->getMessage() . "\n";
                }
            }
        }
    }
} else {
    echo "ERRORE: API non raggiungibile o risposta non valida\n";
    if ($response) {
        echo "Response (primi 500 chars): " . substr($response, 0, 500) . "\n";
    }
}

// Step 8: Verifica versione legal-pages.php
echo "\n=== STEP 8: Verifica legal-pages.php ===\n";
$legalPagesPath = __DIR__ . '/legal-pages.php';
echo "Esiste: " . (file_exists($legalPagesPath) ? 'SI' : 'NO') . "\n";
if (file_exists($legalPagesPath)) {
    $content = file_get_contents($legalPagesPath);
    echo "Size: " . strlen($content) . " bytes\n";

    // Verifica se ha le fix applicate
    echo "\nVerifica fix applicate:\n";
    echo "- Usa API_BASE_URL: " . (strpos($content, "API_BASE_URL") !== false ? 'SI ✅' : 'NO ❌ (usa ancora API_URL)') . "\n";
    echo "- Usa /page/ (no /api/page/): " . (strpos($content, "'/api/page/'") !== false ? 'NO ❌ (doppio /api/)' : 'SI ✅') . "\n";
    echo "- Formato risposta corretto: " . (strpos($content, "\$response['success']") !== false ? 'NO ❌ (cerca wrapper)' : 'SI ✅') . "\n";
}

echo "\n=== RISULTATO ===\n";
echo "Se tutti gli step sono OK, legal-pages.php dovrebbe funzionare.\n";
echo "Se vedi errori, correggi e riprova.\n";
echo "</pre>";
