<?php

namespace App\Services;

class EmailLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('email', 'MAIL_LOG_LEVEL');
    }

    /**
     * Callback for PHPMailer Debugoutput — captures SMTP dialogue.
     * Signature required by PHPMailer: callable(string $str, int $level)
     */
    public function smtpOutput(string $str, int $level): void
    {
        $str = trim($str);
        if ($str === '') {
            return;
        }
        $this->write('debug', 'smtp_dialog', ['output' => $str]);
    }

    /**
     * Obfuscate email address for info/warning logs.
     * mario@rossi.com -> m***@r***.com
     */
    public static function obfuscateEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return '***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $localObf    = substr($local, 0, 1) . '***';
        $domainParts = explode('.', $domain, 2);
        $domainObf   = substr($domainParts[0], 0, 1) . '***' . (isset($domainParts[1]) ? '.' . $domainParts[1] : '');
        return $localObf . '@' . $domainObf;
    }

    public static function obfuscateEmails(array $emails): array
    {
        return array_map([self::class, 'obfuscateEmail'], $emails);
    }
}
