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
            // Job management
            if ($action === 'job_create') {
                if (!empty($_POST['job_title'])) {
                    $pdo = DB::pdo();
                    $expiresAt = !empty($_POST['job_expires_at']) ? $_POST['job_expires_at'] : null;
                    $stmt = $pdo->prepare('INSERT INTO jobs (title, company_name, location, description, url, expires_at, is_active) VALUES (?,?,?,?,?,?,1)');
                    $stmt->execute([
                        $_POST['job_title'],
                        $_POST['job_company'] ?? '',
                        $_POST['job_location'] ?? '',
                        $_POST['job_description'] ?? '',
                        $_POST['job_url'] ?? '',
                        $expiresAt,
                    ]);
                    $successMessage = 'Job posted successfully.';
                }
            } elseif ($action === 'job_toggle') {
                $pdo = DB::pdo();
                $stmt = $pdo->prepare('UPDATE jobs SET is_active = 1 - is_active WHERE id = ?');
                $stmt->execute([(int)$_POST['job_id']]);
                $successMessage = 'Job status updated.';
                
            } elseif ($action === 'job_delete') {
                $pdo = DB::pdo();
                $stmt = $pdo->prepare('DELETE FROM jobs WHERE id = ?');
                $stmt->execute([(int)$_POST['job_id']]);
                $successMessage = 'Job deleted.';
            }
            
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
    }
}

// Get jobs
try {
    $pdo = DB::pdo();
    $jobs = $pdo->query('SELECT * FROM jobs ORDER BY created_at DESC LIMIT 50')->fetchAll();
} catch (\Throwable $e) {
    $jobs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management - Admin Dashboard</title>
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
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i>
                                <span class="ms-2">User Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="jobs.php">
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
                        <li class="nav-item">
                            <a class="nav-link" href="semantic-scholar.php">
                                <i class="bi bi-search"></i>
                                <span class="ms-2">Academic Search</span>
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
                            <h2 class="mb-1">Job Management</h2>
                            <p class="text-muted">Post and manage job listings</p>
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

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Post New Job</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                        <input type="hidden" name="action" value="job_create">
                                        <div class="mb-3">
                                            <label class="form-label">Job Title</label>
                                            <input name="job_title" class="form-control" placeholder="e.g. Graduate Trainee - IT" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Company</label>
                                            <input name="job_company" class="form-control" placeholder="e.g. Econet" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Location</label>
                                            <input name="job_location" class="form-control" placeholder="Harare / Remote" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="job_description" class="form-control" rows="3" placeholder="Job description..."></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Job URL</label>
                                            <input name="job_url" class="form-control" placeholder="https://..." type="url">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Expiry Date</label>
                                            <input name="job_expires_at" class="form-control" type="datetime-local">
                                        </div>
                                        <button class="btn btn-primary" type="submit">Post Job</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Recent Jobs</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($jobs)): ?>
                                        <p class="text-muted">No jobs posted yet.</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($jobs as $job): ?>
                                                <div class="list-group-item px-0">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1"><?= htmlspecialchars($job['title']) ?></h6>
                                                            <p class="mb-1 text-muted"><?= htmlspecialchars($job['company_name']) ?> - <?= htmlspecialchars($job['location']) ?></p>
                                                            <small class="text-muted"><?= date('M j, Y', strtotime($job['created_at'])) ?></small>
                                                        </div>
                                                        <div>
                                                            <span class="badge <?= $job['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $job['is_active'] ? 'Active' : 'Inactive' ?></span>
                                                            <div class="btn-group btn-group-sm mt-1">
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                                                    <input type="hidden" name="action" value="job_toggle">
                                                                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                                    <button class="btn btn-outline-secondary">Toggle</button>
                                                                </form>
                                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this job?')">
                                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                                                    <input type="hidden" name="action" value="job_delete">
                                                                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                                    <button class="btn btn-outline-danger">Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
