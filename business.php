<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
<?php if (!(($siteConfig['features']['businesses'] ?? true))): include __DIR__ . '/errors/404.php'; exit; endif; ?>
<?php 
$pageTitle = isset($biz['name']) ? ($biz['name'] . ' - Business') : 'Business';
$metaDescription = !empty($biz['description']) ? substr(strip_tags((string)$biz['description']), 0, 160) : '';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/business.php?id=' . (int)($id ?? 0);
include __DIR__ . '/includes/header.php'; ?>
<?php use Database\DB; ?>

<?php
$id = (int)($_GET['id'] ?? 0);
$biz = null;
try {
	$pdo = DB::pdo();
	$stmt = $pdo->prepare('SELECT * FROM businesses WHERE id = ? AND is_active = 1');
	$stmt->execute([$id]);
	$biz = $stmt->fetch();
} catch (\Throwable $e) { $biz = null; }
if (!$biz) { include __DIR__ . '/errors/404.php'; include __DIR__ . '/includes/footer.php'; exit; }
?>

<article class="mx-auto" style="max-width: 860px;">
	<header class="mb-3">
		<h1 class="h4 mb-1"><?= htmlspecialchars($biz['name']) ?></h1>
		<div class="text-muted small d-flex align-items-center gap-2">
			<span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($biz['city'] ?? '') ?></span>
			<?php if (!empty($biz['category'])): ?><span>·</span><span><?= htmlspecialchars($biz['category']) ?></span><?php endif; ?>
		</div>
	</header>
	<?php if (!empty($biz['description'])): ?>
	<div class="border rounded p-3" style="white-space: pre-wrap;">
		<?= nl2br(htmlspecialchars($biz['description'])) ?>
	</div>
	<?php endif; ?>
    <?php if (!empty($biz['website'])): ?>
    <div class="mt-3"><a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="<?= htmlspecialchars($biz['website']) ?>">Visit website</a></div>
    <?php endif; ?>
</article>

<?php include __DIR__ . '/includes/footer.php'; ?>


