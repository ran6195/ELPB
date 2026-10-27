<?php

namespace App\Audit;

/**
 * Script di backend/scripts (attività pianificate di Plesk o lanciati a mano):
 * registra l'esecuzione (categoria system, canale console) con argomenti, durata ed esito.
 * Le modifiche ai modelli fatte dallo script prendono il canale console e il nome dello script.
 *
 * Uso, dopo config/database.php:  ScriptAudit::start(basename(__FILE__, '.php'), $argv);
 *
 * La voce si scrive alla chiusura dello script (anche dopo exit()); il codice di uscita
 * non è leggibile da PHP a quel punto, l'esito è "error" solo per gli errori fatali.
 */
class ScriptAudit
{
    public static function start(string $name, array $argv = []): void
    {
        $start = microtime(true);
        $auditor = Auditor::get();
        $auditor->enterSource('console', $name);

        register_shutdown_function(function () use ($auditor, $name, $argv, $start) {
            $error = error_get_last();
            $fatal = $error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);

            $properties = ['args' => Auditor::clean(array_values(array_slice($argv, 1)))];
            if ($fatal) {
                $properties['error'] = mb_substr($error['message'], 0, 300);
            }

            $auditor->record(Auditor::SYSTEM, 'script', [
                'user' => null,
                'subject_label' => $name,
                'outcome' => $fatal ? 'error' : 'ok',
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'properties' => $properties,
                'at' => $start,
            ]);
            $auditor->leaveSource();
            $auditor->flush();
        });
    }
}
