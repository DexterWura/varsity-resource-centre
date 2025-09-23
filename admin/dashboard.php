<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\UserAuth;
use Content\Article;
use Database\DB;
use Database\MigrationRunner;
use Security\Csrf;
use Config\Settings;

$userAuth = new UserAuth();
if (!$userAuth->check() || !$userAuth->user()->hasRole('admin')) { 
    header('Location: /admin/login.php'); 
    exit; 
}

$user = $userAuth->user();
$successMessage = '';
$errorMessage = '';

// Load settings
$settings = new Settings(__DIR__ . '/../storage/settings.json');
$data = $settings->all();

// Initialize migration runner
$migrationRunner = new MigrationRunner();

// Feature flags
$siteConfig = is_file(__DIR__ . '/../storage/app.php') ? (include __DIR__ . '/../storage/app.php') : [];
$features = $siteConfig['features'] ?? [
    'articles' => true,
    'houses' => true,
    'businesses' => true,
    'news' => true,
    'jobs' => true,
];

// Get data for dashboard
try {
    $pdo = DB::pdo();
    
    // Articles for review
    $pendingArticles = Article::getForReview(50, 0);
    $assignedArticles = Article::getAssignedToReviewer(1, 50, 0); // Admin user ID 1
    
    // Role requests
    $roleRequests = $pdo->query('
        SELECT ura.id, ura.user_id, ura.status, ura.requested_at, ura.reviewed_at, ura.notes,
               u.full_name, u.email, ur.name as role_name, ur.description as role_description
        FROM user_role_assignments ura
        JOIN users u ON ura.user_id = u.id
        JOIN user_roles ur ON ura.role_id = ur.id
        WHERE ura.status = "pending"
        ORDER BY ura.requested_at DESC
    ')->fetchAll();
    
    // Jobs
    $jobs = $pdo->query('SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10')->fetchAll();
    
    // Migration data
    $pendingMigrations = $migrationRunner->getPendingMigrations();
    $migrationHistory = $migrationRunner->getMigrationHistory();
    
    // Stats
    $stats = [
        'total_users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'total_articles' => (int)$pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
        'pending_reviews' => count($pendingArticles),
        'total_jobs' => (int)$pdo->query('SELECT COUNT(*) FROM jobs WHERE is_active = 1')->fetchColumn(),
        'pending_migrations' => count($pendingMigrations),
    ];
    
} catch (\Throwable $e) {
    $stats = ['total_users' => 0, 'total_articles' => 0, 'pending_reviews' => 0, 'total_jobs' => 0, 'pending_migrations' => 0];
    $pendingArticles = [];
    $assignedArticles = [];
    $roleRequests = [];
    $jobs = [];
    $pendingMigrations = [];
    $migrationHistory = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Varsity Resource Centre</title>
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card .card-body { padding: 2rem; }
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
                            <a class="nav-link active" href="dashboard.php">
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
                        <div>
                            <h2 class="mb-1">Admin Dashboard</h2>
                            <p class="text-muted">Manage your Varsity Resource Centre</p>
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

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="bi bi-people fs-1 mb-2"></i>
                                    <h3 class="mb-1"><?= $stats['total_users'] ?></h3>
                                    <p class="mb-0">Total Users</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="bi bi-file-text fs-1 mb-2"></i>
                                    <h3 class="mb-1"><?= $stats['total_articles'] ?></h3>
                                    <p class="mb-0">Total Articles</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="bi bi-clock fs-1 mb-2"></i>
                                    <h3 class="mb-1"><?= $stats['pending_reviews'] ?></h3>
                                    <p class="mb-0">Pending Reviews</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="bi bi-briefcase fs-1 mb-2"></i>
                                    <h3 class="mb-1"><?= $stats['total_jobs'] ?></h3>
                                    <p class="mb-0">Active Jobs</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="review.php" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i>Review Articles (<?= count($pendingArticles) ?> pending)
                                        </a>
                                        <a href="users.php" class="btn btn-outline-primary">
                                            <i class="bi bi-people me-2"></i>Manage Role Requests (<?= count($roleRequests) ?> pending)
                                        </a>
                                        <a href="jobs.php" class="btn btn-outline-success">
                                            <i class="bi bi-briefcase me-2"></i>Post New Job
                                        </a>
                                            <a href="migrations.php" class="btn btn-outline-warning">
                                                <i class="bi bi-database-gear me-2"></i>Run Migrations (<?= $stats['pending_migrations'] ?> pending)
                                            </a>
                                            <a href="semantic-scholar.php" class="btn btn-outline-info">
                                                <i class="bi bi-search me-2"></i>Search Academic Papers
                                            </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <?php if (!empty($pendingArticles)): ?>
                                            <?php foreach (array_slice($pendingArticles, 0, 3) as $article): ?>
                                                <div class="list-group-item px-0">
                                                    <small class="text-muted">New article for review</small>
                                                    <div class="fw-bold"><?= htmlspecialchars($article['title']) ?></div>
                                                    <small class="text-muted">by <?= htmlspecialchars($article['author_name']) ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="list-group-item px-0">
                                                <small class="text-muted">No recent activity</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
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
        
        // Session timeout warning (30 minutes = 1800 seconds)
        let sessionTimeout = 1800; // 30 minutes
        let warningTime = 300; // Show warning 5 minutes before timeout
        let warningShown = false;
        
        function checkSessionTimeout() {
            if (sessionTimeout <= warningTime && !warningShown) {
                warningShown = true;
                showSessionWarning();
            }
            
            if (sessionTimeout <= 0) {
                window.location.href = '/admin/logout.php?timeout=1';
                return;
            }
            
            sessionTimeout--;
        }
        
        function showSessionWarning() {
            const warning = document.createElement('div');
            warning.className = 'alert alert-warning alert-dismissible fade show position-fixed';
            warning.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            warning.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Session Timeout Warning</strong><br>
                Your session will expire in 5 minutes due to inactivity. 
                <a href="#" onclick="extendSession()" class="alert-link">Click here to extend</a> or you will be automatically logged out.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(warning);
        }
        
        function extendSession() {
            // Make a request to extend the session
            fetch('/admin/dashboard.php', { method: 'HEAD' })
                .then(() => {
                    sessionTimeout = 1800; // Reset timeout
                    warningShown = false;
                    // Remove warning
                    const warning = document.querySelector('.alert-warning');
                    if (warning) warning.remove();
                })
                .catch(() => {
                    window.location.href = '/admin/logout.php?timeout=1';
                });
        }
        
        // Check session timeout every minute
        setInterval(checkSessionTimeout, 60000);
    </script>
</body>
</html>