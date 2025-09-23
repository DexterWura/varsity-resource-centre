<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;
use Database\MigrationRunner;
use Security\Csrf;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { header('Location: /admin/login.php'); exit; }

$user = $auth->user();
$successMessage = '';
$errorMessage = '';
$results = [];

$migrationRunner = new MigrationRunner();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!Csrf::validate($csrf)) {
        $errorMessage = 'Invalid request token.';
    } else {
        try {
            if ($action === 'run_migrations') {
                $results = $migrationRunner->runAllPendingMigrations();
                if (empty($results)) {
                    $successMessage = 'No pending migrations to run.';
                } else {
                    $successCount = count(array_filter($results, fn($r) => $r['success']));
                    $message = "Migration completed. {$successCount}/" . count($results) . " migrations successful.";
                    $successMessage = $message;
                }
            } elseif ($action === 'validate_migrations') {
                $issues = $migrationRunner->validateMigrations();
                if (empty($issues)) {
                    $successMessage = 'All migrations are valid.';
                } else {
                    $errorMessage = 'Validation issues found: ' . implode(', ', $issues);
                }
            }
        } catch (\Exception $e) {
            $errorMessage = 'Error: ' . $e->getMessage();
        }
    }
}

$pendingMigrations = $migrationRunner->getPendingMigrations();
$migrationHistory = $migrationRunner->getMigrationHistory();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migrations - Admin Dashboard</title>
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
                            <a class="nav-link active" href="migrations.php">
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
                            <h2 class="mb-1">Database Migrations</h2>
                            <p class="text-muted">Manage database schema updates and migrations</p>
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
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-database-gear me-2"></i>Migration Actions</h5>
                                    <span class="badge bg-warning"><?= count($pendingMigrations) ?> pending</span>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                            <input type="hidden" name="action" value="run_migrations">
                                            <button class="btn btn-primary w-100" type="submit" onclick="return confirm('Run all pending migrations? This action cannot be undone.')">
                                                <i class="bi bi-play-circle me-2"></i>Run All Pending Migrations
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                            <input type="hidden" name="action" value="validate_migrations">
                                            <button class="btn btn-outline-info w-100" type="submit">
                                                <i class="bi bi-check-circle me-2"></i>Validate Migrations
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <?php if (!empty($pendingMigrations)): ?>
                                    <div class="mt-4">
                                        <h6 class="text-warning">Pending Migrations:</h6>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($pendingMigrations as $migration): ?>
                                                <div class="list-group-item px-0">
                                                    <div class="fw-bold"><?= htmlspecialchars($migration['version']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($migration['description']) ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="mt-4 text-center">
                                        <i class="bi bi-check-circle text-success fs-1"></i>
                                        <p class="text-muted mt-2">All migrations are up to date!</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Migration History</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($migrationHistory)): ?>
                                        <p class="text-muted">No migration history available.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Version</th>
                                                        <th>Description</th>
                                                        <th>Status</th>
                                                        <th>Executed</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (array_slice($migrationHistory, 0, 10) as $migration): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($migration['version']) ?></td>
                                                            <td class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($migration['description']) ?>">
                                                                <?= htmlspecialchars($migration['description']) ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge <?= $migration['success'] ? 'bg-success' : 'bg-danger' ?>">
                                                                    <?= $migration['success'] ? 'Success' : 'Failed' ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?= date('M j, Y H:i', strtotime($migration['executed_at'])) ?>
                                                                </small>
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
                    </div>
                    
                    <!-- Migration Results -->
                    <?php if (!empty($results)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Migration Results</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Version</th>
                                            <th>Description</th>
                                            <th>File</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td><code>V<?= htmlspecialchars($result['version']) ?></code></td>
                                                <td><?= htmlspecialchars($result['description']) ?></td>
                                                <td><small class="text-muted"><?= htmlspecialchars($result['file']) ?></small></td>
                                                <td>
                                                    <?php if ($result['success']): ?>
                                                        <span class="badge bg-success">Success</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Failed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $result['execution_time'] ?>ms</td>
                                            </tr>
                                            <?php if (!$result['success'] && $result['error']): ?>
                                                <tr>
                                                    <td colspan="5">
                                                        <div class="alert alert-danger alert-sm mb-0">
                                                            <strong>Error:</strong> <?= htmlspecialchars($result['error']) ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Migration Details -->
                    <?php if (!empty($pendingMigrations)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Migration Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="migrationAccordion">
                                <?php foreach ($pendingMigrations as $index => $migration): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?= $index ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>">
                                                <strong><?= htmlspecialchars($migration['version']) ?></strong>
                                                <span class="ms-2 text-muted">- <?= htmlspecialchars($migration['description']) ?></span>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#migrationAccordion">
                                            <div class="accordion-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Migration Info:</h6>
                                                        <ul class="list-unstyled">
                                                            <li><strong>Version:</strong> <?= htmlspecialchars($migration['version']) ?></li>
                                                            <li><strong>Description:</strong> <?= htmlspecialchars($migration['description']) ?></li>
                                                            <li><strong>Type:</strong> <?= htmlspecialchars($migration['type']) ?></li>
                                                            <li><strong>Checksum:</strong> <code><?= htmlspecialchars($migration['checksum']) ?></code></li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>SQL Preview:</h6>
                                                        <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto; font-size: 0.8rem;"><?= htmlspecialchars(substr($migration['script'], 0, 500)) ?><?= strlen($migration['script']) > 500 ? '...' : '' ?></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
