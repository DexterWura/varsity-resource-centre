<?php
declare(strict_types=1);

namespace Auth;

use Database\DB;

class Auth {
    private const SESSION_KEY = 'admin_user';
    private const SESSION_TIMEOUT = 1800; // 30 minutes
    private const LAST_ACTIVITY_KEY = 'admin_last_activity';
    private string $usersFile;

    public function __construct(string $usersFile) {
        $this->usersFile = $usersFile;
        $dir = dirname($this->usersFile);
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        if (!is_file($this->usersFile)) {
            $default = [
                'superadmin' => password_hash('ChangeMe123!', PASSWORD_DEFAULT),
            ];
            @file_put_contents($this->usersFile, json_encode($default, JSON_PRETTY_PRINT));
        }
        
        // Configure secure session settings
        $this->configureSecureSession();
    }
    
    private function configureSecureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_lifetime', '0'); // Session cookie (not persistent)
            ini_set('session.gc_maxlifetime', (string)self::SESSION_TIMEOUT);
            
            session_start();
            
            // Regenerate session ID on first access for security
            if (!isset($_SESSION['_admin_session_initialized'])) {
                session_regenerate_id(true);
                $_SESSION['_admin_session_initialized'] = true;
            }
        }
    }

    public function login(string $username, string $password): bool {
        // Try DB first
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            $row = $stmt->fetch();
            if ($row && password_verify($password, (string)$row['password_hash'])) {
                $this->setAuthenticatedSession($username);
                if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->info('Admin login success (db)', ['user' => $username]); }
                return true;
            }
        } catch (\Throwable $e) {
            // Fallback to file
        }

        $users = $this->loadUsers();
        if (isset($users[$username]) && password_verify($password, $users[$username])) {
            $this->setAuthenticatedSession($username);
            if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->info('Admin login success (file)', ['user' => $username]); }
            return true;
        }
        if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->warning('Admin login failed', ['user' => $username]); }
        return false;
    }
    
    private function setAuthenticatedSession(string $username): void {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION[self::SESSION_KEY] = $username;
        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
        $_SESSION['_admin_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function logout(): void {
        // Log the logout
        if (isset($GLOBALS['app_logger']) && isset($_SESSION[self::SESSION_KEY])) {
            $GLOBALS['app_logger']->info('Admin logout', ['user' => $_SESSION[self::SESSION_KEY]]);
        }
        
        // Destroy all session data
        $_SESSION = [];
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Start a new session to prevent session fixation
        session_start();
        session_regenerate_id(true);
    }

    public function check(): bool {
        // Check if user is logged in
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        
        // Check session timeout
        if (!$this->isSessionValid()) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
        
        return true;
    }
    
    private function isSessionValid(): bool {
        // Check if last activity is within timeout
        if (!isset($_SESSION[self::LAST_ACTIVITY_KEY])) {
            return false;
        }
        
        $lastActivity = (int)$_SESSION[self::LAST_ACTIVITY_KEY];
        if ((time() - $lastActivity) > self::SESSION_TIMEOUT) {
            return false;
        }
        
        // Check IP address (optional - can be disabled for users behind proxies)
        if (isset($_SESSION['_admin_ip']) && $_SESSION['_admin_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            // IP changed - could be session hijacking
            if (isset($GLOBALS['app_logger'])) {
                $GLOBALS['app_logger']->warning('Admin session IP mismatch', [
                    'expected' => $_SESSION['_admin_ip'],
                    'actual' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user' => $_SESSION[self::SESSION_KEY] ?? 'unknown'
                ]);
            }
            // For now, we'll allow IP changes (users behind proxies, mobile networks)
            // return false;
        }
        
        return true;
    }

    public function user(): ?string {
        return $this->check() ? ($_SESSION[self::SESSION_KEY] ?? null) : null;
    }
    
    public function getSessionInfo(): array {
        if (!$this->check()) {
            return [];
        }
        
        return [
            'username' => $_SESSION[self::SESSION_KEY] ?? null,
            'last_activity' => $_SESSION[self::LAST_ACTIVITY_KEY] ?? null,
            'timeout_in' => self::SESSION_TIMEOUT - (time() - ($_SESSION[self::LAST_ACTIVITY_KEY] ?? 0)),
            'ip' => $_SESSION['_admin_ip'] ?? null
        ];
    }

    private function loadUsers(): array {
        $raw = @file_get_contents($this->usersFile);
        $data = $raw ? json_decode($raw, true) : [];
        return is_array($data) ? $data : [];
    }
}


