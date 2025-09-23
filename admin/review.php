<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;
use Content\Article;
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
            }
            
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
    }
}

// Get data for review page
try {
    $pdo = DB::pdo();
    
    // Articles for review
    $pendingArticles = Article::getForReview(50, 0);
    $assignedArticles = Article::getAssignedToReviewer(1, 50, 0); // Admin user ID 1
    
} catch (\Throwable $e) {
    $pendingArticles = [];
    $assignedArticles = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article Review - Admin Dashboard</title>
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
                            <a class="nav-link active" href="review.php">
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
                            <h2 class="mb-1">Article Review</h2>
                            <p class="text-muted">Review and approve articles for publication</p>
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
