<?php

namespace App\Utils;

/**
 * Normalizzazione dei domini su cui sono pubblicate le landing page.
 * Unico punto in cui si decide quando due hostname sono "lo stesso sito".
 */
class Hostname
{
    /**
     * Restituisce l'hostname normalizzato o null se il valore non è un dominio valido.
     *
     * - accetta anche URL completi ("https://www.Cliente.it:443/lp/" → "cliente.it")
     * - minuscolo, senza porta né punto finale
     * - "www." rimosso: www.cliente.it e cliente.it sono lo stesso sito
     *   (gli altri sottodomini restano distinti)
     * - domini con caratteri accentati convertiti in punycode, se c'è l'estensione intl
     * - rifiuta IP e nomi senza punto (localhost)
     */
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Senza schema parse_url interpreta "cliente.it/lp" come path
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            $value = 'http://' . $value;
        }
        $host = parse_url($value, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        $host = rtrim(mb_strtolower($host, 'UTF-8'), '.');

        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
            return null;
        }

        if (preg_match('/[^\x20-\x7e]/', $host) && function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii === false) {
                return null;
            }
            $host = $ascii;
        }

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if (strlen($host) > 253 || !str_contains($host, '.')
            || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return null;
        }

        return $host;
    }

    /** Spiegazione per l'utente del perché il valore non è un dominio valido */
    public static function invalidReason(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'Inserisci un dominio (esempio: cliente.it)';
        }
        $shown = '"' . mb_substr($value, 0, 60) . '"';
        $host = parse_url(preg_match('#://#', $value) ? $value : 'http://' . $value, PHP_URL_HOST) ?: $value;
        if (preg_match('/\s/u', $value)) {
            $reason = 'contiene spazi';
        } elseif (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
            $reason = 'è un indirizzo IP, serve il nome del dominio';
        } elseif (!str_contains($host, '.')) {
            $reason = 'manca l\'estensione (esempio: .it, .com)';
        } elseif (str_contains($host, '_')) {
            $reason = 'il trattino basso "_" non è ammesso nei domini (usa "-")';
        } else {
            $reason = 'contiene caratteri non ammessi';
        }
        return "{$shown} non è un dominio valido: {$reason}. Esempio: cliente.it";
    }

    /** Versione leggibile di un hostname normalizzato (punycode → caratteri accentati) */
    public static function display(?string $host): string
    {
        $host = (string) $host;
        if (str_contains($host, 'xn--') && function_exists('idn_to_utf8')) {
            $utf8 = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($utf8 !== false) {
                return $utf8;
            }
        }
        return $host;
    }

    /**
     * Host dell'applicazione (anteprima /p/:slug): quello di APP_URL, più localhost
     */
    public static function isAppHost(?string $value): bool
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return false;
        }
        $rawHost = parse_url(preg_match('#://#', $raw) ? $raw : 'http://' . $raw, PHP_URL_HOST);
        if (in_array($rawHost, ['localhost', '127.0.0.1', '::1', '[::1]'], true)) {
            return true;
        }

        $host = self::normalize($raw);
        return $host !== null && !empty($_ENV['APP_URL']) && self::normalize($_ENV['APP_URL']) === $host;
    }
}
