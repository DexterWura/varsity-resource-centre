<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Auth\UserAuth;
use Database\DB;

$userAuth = new UserAuth();
$userAuth->requireAuth();

$user = $userAuth->user();
$message = '';
$error = '';

// Handle role request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_role'])) {
    $roleId = (int)($_POST['role_id'] ?? 0);
    
    if ($roleId > 0) {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('
                INSERT INTO user_role_assignments (user_id, role_id, status) 
                VALUES (?, ?, "pending")
                ON DUPLICATE KEY UPDATE status = "pending", requested_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$user->getId(), $roleId]);
            $message = 'Role request submitted successfully!';
        } catch (\Throwable $e) {
            $error = 'Failed to submit role request. Please try again.';
        }
    }
}

// Get user's current roles
$currentRoles = [];
try {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('
        SELECT ur.name, ur.description, ura.status, ura.requested_at, ura.reviewed_at, ura.notes
        FROM user_role_assignments ura
        JOIN user_roles ur ON ura.role_id = ur.id
        WHERE ura.user_id = ?
        ORDER BY ura.requested_at DESC
    ');
    $stmt->execute([$user->getId()]);
    $currentRoles = $stmt->fetchAll();
} catch (\Throwable $e) {
    $error = 'Failed to load role information.';
}

// Get available roles for request
$availableRoles = [];
try {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('
        SELECT ur.* FROM user_roles ur
        WHERE ur.name NOT IN ("user")
        AND ur.id NOT IN (
            SELECT role_id FROM user_role_assignments 
            WHERE user_id = ? AND status IN ("approved", "pending")
        )
    ');
    $stmt->execute([$user->getId()]);
    $availableRoles = $stmt->fetchAll();
} catch (\Throwable $e) {
    // Handle error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management - Varsity Resource Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .role-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .status-approved {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        .status-rejected {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            color: white;
        }
        .permission-tag {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin: 2px;
            display: inline-block;
        }
    
        :root {
            --dash-bg: #f8f9fb;
            --dash-card: #ffffff;
            --dash-text: #212529;
            --dash-muted: #6c757d;
            --dash-accent: #7367f0;
            --dash-border: #e9ecef;
            --dash-sidebar: #0f1521;
            --dash-sidebar-text: #cfd6dd;
            --dash-sidebar-active: #1a2335;
        }
        [data-theme="dark"] {
            --dash-bg: #0f1521;
            --dash-card: #121a2a;
            --dash-text: #e9ecef;
            --dash-muted: #aab1b9;
            --dash-accent: #7367f0;
            --dash-border: #243147;
            --dash-sidebar: #0f1521;
            --dash-sidebar-text: #cfd6dd;
            --dash-sidebar-active: #1a2335;
        }
        body { background: var(--dash-bg); color: var(--dash-text); }
        .sidebar { min-height: 100vh; background: var(--dash-sidebar); transition: all 0.3s ease; }
        .sidebar .nav-link { color: var(--dash-sidebar-text); padding: 12px 20px; border-radius: 10px; margin: 5px 10px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: var(--dash-sidebar-active); transform: translateX(5px); }
        .sidebar .nav-link i { width: 20px; text-align: center; }
        .main-content { background: var(--dash-bg); min-height: 100vh; }
        .card { background: var(--dash-card); border: 1px solid var(--dash-border); border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .text-muted { color: var(--dash-muted) !important; }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                z-index: 1000;
                width: 250px;
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 px-0 sidebar" id="sidebar">
                <div class="p-3">
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-mortarboard-fill text-white fs-3 me-2"></i>
                        <span class="text-white fw-bold" id="brand-text">VRC</span>
                        <button class="btn btn-sm btn-outline-light ms-auto" id="dashThemeToggle" title="Toggle theme"><i class="bi bi-moon"></i></button>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house-door"></i>
                                <span class="ms-2">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="user_roles.php">
                                <i class="bi bi-shield-check"></i>
                                <span class="ms-2">Roles</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="mt-auto p-3">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="ms-2">Logout</span>
                    </a>
                </div>
            </nav>

            <main class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Role Management</h2>
                            <p class="text-muted">Manage your roles and permissions</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary d-md-none" id="sidebarToggle">
                                <i class="bi bi-list"></i>
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                    <!-- Current Roles -->
                <div class="card role-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Your Current Roles</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($currentRoles)): ?>
                            <p class="text-muted">You currently have no roles assigned.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($currentRoles as $role): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0 text-capitalize"><?= htmlspecialchars($role['name']) ?></h6>
                                                    <span class="status-badge status-<?= $role['status'] ?>">
                                                        <?= ucfirst($role['status']) ?>
                                                    </span>
                                                </div>
                                                <p class="card-text small text-muted mb-2"><?= htmlspecialchars($role['description']) ?></p>
                                                
                                                <?php if ($role['status'] === 'approved'): ?>
                                                    <?php 
                                                    $permissions = json_decode($role['permissions'] ?? '{}', true);
                                                    if (!empty($permissions)):
                                                    ?>
                                                        <div class="mb-2">
                                                            <small class="text-muted">Permissions:</small><br>
                                                            <?php foreach ($permissions as $perm => $value): ?>
                                                                <?php if ($value): ?>
                                                                    <span class="permission-tag"><?= htmlspecialchars($perm) ?></span>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if ($role['status'] === 'rejected' && !empty($role['notes'])): ?>
                                                    <div class="alert alert-warning alert-sm mb-0">
                                                        <small><strong>Review Notes:</strong> <?= htmlspecialchars($role['notes']) ?></small>
                                                    </div>
                                                <?php endif; ?>

                                                <small class="text-muted">
                                                    Requested: <?= date('M j, Y', strtotime($role['requested_at'])) ?>
                                                    <?php if ($role['reviewed_at']): ?>
                                                        | Reviewed: <?= date('M j, Y', strtotime($role['reviewed_at'])) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                    <!-- Available Roles -->
                <?php if (!empty($availableRoles)): ?>
                    <div class="card role-card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Request New Role</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($availableRoles as $role): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title text-capitalize"><?= htmlspecialchars($role['name']) ?></h6>
                                                <p class="card-text small text-muted mb-3"><?= htmlspecialchars($role['description']) ?></p>
                                                
                                                <?php 
                                                $permissions = json_decode($role['permissions'] ?? '{}', true);
                                                if (!empty($permissions)):
                                                ?>
                                                    <div class="mb-3">
                                                        <small class="text-muted">Permissions you'll get:</small><br>
                                                        <?php foreach ($permissions as $perm => $value): ?>
                                                            <?php if ($value): ?>
                                                                <span class="permission-tag"><?= htmlspecialchars($perm) ?></span>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                                                    <button type="submit" name="request_role" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-send me-1"></i>Request Role
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card role-card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-check-circle text-success fs-1 mb-3"></i>
                            <h5>All Available Roles Requested</h5>
                            <p class="text-muted">You have already requested all available roles. Check back later for new opportunities!</p>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            try { var t = localStorage.getItem('dash-theme'); if (t === 'dark') document.documentElement.setAttribute('data-theme','dark'); } catch(e) {}
        })();
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('collapsed');
        }
        document.getElementById('dashThemeToggle')?.addEventListener('click', function(){
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            document.documentElement.setAttribute('data-theme', isDark ? '' : 'dark');
            try { localStorage.setItem('dash-theme', isDark ? 'light' : 'dark'); } catch(e) {}
        });
    </script>
</body>
</html>
