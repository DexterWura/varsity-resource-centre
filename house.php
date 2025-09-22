<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
<?php if (!(($siteConfig['features']['houses'] ?? true))): include __DIR__ . '/errors/404.php'; exit; endif; ?>
<?php 
$pageTitle = isset($house['title']) ? ($house['title'] . ' - House') : 'House';
$metaDescription = !empty($house['description']) ? substr(strip_tags((string)$house['description']), 0, 160) : '';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/house.php?id=' . (int)($id ?? 0);
include __DIR__ . '/includes/header.php'; ?>
<?php use Database\DB; ?>

<?php
$id = (int)($_GET['id'] ?? 0);
$house = null;
try {
	$pdo = DB::pdo();
	$stmt = $pdo->prepare('SELECT * FROM houses WHERE id = ? AND is_active = 1');
	$stmt->execute([$id]);
	$house = $stmt->fetch();
} catch (\Throwable $e) { $house = null; }
if (!$house) { include __DIR__ . '/errors/404.php'; include __DIR__ . '/includes/footer.php'; exit; }
?>

<article class="mx-auto" style="max-width: 860px;">
	<header class="mb-3">
		<h1 class="h4 mb-1"><?= htmlspecialchars($house['title']) ?></h1>
		<div class="text-muted small d-flex align-items-center gap-2">
			<span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($house['city'] ?? '') ?></span>
			<?php if (!empty($house['address'])): ?><span>·</span><span><?= htmlspecialchars($house['address']) ?></span><?php endif; ?>
		</div>
	</header>
	<div class="mb-3">
		<strong>Price:</strong> <?= htmlspecialchars(number_format((float)$house['price'], 2)) ?> <span class="text-muted">/ <?= htmlspecialchars(str_replace('per_', '', $house['price_type'])) ?></span>
	</div>
	<?php if (!empty($house['description'])): ?>
	<div class="border rounded p-3" style="white-space: pre-wrap;">
		<?= nl2br(htmlspecialchars($house['description'])) ?>
	</div>
	<?php endif; ?>
</article>

<?php include __DIR__ . '/includes/footer.php'; ?>


