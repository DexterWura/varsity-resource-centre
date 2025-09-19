<?php
declare(strict_types=1);

// Start session for CSRF tokens
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default timezone
date_default_timezone_set('Africa/Harare');

// Basic security headers (only if headers not already sent)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Simple PSR-4-like autoloader for src/
spl_autoload_register(function (string $class): void {
    $prefix = '';
    $baseDir = __DIR__ . '/src/';
    $relativeClass = ltrim($class, '\\');
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// CSRF helper
namespace Security {
    class Csrf {
        public static function issueToken(): string {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }

        public static function validate(?string $token): bool {
            return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
        }
    }
}


