<?php
declare(strict_types=1);

namespace Logging;

class Logger {
    private string $logFilePath;

    public function __construct(string $logFilePath) {
        $this->logFilePath = $logFilePath;
        $dir = dirname($this->logFilePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public function info(string $message, array $context = []): void {
        $this->writeLog('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->writeLog('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->writeLog('ERROR', $message, $context);
    }

    private function writeLog(string $level, string $message, array $context): void {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $line = sprintf('[%s] [%s] [%s %s] [%s] %s', $timestamp, $level, $method, $uri, $ip, $message);
        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $line .= PHP_EOL;
        @file_put_contents($this->logFilePath, $line, FILE_APPEND | LOCK_EX);
    }
}


