<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
<?php if (!(($siteConfig['features']['houses'] ?? true))): include __DIR__ . '/errors/404.php'; exit; endif; ?>
<?php 
$pageTitle = 'Houses';
$metaDescription = 'Off-campus accommodation listings with pricing and locations.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/houses.php';
include __DIR__ . '/includes/header.php'; ?>
<?php use Database\DB; ?>

<?php
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$rows = [];
$total = 0;
try {
	$pdo = DB::pdo();
	$params = [];
	$where = ['is_active = 1'];
	if ($q !== '') { $where[] = '(title LIKE ? OR address LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
	if ($city !== '') { $where[] = 'city = ?'; $params[] = $city; }
	$whereSql = implode(' AND ', $where);
	$countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM houses WHERE $whereSql");
	$countStmt->execute($params);
	$total = (int)($countStmt->fetch()['cnt'] ?? 0);
	$pages = max(1, (int)ceil($total / $limit));
	$stmt = $pdo->prepare("SELECT * FROM houses WHERE $whereSql ORDER BY updated_at DESC, created_at DESC LIMIT ? OFFSET ?");
	$stmt->execute(array_merge($params, [$limit, $offset]));
	$rows = $stmt->fetchAll();
} catch (\Throwable $e) { $rows = []; $pages = 1; }

// Distinct cities for filter
$cities = [];
try { $cities = DB::pdo()->query('SELECT DISTINCT city FROM houses WHERE is_active = 1 AND city IS NOT NULL AND city <> "" ORDER BY city ASC')->fetchAll() ?: []; } catch (\Throwable $e) { $cities = []; }
?>

<h1 class="h4 mb-3">Houses</h1>
<form class="row g-2 align-items-end mb-3" method="get">
	<div class="col-md-6">
		<label class="form-label">Search</label>
		<input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search title or address">
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
	<div class="col-md-2">
		<button class="btn btn-primary w-100" type="submit">Filter</button>
	</div>
</form>

<div class="row g-3">
	<?php foreach ($rows as $h): ?>
	<div class="col-12 col-md-6 col-lg-4">
		<div class="card h-100">
			<?php if (!empty($h['images'])): $imgs = json_decode($h['images'], true); $thumb = is_array($imgs) && !empty($imgs) ? (string)reset($imgs) : ''; if ($thumb !== ''): ?>
				<img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($h['title']) ?>" class="card-img-top" style="object-fit: cover; height: 160px;">
			<?php endif; endif; ?>
			<div class="card-body">
				<h6 class="mb-1 text-truncate" title="<?= htmlspecialchars($h['title']) ?>"><?= htmlspecialchars($h['title']) ?></h6>
				<div class="text-muted small d-flex justify-content-between"><span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($h['city'] ?? '') ?></span><span><?= htmlspecialchars(number_format((float)$h['price'], 2)) ?> <span class="text-lowercase">/ <?= htmlspecialchars(str_replace('per_', '', $h['price_type'])) ?></span></span></div>
			</div>
			<a class="stretched-link" href="house.php?id=<?= (int)$h['id'] ?>"></a>
		</div>
	</div>
	<?php endforeach; ?>
</div>

<?php if ($pages > 1): $baseUrl = 'houses.php?' . http_build_query(array_filter(['q' => $q !== '' ? $q : null, 'city' => $city !== '' ? $city : null])); ?>
<nav class="mt-3" aria-label="Houses pagination">
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


