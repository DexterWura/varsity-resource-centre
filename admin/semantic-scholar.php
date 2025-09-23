<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\UserAuth;
use Content\SemanticScholar;
use Content\Article;
use Security\Csrf;

$userAuth = new UserAuth();
if (!$userAuth->check() || !$userAuth->user()->hasRole('admin')) { 
    header('Location: /admin/login.php'); 
    exit; 
}

$user = $userAuth->user();
$successMessage = '';
$errorMessage = '';
$searchResults = [];
$searchQuery = '';

// Load API key from settings
$settings = new \Config\Settings(__DIR__ . '/../storage/settings.json');
$apiKey = $settings->get('semantic_scholar_api_key');

$semanticScholar = new SemanticScholar($apiKey);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!Csrf::validate($csrf)) {
        $errorMessage = 'Invalid request token.';
    } else {
        try {
            if ($action === 'search_papers') {
                $searchQuery = trim($_POST['search_query'] ?? '');
                $limit = (int)($_POST['limit'] ?? 20);
                
                if (empty($searchQuery)) {
                    $errorMessage = 'Please enter a search query.';
                } else {
                    $searchResults = $semanticScholar->searchPapers($searchQuery, $limit);
                    if (empty($searchResults)) {
                        $errorMessage = 'No papers found for your search query.';
                    }
                }
                
            } elseif ($action === 'import_paper') {
                $paperId = $_POST['paper_id'] ?? '';
                $adminUserId = 1; // Admin user ID
                
                if (empty($paperId)) {
                    $errorMessage = 'Paper ID is required.';
                } else {
                    $paper = $semanticScholar->getPaper($paperId);
                    if (!$paper) {
                        $errorMessage = 'Could not retrieve paper details.';
                    } else {
                        $articleData = $semanticScholar->paperToArticle($paper, $adminUserId);
                        $created = Article::create($articleData);
                        
                        if ($created) {
                            $successMessage = 'Paper imported successfully as article draft.';
                        } else {
                            $errorMessage = 'Failed to create article from paper.';
                        }
                    }
                }
                
            } elseif ($action === 'update_api_key') {
                $newApiKey = trim($_POST['api_key'] ?? '');
                $settings->set('semantic_scholar_api_key', $newApiKey);
                $apiKey = $newApiKey;
                $semanticScholar = new SemanticScholar($apiKey);
                $successMessage = 'API key updated successfully.';
            }
            
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semantic Scholar Integration - Admin Dashboard</title>
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
        .paper-card {
            border-left: 4px solid #7367f0;
        }
        .citation-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
                            <a class="nav-link" href="migrations.php">
                                <i class="bi bi-database-gear"></i>
                                <span class="ms-2">Migrations</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="semantic-scholar.php">
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
                            <h2 class="mb-1">Academic Paper Search</h2>
                            <p class="text-muted">Search and import papers from Semantic Scholar</p>
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

                    <!-- API Key Configuration -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-key me-2"></i>API Configuration</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                <input type="hidden" name="action" value="update_api_key">
                                <div class="col-md-8">
                                    <label class="form-label">Semantic Scholar API Key (Optional)</label>
                                    <input type="password" class="form-control" name="api_key" value="<?= htmlspecialchars($apiKey ?? '') ?>" placeholder="Enter your API key for higher rate limits">
                                    <div class="form-text">
                                        <a href="https://www.semanticscholar.org/product/api" target="_blank">Get your free API key</a> for higher rate limits (1000 requests/second vs 1 request/second)
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button class="btn btn-outline-primary d-block w-100" type="submit">
                                        <i class="bi bi-save me-2"></i>Save API Key
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Search Academic Papers</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                <input type="hidden" name="action" value="search_papers">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Search Query</label>
                                        <input type="text" class="form-control" name="search_query" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="e.g., machine learning, artificial intelligence, climate change" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Results</label>
                                        <select class="form-select" name="limit">
                                            <option value="10">10</option>
                                            <option value="20" selected>20</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button class="btn btn-primary d-block w-100" type="submit">
                                            <i class="bi bi-search me-2"></i>Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <?php if (!empty($searchResults)): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Search Results</h5>
                            <span class="badge bg-primary"><?= count($searchResults) ?> papers found</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($searchResults as $paper): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card paper-card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?= htmlspecialchars($paper['title']) ?></h6>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-people me-1"></i>
                                                        <?= htmlspecialchars($paper['author_names']) ?>
                                                    </small>
                                                </div>
                                                
                                                <?php if (!empty($paper['venue'])): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-building me-1"></i>
                                                        <?= htmlspecialchars($paper['venue']) ?>
                                                        <?php if (!empty($paper['year'])): ?>
                                                            (<?= $paper['year'] ?>)
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($paper['abstract'])): ?>
                                                <p class="card-text small text-muted">
                                                    <?= htmlspecialchars(substr($paper['abstract'], 0, 200)) ?><?= strlen($paper['abstract']) > 200 ? '...' : '' ?>
                                                </p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <?php if ($paper['citation_count'] > 0): ?>
                                                            <span class="badge citation-badge text-white me-2">
                                                                <i class="bi bi-quote me-1"></i><?= $paper['citation_count'] ?> citations
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($paper['is_open_access']): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-unlock me-1"></i>Open Access
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                                        <input type="hidden" name="action" value="import_paper">
                                                        <input type="hidden" name="paper_id" value="<?= htmlspecialchars($paper['id']) ?>">
                                                        <button class="btn btn-sm btn-outline-primary" type="submit" onclick="return confirm('Import this paper as an article draft?')">
                                                            <i class="bi bi-download me-1"></i>Import
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <?php if (!empty($paper['url'])): ?>
                                                <div class="mt-2">
                                                    <a href="<?= htmlspecialchars($paper['url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i>View on Semantic Scholar
                                                    </a>
                                                    
                                                    <?php if (!empty($paper['pdf_url'])): ?>
                                                        <a href="<?= htmlspecialchars($paper['pdf_url']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-file-pdf me-1"></i>PDF
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
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
