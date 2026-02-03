<?php
/**
 * Script di diagnostica per verificare setup pagine legali
 * Usa questo per capire cosa manca sul server
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostica Pagine Legali</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        h2 { margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px; }
        pre { background: #eee; padding: 10px; overflow-x: auto; }
        .path { background: #ffe; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostica Setup Pagine Legali</h1>

    <?php
    $allOk = true;

    // 1. Directory corrente
    echo '<div class="section">';
    echo '<h2>1️⃣ Directory Corrente</h2>';
    echo '<strong>__DIR__:</strong> <span class="path">' . __DIR__ . '</span><br>';
    echo '<strong>Script:</strong> <span class="path">' . __FILE__ . '</span>';
    echo '</div>';

    // 2. File legal-pages.php
    echo '<div class="section">';
    echo '<h2>2️⃣ File legal-pages.php</h2>';
    $legalPagesFile = __DIR__ . '/legal-pages.php';
    if (file_exists($legalPagesFile)) {
        echo '<span class="ok">✅ TROVATO</span><br>';
        echo '<span class="path">' . $legalPagesFile . '</span><br>';
        echo 'Size: ' . filesize($legalPagesFile) . ' bytes';
    } else {
        echo '<span class="error">❌ NON TROVATO</span><br>';
        echo '<span class="path">' . $legalPagesFile . '</span>';
        $allOk = false;
    }
    echo '</div>';

    // 3. Directory menu-legali
    echo '<div class="section">';
    echo '<h2>3️⃣ Directory menu-legali</h2>';
    $menuLegaliDir = __DIR__ . '/../menu-legali';
    $menuLegaliDirReal = realpath($menuLegaliDir);

    echo '<strong>Path relativo:</strong> <span class="path">' . $menuLegaliDir . '</span><br>';

    if ($menuLegaliDirReal) {
        echo '<span class="ok">✅ TROVATA</span><br>';
        echo '<strong>Path assoluto:</strong> <span class="path">' . $menuLegaliDirReal . '</span><br>';
        echo '<strong>Leggibile:</strong> ' . (is_readable($menuLegaliDirReal) ? '<span class="ok">✅ SI</span>' : '<span class="error">❌ NO</span>');
    } else {
        echo '<span class="error">❌ NON TROVATA</span><br>';
        echo '<span class="warning">⚠️ La directory menu-legali deve essere un livello sopra standalone-renderer</span>';
        $allOk = false;
    }
    echo '</div>';

    // 4. File LegalTemplateProcessor.php
    echo '<div class="section">';
    echo '<h2>4️⃣ File LegalTemplateProcessor.php</h2>';
    $processorFile = __DIR__ . '/../menu-legali/LegalTemplateProcessor.php';
    $processorFileReal = realpath($processorFile);

    if ($processorFileReal && file_exists($processorFileReal)) {
        echo '<span class="ok">✅ TROVATO</span><br>';
        echo '<span class="path">' . $processorFileReal . '</span><br>';
        echo 'Size: ' . filesize($processorFileReal) . ' bytes';
    } else {
        echo '<span class="error">❌ NON TROVATO</span><br>';
        echo '<span class="path">' . $processorFile . '</span>';
        $allOk = false;
    }
    echo '</div>';

    // 5. Template file
    echo '<div class="section">';
    echo '<h2>5️⃣ Template File</h2>';
    $templates = [
        'privacy' => 'privacy.php',
        'condizioni' => 'condizioni.php',
        'cookies' => 'cookies.php'
    ];

    foreach ($templates as $type => $filename) {
        $templatePath = __DIR__ . '/../menu-legali/' . $filename;
        $templatePathReal = realpath($templatePath);

        echo "<strong>$type:</strong> ";
        if ($templatePathReal && file_exists($templatePathReal)) {
            echo '<span class="ok">✅</span> ';
            echo '<span class="path">' . $templatePathReal . '</span> ';
            echo '(' . filesize($templatePathReal) . ' bytes)<br>';
        } else {
            echo '<span class="error">❌</span> ';
            echo '<span class="path">' . $templatePath . '</span><br>';
            $allOk = false;
        }
    }
    echo '</div>';

    // 6. File .htaccess
    echo '<div class="section">';
    echo '<h2>6️⃣ File .htaccess</h2>';
    $htaccessFile = __DIR__ . '/.htaccess';

    if (file_exists($htaccessFile)) {
        echo '<span class="ok">✅ TROVATO</span><br>';
        echo '<span class="path">' . $htaccessFile . '</span><br>';

        // Verifica regola legal routing
        $htaccessContent = file_get_contents($htaccessFile);
        if (strpos($htaccessContent, 'legal-pages.php') !== false) {
            echo '<span class="ok">✅ Contiene routing per legal-pages.php</span>';
        } else {
            echo '<span class="warning">⚠️ Non contiene routing per legal-pages.php</span>';
        }
    } else {
        echo '<span class="error">❌ NON TROVATO</span><br>';
        echo '<span class="path">' . $htaccessFile . '</span>';
    }
    echo '</div>';

    // 7. PHP Version
    echo '<div class="section">';
    echo '<h2>7️⃣ Ambiente PHP</h2>';
    echo '<strong>PHP Version:</strong> ' . phpversion() . '<br>';
    echo '<strong>file_get_contents():</strong> ' . (function_exists('file_get_contents') ? '<span class="ok">✅</span>' : '<span class="error">❌</span>') . '<br>';
    echo '<strong>realpath():</strong> ' . (function_exists('realpath') ? '<span class="ok">✅</span>' : '<span class="error">❌</span>') . '<br>';
    echo '</div>';

    // 8. Test creazione LegalTemplateProcessor
    echo '<div class="section">';
    echo '<h2>8️⃣ Test LegalTemplateProcessor</h2>';

    if ($processorFileReal && file_exists($processorFileReal)) {
        try {
            require_once $processorFileReal;
            echo '<span class="ok">✅ Classe caricata correttamente</span><br>';

            $processor = new LegalTemplateProcessor(__DIR__ . '/../menu-legali');
            echo '<span class="ok">✅ Istanza creata correttamente</span><br>';

            // Test render
            $testData = [
                'ragioneSociale' => 'Test SRL',
                'partitaIva' => '12345678901',
                'sede' => 'Via Test 123, 00100 Roma',
                'email' => 'test@example.com',
                'pec' => 'test@pec.it',
                'telefono' => '06 1234567',
                'nomeResponsabileTrattamento' => 'Mario Rossi',
                'emailResponsabileTrattamento' => 'privacy@example.com',
                'sitoDitta' => 'https://www.example.com',
                'indirizzoSito' => 'www.example.com',
                'emailSito' => 'info@example.com',
                'telefonoSito' => '06 7654321',
                'emailContatto' => 'contatti@example.com'
            ];

            $rendered = $processor->render('privacy', $testData);
            echo '<span class="ok">✅ Render privacy funzionante</span><br>';
            echo 'Output size: ' . strlen($rendered) . ' bytes';

        } catch (Exception $e) {
            echo '<span class="error">❌ ERRORE:</span> ' . htmlspecialchars($e->getMessage());
            $allOk = false;
        }
    } else {
        echo '<span class="error">❌ Impossibile testare: LegalTemplateProcessor.php non trovato</span>';
    }
    echo '</div>';

    // 9. Struttura directory suggerita
    echo '<div class="section">';
    echo '<h2>9️⃣ Struttura Directory Richiesta</h2>';
    echo '<pre>';
    echo 'edysma.net/ELPB/
├── menu-legali/
│   ├── LegalTemplateProcessor.php
│   ├── privacy.php
│   ├── condizioni.php
│   └── cookies.php
└── standalone-renderer/
    ├── legal-pages.php
    ├── page.php
    ├── BlockRenderer.php
    └── .htaccess
';
    echo '</pre>';
    echo '</div>';

    // 10. Riepilogo
    echo '<div class="section" style="background: ' . ($allOk ? '#d4edda' : '#f8d7da') . ';">';
    echo '<h2>📊 Riepilogo</h2>';
    if ($allOk) {
        echo '<h3 style="color: green;">✅ Setup Completo!</h3>';
        echo '<p>Tutti i file necessari sono presenti e accessibili.</p>';
        echo '<p><strong>Test pagina legale:</strong><br>';
        echo '<a href="legal-pages.php?slug=test&type=privacy">legal-pages.php?slug=test&type=privacy</a></p>';
    } else {
        echo '<h3 style="color: red;">❌ Setup Incompleto</h3>';
        echo '<p><strong>Azioni richieste:</strong></p>';
        echo '<ol>';
        if (!$menuLegaliDirReal) {
            echo '<li>Caricare la directory <code>menu-legali/</code> sul server</li>';
            echo '<li>Posizionarla un livello sopra <code>standalone-renderer/</code></li>';
        }
        if (!$processorFileReal || !file_exists($processorFileReal)) {
            echo '<li>Verificare che <code>LegalTemplateProcessor.php</code> sia presente in <code>menu-legali/</code></li>';
        }
        foreach ($templates as $type => $filename) {
            $templatePath = __DIR__ . '/../menu-legali/' . $filename;
            if (!realpath($templatePath)) {
                echo "<li>Verificare che <code>$filename</code> sia presente in <code>menu-legali/</code></li>";
            }
        }
        echo '</ol>';
    }
    echo '</div>';
    ?>

    <div class="section">
        <h2>🔗 Link Utili</h2>
        <ul>
            <li><a href="<?php echo $_SERVER['PHP_SELF']; ?>">Ricarica diagnostica</a></li>
            <li><a href="page.php?slug=test">Test page.php</a></li>
            <?php if ($allOk): ?>
            <li><a href="legal-pages.php?slug=test&type=privacy">Test legal-pages.php (privacy)</a></li>
            <li><a href="legal-pages.php?slug=test&type=condizioni">Test legal-pages.php (condizioni)</a></li>
            <li><a href="legal-pages.php?slug=test&type=cookies">Test legal-pages.php (cookies)</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <p style="text-align: center; color: #666; margin-top: 30px;">
        <small>Landing Page Builder - Diagnostica Pagine Legali v1.0</small>
    </p>
</body>
</html>
