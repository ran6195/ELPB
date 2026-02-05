<?php
/**
 * Script di test notifica email lead
 *
 * Simula l'invio di un lead e verifica che la notifica email venga inviata
 *
 * Usage: php backend/test-lead-email.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Database configuration
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'port'      => $_ENV['DB_PORT'] ?? '3306',
    'database'  => $_ENV['DB_DATABASE'] ?? 'landing_page_builder',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

use App\Models\Page;
use App\Models\Lead;
use App\Services\EmailService;

echo "===========================================\n";
echo "  TEST NOTIFICA EMAIL LEAD\n";
echo "===========================================\n\n";

// Step 1: Trova pagine con notifiche abilitate
echo "1. Cerco pagine con notifiche abilitate...\n";

$pages = Page::with('user')
    ->whereNotNull('notification_settings')
    ->get()
    ->filter(function($page) {
        return !empty($page->notification_settings['enabled']);
    });

if ($pages->isEmpty()) {
    echo "   ✗ Nessuna pagina con notifiche abilitate trovata!\n\n";
    echo "SOLUZIONE:\n";
    echo "1. Vai nell'editor di una pagina\n";
    echo "2. Apri 'Impostazioni Pagina'\n";
    echo "3. Scorri fino a 'Notifiche Email'\n";
    echo "4. Attiva il toggle\n";
    echo "5. Salva impostazioni\n";
    exit(1);
}

echo "   ✓ Trovate {$pages->count()} pagina/e con notifiche abilitate:\n\n";

foreach ($pages as $index => $page) {
    $ownerEmail = $page->user->email ?? 'N/A';
    $additionalEmails = $page->notification_settings['additional_emails'] ?? '';
    echo "   [{$index}] ID: {$page->id} - Slug: {$page->slug}\n";
    echo "       Owner: {$ownerEmail}\n";
    echo "       Email aggiuntive: " . ($additionalEmails ?: 'Nessuna') . "\n\n";
}

// Step 2: Seleziona pagina
echo "Seleziona pagina (0-" . ($pages->count() - 1) . "): ";
$selection = trim(fgets(STDIN));

if (!is_numeric($selection) || $selection < 0 || $selection >= $pages->count()) {
    echo "✗ Selezione non valida\n";
    exit(1);
}

$selectedPage = $pages->values()[$selection];

echo "\n2. Pagina selezionata: {$selectedPage->title} (ID: {$selectedPage->id})\n";

// Step 3: Crea lead di test
echo "\n3. Creazione lead di test...\n";

$testLead = Lead::create([
    'page_id' => $selectedPage->id,
    'name' => 'Test Lead',
    'email' => 'test-lead@example.com',
    'phone' => '+39 123 456 7890',
    'message' => 'Questo è un messaggio di test per verificare l\'invio delle notifiche email.',
    'metadata' => ['test' => true]
]);

echo "   ✓ Lead creato: ID {$testLead->id}\n";

// Step 4: Invia notifica
echo "\n4. Invio notifica email...\n";

try {
    $emailService = new EmailService();
    $result = $emailService->sendLeadNotification($testLead, $selectedPage);

    if ($result) {
        echo "\n";
        echo "===========================================\n";
        echo "  ✓ NOTIFICA INVIATA CON SUCCESSO!\n";
        echo "===========================================\n";
        echo "Destinatari:\n";
        echo "  - {$selectedPage->user->email}\n";

        if (!empty($selectedPage->notification_settings['additional_emails'])) {
            $additionalEmails = array_map('trim', explode(',', $selectedPage->notification_settings['additional_emails']));
            foreach ($additionalEmails as $email) {
                if (!empty($email)) {
                    echo "  - $email\n";
                }
            }
        }

        echo "\nControlla le caselle email (anche spam).\n";
        echo "\nLead di test creato con ID: {$testLead->id}\n";
        echo "Puoi eliminarlo manualmente dal database se necessario.\n\n";
    } else {
        echo "\n";
        echo "===========================================\n";
        echo "  ✗ INVIO FALLITO\n";
        echo "===========================================\n";
        echo "Controlla i log per dettagli.\n";
        echo "Possibili cause:\n";
        echo "  - Email owner non configurata\n";
        echo "  - Errore SMTP\n";
        echo "  - Email aggiuntive non valide\n\n";
    }
} catch (\Exception $e) {
    echo "\n";
    echo "===========================================\n";
    echo "  ✗ ERRORE\n";
    echo "===========================================\n";
    echo "Messaggio: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}
