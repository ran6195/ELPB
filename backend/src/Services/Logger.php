<?php

namespace App\Services;

/**
 * Logger su file a canali: ogni canale scrive su un file separato
 * (es. canale "upload" -> upload-YYYY-MM-DD.log) con livello configurabile
 * tramite la variabile d'ambiente indicata in $levelEnvVar.
 */
class Logger
{
    protected const LEVELS = [
        'off'     => 0,
        'error'   => 1,
        'warning' => 2,
        'info'    => 3,
        'debug'   => 4,
    ];

    protected const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    protected int $level;
    protected string $logDir;
    protected string $channel;

    public function __construct(string $channel = 'app', string $levelEnvVar = 'LOG_LEVEL')
    {
        $this->channel = $channel;

        $levelName   = strtolower(trim($_ENV[$levelEnvVar] ?? 'error'));
        $this->level = self::LEVELS[$levelName] ?? self::LEVELS['error'];

        $logPath = $_ENV['MAIL_LOG_PATH'] ?? 'storage/logs';
        if (!str_starts_with($logPath, '/')) {
            // Resolve relative to backend root: src/Services -> src -> backend
            $backendRoot  = dirname(__DIR__, 2);
            $this->logDir = $backendRoot . '/' . $logPath;
        } else {
            $this->logDir = $logPath;
        }
    }

    public function error(string $event, array $context = []): void
    {
        $this->write('error', $event, $context);
    }

    public function warning(string $event, array $context = []): void
    {
        $this->write('warning', $event, $context);
    }

    public function info(string $event, array $context = []): void
    {
        $this->write('info', $event, $context);
    }

    public function debug(string $event, array $context = []): void
    {
        $this->write('debug', $event, $context);
    }

    public function isDebug(): bool
    {
        return $this->level >= self::LEVELS['debug'];
    }

    protected function write(string $level, string $event, array $context): void
    {
        if ($this->level === self::LEVELS['off']) {
            return;
        }
        if ((self::LEVELS[$level] ?? 0) > $this->level) {
            return;
        }

        $this->ensureLogDir();

        $file = $this->logDir . '/' . $this->channel . '-' . date('Y-m-d') . '.log';
        $this->rotateIfNeeded($file);

        $ms        = (int) (microtime(true) * 1000) % 1000;
        $timestamp = date('Y-m-d H:i:s') . '.' . sprintf('%03d', $ms);
        $levelPad  = str_pad(strtoupper($level), 7);
        $eventPad  = str_pad($event, 28);

        $parts = ["[{$timestamp}] [{$levelPad}] {$eventPad}"];
        foreach ($context as $key => $value) {
            $encoded = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
            $parts[] = "{$key}={$encoded}";
        }

        $line = implode('  ', $parts) . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    protected function ensureLogDir(): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }

        // Block direct web access on first creation
        $htaccess = $this->logDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }
    }

    protected function rotateIfNeeded(string $file): void
    {
        if (file_exists($file) && filesize($file) > self::MAX_FILE_SIZE) {
            rename($file, $file . '.1');
        }
    }
}
