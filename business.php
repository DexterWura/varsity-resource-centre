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
$owner = null;
$reviews = [];
try {
	$pdo = DB::pdo();
	$stmt = $pdo->prepare('SELECT b.*, u.full_name as owner_name, u.email as owner_email FROM businesses b LEFT JOIN users u ON b.owner_id = u.id WHERE b.id = ? AND b.is_active = 1');
	$stmt->execute([$id]);
	$biz = $stmt->fetch();
	
	if ($biz) {
		// Get reviews for this business
		$reviewStmt = $pdo->prepare('SELECT r.*, u.full_name as reviewer_name FROM business_reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.business_id = ? AND r.is_active = 1 ORDER BY r.created_at DESC LIMIT 10');
		$reviewStmt->execute([$id]);
		$reviews = $reviewStmt->fetchAll();
	}
} catch (\Throwable $e) { $biz = null; }
if (!$biz) { include __DIR__ . '/errors/404.php'; include __DIR__ . '/includes/footer.php'; exit; }
?>

<article class="mx-auto" style="max-width: 860px;">
	<header class="mb-4">
		<h1 class="h3 mb-2"><?= htmlspecialchars($biz['name']) ?></h1>
		<div class="text-muted small d-flex align-items-center gap-2 mb-3">
			<span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($biz['city'] ?? '') ?></span>
			<?php if (!empty($biz['category'])): ?><span>·</span><span class="badge bg-primary"><?= htmlspecialchars($biz['category']) ?></span><?php endif; ?>
		</div>
	</header>

	<div class="row g-4">
		<div class="col-lg-8">
			<?php if (!empty($biz['description'])): ?>
			<div class="card mb-4">
				<div class="card-header">
					<h5 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>About</h5>
				</div>
				<div class="card-body">
					<div style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($biz['description'])) ?></div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Reviews Section -->
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h5 class="mb-0"><i class="fa-solid fa-star me-2"></i>Reviews</h5>
					<button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal">
						<i class="fa-solid fa-plus me-1"></i>Write Review
					</button>
				</div>
				<div class="card-body">
					<?php if (!empty($reviews)): ?>
						<?php foreach ($reviews as $review): ?>
						<div class="review-item border-bottom pb-3 mb-3">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div>
									<strong><?= htmlspecialchars($review['reviewer_name'] ?? 'Anonymous') ?></strong>
									<div class="rating">
										<?php for ($i = 1; $i <= 5; $i++): ?>
											<i class="fa-solid fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
										<?php endfor; ?>
									</div>
								</div>
								<small class="text-muted"><?= date('M j, Y', strtotime($review['created_at'])) ?></small>
							</div>
							<p class="mb-0"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
						</div>
						<?php endforeach; ?>
					<?php else: ?>
						<div class="text-center text-muted py-4">
							<i class="fa-solid fa-comment-dots fa-3x mb-3"></i>
							<p>No reviews yet. Be the first to review this business!</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="col-lg-4">
			<!-- Contact Information -->
			<div class="card mb-4">
				<div class="card-header">
					<h5 class="mb-0"><i class="fa-solid fa-phone me-2"></i>Contact</h5>
				</div>
				<div class="card-body">
					<?php if (!empty($biz['owner_name'])): ?>
					<div class="mb-3">
						<strong>Owner:</strong><br>
						<?= htmlspecialchars($biz['owner_name']) ?>
					</div>
					<?php endif; ?>
					
					<?php if (!empty($biz['contact_phone'])): ?>
					<div class="mb-3">
						<strong>Phone:</strong><br>
						<a href="tel:<?= htmlspecialchars($biz['contact_phone']) ?>" class="text-decoration-none">
							<i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($biz['contact_phone']) ?>
						</a>
					</div>
					<?php endif; ?>
					
					<?php if (!empty($biz['contact_email'])): ?>
					<div class="mb-3">
						<strong>Email:</strong><br>
						<a href="mailto:<?= htmlspecialchars($biz['contact_email']) ?>" class="text-decoration-none">
							<i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($biz['contact_email']) ?>
						</a>
					</div>
					<?php endif; ?>
					
					<?php if (!empty($biz['website'])): ?>
					<div class="mb-3">
						<strong>Website:</strong><br>
						<a href="<?= htmlspecialchars($biz['website']) ?>" target="_blank" rel="noopener" class="text-decoration-none">
							<i class="fa-solid fa-globe me-1"></i> Visit Website
						</a>
					</div>
					<?php endif; ?>
					
					<?php if (!empty($biz['location'])): ?>
					<div class="mb-3">
						<strong>Address:</strong><br>
						<i class="fa-solid fa-map-marker-alt me-1"></i> <?= htmlspecialchars($biz['location']) ?>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Social Media -->
			<?php if (!empty($biz['social_media'])): ?>
			<?php $socialMedia = json_decode($biz['social_media'], true); ?>
			<?php if (is_array($socialMedia) && !empty($socialMedia)): ?>
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0"><i class="fa-solid fa-share-alt me-2"></i>Social Media</h5>
				</div>
				<div class="card-body">
					<?php foreach ($socialMedia as $platform => $url): ?>
						<?php if (!empty($url)): ?>
						<a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm me-2 mb-2">
							<i class="fab fa-<?= htmlspecialchars($platform) ?> me-1"></i> <?= ucfirst($platform) ?>
						</a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</article>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="reviewModalLabel">Write a Review</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form action="submit_review.php" method="POST">
				<div class="modal-body">
					<input type="hidden" name="business_id" value="<?= (int)$biz['id'] ?>">
					<input type="hidden" name="type" value="business">
					
					<div class="mb-3">
						<label for="rating" class="form-label">Rating</label>
						<div class="rating-input">
							<?php for ($i = 1; $i <= 5; $i++): ?>
								<input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" class="d-none">
								<label for="star<?= $i ?>" class="star-label"><i class="fa-solid fa-star"></i></label>
							<?php endfor; ?>
						</div>
					</div>
					
					<div class="mb-3">
						<label for="comment" class="form-label">Your Review</label>
						<textarea class="form-control" name="comment" id="comment" rows="4" placeholder="Share your experience with this business..." required></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary">Submit Review</button>
				</div>
			</form>
		</div>
	</div>
</div>

<style>
.rating-input {
	display: flex;
	gap: 5px;
}

.star-label {
	cursor: pointer;
	font-size: 1.5rem;
	color: #ddd;
	transition: color 0.2s;
}

.star-label:hover,
.star-label:hover ~ .star-label {
	color: #ffc107;
}

.rating-input input:checked ~ .star-label,
.rating-input input:checked ~ .star-label ~ .star-label {
	color: #ffc107;
}

.review-item:last-child {
	border-bottom: none !important;
	margin-bottom: 0 !important;
	padding-bottom: 0 !important;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>


