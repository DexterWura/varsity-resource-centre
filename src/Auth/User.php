<?php
declare(strict_types=1);

namespace Auth;

use Database\DB;

class User
{
    private array $user;
    private array $roles = [];
    private array $permissions = [];

    public function __construct(array $user = null)
    {
        if ($user) {
            $this->user = $user;
            $this->loadRoles();
        }
    }

    public static function findById(int $id): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            return $user ? new self($user) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function findByEmail(string $email): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            return $user ? new self($user) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function create(array $data): ?self
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                INSERT INTO users (email, full_name, password_hash) 
                VALUES (?, ?, ?)
            ');
            $stmt->execute([
                $data['email'],
                $data['full_name'],
                password_hash($data['password'], PASSWORD_DEFAULT)
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Assign default 'user' role
            $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
            $stmt->execute(['user']);
            $userRole = $stmt->fetch();
            
            if ($userRole) {
                $stmt = $pdo->prepare('
                    INSERT INTO user_roles (user_id, role_id) 
                    VALUES (?, ?)
                ');
                $stmt->execute([$userId, $userRole['id']]);
            }
            
            return self::findById((int)$userId);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->user['password_hash']);
    }

    public function getId(): int
    {
        return (int)$this->user['id'];
    }

    public function getEmail(): string
    {
        return $this->user['email'];
    }

    public function getFullName(): string
    {
        return $this->user['full_name'];
    }

    public function isActive(): bool
    {
        return (bool)$this->user['is_active'];
    }

    public function isEmailVerified(): bool
    {
        return (bool)$this->user['email_verified'];
    }

    private function loadRoles(): void
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                SELECT r.name, r.permissions
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = ?
            ');
            $stmt->execute([$this->getId()]);
            $this->roles = $stmt->fetchAll();
            
            // Build permissions array
            $this->permissions = [];
            foreach ($this->roles as $role) {
                $permissions = json_decode($role['permissions'], true) ?? [];
                $this->permissions = array_merge($this->permissions, $permissions);
            }
        } catch (\Throwable $e) {
            $this->roles = [];
            $this->permissions = [];
        }
    }

    public function hasRole(string $roleName): bool
    {
        foreach ($this->roles as $role) {
            if ($role['name'] === $roleName) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(string $permission): bool
    {
        return isset($this->permissions[$permission]) && $this->permissions[$permission] === true;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function requestRole(int $roleId): bool
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                INSERT INTO user_role_assignments (user_id, role_id, status) 
                VALUES (?, ?, "pending")
                ON DUPLICATE KEY UPDATE status = "pending", requested_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$this->getId(), $roleId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'full_name' => $this->getFullName(),
            'is_active' => $this->isActive(),
            'email_verified' => $this->isEmailVerified(),
            'roles' => $this->getRoles(),
            'permissions' => $this->getPermissions()
        ];
    }

    /**
     * Check if user has Pro access (paid subscription)
     * For now, this always returns false to redirect to payment page
     * In the future, this will check actual subscription status
     */
    public function hasProAccess(): bool
    {
        // TODO: Implement actual subscription checking
        // For now, always return false to show payment page
        return false;
        
        // Future implementation will check:
        // - Active subscription in database
        // - Subscription expiry date
        // - Payment status
        // - Plan type (monthly/yearly)
    }
}
