<?php
declare(strict_types=1);

namespace Auth;

use Database\DB;

class Auth {
    private const SESSION_KEY = 'admin_user';
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
    }

    public function login(string $username, string $password): bool {
        // Try DB first
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            $row = $stmt->fetch();
            if ($row && password_verify($password, (string)$row['password_hash'])) {
                $_SESSION[self::SESSION_KEY] = $username;
                if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->info('Admin login success (db)', ['user' => $username]); }
                return true;
            }
        } catch (\Throwable $e) {
            // Fallback to file
        }

        $users = $this->loadUsers();
        if (isset($users[$username]) && password_verify($password, $users[$username])) {
            $_SESSION[self::SESSION_KEY] = $username;
            if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->info('Admin login success (file)', ['user' => $username]); }
            return true;
        }
        if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->warning('Admin login failed', ['user' => $username]); }
        return false;
    }

    public function logout(): void {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function check(): bool {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public function user(): ?string {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    private function loadUsers(): array {
        $raw = @file_get_contents($this->usersFile);
        $data = $raw ? json_decode($raw, true) : [];
        return is_array($data) ? $data : [];
    }
}


