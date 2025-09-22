<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
<?php if (!(($siteConfig['features']['businesses'] ?? true))): include __DIR__ . '/errors/404.php'; exit; endif; ?>
<?php 
$pageTitle = 'Businesses';
$metaDescription = 'Local businesses near campus including categories and locations.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/businesses.php';
include __DIR__ . '/includes/header.php'; ?>
<?php use Database\DB; ?>

<?php
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$rows = [];
$total = 0;
try {
	$pdo = DB::pdo();
	$params = [];
	$where = ['is_active = 1'];
	if ($q !== '') { $where[] = '(name LIKE ? OR description LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
	if ($city !== '') { $where[] = 'city = ?'; $params[] = $city; }
	if ($category !== '') { $where[] = 'category = ?'; $params[] = $category; }
	$whereSql = implode(' AND ', $where);
	$countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM businesses WHERE $whereSql");
	$countStmt->execute($params);
	$total = (int)($countStmt->fetch()['cnt'] ?? 0);
	$pages = max(1, (int)ceil($total / $limit));
	$stmt = $pdo->prepare("SELECT * FROM businesses WHERE $whereSql ORDER BY updated_at DESC, created_at DESC LIMIT ? OFFSET ?");
	$stmt->execute(array_merge($params, [$limit, $offset]));
	$rows = $stmt->fetchAll();
} catch (\Throwable $e) { $rows = []; $pages = 1; }

// Distinct filters
$cities = []; $categories = [];
try { $cities = DB::pdo()->query('SELECT DISTINCT city FROM businesses WHERE is_active = 1 AND city IS NOT NULL AND city <> "" ORDER BY city ASC')->fetchAll() ?: []; } catch (\Throwable $e) {}
try { $categories = DB::pdo()->query('SELECT DISTINCT category FROM businesses WHERE is_active = 1 AND category IS NOT NULL AND category <> "" ORDER BY category ASC')->fetchAll() ?: []; } catch (\Throwable $e) {}
?>

<h1 class="h4 mb-3">Businesses</h1>
<form class="row g-2 align-items-end mb-3" method="get">
	<div class="col-md-4">
		<label class="form-label">Search</label>
		<input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name or description">
	</div>
	<div class="col-md-4">
		<label class="form-label">City</label>
		<select class="form-select" name="city">
			<option value="">All</option>
			<?php foreach ($cities as $c): $cv = $c['city']; ?>
			<option value="<?= htmlspecialchars($cv) ?>" <?= ($city === $cv ? 'selected' : '') ?>><?= htmlspecialchars($cv) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="col-md-3">
		<label class="form-label">Category</label>
		<select class="form-select" name="category">
			<option value="">All</option>
			<?php foreach ($categories as $cat): $cv = $cat['category']; ?>
			<option value="<?= htmlspecialchars($cv) ?>" <?= ($category === $cv ? 'selected' : '') ?>><?= htmlspecialchars($cv) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="col-md-1">
		<button class="btn btn-primary w-100" type="submit">Go</button>
	</div>
</form>

<div class="row g-3">
	<?php foreach ($rows as $b): ?>
	<div class="col-12 col-md-6 col-lg-4">
		<div class="card h-100">
			<?php if (!empty($b['images'])): $imgs = json_decode($b['images'], true); $thumb = is_array($imgs) && !empty($imgs) ? (string)reset($imgs) : ''; if ($thumb !== ''): ?>
				<img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($b['name']) ?>" class="card-img-top" style="object-fit: cover; height: 160px;">
			<?php endif; endif; ?>
			<div class="card-body">
				<h6 class="mb-1 text-truncate" title="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></h6>
				<div class="text-muted small d-flex justify-content-between"><span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($b['city'] ?? '') ?></span><span><?= htmlspecialchars($b['category'] ?? '') ?></span></div>
			</div>
			<a class="stretched-link" href="business.php?id=<?= (int)$b['id'] ?>"></a>
		</div>
	</div>
	<?php endforeach; ?>
</div>

<?php if ($pages > 1): $baseUrl = 'businesses.php?' . http_build_query(array_filter(['q' => $q !== '' ? $q : null, 'city' => $city !== '' ? $city : null, 'category' => $category !== '' ? $category : null])); ?>
<nav class="mt-3" aria-label="Businesses pagination">
	<ul class="pagination">
		<li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>"><a class="page-link" href="<?= htmlspecialchars($baseUrl . ($baseUrl !== '' ? '&' : '') . 'page=' . max(1, $page - 1)) ?>">Previous</a></li>
		<?php for ($p = 1; $p <= $pages; $p++): ?>
		<li class="page-item <?= ($p === $page ? 'active' : '') ?>"><a class="page-link" href="<?= htmlspecialchars($baseUrl . ($baseUrl !== '' ? '&' : '') . 'page=' . $p) ?>"><?= (int)$p ?></a></li>
		<?php endfor; ?>
		<li class="page-item <?= ($page >= $pages ? 'disabled' : '') ?>"><a class="page-link" href="<?= htmlspecialchars($baseUrl . ($baseUrl !== '' ? '&' : '') . 'page=' . min($pages, $page + 1)) ?>">Next</a></li>
	</ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>


