<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailService
{
    private PHPMailer $mailer;
    private array $config;
    private EmailLogger $logger;

    public function __construct()
    {
        $this->logger = new EmailLogger();

        $this->config = [
            'host'         => $_ENV['MAIL_HOST'] ?? '',
            'port'         => $_ENV['MAIL_PORT'] ?? 587,
            'username'     => $_ENV['MAIL_USERNAME'] ?? '',
            'password'     => $_ENV['MAIL_PASSWORD'] ?? '',
            'encryption'   => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
            'from_name'    => $_ENV['MAIL_FROM_NAME'] ?? 'Landing Page Builder',
            'timeout'      => $_ENV['MAIL_TIMEOUT'] ?? 10,
        ];

        $this->initializeMailer();
    }

    private function initializeMailer(): void
    {
        $this->mailer = new PHPMailer(true);

        $this->mailer->isSMTP();
        $this->mailer->Host       = $this->config['host'];
        $this->mailer->Port       = (int) $this->config['port'];
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $this->config['username'];
        $this->mailer->Password   = $this->config['password'];
        $this->mailer->SMTPSecure = $this->config['encryption'];
        $this->mailer->Timeout    = (int) $this->config['timeout'];

        $this->mailer->SMTPKeepAlive = true;
        $this->mailer->SMTPAutoTLS   = true;

        $this->mailer->CharSet  = 'UTF-8';
        $this->mailer->Encoding = 'base64';

        $this->mailer->setFrom($this->config['from_address'], $this->config['from_name']);
        $this->mailer->XMailer  = ' ';
        $this->mailer->Priority = 3;

        if ($this->logger->isDebug()) {
            // Cattura dialogo SMTP completo nel log file (non su stdout)
            $this->mailer->SMTPDebug  = SMTP::DEBUG_SERVER;
            $this->mailer->Debugoutput = [$this->logger, 'smtpOutput'];
        } else {
            $this->mailer->SMTPDebug = 0;
        }
    }

    /**
     * Invia notifica email per nuovo lead
     */
    public function sendLeadNotification($lead, $page): bool
    {
        $startTime = microtime(true);

        try {
            $notificationSettings = $page->notification_settings ?? [];

            if (empty($notificationSettings['enabled'])) {
                $this->logger->info('lead_notification_skipped', [
                    'reason'  => 'notifications_disabled',
                    'page_id' => $page->id,
                    'lead_id' => $lead->id ?? null,
                ]);
                return false;
            }

            $recipients = $this->getRecipients($page, $notificationSettings);

            if (empty($recipients)) {
                $this->logger->error('lead_notification_failed', [
                    'reason'  => 'no_recipients_configured',
                    'page_id' => $page->id,
                    'lead_id' => $lead->id ?? null,
                ]);
                return false;
            }

            $this->logger->debug('smtp_config', [
                'host'       => $this->config['host'],
                'port'       => $this->config['port'],
                'encryption' => $this->config['encryption'],
                'username'   => $this->config['username'],
                'timeout'    => $this->config['timeout'] . 's',
            ]);

            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            $fromName    = !empty($notificationSettings['from_name'])    ? $notificationSettings['from_name']    : $this->config['from_name'];
            $fromAddress = !empty($notificationSettings['from_address']) ? $notificationSettings['from_address'] : $this->config['from_address'];
            $this->mailer->setFrom($fromAddress, $fromName);

            foreach ($recipients as $email) {
                $this->mailer->addAddress($email);
                $this->logger->debug('recipient_added', [
                    'type'    => 'lead_notification',
                    'email'   => $email,
                    'page_id' => $page->id,
                ]);
            }

            if (!empty($lead->email) && filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addReplyTo($lead->email, $lead->name ?? '');
            }

            $this->mailer->addCustomHeader('X-MSMail-Priority', 'Normal');
            $this->mailer->addCustomHeader('Importance', 'Normal');

            $domain    = parse_url($this->config['host'], PHP_URL_HOST) ?: $this->config['host'];
            $messageId = sprintf('<%s.%s@%s>', uniqid(), time(), $domain);
            $this->mailer->MessageID = $messageId;

            $subject = "Nuovo contatto dalla landing page: {$page->title}";
            $this->mailer->Subject = $subject;

            $this->logger->debug('email_preparing', [
                'type'       => 'lead_notification',
                'page_id'    => $page->id,
                'lead_id'    => $lead->id ?? null,
                'subject'    => $subject,
                'from'       => $fromAddress,
                'to_count'   => count($recipients),
                'message_id' => $messageId,
            ]);

            $this->mailer->isHTML(true);
            $this->mailer->Body    = $this->getLeadEmailTemplate($lead, $page);
            $this->mailer->AltBody = $this->getLeadEmailPlainText($lead, $page);

            $result   = $this->mailer->send();
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            if ($result) {
                $this->logger->info('lead_notification_sent', [
                    'page_id'  => $page->id,
                    'lead_id'  => $lead->id ?? null,
                    'to'       => implode(', ', EmailLogger::obfuscateEmails($recipients)),
                    'subject'  => $subject,
                    'duration' => $duration . 'ms',
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->error('lead_notification_failed', [
                'page_id'   => $page->id ?? null,
                'lead_id'   => $lead->id ?? null,
                'error'     => $e->getMessage(),
                'smtp_info' => $this->mailer->ErrorInfo ?? '',
                'duration'  => $duration . 'ms',
            ]);
            return false;
        }
    }

    /**
     * Invia email di cortesia al cliente che ha inviato il form
     */
    public function sendLeadConfirmation($lead, $page): bool
    {
        $startTime = microtime(true);

        try {
            $confirmationSettings = $page->notification_settings['confirmation_email'] ?? [];

            if (empty($confirmationSettings['enabled'])) {
                $this->logger->info('lead_confirmation_skipped', [
                    'reason'  => 'confirmation_disabled',
                    'page_id' => $page->id,
                    'lead_id' => $lead->id ?? null,
                ]);
                return false;
            }

            if (empty($lead->email) || !filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
                $this->logger->warning('lead_confirmation_skipped', [
                    'reason'  => 'invalid_lead_email',
                    'page_id' => $page->id,
                    'lead_id' => $lead->id ?? null,
                    'email'   => $lead->email ?? '(empty)',
                ]);
                return false;
            }

            $subject        = $confirmationSettings['subject']      ?? 'Abbiamo ricevuto la tua richiesta';
            $bodyTemplate   = $confirmationSettings['body']         ?? "Ciao {name},\n\ngrazie per averci contattato. Abbiamo ricevuto la tua richiesta e ti risponderemo al più presto.\n\nA presto!";
            $fromName       = !empty($confirmationSettings['from_name'])    ? $confirmationSettings['from_name']    : $this->config['from_name'];
            $fromAddress    = !empty($confirmationSettings['from_address']) ? $confirmationSettings['from_address'] : $this->config['from_address'];
            $headerColor    = $confirmationSettings['header_color']     ?? '#667eea';
            $headerColorEnd = $confirmationSettings['header_color_end'] ?? '#764ba2';

            $bodyText = str_replace('{name}', $lead->name ?? 'Cliente', $bodyTemplate);

            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            $this->mailer->setFrom($fromAddress, $fromName);
            $this->mailer->addAddress($lead->email, $lead->name ?? '');

            $this->logger->debug('email_preparing', [
                'type'    => 'lead_confirmation',
                'page_id' => $page->id,
                'lead_id' => $lead->id ?? null,
                'subject' => $subject,
                'from'    => $fromAddress,
                'to'      => $lead->email,
            ]);

            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body    = $this->getConfirmationEmailTemplate($lead, $page, $bodyText, $headerColor, $headerColorEnd);
            $this->mailer->AltBody = $bodyText;

            $result   = $this->mailer->send();
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            if ($result) {
                $this->logger->info('lead_confirmation_sent', [
                    'page_id'  => $page->id,
                    'lead_id'  => $lead->id ?? null,
                    'to'       => EmailLogger::obfuscateEmail($lead->email),
                    'subject'  => $subject,
                    'duration' => $duration . 'ms',
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->error('lead_confirmation_failed', [
                'page_id'   => $page->id ?? null,
                'lead_id'   => $lead->id ?? null,
                'error'     => $e->getMessage(),
                'smtp_info' => $this->mailer->ErrorInfo ?? '',
                'duration'  => $duration . 'ms',
            ]);
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
     */
    private function getRecipients($page, $notificationSettings): array
    {
        $recipients = [];

        $ownerEmail = !empty($notificationSettings['owner_email'])
            ? $notificationSettings['owner_email']
            : ($page->user->email ?? null);

        if ($ownerEmail) {
            $recipients[] = $ownerEmail;
        }

        if (!empty($notificationSettings['additional_emails'])) {
            $additionalEmails = array_map('trim', explode(',', $notificationSettings['additional_emails']));
            foreach ($additionalEmails as $email) {
                if ($email === '') {
                    continue;
                }
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $email;
                } else {
                    $this->logger->warning('invalid_additional_email_skipped', [
                        'email'   => $email,
                        'page_id' => $page->id,
                    ]);
                }
            }
        }

        return array_unique($recipients);
    }

    /**
     * Template HTML per email notifica lead
     */
    private function getLeadEmailTemplate($lead, $page)
    {
        $pageTitle = htmlspecialchars($page->title);
        $leadName = htmlspecialchars($lead->name ?? 'Non specificato');
        $leadEmail = htmlspecialchars($lead->email ?? 'Non specificata');
        $leadPhone = htmlspecialchars($lead->phone ?? 'Non specificato');
        $leadMessage = nl2br(htmlspecialchars($lead->message ?? 'Nessun messaggio'));
        $leadDate = date('d/m/Y H:i', strtotime($lead->created_at));

        $extraFieldsHtml = '';
        $systemKeys = ['privacy_accepted', 'page_slug', 'thank_you_url', 'recaptcha_token'];
        $metadata = $lead->metadata ?? [];
        $extraFields = array_filter($metadata, function ($key) use ($systemKeys) {
            return !in_array($key, $systemKeys) && strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($extraFields)) {
            $extraFieldsHtml = '<div class="info-box" style="margin-top:0">';
            foreach ($extraFields as $key => $value) {
                $label = htmlspecialchars(ucwords(str_replace('_', ' ', $key)));
                $val = nl2br(htmlspecialchars((string) ($value ?? '')));
                $extraFieldsHtml .= "<p><strong>{$label}:</strong> {$val}</p>";
            }
            $extraFieldsHtml .= '</div>';
        }

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

            {$extraFieldsHtml}

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
     */
    private function getLeadEmailPlainText($lead, $page)
    {
        $pageTitle = $page->title;
        $leadDate = date('d/m/Y H:i', strtotime($lead->created_at));

        $extraFieldsPlain = '';
        $systemKeys = ['privacy_accepted', 'page_slug', 'thank_you_url', 'recaptcha_token'];
        $metadata = $lead->metadata ?? [];
        $extraFields = array_filter($metadata, function ($key) use ($systemKeys) {
            return !in_array($key, $systemKeys) && strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($extraFields)) {
            $extraFieldsPlain = "\nALTRI CAMPI:\n";
            foreach ($extraFields as $key => $value) {
                $label = ucwords(str_replace('_', ' ', $key));
                $extraFieldsPlain .= "{$label}: " . ((string) ($value ?? '')) . "\n";
            }
        }

        return <<<TEXT
NUOVO CONTATTO RICEVUTO

Hai ricevuto un nuovo contatto dalla landing page: {$pageTitle}

DATI CONTATTO:
Nome: {$lead->name}
Email: {$lead->email}
Telefono: {$lead->phone}
Data: {$leadDate}
{$extraFieldsPlain}
MESSAGGIO:
{$lead->message}

---
Questa email è stata inviata automaticamente da Landing Page Builder.
TEXT;
    }
}
