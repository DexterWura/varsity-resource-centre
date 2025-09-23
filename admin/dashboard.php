<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;
use Content\Article;
use Database\DB;
use Database\MigrationRunner;
use Security\Csrf;
use Config\Settings;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { header('Location: /admin/login.php'); exit; }

$user = $auth->user();
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

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!Csrf::validate($csrf)) {
        $errorMessage = 'Invalid request token.';
    } else {
        try {
            // Article review actions
            if ($action === 'article_assign') {
                $articleId = (int)($_POST['article_id'] ?? 0);
                $article = Article::findById($articleId);
                if (!$article) {
                    throw new \RuntimeException('Article not found.');
                }
                // Create a mock admin user ID for now - in real implementation you'd have admin user management
                $adminUserId = 1; 
                if (!$article->assignToReviewer($adminUserId)) {
                    throw new \RuntimeException('Failed to assign article for review.');
                }
                $successMessage = 'Article assigned for review.';
                
            } elseif ($action === 'article_approve' || $action === 'article_reject') {
                $articleId = (int)($_POST['article_id'] ?? 0);
                $notes = trim($_POST['notes'] ?? '');
                $article = Article::findById($articleId);
                if (!$article) {
                    throw new \RuntimeException('Article not found.');
                }
                $adminUserId = 1;
                $ok = $action === 'article_approve'
                    ? $article->approve($adminUserId, $notes)
                    : $article->reject($adminUserId, $notes);
                if (!$ok) {
                    throw new \RuntimeException('Failed to process review action.');
                }
                $successMessage = $action === 'article_approve' ? 'Article approved and published.' : 'Article rejected.';
                
            // Settings actions
            } elseif ($action === 'update_settings') {
                $updates = [
                    'site_name' => trim($_POST['site_name'] ?? ''),
                    'site_description' => trim($_POST['site_description'] ?? ''),
                    'adsense_client' => trim($_POST['adsense_client'] ?? ''),
                    'adsense_slot_header' => trim($_POST['adsense_slot_header'] ?? ''),
                    'adsense_slot_sidebar' => trim($_POST['adsense_slot_sidebar'] ?? ''),
                    'donate_url' => trim($_POST['donate_url'] ?? ''),
                    'theme' => [
                        'primary' => $_POST['theme_primary'] ?? '#7367f0',
                        'secondary' => $_POST['theme_secondary'] ?? '#6c757d',
                        'background' => $_POST['theme_background'] ?? '#f8f9fa',
                    ],
                    'features' => [
                        'articles' => isset($_POST['feature_articles']),
                        'houses' => isset($_POST['feature_houses']),
                        'businesses' => isset($_POST['feature_businesses']),
                        'news' => isset($_POST['feature_news']),
                        'jobs' => isset($_POST['feature_jobs']),
                    ]
                ];
                $settings->setMany($updates);
                
                // Update app config features
                $appConfig = include __DIR__ . '/../storage/app.php';
                $appConfig['features'] = $updates['features'];
                file_put_contents(__DIR__ . '/../storage/app.php', '<?php' . PHP_EOL . 'return ' . var_export($appConfig, true) . ';');
                
                $successMessage = 'Settings updated successfully.';
                
            // Job management
            } elseif ($action === 'job_create') {
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
                
            // Role management
            } elseif ($action === 'approve_role') {
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
                
            // Migration actions
            } elseif ($action === 'run_migrations') {
                $results = $migrationRunner->runAllPendingMigrations();
                if (empty($results)) {
                    $successMessage = 'No pending migrations to run.';
                } else {
                    $successCount = count(array_filter($results, fn($r) => $r['success']));
                    $successMessage = "Migration completed. {$successCount}/" . count($results) . " migrations successful.";
                }
            } elseif ($action === 'validate_migrations') {
                $issues = $migrationRunner->validateMigrations();
                if (empty($issues)) {
                    $successMessage = 'All migrations are valid.';
                } else {
                    $errorMessage = 'Validation issues found: ' . implode(', ', $issues);
                }
            }
            
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
    }
}

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
                            <a class="nav-link active" href="#dashboard" data-section="dashboard">
                                <i class="bi bi-speedometer2"></i>
                                <span class="ms-2">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#review" data-section="review">
                                <i class="bi bi-check-circle"></i>
                                <span class="ms-2">Review Articles</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#users" data-section="users">
                                <i class="bi bi-people"></i>
                                <span class="ms-2">User Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#jobs" data-section="jobs">
                                <i class="bi bi-briefcase"></i>
                                <span class="ms-2">Job Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#settings" data-section="settings">
                                <i class="bi bi-gear"></i>
                                <span class="ms-2">Site Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#migrations" data-section="migrations">
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

                    <!-- Dashboard Section -->
                    <div id="dashboard-section" style="display: block;">
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
                                            <a href="#review" class="btn btn-primary" data-section="review">
                                                <i class="bi bi-check-circle me-2"></i>Review Articles (<?= count($pendingArticles) ?> pending)
                                            </a>
                                            <a href="#users" class="btn btn-outline-primary" data-section="users">
                                                <i class="bi bi-people me-2"></i>Manage Role Requests (<?= count($roleRequests) ?> pending)
                                            </a>
                                            <a href="#jobs" class="btn btn-outline-success" data-section="jobs">
                                                <i class="bi bi-briefcase me-2"></i>Post New Job
                                            </a>
                                            <a href="#migrations" class="btn btn-outline-warning" data-section="migrations">
                                                <i class="bi bi-database-gear me-2"></i>Run Migrations (<?= $stats['pending_migrations'] ?> pending)
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

                    <!-- Article Review Section -->
                    <div id="review-section" style="display: none;">
                        <?php
                            $csrfToken = Csrf::issueToken();
                        ?>
                        
                        <!-- Assigned Articles -->
                        <?php if (!empty($assignedArticles)): ?>
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-person-check me-2"></i>Your Assigned Articles</h5>
                                <span class="badge bg-primary"><?= count($assignedArticles) ?> assigned</span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($assignedArticles as $a): ?>
                                                <tr>
                                                    <td class="text-truncate" style="max-width:320px;" title="<?= htmlspecialchars($a['title']) ?>"><?= htmlspecialchars($a['title']) ?></td>
                                                    <td><?= htmlspecialchars($a['author_name'] ?? 'Author') ?></td>
                                                    <td class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#assigned-<?= (int)$a['id'] ?>">Review</button>
                                                    </td>
                                                </tr>
                                                <tr class="collapse" id="assigned-<?= (int)$a['id'] ?>">
                                                    <td colspan="3">
                                                        <div class="mb-2">
                                                            <div class="fw-semibold mb-1">Content</div>
                                                            <div class="border rounded p-2" style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($a['content'])) ?></div>
                                                        </div>
                                                        <form method="post" class="d-flex gap-2">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                            <input type="hidden" name="article_id" value="<?= (int)$a['id'] ?>">
                                                            <input name="notes" class="form-control" placeholder="Review notes (optional)">
                                                            <button name="action" value="article_reject" class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                                            <button name="action" value="article_approve" class="btn btn-sm btn-success" type="submit">Approve & Publish</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Available Articles -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-inbox me-2"></i>Articles Awaiting Review</h5>
                                <span class="badge bg-warning"><?= count($pendingArticles) ?> pending</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pendingArticles)): ?>
                                    <p class="text-muted mb-0">No articles awaiting review.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Author</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pendingArticles as $p): ?>
                                                    <tr>
                                                        <td class="text-truncate" style="max-width:320px;" title="<?= htmlspecialchars($p['title']) ?>"><?= htmlspecialchars($p['title']) ?></td>
                                                        <td><?= htmlspecialchars($p['author_name'] ?? 'Author') ?></td>
                                                        <td class="d-flex gap-2">
                                                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#preview-<?= (int)$p['id'] ?>">Preview</button>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                                <input type="hidden" name="article_id" value="<?= (int)$p['id'] ?>">
                                                                <button name="action" value="article_assign" class="btn btn-sm btn-primary" type="submit">Take for Review</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <tr class="collapse" id="preview-<?= (int)$p['id'] ?>">
                                                        <td colspan="3">
                                                            <div class="mb-2">
                                                                <div class="fw-semibold mb-1">Content Preview</div>
                                                                <div class="border rounded p-2" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto;"><?= nl2br(htmlspecialchars($p['content'])) ?></div>
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

                    <!-- User Management Section -->
                    <div id="users-section" style="display: none;">
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
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
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
                                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
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

                    <!-- Job Management Section -->
                    <div id="jobs-section" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Post New Job</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
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
                                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                                        <input type="hidden" name="action" value="job_toggle">
                                                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                                        <button class="btn btn-outline-secondary">Toggle</button>
                                                                    </form>
                                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this job?')">
                                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
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

                    <!-- Settings Section -->
                    <div id="settings-section" style="display: none;">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <!-- Site Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-globe me-2"></i>Site Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Site Name</label>
                                            <input class="form-control" name="site_name" value="<?= htmlspecialchars($data['site_name'] ?? 'Varsity Resource Centre') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Site Description</label>
                                            <input class="form-control" name="site_description" value="<?= htmlspecialchars($data['site_description'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Feature Toggles -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-toggles me-2"></i>Feature Management</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="feature_articles" <?= ($features['articles'] ?? true) ? 'checked' : '' ?>>
                                                <label class="form-check-label">Articles System</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="feature_houses" <?= ($features['houses'] ?? true) ? 'checked' : '' ?>>
                                                <label class="form-check-label">House Rentals</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="feature_businesses" <?= ($features['businesses'] ?? true) ? 'checked' : '' ?>>
                                                <label class="form-check-label">Business Listings</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="feature_news" <?= ($features['news'] ?? true) ? 'checked' : '' ?>>
                                                <label class="form-check-label">News Section</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="feature_jobs" <?= ($features['jobs'] ?? true) ? 'checked' : '' ?>>
                                                <label class="form-check-label">Job Postings</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Theme Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-palette me-2"></i>Theme Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Primary Color</label>
                                            <input type="color" class="form-control form-control-color" name="theme_primary" value="<?= htmlspecialchars($data['theme']['primary'] ?? '#7367f0') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Secondary Color</label>
                                            <input type="color" class="form-control form-control-color" name="theme_secondary" value="<?= htmlspecialchars($data['theme']['secondary'] ?? '#6c757d') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Background Color</label>
                                            <input type="color" class="form-control form-control-color" name="theme_background" value="<?= htmlspecialchars($data['theme']['background'] ?? '#f8f9fa') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- AdSense Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-cash me-2"></i>Google AdSense</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Client ID</label>
                                            <input class="form-control" name="adsense_client" value="<?= htmlspecialchars($data['adsense_client'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Header Slot</label>
                                            <input class="form-control" name="adsense_slot_header" value="<?= htmlspecialchars($data['adsense_slot_header'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Sidebar Slot</label>
                                            <input class="form-control" name="adsense_slot_sidebar" value="<?= htmlspecialchars($data['adsense_slot_sidebar'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Donations -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-heart me-2"></i>Donations</h5>
                                </div>
                                <div class="card-body">
                                    <label class="form-label">Donate URL</label>
                                    <input class="form-control" name="donate_url" value="<?= htmlspecialchars($data['donate_url'] ?? '') ?>" placeholder="https://paypal.me/yourlink">
                                </div>
                            </div>

                            <button class="btn btn-primary btn-lg" type="submit">
                                <i class="bi bi-check-circle me-2"></i>Save All Settings
                            </button>
                        </form>
                    </div>

                    <!-- Migrations Section -->
                    <div id="migrations-section" style="display: none;">
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
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="action" value="run_migrations">
                                                <button class="btn btn-primary w-100" type="submit" onclick="return confirm('Run all pending migrations? This action cannot be undone.')">
                                                    <i class="bi bi-play-circle me-2"></i>Run All Pending Migrations
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
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
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing admin dashboard...');
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

            // Section navigation
            document.querySelectorAll('[data-section]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.getAttribute('data-section');
                    showSection(section);
                });
            });
            
            function showSection(section) {
                console.log('showSection called with:', section);
                
                // Hide all sections
                document.querySelectorAll('[id$="-section"]').forEach(s => {
                    s.style.display = 'none';
                    console.log('Hiding:', s.id);
                });
                
                // Show selected section
                const targetSection = document.getElementById(section + '-section');
                if (targetSection) {
                    targetSection.style.display = 'block';
                    console.log('Showing:', section + '-section');
                } else {
                    console.error('Section not found:', section + '-section');
                }
                
                // Update active nav link
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                const activeLink = document.querySelector(`[data-section="${section}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                    console.log('Activated nav link:', section);
                } else {
                    console.error('Nav link not found:', section);
                }
            }
            
            // Handle URL hash on page load
            function handleHashNavigation() {
                console.log('handleHashNavigation called, hash:', window.location.hash);
                if (window.location.hash) {
                    const section = window.location.hash.substring(1); // Remove the #
                    console.log('Hash section:', section);
                    if (section && document.getElementById(section + '-section')) {
                        console.log('Found section, showing:', section);
                        showSection(section);
                    } else {
                        console.error('Section not found for hash:', section);
                    }
                } else {
                    console.log('No hash, showing dashboard');
                    // Default to dashboard section
                    showSection('dashboard');
                }
            }
            
            // Run on page load
            handleHashNavigation();
            
            // Also listen for hash changes
            window.addEventListener('hashchange', handleHashNavigation);
        });
    </script>
</body>
</html>
