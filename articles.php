<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
<?php if (!(($siteConfig['features']['articles'] ?? true))): include __DIR__ . '/errors/404.php'; exit; endif; ?>
<?php 
$pageTitle = 'Articles';
$metaDescription = 'Published articles for students.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/articles.php';
include __DIR__ . '/includes/header.php'; ?>
<?php use Database\DB; use Content\Article; ?>

<?php
// Internal published articles listing
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// If simple search query provided, filter in SQL
$items = [];
$total = 0;
$pages = 1;
try {
	$pdo = DB::pdo();
	if ($q !== '') {
		$stmt = $pdo->prepare('
			SELECT a.*, u.full_name as author_name
			FROM articles a
			JOIN users u ON a.author_id = u.id
			WHERE a.status = "published" AND (a.title LIKE ? OR a.excerpt LIKE ?)
			ORDER BY a.published_at DESC
			LIMIT ? OFFSET ?
		');
		$like = '%' . $q . '%';
		$stmt->execute([$like, $like, $limit, $offset]);
		$items = $stmt->fetchAll();

		// Count total for pagination
		$countStmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM articles WHERE status = "published" AND (title LIKE ? OR excerpt LIKE ?)');
		$countStmt->execute([$like, $like]);
		$total = (int)($countStmt->fetch()['cnt'] ?? 0);
	} else {
		$items = Article::getPublished($limit, $offset);
		// Count total published
		$countStmt = $pdo->query('SELECT COUNT(*) as cnt FROM articles WHERE status = "published"');
		$total = (int)($countStmt->fetch()['cnt'] ?? 0);
	}
	$pages = max(1, (int)ceil($total / $limit));
} catch (\Throwable $e) {
	$items = [];
}
?>

    <h1 class="h4 mb-3">Articles</h1>
    <form class="mb-3" method="get">
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Search articles (title or excerpt)">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <div class="list-group">
        <?php foreach ($items as $it): ?>
            <a class="list-group-item list-group-item-action" href="article.php?slug=<?= urlencode($it['slug']) ?>">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($it['title'] ?? 'Untitled') ?></h6>
                    <small class="text-muted"><?= !empty($it['published_at']) ? date('M j, Y', strtotime($it['published_at'])) : '' ?></small>
                </div>
                <small class="text-muted"><?= htmlspecialchars($it['excerpt'] ?? '') ?></small>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="mt-3" aria-label="Articles pagination">
        <ul class="pagination">
            <?php $baseUrl = 'articles.php?' . http_build_query(array_filter(['q' => $q !== '' ? $q : null])); ?>
            <li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>">
                <a class="page-link" href="<?= htmlspecialchars($baseUrl . ($baseUrl !== '' ? '&' : '') . 'page=' . max(1, $page - 1)) ?>">Previous</a>
            </li>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <li class="page-item <?= ($p === $page ? 'active' : '') ?>">
                    <a class="page-link" href="<?= htmlspecialchars($baseUrl . ($baseUrl !== '' ? '&' : '') . 'page=' . $p) ?>"><?= (int)$p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $pages ? 'disabled' : '') ?>">
                <a class="page-link" href="<?= htmlspecialchars($baseUrl . ($baseUrl !== '' ? '&' : '') . 'page=' . min($pages, $page + 1)) ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>


