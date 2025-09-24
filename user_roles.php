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

$pageTitle = 'Role Requests & Permissions';
$metaDescription = 'Request roles and view your current permissions.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/user_roles.php';

$successMessage = '';
$errorMessage = '';

// Handle role requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'request_role') {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($csrf)) {
            $errorMessage = 'Invalid request token.';
        } else {
            $roleId = (int)($_POST['role_id'] ?? 0);
            $reason = trim((string)($_POST['reason'] ?? ''));
            
            if ($roleId <= 0) {
                $errorMessage = 'Please select a valid role.';
            } elseif (empty($reason)) {
                $errorMessage = 'Please provide a reason for your role request.';
            } else {
                try {
                    $pdo = \Database\DB::pdo();
                    
                    // Check if user already has this role or has a pending request
                    $stmt = $pdo->prepare('
                        SELECT COUNT(*) FROM user_roles ur 
                        WHERE ur.user_id = ? AND ur.role_id = ?
                    ');
                    $stmt->execute([$user->getId(), $roleId]);
                    $hasRole = $stmt->fetchColumn() > 0;
                    
                    if ($hasRole) {
                        $errorMessage = 'You already have this role.';
                    } else {
                        // Check if there's already a pending request
                        $stmt = $pdo->prepare('
                            SELECT COUNT(*) FROM user_role_requests 
                            WHERE user_id = ? AND role_id = ? AND status = "pending"
                        ');
                        $stmt->execute([$user->getId(), $roleId]);
                        $hasPendingRequest = $stmt->fetchColumn() > 0;
                        
                        if ($hasPendingRequest) {
                            $errorMessage = 'You already have a pending request for this role.';
                        } else {
                            // Create role request
                            $stmt = $pdo->prepare('
                                INSERT INTO user_role_requests (user_id, role_id, reason, status, requested_at) 
                                VALUES (?, ?, ?, "pending", NOW())
                            ');
                            $stmt->execute([$user->getId(), $roleId, $reason]);
                            
                            $successMessage = 'Role request submitted successfully! An admin will review your request.';
                        }
                    }
                } catch (Exception $e) {
                    $errorMessage = 'Failed to submit role request: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get user's current roles
$userRoles = $user->getRoles();

// Get available roles for request
$availableRoles = [];
try {
    $pdo = \Database\DB::pdo();
    $stmt = $pdo->prepare('
        SELECT r.* FROM roles r
        WHERE r.name NOT IN ("user", "admin")
        AND r.id NOT IN (
            SELECT role_id FROM user_roles 
            WHERE user_id = ?
        )
        AND r.id NOT IN (
            SELECT role_id FROM user_role_requests 
            WHERE user_id = ? AND status = "pending"
        )
    ');
    $stmt->execute([$user->getId(), $user->getId()]);
    $availableRoles = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get user's pending role requests
$pendingRequests = [];
try {
    $pdo = \Database\DB::pdo();
    $stmt = $pdo->prepare('
        SELECT urr.*, r.name as role_name, r.description as role_description
        FROM user_role_requests urr
        JOIN roles r ON urr.role_id = r.id
        WHERE urr.user_id = ? AND urr.status = "pending"
        ORDER BY urr.requested_at DESC
    ');
    $stmt->execute([$user->getId()]);
    $pendingRequests = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

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
                            <a class="nav-link" href="dashboard.php#profile" data-section="profile">
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
                            <a class="nav-link" href="dashboard.php#articles" data-section="articles">
                                <i class="bi bi-file-text"></i>
                                <span class="ms-2">Articles</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['articles'] ?? true) && $user->hasPermission('review_articles')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php#review" data-section="review">
                                <i class="bi bi-check-circle"></i>
                                <span class="ms-2">Review</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['houses'] ?? true) && $user->hasPermission('manage_houses')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php#houses" data-section="houses">
                                <i class="bi bi-house"></i>
                                <span class="ms-2">Houses</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['businesses'] ?? true) && $user->hasPermission('manage_business')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php#businesses" data-section="businesses">
                                <i class="bi bi-building"></i>
                                <span class="ms-2">Businesses</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="mt-auto p-3">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="ms-2">Logout</span>
                    </a>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0 fw-bold">
                            <i class="bi bi-shield-check me-2"></i>Role Requests & Permissions
                        </h2>
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                    
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($errorMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
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
                                        <p class="form-control-plaintext fw-semibold"><?= htmlspecialchars((string)$user->getId()) ?></p>
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
                    
                    <!-- Request New Role Card -->
                    <?php if (!empty($availableRoles)): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">
                                <i class="bi bi-plus-circle me-2"></i>Request New Role
                            </h5>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                <input type="hidden" name="action" value="request_role">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Select Role</label>
                                            <select name="role_id" class="form-select" required>
                                                <option value="">Choose a role...</option>
                                                <?php foreach ($availableRoles as $role): ?>
                                                    <option value="<?= htmlspecialchars($role['id']) ?>">
                                                        <?= htmlspecialchars($role['name']) ?> - <?= htmlspecialchars($role['description'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Reason for Request</label>
                                            <textarea name="reason" class="form-control" rows="3" placeholder="Explain why you need this role..." required></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>Submit Request
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Pending Requests Card -->
                    <?php if (!empty($pendingRequests)): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">
                                <i class="bi bi-clock me-2"></i>Pending Role Requests
                            </h5>
                            <div class="row">
                                <?php foreach ($pendingRequests as $request): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-0 bg-warning bg-opacity-10">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-2">
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <?= htmlspecialchars($request['role_name']) ?>
                                                    </span>
                                                </h6>
                                                <p class="card-text small text-muted mb-2">
                                                    <strong>Reason:</strong> <?= htmlspecialchars($request['reason']) ?>
                                                </p>
                                                <p class="card-text small text-muted">
                                                    <strong>Requested:</strong> <?= date('M j, Y H:i', strtotime($request['requested_at'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Current Roles Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3">
                                <i class="bi bi-shield-check me-2"></i>Your Current Roles
                            </h5>
                            <div class="row">
                                <?php 
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
