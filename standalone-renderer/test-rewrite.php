<?php
/**
 * Script di test per verificare la configurazione URL Rewriting
 *
 * Accedi a questo file per diagnosticare problemi con mod_rewrite
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test URL Rewriting</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .success { color: #059669; background: #d1fae5; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { color: #dc2626; background: #fee2e2; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .warning { color: #d97706; background: #fef3c7; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { color: #2563eb; background: #dbeafe; padding: 15px; border-radius: 8px; margin: 10px 0; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto; }
        h1 { color: #1f2937; }
        h2 { color: #374151; margin-top: 30px; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <h1>🔧 Test Configurazione URL Rewriting</h1>

    <?php
    // Test 1: mod_rewrite abilitato
    echo '<h2>1. Verifica mod_rewrite</h2>';
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        if (in_array('mod_rewrite', $modules)) {
            echo '<div class="success">✅ mod_rewrite è abilitato</div>';
        } else {
            echo '<div class="error">❌ mod_rewrite NON è abilitato<br>';
            echo 'Soluzione: Abilita mod_rewrite nel tuo Apache config</div>';
        }
    } else {
        echo '<div class="warning">⚠️ Impossibile verificare mod_rewrite (funzione apache_get_modules non disponibile)<br>';
        echo 'Questo è normale su alcuni hosting. Se gli URL puliti non funzionano, contatta il supporto hosting.</div>';
    }

    // Test 2: .htaccess presente
    echo '<h2>2. Verifica file .htaccess</h2>';
    if (file_exists(__DIR__ . '/.htaccess')) {
        echo '<div class="success">✅ File .htaccess trovato</div>';

        $htaccess = file_get_contents(__DIR__ . '/.htaccess');

        // Controlla RewriteBase
        if (preg_match('/RewriteBase\s+(.+)/', $htaccess, $matches)) {
            $rewriteBase = trim($matches[1]);
            echo '<div class="info">📍 RewriteBase configurato: <code>' . htmlspecialchars($rewriteBase) . '</code></div>';

            // Suggerisci la directory corretta
            $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
            if ($scriptPath === '/') {
                $expectedBase = '/';
            } else {
                $expectedBase = rtrim($scriptPath, '/') . '/';
            }

            if ($rewriteBase !== $expectedBase) {
                echo '<div class="warning">⚠️ RewriteBase potrebbe non essere corretto<br>';
                echo 'Valore attuale: <code>' . htmlspecialchars($rewriteBase) . '</code><br>';
                echo 'Valore suggerito: <code>' . htmlspecialchars($expectedBase) . '</code><br><br>';
                echo 'Modifica il file .htaccess e cambia la riga:<br>';
                echo '<code>RewriteBase ' . htmlspecialchars($expectedBase) . '</code></div>';
            } else {
                echo '<div class="success">✅ RewriteBase configurato correttamente</div>';
            }
        }
    } else {
        echo '<div class="error">❌ File .htaccess NON trovato<br>';
        echo 'Soluzione: Copia il file .htaccess nella directory corrente</div>';
    }

    // Test 3: PATH_INFO
    echo '<h2>3. Verifica PATH_INFO</h2>';
    if (isset($_SERVER['PATH_INFO'])) {
        echo '<div class="success">✅ PATH_INFO funziona<br>';
        echo 'Valore: <code>' . htmlspecialchars($_SERVER['PATH_INFO']) . '</code></div>';
    } else {
        echo '<div class="info">ℹ️ PATH_INFO non impostato (normale se accedi direttamente a questo file)</div>';
    }

    // Test 4: Informazioni Server
    echo '<h2>4. Informazioni Server</h2>';
    echo '<div class="info">';
    echo '<strong>Document Root:</strong> <code>' . htmlspecialchars($_SERVER['DOCUMENT_ROOT']) . '</code><br>';
    echo '<strong>Script Path:</strong> <code>' . htmlspecialchars(dirname($_SERVER['SCRIPT_NAME'])) . '</code><br>';
    echo '<strong>Request URI:</strong> <code>' . htmlspecialchars($_SERVER['REQUEST_URI']) . '</code><br>';
    echo '<strong>PHP Version:</strong> <code>' . phpversion() . '</code><br>';
    echo '<strong>Server Software:</strong> <code>' . htmlspecialchars($_SERVER['SERVER_SOFTWARE']) . '</code>';
    echo '</div>';

    // Test 5: Test pratico
    echo '<h2>5. Test Pratico URL Rewriting</h2>';
    echo '<div class="info">';
    echo '<p>Prova questi URL per verificare il funzionamento:</p>';

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
               "://" . $_SERVER['HTTP_HOST'] .
               dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = rtrim($baseUrl, '/');

    echo '<ol>';
    echo '<li><strong>Con query string (dovrebbe funzionare):</strong><br>';
    echo '<a href="' . $baseUrl . '/page.php?slug=test" target="_blank">' . $baseUrl . '/page.php?slug=test</a></li>';
    echo '<li><strong>URL pulito (da testare):</strong><br>';
    echo '<a href="' . $baseUrl . '/test" target="_blank">' . $baseUrl . '/test</a></li>';
    echo '</ol>';
    echo '</div>';

    // Istruzioni
    echo '<h2>6. Istruzioni per Risolvere i Problemi</h2>';
    echo '<div class="warning">';
    echo '<h3>Se gli URL puliti non funzionano:</h3>';
    echo '<ol>';
    echo '<li>Verifica che <code>mod_rewrite</code> sia abilitato (vedi sopra)</li>';
    echo '<li>Controlla che <code>RewriteBase</code> nell\'.htaccess sia corretto</li>';
    echo '<li>Verifica che il file <code>.htaccess</code> sia nella stessa directory di <code>page.php</code></li>';
    echo '<li>Controlla che AllowOverride sia impostato su <code>All</code> nel VirtualHost di Apache</li>';
    echo '<li>Se usi hosting condiviso, contatta il supporto per verificare la configurazione</li>';
    echo '</ol>';
    echo '</div>';

    echo '<h2>7. Fix Rapido per ' . htmlspecialchars(dirname($_SERVER['SCRIPT_NAME'])) . '</h2>';
    $currentDir = dirname($_SERVER['SCRIPT_NAME']);
    if ($currentDir === '/') {
        $suggestedRewriteBase = '/';
    } else {
        $suggestedRewriteBase = rtrim($currentDir, '/') . '/';
    }

    echo '<div class="info">';
    echo '<p>Modifica il file <code>.htaccess</code> e imposta:</p>';
    echo '<pre>RewriteBase ' . htmlspecialchars($suggestedRewriteBase) . '</pre>';
    echo '</div>';
    ?>

    <hr style="margin: 40px 0;">
    <p style="text-align: center; color: #6b7280;">
        <small>Test completato. Se hai bisogno di aiuto, consulta il file TROUBLESHOOTING.md</small>
    </p>
</body>
</html>
