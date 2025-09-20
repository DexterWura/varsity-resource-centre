<?php
declare(strict_types=1);

namespace Auth;

class UserAuth
{
    private const SESSION_KEY = 'user_id';
    private ?User $user = null;

    public function __construct()
    {
        $this->startSession();
        $this->loadUser();
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function loadUser(): void
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            $this->user = User::findById((int)$_SESSION[self::SESSION_KEY]);
            if (!$this->user) {
                $this->logout();
            }
        }
    }

    public function login(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user && $user->verifyPassword($password) && $user->isActive()) {
            $_SESSION[self::SESSION_KEY] = $user->getId();
            $this->user = $user;
            return true;
        }
        return false;
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        $this->user = null;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function requireAuth(): void
    {
        if (!$this->check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requireRole(string $role): void
    {
        $this->requireAuth();
        if (!$this->user->hasRole($role)) {
            header('Location: /dashboard.php?error=insufficient_permissions');
            exit;
        }
    }

    public function requirePermission(string $permission): void
    {
        $this->requireAuth();
        if (!$this->user->hasPermission($permission)) {
            header('Location: /dashboard.php?error=insufficient_permissions');
            exit;
        }
    }

    public function redirectIfAuthenticated(): void
    {
        if ($this->check()) {
            header('Location: /dashboard.php');
            exit;
        }
    }
}
