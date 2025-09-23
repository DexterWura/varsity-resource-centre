<?php
declare(strict_types=1);

// Start session for CSRF tokens
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default timezone
date_default_timezone_set('Africa/Harare');

// Enhanced security headers (only if headers not already sent)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    // Additional security for admin pages
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') === 0) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; img-src \'self\' data:; font-src \'self\' cdn.jsdelivr.net;');
    }
}

// Simple PSR-4-like autoloader for src/
spl_autoload_register(function (string $class): void {
    $baseDir = __DIR__ . '/src/';
    $relativeClass = ltrim($class, '\\');
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Global logger and error handling
try {
    $logPath = __DIR__ . '/storage/logs/app.log';
    $GLOBALS['app_logger'] = new \Logging\Logger($logPath);
} catch (\Throwable $e) {
    // ignore logger init failure
}

set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity)) { return false; }
    if (isset($GLOBALS['app_logger'])) {
        $GLOBALS['app_logger']->error('PHP Error', ['severity' => $severity, 'message' => $message, 'file' => $file, 'line' => $line]);
    }
    http_response_code(500);
    include __DIR__ . '/errors/500.php';
    return true;
});

set_exception_handler(function (\Throwable $e): void {
    if (isset($GLOBALS['app_logger'])) {
        $GLOBALS['app_logger']->error('Uncaught Exception', ['type' => get_class($e), 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    }
    http_response_code(500);
    include __DIR__ . '/errors/500.php';
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (isset($GLOBALS['app_logger'])) {
            $GLOBALS['app_logger']->error('Fatal Error', $error);
        }
        http_response_code(500);
        include __DIR__ . '/errors/500.php';
    }
});

// Installer guard: if not installed, redirect to /install
try {
    $appConfigFile = __DIR__ . '/storage/app.php';
    $isInstaller = (strpos($_SERVER['REQUEST_URI'] ?? '', '/install') === 0);
    if (!$isInstaller) {
        if (!is_file($appConfigFile)) {
            header('Location: /install/');
            exit;
        }
        $config = include $appConfigFile;
        if (!is_array($config) || empty($config['installed'])) {
            header('Location: /install/');
            exit;
        }
        // Make DB credentials available as env
        if (!empty($config['db'])) {
            putenv('DB_HOST=' . ($config['db']['host'] ?? ''));
            putenv('DB_NAME=' . ($config['db']['name'] ?? ''));
            putenv('DB_USER=' . ($config['db']['user'] ?? ''));
            putenv('DB_PASS=' . ($config['db']['pass'] ?? ''));
        }
    }
} catch (\Throwable $e) {
    // ignore
}

