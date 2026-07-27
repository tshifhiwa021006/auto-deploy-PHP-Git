<?php

namespace AutoDeployPHP;

class Logger
{
    private string $logDir;
    private string $logFile;
    private string $level;

    public function __construct(string $logDir, string $level = 'info')
    {
        $this->logDir = $logDir;
        $this->level = $level;
        $this->logFile = $logDir . '/deploy-' . date('Y-m-d') . '.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log debug message
     */
    public function debug(string $message): void
    {
        $this->log('DEBUG', $message);
    }

    /**
     * Log info message
     */
    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    /**
     * Log warning message
     */
    public function warning(string $message): void
    {
        $this->log('WARNING', $message);
    }

    /**
     * Log error message
     */
    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }

    /**
     * Log message to file
     */
    private function log(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);

        // Also log to latest.log symlink
        $latestLog = $this->logDir . '/latest.log';
        file_put_contents($latestLog, $logMessage, FILE_APPEND);
    }

    /**
     * Get log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }
}
