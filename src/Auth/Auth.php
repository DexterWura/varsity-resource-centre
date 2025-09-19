<?php
declare(strict_types=1);

namespace Auth;

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
        $users = $this->loadUsers();
        if (isset($users[$username]) && password_verify($password, $users[$username])) {
            $_SESSION[self::SESSION_KEY] = $username;
            if (isset($GLOBALS['app_logger'])) { $GLOBALS['app_logger']->info('Admin login success', ['user' => $username]); }
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


