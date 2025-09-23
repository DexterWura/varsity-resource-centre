<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Auth\UserAuth;
use Security\Csrf;

$userAuth = new UserAuth();
$userAuth->requireAuth();

$user = $userAuth->user();

// Redirect admin users to admin dashboard
if ($user->hasRole('admin')) {
    header('Location: /admin/dashboard.php');
    exit;
}

$pageTitle = 'User Roles & Permissions';
$metaDescription = 'View your user roles and permissions.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/user_roles.php';

// Feature flags
$siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : [];
$features = $siteConfig['features'] ?? [
    'articles' => true,
    'houses' => true,
    'businesses' => true,
    'news' => true,
    'jobs' => true,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Varsity Resource Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
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
        .sidebar.collapsed { width: 70px; }
        .sidebar .nav-link { color: var(--dash-sidebar-text); padding: 12px 20px; border-radius: 10px; margin: 5px 10px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: var(--dash-sidebar-active); transform: translateX(5px); }
        .sidebar .nav-link i { width: 20px; text-align: center; }
        .main-content { background: var(--dash-bg); min-height: 100vh; }
        .card { background: var(--dash-card); border: 1px solid var(--dash-border); border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s ease; }
        .card:hover { transform: translateY(-5px); }
        .text-muted { color: var(--dash-muted) !important; }
        .btn-theme { border: 1px solid var(--dash-border); color: var(--dash-text); }
        .role-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .permission-badge {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
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
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 px-0 sidebar" id="sidebar">
                <div class="p-3">
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-mortarboard-fill text-white fs-3 me-2"></i>
                        <span class="text-white fw-bold" id="brand-text">VRC</span>
                        <button class="btn btn-sm btn-theme ms-auto" id="dashThemeToggle" title="Toggle theme"><i class="bi bi-moon"></i></button>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house-door"></i>
                                <span class="ms-2">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#profile" data-section="profile">
                                <i class="bi bi-person"></i>
                                <span class="ms-2">Profile</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="user_roles.php">
                                <i class="bi bi-shield-check"></i>
                                <span class="ms-2">Roles</span>
                            </a>
                        </li>
                        <?php if (($features['articles'] ?? true) && $user->hasPermission('write_articles')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php#articles">
                                <i class="bi bi-file-text"></i>
                                <span class="ms-2">Articles</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['articles'] ?? true) && $user->hasPermission('review_articles')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php#review">
                                <i class="bi bi-check-circle"></i>
                                <span class="ms-2">Review</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['houses'] ?? true) && $user->hasPermission('manage_houses')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php#houses">
                                <i class="bi bi-house"></i>
                                <span class="ms-2">Houses</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['businesses'] ?? true) && $user->hasPermission('manage_businesses')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php#businesses">
                                <i class="bi bi-building"></i>
                                <span class="ms-2">Businesses</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0 fw-bold">
                            <i class="bi bi-shield-check me-2"></i>User Roles & Permissions
                        </h2>
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                    
                    <!-- User Information Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">
                                <i class="bi bi-person-circle me-2"></i>Account Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Full Name</label>
                                        <p class="form-control-plaintext fw-semibold"><?= htmlspecialchars($user->getFullName()) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Email Address</label>
                                        <p class="form-control-plaintext fw-semibold"><?= htmlspecialchars($user->getEmail()) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">User ID</label>
                                        <p class="form-control-plaintext fw-semibold"><?= htmlspecialchars($user->getId()) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Account Status</label>
                                        <p class="form-control-plaintext">
                                            <?php if ($user->isActive()): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Roles Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">
                                <i class="bi bi-shield-check me-2"></i>Your Roles
                            </h5>
                            <div class="row">
                                <?php 
                                $userRoles = $user->getRoles();
                                if (empty($userRoles)): 
                                ?>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>No roles assigned to your account.
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($userRoles as $role): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card border-0 bg-light">
                                                <div class="card-body p-3">
                                                    <h6 class="card-title mb-2">
                                                        <span class="role-badge">
                                                            <i class="bi bi-shield-check me-1"></i>
                                                            <?= htmlspecialchars($role['name']) ?>
                                                        </span>
                                                    </h6>
                                                    <p class="card-text small text-muted">
                                                        <?php
                                                        $permissions = json_decode($role['permissions'] ?? '[]', true);
                                                        if (!empty($permissions)) {
                                                            echo implode(', ', $permissions);
                                                        } else {
                                                            echo 'No specific permissions';
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Permissions Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">
                                <i class="bi bi-key me-2"></i>Your Permissions
                            </h5>
                            <div class="row">
                                <?php 
                                $userPermissions = $user->getPermissions();
                                if (empty($userPermissions)): 
                                ?>
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>No permissions assigned to your account.
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($userPermissions as $permission): ?>
                                        <div class="col-md-4 mb-2">
                                            <span class="permission-badge">
                                                <i class="bi bi-check-circle me-1"></i>
                                                <?= htmlspecialchars($permission) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('dashThemeToggle');
            const body = document.body;
            
            // Load saved theme
            const savedTheme = localStorage.getItem('dash-theme') || 'light';
            body.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
            
            // Theme toggle event
            themeToggle.addEventListener('click', function() {
                const currentTheme = body.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                body.setAttribute('data-theme', newTheme);
                localStorage.setItem('dash-theme', newTheme);
                updateThemeIcon(newTheme);
            });
            
            function updateThemeIcon(theme) {
                const icon = themeToggle.querySelector('i');
                icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
            }
        });
    </script>
</body>
</html>
