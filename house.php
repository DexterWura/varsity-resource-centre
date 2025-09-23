<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
<?php if (!(($siteConfig['features']['houses'] ?? true))): include __DIR__ . '/errors/404.php'; exit; endif; ?>
<?php 
$pageTitle = isset($house['title']) ? ($house['title'] . ' - House') : 'House';
$metaDescription = !empty($house['description']) ? substr(strip_tags((string)$house['description']), 0, 160) : '';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/house.php?id=' . (int)($id ?? 0);
include __DIR__ . '/includes/header.php'; ?>
<?php use Database\DB; use Security\Csrf; use Auth\UserAuth; ?>

<?php
// Handle review submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	try {
		$auth = new UserAuth();
		$token = $_POST['csrf'] ?? null;
		if (!Csrf::validate($token)) { throw new \RuntimeException('Invalid CSRF token'); }
		$rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
		$title = trim((string)($_POST['title'] ?? ''));
		$body = trim((string)($_POST['body'] ?? ''));
		if ($rating < 1 || $rating > 5 || $body === '') { throw new \InvalidArgumentException('Invalid input'); }
		$pdo = DB::pdo();
		$id = (int)($_GET['id'] ?? 0);
		$stmt = $pdo->prepare('INSERT INTO house_reviews (house_id, user_id, rating, title, body) VALUES (?, ?, ?, ?, ?)');
		$userId = $auth->check() ? $auth->user()->getId() : null;
		$stmt->execute([$id, $userId, $rating, $title !== '' ? $title : null, $body]);
		header('Location: /house.php?id=' . $id . '#reviews');
		exit;
	} catch (\Throwable $e) {
		// swallow
	}
}

$id = (int)($_GET['id'] ?? 0);
$house = null;
try {
	$pdo = DB::pdo();
	$stmt = $pdo->prepare('SELECT * FROM houses WHERE id = ? AND is_active = 1');
	$stmt->execute([$id]);
	$house = $stmt->fetch();
} catch (\Throwable $e) { $house = null; }
if (!$house) { include __DIR__ . '/errors/404.php'; include __DIR__ . '/includes/footer.php'; exit; }

// Owner and reviews
$owner = null; $reviews = []; $avg = null; $count = 0;
try {
	$ownerStmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE id = ?');
	$ownerStmt->execute([(int)$house['owner_id']]);
	$owner = $ownerStmt->fetch();
	$agg = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM house_reviews WHERE house_id = ?');
	$agg->execute([$id]);
	$aggRow = $agg->fetch();
	$avg = $aggRow ? (float)$aggRow['avg_rating'] : null;
	$count = $aggRow ? (int)$aggRow['cnt'] : 0;
	$revStmt = $pdo->prepare('SELECT hr.*, u.full_name FROM house_reviews hr LEFT JOIN users u ON hr.user_id = u.id WHERE hr.house_id = ? ORDER BY hr.created_at DESC LIMIT 20');
	$revStmt->execute([$id]);
	$reviews = $revStmt->fetchAll() ?: [];
} catch (\Throwable $e) {}
?>

<article class="mx-auto" style="max-width: 860px;">
	<header class="mb-3">
		<h1 class="h4 mb-1"><?= htmlspecialchars($house['title']) ?></h1>
		<div class="text-muted small d-flex align-items-center gap-2">
			<span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($house['city'] ?? '') ?></span>
			<?php if (!empty($house['address'])): ?><span>·</span><span><?= htmlspecialchars($house['address']) ?></span><?php endif; ?>
		</div>
	</header>
	<?php if ($avg !== null): ?>
	<div class="mb-2 small text-muted">Rating: <?= number_format($avg, 1) ?> / 5 (<?= (int)$count ?>)</div>
	<?php endif; ?>
	<div class="mb-3">
		<strong>Price:</strong> <?= htmlspecialchars(number_format((float)$house['price'], 2)) ?> <span class="text-muted">/ <?= htmlspecialchars(str_replace('per_', '', $house['price_type'])) ?></span>
	</div>
	<?php if (!empty($house['description'])): ?>
	<div class="border rounded p-3" style="white-space: pre-wrap;">
		<?= nl2br(htmlspecialchars($house['description'])) ?>
	</div>
	<?php endif; ?>

	<div class="mt-3 d-flex flex-wrap gap-2">
		<?php if (!empty($house['agent_name'])): ?>
		<span class="badge bg-light text-dark">Agent: <?= htmlspecialchars($house['agent_name']) ?></span>
		<?php endif; ?>
	</div>

	<?php if ($owner): ?>
	<div class="mt-4 p-3 border rounded bg-white">
		<div class="small text-muted mb-1">Owner</div>
		<div class="d-flex align-items-center justify-content-between">
			<div><?= htmlspecialchars($owner['full_name'] ?? 'Owner') ?></div>
			<?php if (!empty($owner['email'])): ?><a class="small" href="mailto:<?= htmlspecialchars($owner['email']) ?>"><?= htmlspecialchars($owner['email']) ?></a><?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<hr class="my-4">
	<section id="reviews">
		<div class="d-flex align-items-center justify-content-between mb-2">
			<h2 class="h6 mb-0">Reviews</h2>
			<span class="small text-muted"><?= (int)$count ?> total</span>
		</div>
		<?php if (!empty($reviews)): ?>
		<ul class="list-unstyled mb-3">
			<?php foreach ($reviews as $r): ?>
			<li class="mb-3">
				<div class="d-flex justify-content-between small text-muted">
					<span><?= htmlspecialchars($r['full_name'] ?? 'Anonymous') ?></span>
					<span><?= htmlspecialchars(date('Y-m-d', strtotime((string)$r['created_at']))) ?></span>
				</div>
				<div class="fw-semibold">Rating: <?= (int)$r['rating'] ?>/5<?= !empty($r['title']) ? (' · ' . htmlspecialchars($r['title'])) : '' ?></div>
				<?php if (!empty($r['body'])): ?><div><?= nl2br(htmlspecialchars($r['body'])) ?></div><?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php else: ?>
		<div class="text-muted small mb-3">No reviews yet.</div>
		<?php endif; ?>

		<?php $authTmp = new UserAuth(); $csrfToken = \Security\Csrf::issueToken(); ?>
		<form method="post" class="border rounded p-3">
			<input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
			<div class="row g-2 align-items-end">
				<div class="col-md-2">
					<label class="form-label">Rating</label>
					<select class="form-select" name="rating" required>
						<?php for ($i=5; $i>=1; $i--): ?>
						<option value="<?= $i ?>"><?= $i ?></option>
						<?php endfor; ?>
					</select>
				</div>
				<div class="col-md-5">
					<label class="form-label">Title (optional)</label>
					<input class="form-control" name="title" maxlength="255" placeholder="Short summary">
				</div>
				<div class="col-12">
					<label class="form-label">Your review</label>
					<textarea class="form-control" name="body" rows="3" required placeholder="Share your experience"></textarea>
				</div>
				<div class="col-12">
					<button class="btn btn-primary" type="submit">Submit review</button>
					<?php if (!$authTmp->check()): ?><span class="small text-muted ms-2">Submitting as guest</span><?php endif; ?>
				</div>
			</div>
		</form>
	</section>
</article>

<?php include __DIR__ . '/includes/footer.php'; ?>


