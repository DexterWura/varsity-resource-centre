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
$owner = null;
$reviews = [];
try {
	$pdo = DB::pdo();
	$stmt = $pdo->prepare('SELECT h.*, u.full_name as owner_name, u.email as owner_email FROM houses h LEFT JOIN users u ON h.owner_id = u.id WHERE h.id = ? AND h.is_active = 1');
	$stmt->execute([$id]);
	$house = $stmt->fetch();
	
	if ($house) {
		// Get reviews for this house
		$reviewStmt = $pdo->prepare('SELECT r.*, u.full_name as reviewer_name FROM house_reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.house_id = ? AND r.is_active = 1 ORDER BY r.created_at DESC LIMIT 10');
		$reviewStmt->execute([$id]);
		$reviews = $reviewStmt->fetchAll();
	}
} catch (\Throwable $e) { $house = null; }
if (!$house) { include __DIR__ . '/errors/404.php'; include __DIR__ . '/includes/footer.php'; exit; }
?>

<article class="mx-auto" style="max-width: 860px;">
	<header class="mb-4">
		<h1 class="h3 mb-2"><?= htmlspecialchars($house['title']) ?></h1>
		<div class="text-muted small d-flex align-items-center gap-2 mb-3">
			<span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($house['city'] ?? '') ?></span>
			<?php if (!empty($house['address'])): ?><span>·</span><span><?= htmlspecialchars($house['address']) ?></span><?php endif; ?>
		</div>
		<div class="alert alert-info">
			<strong>Price:</strong> $<?= htmlspecialchars(number_format((float)$house['price'], 2)) ?> 
			<span class="text-muted">/ <?= htmlspecialchars(str_replace('per_', '', $house['price_type'])) ?></span>
		</div>
	</header>

	<div class="row g-4">
		<div class="col-lg-8">
			<?php if (!empty($house['description'])): ?>
			<div class="card mb-4">
				<div class="card-header">
					<h5 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Description</h5>
				</div>
				<div class="card-body">
					<div style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($house['description'])) ?></div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Property Details -->
			<div class="card mb-4">
				<div class="card-header">
					<h5 class="mb-0"><i class="fa-solid fa-home me-2"></i>Property Details</h5>
				</div>
				<div class="card-body">
					<div class="row g-3">
						<?php if (!empty($house['bedrooms'])): ?>
						<div class="col-md-6">
							<strong>Bedrooms:</strong> <?= (int)$house['bedrooms'] ?>
						</div>
						<?php endif; ?>
						<?php if (!empty($house['bathrooms'])): ?>
						<div class="col-md-6">
							<strong>Bathrooms:</strong> <?= (int)$house['bathrooms'] ?>
						</div>
						<?php endif; ?>
						<?php if (!empty($house['university'])): ?>
						<div class="col-md-6">
							<strong>University:</strong> <?= htmlspecialchars($house['university']) ?>
						</div>
						<?php endif; ?>
						<?php if (!empty($house['campus'])): ?>
						<div class="col-md-6">
							<strong>Campus:</strong> <?= htmlspecialchars($house['campus']) ?>
						</div>
						<?php endif; ?>
					</div>
					
					<?php if (!empty($house['amenities'])): ?>
					<?php $amenities = json_decode($house['amenities'], true); ?>
					<?php if (is_array($amenities) && !empty($amenities)): ?>
					<div class="mt-3">
						<strong>Amenities:</strong>
						<div class="d-flex flex-wrap gap-2 mt-2">
							<?php foreach ($amenities as $amenity): ?>
								<span class="badge bg-light text-dark"><?= htmlspecialchars($amenity) ?></span>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

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
							<p>No reviews yet. Be the first to review this property!</p>
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
					<?php if (!empty($house['owner_name'])): ?>
					<div class="mb-3">
						<strong>Owner:</strong><br>
						<?= htmlspecialchars($house['owner_name']) ?>
					</div>
					<?php endif; ?>
					
					<?php if (!empty($house['owner_email'])): ?>
					<div class="mb-3">
						<strong>Email:</strong><br>
						<a href="mailto:<?= htmlspecialchars($house['owner_email']) ?>" class="text-decoration-none">
							<i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($house['owner_email']) ?>
						</a>
					</div>
					<?php endif; ?>
					
					<?php if (!empty($house['address'])): ?>
					<div class="mb-3">
						<strong>Address:</strong><br>
						<i class="fa-solid fa-map-marker-alt me-1"></i> <?= htmlspecialchars($house['address']) ?>
					</div>
					<?php endif; ?>
					
					<?php if ($house['is_agent'] && !empty($house['agent_name'])): ?>
					<div class="mb-3">
						<strong>Agent:</strong><br>
						<?= htmlspecialchars($house['agent_name']) ?>
					</div>
					<?php endif; ?>
					
					<div class="d-grid">
						<button class="btn btn-primary" onclick="contactOwner()">
							<i class="fa-solid fa-envelope me-1"></i> Contact Owner
						</button>
					</div>
				</div>
			</div>

			<!-- Property Status -->
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Status</h5>
				</div>
				<div class="card-body">
					<?php if ($house['is_booked']): ?>
						<div class="alert alert-warning">
							<i class="fa-solid fa-exclamation-triangle me-1"></i> This property is currently booked
						</div>
					<?php else: ?>
						<div class="alert alert-success">
							<i class="fa-solid fa-check-circle me-1"></i> Available for rent
						</div>
					<?php endif; ?>
					
					<?php if (!empty($house['expires_at'])): ?>
					<div class="small text-muted">
						<strong>Listing expires:</strong><br>
						<?= date('M j, Y', strtotime($house['expires_at'])) ?>
					</div>
					<?php endif; ?>
				</div>
			</div>
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
					<input type="hidden" name="house_id" value="<?= (int)$house['id'] ?>">
					<input type="hidden" name="type" value="house">
					
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
						<textarea class="form-control" name="comment" id="comment" rows="4" placeholder="Share your experience with this property..." required></textarea>
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

<script>
function contactOwner() {
	<?php if (!empty($house['owner_email'])): ?>
		window.location.href = 'mailto:<?= htmlspecialchars($house['owner_email']) ?>?subject=Inquiry about <?= urlencode($house['title']) ?>';
	<?php else: ?>
		alert('Contact information not available');
	<?php endif; ?>
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


