<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Auth\UserAuth;
use Database\DB;

$userAuth = new UserAuth();
$userAuth->requireAuth();

$user = $userAuth->user();
$error = $_GET['error'] ?? '';

// Get user's role requests
$roleRequests = [];
try {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('
        SELECT ura.*, ur.name as role_name, ur.description as role_description
        FROM user_role_assignments ura
        JOIN user_roles ur ON ura.role_id = ur.id
        WHERE ura.user_id = ? AND ura.status = "pending"
        ORDER BY ura.requested_at DESC
    ');
    $stmt->execute([$user->getId()]);
    $roleRequests = $stmt->fetchAll();
} catch (\Throwable $e) {
    // Handle error silently
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
    <title>Dashboard - Varsity Resource Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card .card-body {
            padding: 2rem;
        }
        .role-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .request-badge {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
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
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#dashboard" data-section="dashboard">
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
                            <a class="nav-link" href="user_roles.php">
                                <i class="bi bi-shield-check"></i>
                                <span class="ms-2">Roles</span>
                            </a>
                        </li>
                        <?php if ($user->hasPermission('write_articles')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#articles" data-section="articles">
                                <i class="bi bi-file-text"></i>
                                <span class="ms-2">Articles</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($user->hasPermission('review_articles')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#review" data-section="review">
                                <i class="bi bi-check-circle"></i>
                                <span class="ms-2">Review</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($user->hasPermission('manage_houses')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#houses" data-section="houses">
                                <i class="bi bi-house"></i>
                                <span class="ms-2">Houses</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($user->hasPermission('manage_business')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#businesses" data-section="businesses">
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
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Welcome back, <?= htmlspecialchars($user->getFullName()) ?>!</h2>
                            <p class="text-muted mb-0">Here's what's happening with your account</p>
                        </div>
                        <button class="btn btn-outline-primary d-md-none" id="sidebarToggle">
                            <i class="bi bi-list"></i>
                        </button>
                    </div>

                    <?php if ($error === 'insufficient_permissions'): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            You don't have sufficient permissions to access that feature.
                        </div>
                    <?php endif; ?>

                    <!-- Dashboard Section -->
                    <div id="dashboard-section">
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-person-circle fs-1 mb-2"></i>
                                        <h5 class="card-title">Profile</h5>
                                        <p class="card-text">Manage your account</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-shield-check fs-1 mb-2"></i>
                                        <h5 class="card-title">Roles</h5>
                                        <p class="card-text"><?= count($user->getRoles()) ?> active</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-clock-history fs-1 mb-2"></i>
                                        <h5 class="card-title">Requests</h5>
                                        <p class="card-text"><?= count($roleRequests) ?> pending</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-gear fs-1 mb-2"></i>
                                        <h5 class="card-title">Settings</h5>
                                        <p class="card-text">Account preferences</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Your Roles</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($user->getRoles())): ?>
                                            <p class="text-muted">You currently have no special roles assigned.</p>
                                        <?php else: ?>
                                            <?php foreach ($user->getRoles() as $role): ?>
                                                <span class="role-badge me-2 mb-2"><?= htmlspecialchars($role['name']) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Quick Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <?php if ($user->hasPermission('write_articles')): ?>
                                                <button class="btn btn-primary btn-sm">Write Article</button>
                                            <?php endif; ?>
                                            <?php if ($user->hasPermission('manage_houses')): ?>
                                                <button class="btn btn-success btn-sm">Add House</button>
                                            <?php endif; ?>
                                            <?php if ($user->hasPermission('manage_business')): ?>
                                                <button class="btn btn-info btn-sm">Add Business</button>
                                            <?php endif; ?>
                                            <a href="user_roles.php" class="btn btn-outline-secondary btn-sm">Request Role</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Other sections will be added here -->
                    <div id="profile-section" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <p>Profile section coming soon...</p>
                            </div>
                        </div>
                    </div>

                    <div id="roles-section" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Role Management</h5>
                            </div>
                            <div class="card-body">
                                <p>Role management section coming soon...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Section navigation
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');
                
                // Hide all sections
                document.querySelectorAll('[id$="-section"]').forEach(s => s.style.display = 'none');
                
                // Show selected section
                document.getElementById(section + '-section').style.display = 'block';
                
                // Update active nav link
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Auto-collapse sidebar on mobile
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('collapsed');
        }
    </script>
</body>
</html>
