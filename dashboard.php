<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Auth\UserAuth;
use Content\Article;
use Security\Csrf;
use Database\DB;

$userAuth = new UserAuth();
$userAuth->requireAuth();

$user = $userAuth->user();

// Redirect admin users to admin dashboard
if ($user->hasRole('admin')) {
    header('Location: /admin/dashboard.php');
    exit;
}
$error = $_GET['error'] ?? '';
// Feature flags
$siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : [];
$features = $siteConfig['features'] ?? [
	'articles' => true,
	'houses' => true,
	'businesses' => true,
	'news' => true,
	'jobs' => true,
];

// Handle article-related actions
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$csrf = $_POST['csrf_token'] ?? '';
	if (!Csrf::validate($csrf)) {
		$errorMessage = 'Invalid request token.';
	} else {
		try {
			if ($action === 'article_create') {
				if (!($features['articles'] ?? true)) { throw new \RuntimeException('Articles are disabled.'); }
				$userAuth->requirePermission('write_articles');
				$title = trim($_POST['title'] ?? '');
				$content = trim($_POST['content'] ?? '');
				$excerpt = trim($_POST['excerpt'] ?? '');
				
				// Clean up content - remove empty HTML tags and whitespace
				$content = preg_replace('/<p><br><\/p>/', '', $content);
				$content = preg_replace('/<p><\/p>/', '', $content);
				$content = trim($content);
				
				// Debug: Log what we're receiving
				error_log('Article create debug - Title: "' . $title . '", Content length: ' . strlen($content) . ', Content: "' . substr($content, 0, 100) . '"');
				
				// Determine if this is a draft or submit action
				$isDraft = isset($_POST['save_draft']);
				$isSubmit = isset($_POST['submit_for_review']);
				
				if ($title === '' || $content === '' || $excerpt === '') {
					throw new \RuntimeException('Title, content, and excerpt are required. Please make sure you have entered all three fields for your article.');
				}
				
				$status = $isSubmit ? 'submitted' : 'draft';
				$created = Article::create([
					'title' => $title,
					'content' => $content,
					'excerpt' => $excerpt,
					'author_id' => $user->getId(),
					'status' => $status
				]);
				if (!$created) {
					throw new \RuntimeException('Failed to create article.');
				}
				$successMessage = $isSubmit ? 'Article submitted for review.' : 'Article saved as draft.';
			} elseif ($action === 'article_submit') {
				if (!($features['articles'] ?? true)) { throw new \RuntimeException('Articles are disabled.'); }
				$userAuth->requirePermission('write_articles');
				$articleId = (int)($_POST['article_id'] ?? 0);
				$article = Article::findById($articleId);
				if (!$article || $article->getAuthorId() !== $user->getId()) {
					throw new \RuntimeException('Article not found or access denied.');
				}
				if (!$article->submitForReview()) {
					throw new \RuntimeException('Failed to submit article for review.');
				}
				$successMessage = 'Article submitted for review.';
			} elseif ($action === 'article_update') {
				if (!($features['articles'] ?? true)) { throw new \RuntimeException('Articles are disabled.'); }
				$userAuth->requirePermission('write_articles');
				$articleId = (int)($_POST['article_id'] ?? 0);
				$article = Article::findById($articleId);
				if (!$article || $article->getAuthorId() !== $user->getId()) {
					throw new \RuntimeException('Article not found or access denied.');
				}
				$title = trim($_POST['title'] ?? '');
				$content = trim($_POST['content'] ?? '');
				$excerpt = trim($_POST['excerpt'] ?? '');
				if (!$article->update([
					'title' => ($title !== '' ? $title : null),
					'content' => ($content !== '' ? $content : null),
					'excerpt' => ($excerpt !== '' ? $excerpt : null),
				])) {
					throw new \RuntimeException('Failed to update article.');
				}
				$successMessage = 'Article updated.';
			} elseif ($action === 'article_assign') {
				if (!($features['articles'] ?? true)) { throw new \RuntimeException('Articles are disabled.'); }
				if (!$user->hasPermission('review_articles') && !$user->hasPermission('admin')) {
					throw new \RuntimeException('You do not have permission to review articles.');
				}
				$articleId = (int)($_POST['article_id'] ?? 0);
				$article = Article::findById($articleId);
				if (!$article) {
					throw new \RuntimeException('Article not found.');
				}
				if (!$article->assignToReviewer($user->getId())) {
					throw new \RuntimeException('Failed to assign article for review. It may have already been assigned to another reviewer.');
				}
				$successMessage = 'Article assigned to you for review.';
			} elseif ($action === 'article_approve' || $action === 'article_reject') {
				if (!($features['articles'] ?? true)) { throw new \RuntimeException('Articles are disabled.'); }
				if (!$user->hasPermission('review_articles') && !$user->hasPermission('admin')) {
					throw new \RuntimeException('You do not have permission to review articles.');
				}
				$articleId = (int)($_POST['article_id'] ?? 0);
				$notes = trim($_POST['notes'] ?? '');
				$article = Article::findById($articleId);
				if (!$article) {
					throw new \RuntimeException('Article not found.');
				}
				// Check if the current user is the assigned reviewer
				if ($article->getReviewerId() !== $user->getId()) {
					throw new \RuntimeException('You are not assigned to review this article.');
				}
				$ok = $action === 'article_approve'
					? $article->approve($user->getId(), $notes)
					: $article->reject($user->getId(), $notes);
				if (!$ok) {
					throw new \RuntimeException('Failed to process review action.');
				}
				$successMessage = $action === 'article_approve' ? 'Article approved and published.' : 'Article rejected.';
            } elseif ($action === 'article_delete') {
                if (!($features['articles'] ?? true)) { throw new \RuntimeException('Articles are disabled.'); }
                $userAuth->requirePermission('write_articles');
                $articleId = (int)($_POST['article_id'] ?? 0);
                $article = Article::findById($articleId);
                if (!$article || $article->getAuthorId() !== $user->getId()) {
                    throw new \RuntimeException('Article not found or access denied.');
                }
                if (!Article::deleteById($articleId, $user->getId())) {
                    throw new \RuntimeException('Failed to delete article.');
                }
                $successMessage = 'Article deleted.';
            } elseif ($action === 'house_create') {
				if (!($features['houses'] ?? true)) { throw new \RuntimeException('Houses are disabled.'); }
				$userAuth->requirePermission('manage_houses');
				$pdo = DB::pdo();
				$title = trim($_POST['title'] ?? '');
				$city = trim($_POST['city'] ?? '');
				$price = (float)($_POST['price'] ?? 0);
				$priceType = $_POST['price_type'] ?? 'per_month';
				if ($title === '' || $price <= 0) { throw new \RuntimeException('Title and price are required.'); }
				$stmt = $pdo->prepare('INSERT INTO houses (title, price, price_type, city, owner_id, is_active) VALUES (?, ?, ?, ?, ?, 1)');
				$stmt->execute([$title, $price, $priceType, $city, $user->getId()]);
				$successMessage = 'House listing created.';
			} elseif ($action === 'house_toggle') {
				if (!($features['houses'] ?? true)) { throw new \RuntimeException('Houses are disabled.'); }
				$userAuth->requirePermission('manage_houses');
				$pdo = DB::pdo();
				$id = (int)($_POST['id'] ?? 0);
				$stmt = $pdo->prepare('UPDATE houses SET is_active = 1 - is_active WHERE id = ? AND owner_id = ?');
				$stmt->execute([$id, $user->getId()]);
				$successMessage = 'House listing toggled.';
			} elseif ($action === 'house_delete') {
				if (!($features['houses'] ?? true)) { throw new \RuntimeException('Houses are disabled.'); }
				$userAuth->requirePermission('manage_houses');
				$pdo = DB::pdo();
				$id = (int)($_POST['id'] ?? 0);
				$stmt = $pdo->prepare('DELETE FROM houses WHERE id = ? AND owner_id = ?');
				$stmt->execute([$id, $user->getId()]);
				$successMessage = 'House listing deleted.';
			} elseif ($action === 'business_create') {
				if (!($features['businesses'] ?? true)) { throw new \RuntimeException('Businesses are disabled.'); }
				$userAuth->requirePermission('manage_business');
				$pdo = DB::pdo();
				$name = trim($_POST['name'] ?? '');
				$category = trim($_POST['category'] ?? '');
				$city = trim($_POST['city'] ?? '');
				if ($name === '') { throw new \RuntimeException('Business name is required.'); }
				$stmt = $pdo->prepare('INSERT INTO businesses (name, category, city, owner_id, is_active) VALUES (?, ?, ?, ?, 1)');
				$stmt->execute([$name, $category, $city, $user->getId()]);
				$successMessage = 'Business created.';
			} elseif ($action === 'business_toggle') {
				if (!($features['businesses'] ?? true)) { throw new \RuntimeException('Businesses are disabled.'); }
				$userAuth->requirePermission('manage_business');
				$pdo = DB::pdo();
				$id = (int)($_POST['id'] ?? 0);
				$stmt = $pdo->prepare('UPDATE businesses SET is_active = 1 - is_active WHERE id = ? AND owner_id = ?');
				$stmt->execute([$id, $user->getId()]);
				$successMessage = 'Business toggled.';
			} elseif ($action === 'business_delete') {
				if (!($features['businesses'] ?? true)) { throw new \RuntimeException('Businesses are disabled.'); }
				$userAuth->requirePermission('manage_business');
				$pdo = DB::pdo();
				$id = (int)($_POST['id'] ?? 0);
				$stmt = $pdo->prepare('DELETE FROM businesses WHERE id = ? AND owner_id = ?');
				$stmt->execute([$id, $user->getId()]);
				$successMessage = 'Business deleted.';
			}
		} catch (\Throwable $e) {
			$errorMessage = $e->getMessage();
		}
	}
}

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
        /* Content editor styles */
        #editor:empty:before {
            content: attr(data-placeholder);
            color: #6c757d;
            font-style: italic;
        }
        #editor:focus:before {
            content: none;
        }
        #editor {
            outline: none;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        #editor:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
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
                        <?php if (($features['articles'] ?? true) && $user->hasPermission('write_articles')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#articles" data-section="articles">
                                <i class="bi bi-file-text"></i>
                                <span class="ms-2">Articles</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['articles'] ?? true) && $user->hasPermission('review_articles')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#review" data-section="review">
                                <i class="bi bi-check-circle"></i>
                                <span class="ms-2">Review</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['houses'] ?? true) && $user->hasPermission('manage_houses')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#houses" data-section="houses">
                                <i class="bi bi-house"></i>
                                <span class="ms-2">Houses</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (($features['businesses'] ?? true) && $user->hasPermission('manage_business')): ?>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="mb-1">Welcome back, <?= htmlspecialchars($user->getFullName()) ?>!</h2>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill text-bg-success">Active</span>
                                <?php if (count($user->getRoles())>0): ?><span class="badge rounded-pill text-bg-primary">Roles <?= count($user->getRoles()) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm d-none d-md-flex" style="max-width: 280px;">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                                <input type="search" class="form-control border-start-0" placeholder="Search dashboard..." oninput="window.userQuickSearch && window.userQuickSearch(this.value)">
                            </div>
                            <button class="btn btn-outline-primary d-md-none" id="sidebarToggle">
                                <i class="bi bi-list"></i>
                            </button>
                            <button class="btn btn-outline-secondary position-relative" title="Notifications">
                                <i class="bi bi-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="dashNotifBadge" style="display:none;">0</span>
                            </button>
                        </div>
                    </div>
                    <ul class="nav nav-pills gap-2 mb-3">
                        <li class="nav-item"><a class="nav-link active" href="#dashboard" data-section="dashboard">Overview</a></li>
                        <li class="nav-item"><a class="nav-link" href="#profile" data-section="profile">Profile</a></li>
                        <?php if (($features['articles'] ?? true) && $user->hasPermission('write_articles')): ?><li class="nav-item"><a class="nav-link" href="#articles" data-section="articles">Articles</a></li><?php endif; ?>
                        <?php if (($features['articles'] ?? true) && $user->hasPermission('review_articles')): ?><li class="nav-item"><a class="nav-link" href="#review" data-section="review">Review</a></li><?php endif; ?>
                        <?php if (($features['houses'] ?? true) && $user->hasPermission('manage_houses')): ?><li class="nav-item"><a class="nav-link" href="#houses" data-section="houses">Houses</a></li><?php endif; ?>
                        <?php if (($features['businesses'] ?? true) && $user->hasPermission('manage_business')): ?><li class="nav-item"><a class="nav-link" href="#businesses" data-section="businesses">Businesses</a></li><?php endif; ?>
                    </ul>

                    <?php if ($error === 'insufficient_permissions'): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            You don't have sufficient permissions to access that feature.
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($errorMessage) ?>
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
                                        <div class="small text-white-50">Manage your account</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-shield-check fs-1 mb-2"></i>
                                        <h5 class="card-title">Roles</h5>
                                        <div class="small text-white-50"><?= count($user->getRoles()) ?> active</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <i class="bi bi-clock-history fs-1 mb-2"></i>
                                        <h5 class="card-title">Requests</h5>
                                        <div class="small text-white-50"><?= count($roleRequests) ?> pending</div>
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
                                            <?php if (($features['articles'] ?? true) && $user->hasPermission('write_articles')): ?>
                                                <button class="btn btn-primary btn-sm">Write Article</button>
                                            <?php endif; ?>
                                            <?php if (($features['houses'] ?? true) && $user->hasPermission('manage_houses')): ?>
                                                <button class="btn btn-success btn-sm">Add House</button>
                                            <?php endif; ?>
                                            <?php if (($features['businesses'] ?? true) && $user->hasPermission('manage_business')): ?>
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

					<?php if ($user->hasPermission('manage_houses')): ?>
					<div id="houses-section" style="display: none;">
						<?php $csrfToken = Csrf::issueToken(); ?>
						<div class="row">
							<div class="col-md-6">
								<div class="card mb-4">
									<div class="card-header"><h5 class="mb-0">Add House</h5></div>
									<div class="card-body">
										<form method="post">
											<input type="hidden" name="action" value="house_create">
											<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
											<div class="mb-2"><label class="form-label">Title</label><input name="title" class="form-control"></div>
											<div class="row g-2 mb-2">
												<div class="col-6"><label class="form-label">Price</label><input name="price" type="number" step="0.01" class="form-control"></div>
												<div class="col-6"><label class="form-label">Price type</label>
													<select name="price_type" class="form-select">
														<option value="per_day">per_day</option>
														<option value="per_week">per_week</option>
														<option value="per_month" selected>per_month</option>
													</select>
												</div>
											</div>
											<div class="mb-2"><label class="form-label">City</label><input name="city" class="form-control"></div>
											<button class="btn btn-primary" type="submit">Create</button>
										</form>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<?php
								$myHouses = [];
								try { $pdo = DB::pdo(); $st = $pdo->prepare('SELECT * FROM houses WHERE owner_id = ? ORDER BY updated_at DESC, created_at DESC'); $st->execute([$user->getId()]); $myHouses = $st->fetchAll(); } catch (\Throwable $e) { $myHouses = []; }
								?>
								<div class="card mb-4">
									<div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Your Houses</h5><span class="text-muted small"><?= count($myHouses) ?></span></div>
									<div class="card-body">
										<?php if (empty($myHouses)): ?>
											<p class="text-muted mb-0">No listings yet.</p>
										<?php else: ?>
											<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Title</th><th>City</th><th>Price</th><th>Active</th><th>Actions</th></tr></thead><tbody>
												<?php foreach ($myHouses as $h): ?>
												<tr>
													<td class="text-truncate" style="max-width:200px;" title="<?= htmlspecialchars($h['title']) ?>"><?= htmlspecialchars($h['title']) ?></td>
													<td><?= htmlspecialchars($h['city'] ?? '') ?></td>
													<td><?= htmlspecialchars(number_format((float)$h['price'], 2)) ?> <span class="text-muted small">/ <?= htmlspecialchars(str_replace('per_', '', $h['price_type'])) ?></span></td>
													<td><?= ((int)$h['is_active'] === 1 ? 'Yes' : 'No') ?></td>
													<td class="d-flex gap-2">
														<form method="post" class="d-inline"><input type="hidden" name="action" value="house_toggle"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><button class="btn btn-sm btn-light" type="submit">Toggle</button></form>
														<form method="post" class="d-inline" onsubmit="return confirm('Delete this listing?');"><input type="hidden" name="action" value="house_delete"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
													</td>
												</tr>
												<?php endforeach; ?>
											</tbody></table></div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>

					<?php if ($user->hasPermission('manage_business')): ?>
					<div id="businesses-section" style="display: none;">
						<?php $csrfToken = Csrf::issueToken(); ?>
						<div class="row">
							<div class="col-md-6">
								<div class="card mb-4">
									<div class="card-header"><h5 class="mb-0">Add Business</h5></div>
									<div class="card-body">
										<form method="post">
											<input type="hidden" name="action" value="business_create">
											<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
											<div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control"></div>
											<div class="mb-2"><label class="form-label">Category</label><input name="category" class="form-control"></div>
											<div class="mb-2"><label class="form-label">City</label><input name="city" class="form-control"></div>
											<button class="btn btn-primary" type="submit">Create</button>
										</form>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<?php $myBusinesses = []; try { $pdo = DB::pdo(); $st = $pdo->prepare('SELECT * FROM businesses WHERE owner_id = ? ORDER BY updated_at DESC, created_at DESC'); $st->execute([$user->getId()]); $myBusinesses = $st->fetchAll(); } catch (\Throwable $e) { $myBusinesses = []; } ?>
								<div class="card mb-4">
									<div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Your Businesses</h5><span class="text-muted small"><?= count($myBusinesses) ?></span></div>
									<div class="card-body">
										<?php if (empty($myBusinesses)): ?>
											<p class="text-muted mb-0">No businesses yet.</p>
										<?php else: ?>
											<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>City</th><th>Category</th><th>Active</th><th>Actions</th></tr></thead><tbody>
												<?php foreach ($myBusinesses as $b): ?>
												<tr>
													<td class="text-truncate" style="max-width:200px;" title="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></td>
													<td><?= htmlspecialchars($b['city'] ?? '') ?></td>
													<td><?= htmlspecialchars($b['category'] ?? '') ?></td>
													<td><?= ((int)$b['is_active'] === 1 ? 'Yes' : 'No') ?></td>
													<td class="d-flex gap-2">
														<form method="post" class="d-inline"><input type="hidden" name="action" value="business_toggle"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-light" type="submit">Toggle</button></form>
														<form method="post" class="d-inline" onsubmit="return confirm('Delete this business?');"><input type="hidden" name="action" value="business_delete"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
													</td>
												</tr>
												<?php endforeach; ?>
											</tbody></table></div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>

					<?php if (($features['articles'] ?? true) && $user->hasPermission('write_articles')): ?>
					<div id="articles-section" style="display: none;">
						<?php
							$csrfToken = Csrf::issueToken();
							$myArticles = Article::getByAuthor($user->getId(), 50, 0);
						?>
						<div class="row">
							<div class="col-md-6">
                            <div class="card mb-4">
									<div class="card-header">
										<h5 class="mb-0">Write Article</h5>
									</div>
									<div class="card-body">
                                    <form method="post">
											<input type="hidden" name="action" value="article_create">
											<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
											<div class="mb-3">
												<label class="form-label">Title</label>
												<input name="title" class="form-control" placeholder="Enter title" required>
											</div>
											<div class="mb-3">
                                            <label class="form-label">Content</label>
                                            <div class="btn-toolbar mb-2" role="toolbar">
                                                <div class="btn-group btn-group-sm me-2">
                                                    <button type="button" class="btn btn-outline-secondary" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                                                    <button type="button" class="btn btn-outline-secondary" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                                                    <button type="button" class="btn btn-outline-secondary" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                                                </div>
                                                <div class="btn-group btn-group-sm me-2">
                                                    <button type="button" class="btn btn-outline-secondary" data-cmd="insertUnorderedList"><i class="bi bi-list-ul"></i></button>
                                                    <button type="button" class="btn btn-outline-secondary" data-cmd="insertOrderedList"><i class="bi bi-list-ol"></i></button>
                                                </div>
                                                <div class="btn-group btn-group-sm me-2">
                                                    <button type="button" class="btn btn-outline-secondary" data-cmd="createLink"><i class="bi bi-link-45deg"></i></button>
                                                    <button type="button" class="btn btn-outline-secondary" data-cmd="removeFormat"><i class="bi bi-eraser"></i></button>
                                                </div>
                                            </div>
                                            <div id="editor" contenteditable="true" class="form-control" style="min-height:220px" data-placeholder="Start writing your article content here..."></div>
                                            <textarea name="content" id="editorInput" class="d-none"></textarea>
											</div>
											<div class="mb-3">
												<label class="form-label">Excerpt</label>
												<textarea name="excerpt" rows="2" class="form-control" placeholder="Short summary" required></textarea>
											</div>
											<div class="d-flex gap-2">
												<button class="btn btn-outline-secondary" type="submit" name="save_draft">Save Draft</button>
												<button class="btn btn-primary" type="submit" name="submit_for_review">Submit for Review</button>
											</div>
											<div class="mt-2">
												<small class="text-muted">Make sure to enter a title, content, and excerpt before submitting.</small>
											</div>
										</form>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="card mb-4">
									<div class="card-header d-flex justify-content-between align-items-center">
										<h5 class="mb-0">Your Articles</h5>
										<span class="text-muted small"><?= count($myArticles) ?> total</span>
									</div>
									<div class="card-body">
										<?php if (empty($myArticles)): ?>
											<p class="text-muted mb-0">No articles yet. Create one on the left.</p>
										<?php else: ?>
											<div class="table-responsive">
												<table class="table align-middle">
													<thead>
														<tr>
															<th>Title</th>
															<th>Status</th>
															<th>Actions</th>
														</tr>
													</thead>
													<tbody>
														<?php foreach ($myArticles as $a): ?>
															<tr>
																<td class="text-truncate" style="max-width:240px;" title="<?= htmlspecialchars($a['title']) ?>"><?= htmlspecialchars($a['title']) ?></td>
																<td><span class="badge bg-light text-dark text-uppercase"><?= htmlspecialchars($a['status']) ?></span></td>
																<td class="d-flex gap-2">
																	<form method="post" class="d-inline">
																		<input type="hidden" name="action" value="article_submit">
																		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																		<input type="hidden" name="article_id" value="<?= (int)$a['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-primary" type="submit" <?= ($a['status'] !== 'draft' ? 'disabled' : '') ?>>Submit</button>
                                                </form>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this article? This cannot be undone.');">
                                                    <input type="hidden" name="action" value="article_delete">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="article_id" value="<?= (int)$a['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
																	</form>
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#edit-<?= (int)$a['id'] ?>">Edit</button>
																</td>
															</tr>
															<tr class="collapse" id="edit-<?= (int)$a['id'] ?>">
																<td colspan="3">
																	<form method="post">
																		<input type="hidden" name="action" value="article_update">
																		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																		<input type="hidden" name="article_id" value="<?= (int)$a['id'] ?>">
																		<div class="row g-2">
																			<div class="col-md-6">
																				<input name="title" class="form-control" value="<?= htmlspecialchars($a['title']) ?>">
																			</div>
																			<div class="col-md-6">
																				<input name="excerpt" class="form-control" placeholder="Excerpt (optional)" value="<?= htmlspecialchars($a['excerpt'] ?? '') ?>">
																			</div>
                                                    <div class="col-12">
                                                        <div class="btn-toolbar mb-2" role="toolbar">
                                                            <div class="btn-group btn-group-sm me-2">
                                                                <button type="button" class="btn btn-outline-secondary" data-cmd="bold"><i class="bi bi-type-bold"></i></button>
                                                                <button type="button" class="btn btn-outline-secondary" data-cmd="italic"><i class="bi bi-type-italic"></i></button>
                                                                <button type="button" class="btn btn-outline-secondary" data-cmd="underline"><i class="bi bi-type-underline"></i></button>
                                                            </div>
                                                            <div class="btn-group btn-group-sm me-2">
                                                                <button type="button" class="btn btn-outline-secondary" data-cmd="insertUnorderedList"><i class="bi bi-list-ul"></i></button>
                                                                <button type="button" class="btn btn-outline-secondary" data-cmd="insertOrderedList"><i class="bi bi-list-ol"></i></button>
                                                            </div>
                                                            <div class="btn-group btn-group-sm me-2">
                                                                <button type="button" class="btn btn-outline-secondary" data-cmd="createLink"><i class="bi bi-link-45deg"></i></button>
                                                                <button type="button" class="btn btn-outline-secondary" data-cmd="removeFormat"><i class="bi bi-eraser"></i></button>
                                                            </div>
                                                        </div>
                                                        <div contenteditable="true" class="form-control article-editor" style="min-height:160px"><?= $a['content'] ?></div>
                                                        <textarea name="content" class="d-none"></textarea>
                                                    </div>
																		</div>
																		<div class="mt-2">
																			<button class="btn btn-sm btn-primary" type="submit">Save</button>
																		</div>
																	</form>
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
						<?php endif; ?>

					<?php if (($features['articles'] ?? true) && ($user->hasPermission('review_articles') || $user->hasPermission('admin'))): ?>
					<div id="review-section" style="display: none;">
						<?php
							$csrfToken = Csrf::issueToken();
							$pending = Article::getForReview(50, 0);
							$assigned = Article::getAssignedToReviewer($user->getId(), 50, 0);
						?>
						
						<!-- Assigned Articles -->
						<?php if (!empty($assigned)): ?>
						<div class="card mb-4">
							<div class="card-header d-flex justify-content-between align-items-center">
								<h5 class="mb-0">Your Assigned Articles</h5>
								<span class="text-muted small"><?= count($assigned) ?> assigned</span>
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
											<?php foreach ($assigned as $a): ?>
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
								<h5 class="mb-0">Available Articles for Review</h5>
								<span class="text-muted small"><?= count($pending) ?> pending</span>
							</div>
							<div class="card-body">
								<?php if (empty($pending)): ?>
									<p class="text-muted mb-0">No articles available for review.</p>
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
												<?php foreach ($pending as $p): ?>
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
					<?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            try { var t = localStorage.getItem('dash-theme'); if (t === 'dark') document.documentElement.setAttribute('data-theme','dark'); } catch(e) {}
        })();
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
        document.getElementById('dashThemeToggle')?.addEventListener('click', function(){
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            document.documentElement.setAttribute('data-theme', isDark ? '' : 'dark');
            try { localStorage.setItem('dash-theme', isDark ? 'light' : 'dark'); } catch(e) {}
        });

        // Minimal WYSIWYG handling
        function bindEditor(toolbar, editable, hiddenInput){
            if (!toolbar || !editable || !hiddenInput) return;
            toolbar.querySelectorAll('[data-cmd]').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var cmd = btn.getAttribute('data-cmd');
                    if (cmd === 'createLink') {
                        var url = prompt('Enter URL');
                        if (url) document.execCommand('createLink', false, url);
                    } else {
                        document.execCommand(cmd, false, null);
                    }
                    editable.focus();
                });
            });
            editable.form?.addEventListener('submit', function(){
                var content = editable.innerHTML || editable.textContent || '';
                // Clean up empty paragraphs and whitespace
                content = content.replace(/<p><br><\/p>/g, '').replace(/<p><\/p>/g, '').trim();
                hiddenInput.value = content;
            });
        }
        // Bind create form editor
        (function(){
            var toolbar = document.querySelector('#editor')?.previousElementSibling;
            var editable = document.getElementById('editor');
            var hidden = document.getElementById('editorInput');
            bindEditor(toolbar, editable, hidden);
            
            // Real-time sync and form submit handling
            var form = editable?.closest('form');
            if (form) {
                // Sync content on every change
                editable.addEventListener('input', function() {
                    if (hidden) {
                        var content = editable.innerHTML || editable.textContent || '';
                        content = content.replace(/<p><br><\/p>/g, '').replace(/<p><\/p>/g, '').trim();
                        hidden.value = content;
                    }
                });
                
                // Final sync on form submit
                form.addEventListener('submit', function(e) {
                    if (editable && hidden) {
                        var content = editable.innerHTML || editable.textContent || '';
                        // Clean up empty paragraphs and whitespace
                        content = content.replace(/<p><br><\/p>/g, '').replace(/<p><\/p>/g, '').trim();
                        hidden.value = content;
                        console.log('Final sync - Content length:', hidden.value.length, 'characters');
                        
                        // Check if all required fields are filled
                        var title = form.querySelector('input[name="title"]').value.trim();
                        var excerpt = form.querySelector('textarea[name="excerpt"]').value.trim();
                        
                        if (!title) {
                            e.preventDefault();
                            alert('Please enter a title for your article.');
                            form.querySelector('input[name="title"]').focus();
                            return false;
                        }
                        
                        if (!content.trim()) {
                            e.preventDefault();
                            alert('Please enter some content for your article.');
                            editable.focus();
                            return false;
                        }
                        
                        if (!excerpt) {
                            e.preventDefault();
                            alert('Please enter an excerpt for your article.');
                            form.querySelector('textarea[name="excerpt"]').focus();
                            return false;
                        }
                    }
                });
            }
        })();
        // Bind all update editors
        document.querySelectorAll('.article-editor').forEach(function(ed){
            var toolbar = ed.previousElementSibling;
            var hidden = ed.nextElementSibling;
            bindEditor(toolbar, ed, hidden);
        });
    </script>
</body>
</html>
