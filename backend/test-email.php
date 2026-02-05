<?php
/**
 * Script di test invio email
 *
 * Testa la configurazione SMTP e l'invio email usando PHPMailer
 *
 * Usage: php backend/test-email.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "===========================================\n";
echo "  TEST CONFIGURAZIONE EMAIL SMTP\n";
echo "===========================================\n\n";

// Step 1: Verifica variabili .env
echo "1. Controllo variabili .env...\n";
$config = [
    'MAIL_HOST' => $_ENV['MAIL_HOST'] ?? null,
    'MAIL_PORT' => $_ENV['MAIL_PORT'] ?? null,
    'MAIL_USERNAME' => $_ENV['MAIL_USERNAME'] ?? null,
    'MAIL_PASSWORD' => $_ENV['MAIL_PASSWORD'] ?? null,
    'MAIL_ENCRYPTION' => $_ENV['MAIL_ENCRYPTION'] ?? null,
    'MAIL_FROM_ADDRESS' => $_ENV['MAIL_FROM_ADDRESS'] ?? null,
    'MAIL_FROM_NAME' => $_ENV['MAIL_FROM_NAME'] ?? null,
];

$missing = [];
foreach ($config as $key => $value) {
    if (empty($value)) {
        $missing[] = $key;
        echo "   ✗ $key: NON CONFIGURATO\n";
    } else {
        // Oscura password
        $displayValue = $key === 'MAIL_PASSWORD' ? str_repeat('*', strlen($value)) : $value;
        echo "   ✓ $key: $displayValue\n";
    }
}

if (!empty($missing)) {
    echo "\n✗ ERRORE: Variabili mancanti nel file .env\n";
    echo "Configura: " . implode(', ', $missing) . "\n";
    exit(1);
}

echo "\n2. Inizializzazione PHPMailer...\n";

try {
    $mail = new PHPMailer(true);

    // Debug verbose
    $mail->SMTPDebug = 2; // 0 = off, 1 = client, 2 = client e server
    $mail->Debugoutput = function($str, $level) {
        echo "   DEBUG: $str\n";
    };

    // Server SMTP
    $mail->isSMTP();
    $mail->Host = $config['MAIL_HOST'];
    $mail->Port = $config['MAIL_PORT'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['MAIL_USERNAME'];
    $mail->Password = $config['MAIL_PASSWORD'];
    $mail->SMTPSecure = $config['MAIL_ENCRYPTION'];
    $mail->Timeout = 10;
    $mail->CharSet = 'UTF-8';

    echo "   ✓ PHPMailer configurato\n";

    echo "\n3. Test connessione SMTP...\n";

    // Tenta connessione
    if (!$mail->smtpConnect()) {
        throw new Exception("Impossibile connettersi al server SMTP");
    }
    echo "   ✓ Connessione SMTP riuscita!\n";
    $mail->smtpClose();

    echo "\n4. Invio email di test...\n";

    // Chiedi destinatario
    echo "\nInserisci email destinatario per il test: ";
    $recipient = trim(fgets(STDIN));

    if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email destinatario non valida");
    }

    // Componi email
    $mail->setFrom($config['MAIL_FROM_ADDRESS'], $config['MAIL_FROM_NAME']);
    $mail->addAddress($recipient);
    $mail->Subject = 'Test Email - Landing Page Builder';
    $mail->isHTML(true);

    $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; }
        .content { background: white; padding: 20px; margin-top: 20px; border-radius: 8px; }
        .success { color: #10b981; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Test Email Riuscito!</h1>
        </div>
        <div class="content">
            <p class="success">La configurazione SMTP funziona correttamente.</p>
            <p><strong>Server:</strong> {$config['MAIL_HOST']}:{$config['MAIL_PORT']}</p>
            <p><strong>Encryption:</strong> {$config['MAIL_ENCRYPTION']}</p>
            <p><strong>From:</strong> {$config['MAIL_FROM_ADDRESS']}</p>
            <p><strong>Data invio:</strong> {date('d/m/Y H:i:s')}</p>
        </div>
    </div>
</body>
</html>
HTML;

    $mail->AltBody = "Test email - La configurazione SMTP funziona correttamente.";

    // Invia
    $mail->send();

    echo "\n";
    echo "===========================================\n";
    echo "  ✓ EMAIL INVIATA CON SUCCESSO!\n";
    echo "===========================================\n";
    echo "Destinatario: $recipient\n";
    echo "Controlla la casella email (anche spam).\n\n";

} catch (Exception $e) {
    echo "\n";
    echo "===========================================\n";
    echo "  ✗ ERRORE INVIO EMAIL\n";
    echo "===========================================\n";
    echo "Messaggio: {$mail->ErrorInfo}\n";
    echo "Exception: {$e->getMessage()}\n\n";

    echo "TROUBLESHOOTING:\n";
    echo "1. Verifica credenziali SMTP in .env\n";
    echo "2. Controlla che il server SMTP sia raggiungibile (firewall, porta 587 aperta)\n";
    echo "3. Verifica username/password Aruba corretti\n";
    echo "4. Controlla log errori: tail -f /var/log/php_errors.log\n";
    exit(1);
}
