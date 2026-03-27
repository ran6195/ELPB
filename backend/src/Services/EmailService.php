<?php
/**
 * EmailService - Servizio centralizzato per invio email
 *
 * Responsabilità:
 * - Caricamento configurazione SMTP da .env
 * - Invio notifiche lead via PHPMailer
 * - Template HTML responsive
 * - Gestione errori con logging
 *
 * @version 1.0.0
 * @created 2026-02-05
 */

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;
    private $config;

    /**
     * Constructor - inizializza PHPMailer con config da .env
     */
    public function __construct()
    {
        $this->config = [
            'host' => $_ENV['MAIL_HOST'] ?? '',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Landing Page Builder',
            'timeout' => $_ENV['MAIL_TIMEOUT'] ?? 10,
        ];

        $this->initializeMailer();
    }

    /**
     * Inizializza PHPMailer con configurazione SMTP
     */
    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);

        // Server SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['host'];
        $this->mailer->Port = $this->config['port'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->config['username'];
        $this->mailer->Password = $this->config['password'];
        $this->mailer->SMTPSecure = $this->config['encryption'];
        $this->mailer->Timeout = $this->config['timeout'];

        // Opzioni SMTP avanzate per migliorare deliverability
        $this->mailer->SMTPKeepAlive = true; // Mantieni connessione aperta
        $this->mailer->SMTPAutoTLS = true;   // Auto TLS upgrade

        // Charset
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Encoding = 'base64'; // Encoding più compatibile con Gmail

        // Mittente predefinito
        $this->mailer->setFrom($this->config['from_address'], $this->config['from_name']);

        // Header anti-spam per migliorare deliverability con Gmail
        $this->mailer->XMailer = ' '; // Rimuovi X-Mailer header che può triggerare spam filter

        // Priorità normale (non alta, evita spam flag)
        $this->mailer->Priority = 3;

        // Debug (disabilitato in produzione)
        $this->mailer->SMTPDebug = 0;
    }

    /**
     * Invia notifica email per nuovo lead
     *
     * @param object $lead Lead Eloquent model
     * @param object $page Page Eloquent model (with user and company relations)
     * @return bool True se invio riuscito, false altrimenti
     */
    public function sendLeadNotification($lead, $page)
    {
        try {
            // Verifica che le notifiche siano abilitate
            $notificationSettings = $page->notification_settings ?? [];
            if (empty($notificationSettings['enabled'])) {
                return false; // Notifiche disabilitate
            }

            // Determina destinatari
            $recipients = $this->getRecipients($page, $notificationSettings);
            if (empty($recipients)) {
                error_log("EmailService: Nessun destinatario configurato per pagina {$page->id}");
                return false;
            }

            // Prepara email
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            // Aggiungi destinatari
            foreach ($recipients as $email) {
                $this->mailer->addAddress($email);
            }

            // Reply-to: email del lead (se presente e valida)
            if (!empty($lead->email) && filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addReplyTo($lead->email, $lead->name ?? '');
            }

            // Header custom per migliorare deliverability Gmail
            $this->mailer->addCustomHeader('X-Priority', '3'); // Normale
            $this->mailer->addCustomHeader('X-MSMail-Priority', 'Normal');
            $this->mailer->addCustomHeader('Importance', 'Normal');

            // Message-ID con dominio reale per evitare spam flag
            $domain = parse_url($this->config['host'], PHP_URL_HOST) ?: $this->config['host'];
            $messageId = sprintf('<%s.%s@%s>', uniqid(), time(), $domain);
            $this->mailer->MessageID = $messageId;

            // Oggetto chiaro e non spam-like
            $this->mailer->Subject = "Nuovo contatto dalla landing page: {$page->title}";

            // Corpo email HTML
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getLeadEmailTemplate($lead, $page);

            // Alternativa plain text (IMPORTANTE per Gmail)
            $this->mailer->AltBody = $this->getLeadEmailPlainText($lead, $page);

            // Invia
            $result = $this->mailer->send();

            if ($result) {
                error_log("EmailService: Email lead #{$lead->id} inviata con successo a " . implode(', ', $recipients));
            }

            return $result;

        } catch (Exception $e) {
            error_log("EmailService: Errore invio email lead #{$lead->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invia email di cortesia al cliente che ha inviato il form
     *
     * @param object $lead
     * @param object $page
     * @return bool
     */
    public function sendLeadConfirmation($lead, $page)
    {
        try {
            $confirmationSettings = $page->notification_settings['confirmation_email'] ?? [];

            if (empty($confirmationSettings['enabled'])) {
                return false;
            }

            if (empty($lead->email) || !filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
                error_log("EmailService: Email cliente non valida per lead #{$lead->id}, skip email di cortesia");
                return false;
            }

            $subject = $confirmationSettings['subject'] ?? 'Abbiamo ricevuto la tua richiesta';
            $bodyTemplate = $confirmationSettings['body'] ?? "Ciao {name},\n\ngrazie per averci contattato. Abbiamo ricevuto la tua richiesta e ti risponderemo al più presto.\n\nA presto!";
            $fromName = !empty($confirmationSettings['from_name']) ? $confirmationSettings['from_name'] : $this->config['from_name'];
            $fromAddress = !empty($confirmationSettings['from_address']) ? $confirmationSettings['from_address'] : $this->config['from_address'];
            $headerColor = $confirmationSettings['header_color'] ?? '#667eea';
            $headerColorEnd = $confirmationSettings['header_color_end'] ?? '#764ba2';

            // Sostituisci placeholder
            $bodyText = str_replace('{name}', $lead->name ?? 'Cliente', $bodyTemplate);

            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            $this->mailer->setFrom($fromAddress, $fromName);
            $this->mailer->addAddress($lead->email, $lead->name ?? '');

            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getConfirmationEmailTemplate($lead, $page, $bodyText, $headerColor, $headerColorEnd);
            $this->mailer->AltBody = $bodyText;

            $result = $this->mailer->send();

            if ($result) {
                error_log("EmailService: Email cortesia inviata a {$lead->email} per lead #{$lead->id}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("EmailService: Errore email cortesia lead #{$lead->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template HTML per email di cortesia al cliente
     */
    private function getConfirmationEmailTemplate($lead, $page, $bodyText, $headerColor = '#667eea', $headerColorEnd = '#764ba2')
    {
        $pageTitle = htmlspecialchars($page->title);
        $bodyHtml = nl2br(htmlspecialchars($bodyText));
        $headerColor = htmlspecialchars($headerColor);
        $headerColorEnd = htmlspecialchars($headerColorEnd);

        return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma ricezione richiesta</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, {$headerColor} 0%, {$headerColorEnd} 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
        .content { padding: 30px 20px; font-size: 15px; color: #374151; }
        .footer { background: #f9fafb; padding: 16px 20px; text-align: center; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✉️ {$pageTitle}</h1>
        </div>
        <div class="content">
            <p>{$bodyHtml}</p>
        </div>
        <div class="footer">
            <p>Questa email è stata inviata automaticamente. Si prega di non rispondere a questa email.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Determina lista destinatari email
     *
     * @param object $page
     * @param array $notificationSettings
     * @return array Lista email destinatari
     */
    private function getRecipients($page, $notificationSettings)
    {
        $recipients = [];

        // 1. Email owner della pagina (sempre incluso se presente)
        if (!empty($page->user->email)) {
            $recipients[] = $page->user->email;
        }

        // 2. Email aggiuntive custom (se configurate)
        if (!empty($notificationSettings['additional_emails'])) {
            $additionalEmails = array_map('trim', explode(',', $notificationSettings['additional_emails']));
            foreach ($additionalEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $email;
                }
            }
        }

        // Rimuovi duplicati
        return array_unique($recipients);
    }

    /**
     * Template HTML per email notifica lead
     *
     * @param object $lead
     * @param object $page
     * @return string HTML email
     */
    private function getLeadEmailTemplate($lead, $page)
    {
        $pageTitle = htmlspecialchars($page->title);
        $leadName = htmlspecialchars($lead->name ?? 'Non specificato');
        $leadEmail = htmlspecialchars($lead->email ?? 'Non specificata');
        $leadPhone = htmlspecialchars($lead->phone ?? 'Non specificato');
        $leadMessage = nl2br(htmlspecialchars($lead->message ?? 'Nessun messaggio'));
        $leadDate = date('d/m/Y H:i', strtotime($lead->created_at));

        return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo contatto ricevuto</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 20px;
        }
        .badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 8px 0;
        }
        .info-box strong {
            color: #667eea;
            display: inline-block;
            min-width: 100px;
        }
        .message-box {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .message-box h3 {
            margin: 0 0 10px 0;
            color: #f59e0b;
            font-size: 14px;
            font-weight: 600;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Nuovo Contatto Ricevuto</h1>
        </div>
        <div class="content">
            <span class="badge">Nuovo Lead</span>
            <p>Hai ricevuto un nuovo contatto dalla landing page <strong>{$pageTitle}</strong>.</p>

            <div class="info-box">
                <p><strong>Nome:</strong> {$leadName}</p>
                <p><strong>Email:</strong> <a href="mailto:{$leadEmail}">{$leadEmail}</a></p>
                <p><strong>Telefono:</strong> {$leadPhone}</p>
                <p><strong>Data:</strong> {$leadDate}</p>
            </div>

            <div class="message-box">
                <h3>💬 Messaggio</h3>
                <p>{$leadMessage}</p>
            </div>
        </div>
        <div class="footer">
            <p>Questa email è stata inviata automaticamente da <strong>Landing Page Builder</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Versione plain text dell'email
     *
     * @param object $lead
     * @param object $page
     * @return string Plain text email
     */
    private function getLeadEmailPlainText($lead, $page)
    {
        $pageTitle = $page->title;
        $leadDate = date('d/m/Y H:i', strtotime($lead->created_at));

        return <<<TEXT
NUOVO CONTATTO RICEVUTO

Hai ricevuto un nuovo contatto dalla landing page: {$pageTitle}

DATI CONTATTO:
Nome: {$lead->name}
Email: {$lead->email}
Telefono: {$lead->phone}
Data: {$leadDate}

MESSAGGIO:
{$lead->message}

---
Questa email è stata inviata automaticamente da Landing Page Builder.
TEXT;
    }
}
