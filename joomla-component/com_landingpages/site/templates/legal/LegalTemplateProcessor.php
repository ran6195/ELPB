<?php
/**
 * LegalTemplateProcessor
 *
 * Helper class per processare i template delle pagine legali
 * (Privacy Policy, Condizioni d'uso, Cookie Policy)
 *
 * Sostituisce le variabili nel template con i dati forniti e
 * gestisce l'escape HTML per sicurezza.
 *
 * @version 1.0.0
 * @since 2026-02-01
 */

class LegalTemplateProcessor {

    /**
     * Tipi di pagina legale supportati
     */
    private const ALLOWED_TYPES = ['privacy', 'condizioni', 'cookies'];

    /**
     * Mapping tra tipo e nome file template
     */
    private const TEMPLATE_FILES = [
        'privacy' => 'privacy.php',
        'condizioni' => 'condizioni.php',
        'cookies' => 'cookies.php'
    ];

    /**
     * Directory dei template (relativa a questo file)
     */
    private string $templateDir;

    /**
     * Costruttore
     *
     * @param string|null $templateDir Directory dei template (default: stessa directory del file)
     */
    public function __construct(?string $templateDir = null) {
        $this->templateDir = $templateDir ?? __DIR__;
    }

    /**
     * Renderizza un template legale con i dati forniti
     *
     * @param string $type Tipo di pagina (privacy|condizioni|cookies)
     * @param array $legalInfo Dati da sostituire nel template
     * @return string HTML renderizzato
     * @throws InvalidArgumentException Se il tipo non è valido o il template non esiste
     */
    public function render(string $type, array $legalInfo): string {
        // Validazione tipo
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(
                "Tipo pagina non valido: '$type'. Valori ammessi: " . implode(', ', self::ALLOWED_TYPES)
            );
        }

        // Recupera percorso template
        $templateFile = $this->getTemplatePath($type);

        if (!file_exists($templateFile)) {
            throw new InvalidArgumentException("Template non trovato: $templateFile");
        }

        // Carica il contenuto del template
        $templateContent = file_get_contents($templateFile);

        if ($templateContent === false) {
            throw new RuntimeException("Impossibile leggere il template: $templateFile");
        }

        // Rimuove il codice PHP legacy (session_start, include)
        $templateContent = $this->removeLegacyCode($templateContent);

        // Sostituisce le variabili con i dati reali
        $processedContent = $this->replaceVariables($templateContent, $legalInfo);

        return $processedContent;
    }

    /**
     * Ottiene il percorso completo del template
     *
     * @param string $type Tipo di pagina
     * @return string Percorso assoluto del template
     */
    private function getTemplatePath(string $type): string {
        $filename = self::TEMPLATE_FILES[$type];
        return rtrim($this->templateDir, '/') . '/' . $filename;
    }

    /**
     * Rimuove il codice PHP legacy dai template
     *
     * @param string $content Contenuto del template
     * @return string Contenuto pulito
     */
    private function removeLegacyCode(string $content): string {
        // Rimuove le prime righe con session_start e include
        $lines = explode("\n", $content);
        $cleanedLines = [];
        $skipUntilClosingTag = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Salta le righe con session_start, include, e il tag PHP iniziale
            if (str_contains($trimmed, 'session_start') ||
                str_contains($trimmed, 'include') ||
                $trimmed === '<?php') {
                $skipUntilClosingTag = true;
                continue;
            }

            // Salta il tag di chiusura PHP
            if ($skipUntilClosingTag && $trimmed === '?>') {
                $skipUntilClosingTag = false;
                continue;
            }

            // Aggiunge la riga se non siamo in modalità skip
            if (!$skipUntilClosingTag) {
                $cleanedLines[] = $line;
            }
        }

        return implode("\n", $cleanedLines);
    }

    /**
     * Sostituisce le variabili $_SESSION nel template con i dati reali
     *
     * @param string $content Contenuto del template
     * @param array $legalInfo Dati da sostituire
     * @return string Contenuto con variabili sostituite
     */
    private function replaceVariables(string $content, array $legalInfo): string {
        // Mapping tra variabili SESSION e campi legal_info
        $replacements = [
            '<?php echo $_SESSION["nome"]; ?>' => $this->escape($legalInfo['ragioneSociale'] ?? ''),
            '<?php echo $_SESSION["sito"]; ?>' => $this->escape($legalInfo['sitoWeb'] ?? ''),
            '<?php echo $_SESSION["indirizzo"]; ?>' => $this->escape($legalInfo['indirizzoCompleto'] ?? ''),
            '<?php echo $_SESSION["pemail"]; ?>' => $this->escape($legalInfo['emailContatto'] ?? ''),
            '<?php echo $_SESSION["gestore"]; ?>' => $this->escape($legalInfo['gestoreServer'] ?? 'il gestore del server'),
        ];

        // Sostituisce tutte le occorrenze
        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        // Gestisce anche le varianti con spazi o quote singole
        $content = $this->replaceVariantsPlaceholders($content, $legalInfo);

        return $content;
    }

    /**
     * Gestisce varianti dei placeholder (con spazi, quote singole, ecc.)
     *
     * @param string $content Contenuto
     * @param array $legalInfo Dati
     * @return string Contenuto processato
     */
    private function replaceVariantsPlaceholders(string $content, array $legalInfo): string {
        // Pattern regex per catturare varianti
        $patterns = [
            '/\<\?php\s+echo\s+\$_SESSION\["nome"\]\s*;\s*\?\>/i' => $this->escape($legalInfo['ragioneSociale'] ?? ''),
            '/\<\?php\s+echo\s+\$_SESSION\["sito"\]\s*;\s*\?\>/i' => $this->escape($legalInfo['sitoWeb'] ?? ''),
            '/\<\?php\s+echo\s+\$_SESSION\["indirizzo"\]\s*;\s*\?\>/i' => $this->escape($legalInfo['indirizzoCompleto'] ?? ''),
            '/\<\?php\s+echo\s+\$_SESSION\["pemail"\]\s*;\s*\?\>/i' => $this->escape($legalInfo['emailContatto'] ?? ''),
            '/\<\?php\s+echo\s+\$_SESSION\["gestore"\]\s*;\s*\?\>/i' => $this->escape($legalInfo['gestoreServer'] ?? 'il gestore del server'),
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * Escape HTML per sicurezza (previene XSS)
     *
     * @param string $value Valore da escapare
     * @return string Valore escapato
     */
    private function escape(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Valida che tutti i campi obbligatori siano presenti
     *
     * @param array $legalInfo Dati da validare
     * @return bool True se valido
     * @throws InvalidArgumentException Se mancano campi obbligatori
     */
    public function validate(array $legalInfo): bool {
        $requiredFields = [
            'ragioneSociale',
            'sitoWeb',
            'indirizzoCompleto',
            'emailContatto'
        ];

        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($legalInfo[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new InvalidArgumentException(
                "Campi obbligatori mancanti: " . implode(', ', $missingFields)
            );
        }

        return true;
    }

    /**
     * Helper statico per uso rapido
     *
     * @param string $type Tipo di pagina
     * @param array $legalInfo Dati
     * @return string HTML renderizzato
     */
    public static function renderTemplate(string $type, array $legalInfo): string {
        $processor = new self();
        $processor->validate($legalInfo);
        return $processor->render($type, $legalInfo);
    }
}
