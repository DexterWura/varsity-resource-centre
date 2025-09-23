<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;
use Database\DB;
use Security\Csrf;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { header('Location: /admin/login.php'); exit; }

$user = $auth->user();
$successMessage = '';
$errorMessage = '';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!Csrf::validate($csrf)) {
        $errorMessage = 'Invalid request token.';
    } else {
        try {
            // Role management
            if ($action === 'approve_role') {
                $pdo = DB::pdo();
                $stmt = $pdo->prepare('UPDATE user_role_assignments SET status = "approved", reviewed_at = NOW(), granted_by = 1 WHERE id = ?');
                $stmt->execute([(int)$_POST['role_id']]);
                $successMessage = 'Role request approved.';
                
            } elseif ($action === 'reject_role') {
                $pdo = DB::pdo();
                $notes = trim($_POST['reject_notes'] ?? '');
                $stmt = $pdo->prepare('UPDATE user_role_assignments SET status = "rejected", reviewed_at = NOW(), granted_by = 1, notes = ? WHERE id = ?');
                $stmt->execute([$notes, (int)$_POST['role_id']]);
                $successMessage = 'Role request rejected.';
            }
            
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
    }
}

// Get role requests
try {
    $pdo = DB::pdo();
    $roleRequests = $pdo->query('
        SELECT ura.id, ura.user_id, ura.status, ura.requested_at, ura.reviewed_at, ura.notes,
               u.full_name, u.email, ur.name as role_name, ur.description as role_description
        FROM user_role_assignments ura
        JOIN users u ON ura.user_id = u.id
        JOIN user_roles ur ON ura.role_id = ur.id
        WHERE ura.status = "pending"
        ORDER BY ura.requested_at DESC
    ')->fetchAll();
} catch (\Throwable $e) {
    $roleRequests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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
        .admin-badge {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
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
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
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
                        <i class="bi bi-shield-fill text-danger fs-3 me-2"></i>
                        <span class="text-white fw-bold" id="brand-text">ADMIN</span>
                        <button class="btn btn-sm btn-theme ms-auto" id="dashThemeToggle" title="Toggle theme"><i class="bi bi-moon"></i></button>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i>
                                <span class="ms-2">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="review.php">
                                <i class="bi bi-check-circle"></i>
                                <span class="ms-2">Review Articles</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="users.php">
                                <i class="bi bi-people"></i>
                                <span class="ms-2">User Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="jobs.php">
                                <i class="bi bi-briefcase"></i>
                                <span class="ms-2">Job Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="bi bi-gear"></i>
                                <span class="ms-2">Site Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="migrations.php">
                                <i class="bi bi-database-gear"></i>
                                <span class="ms-2">Migrations</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="mt-auto p-3">
                    <a href="/" class="nav-link text-info mb-2">
                        <i class="bi bi-house"></i>
                        <span class="ms-2">Visit Site</span>
                    </a>
                    <a href="login.php?logout=1" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="ms-2">Logout</span>
                    </a>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">User Management</h2>
                            <p class="text-muted">Manage user role requests and permissions</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="admin-badge">Administrator</span>
                            <button class="btn btn-outline-secondary d-md-none" id="sidebarToggle">
                                <i class="bi bi-list"></i>
                            </button>
                        </div>
                    </div>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($errorMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Role Requests</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($roleRequests)): ?>
                                <p class="text-muted mb-0">No pending role requests.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Requested Role</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roleRequests as $request): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($request['full_name']) ?></td>
                                                    <td><?= htmlspecialchars($request['email']) ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($request['role_name']) ?></span>
                                                        <br><small class="text-muted"><?= htmlspecialchars($request['role_description']) ?></small>
                                                    </td>
                                                    <td><?= date('M j, Y', strtotime($request['requested_at'])) ?></td>
                                                    <td>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                                            <input type="hidden" name="role_id" value="<?= $request['id'] ?>">
                                                            <button name="action" value="approve_role" class="btn btn-sm btn-success me-1" onclick="return confirm('Approve this role request?')">
                                                                <i class="bi bi-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $request['id'] ?>">
                                                            <i class="bi bi-x"></i> Reject
                                                        </button>
                                                        
                                                        <!-- Reject Modal -->
                                                        <div class="modal fade" id="rejectModal<?= $request['id'] ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <form method="post">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Reject Role Request</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <p>Rejecting role request for <strong><?= htmlspecialchars($request['full_name']) ?></strong></p>
                                                                            <div class="mb-3">
                                                                                <label class="form-label">Reason for rejection:</label>
                                                                                <textarea class="form-control" name="reject_notes" rows="3" placeholder="Provide feedback..."></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                                                            <input type="hidden" name="role_id" value="<?= $request['id'] ?>">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button name="action" value="reject_role" class="btn btn-danger">Reject Request</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('dashThemeToggle');
        const body = document.body;
        
        // Load saved theme
        const savedTheme = localStorage.getItem('admin-theme') || 'light';
        body.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
        }
        
        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>
