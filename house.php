<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Database\DB;

$houseId = (int)($_GET['id'] ?? 0);
if ($houseId <= 0) {
    header('Location: /houses.php');
    exit;
}

$house = null;
try {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('SELECT * FROM houses WHERE id = ? AND is_active = 1');
    $stmt->execute([$houseId]);
    $house = $stmt->fetch();
} catch (\Throwable $e) {
    // Handle error
}

if (!$house) {
    header('Location: /houses.php');
    exit;
}

$pageTitle = $house['title'];
$metaDescription = $house['description'] ? substr(strip_tags($house['description']), 0, 160) : 'Off-campus accommodation listing with details and pricing.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/house.php?id=' . $houseId;

include __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <!-- House Images -->
        <?php if (!empty($house['images'])): 
            $images = json_decode($house['images'], true);
            if (is_array($images) && !empty($images)):
        ?>
        <div class="card mb-4">
            <div class="card-body p-0">
                <div id="houseCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($image) ?>" class="d-block w-100" alt="<?= htmlspecialchars($house['title']) ?>" style="height: 400px; object-fit: cover;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#houseCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#houseCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; endif; ?>

        <!-- House Details -->
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="h3 mb-3"><?= htmlspecialchars($house['title']) ?></h1>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-map-marker-alt text-primary me-2"></i>
                            <span><strong>Location:</strong> <?= htmlspecialchars($house['city'] ?? 'Not specified') ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-dollar-sign text-primary me-2"></i>
                            <span><strong>Price:</strong> $<?= htmlspecialchars(number_format((float)$house['price'], 2)) ?> / <?= htmlspecialchars(str_replace('per_', '', $house['price_type'])) ?></span>
                        </div>
                        <?php if (!empty($house['bedrooms'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-bed text-primary me-2"></i>
                            <span><strong>Bedrooms:</strong> <?= htmlspecialchars($house['bedrooms']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($house['bathrooms'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-bath text-primary me-2"></i>
                            <span><strong>Bathrooms:</strong> <?= htmlspecialchars($house['bathrooms']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($house['property_type'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-home text-primary me-2"></i>
                            <span><strong>Type:</strong> <?= htmlspecialchars($house['property_type']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($house['address'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-location-dot text-primary me-2"></i>
                            <span><strong>Address:</strong> <?= htmlspecialchars($house['address']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-calendar text-primary me-2"></i>
                            <span><strong>Listed:</strong> <?= date('M j, Y', strtotime($house['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($house['description'])): ?>
                <div class="mb-4">
                    <h5>Description</h5>
                    <div class="text-muted">
                        <?= nl2br(htmlspecialchars($house['description'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($house['amenities'])): ?>
                <div class="mb-4">
                    <h5>Amenities</h5>
                    <div class="text-muted">
                        <?= nl2br(htmlspecialchars($house['amenities'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($house['rules'])): ?>
                <div class="mb-4">
                    <h5>House Rules</h5>
                    <div class="text-muted">
                        <?= nl2br(htmlspecialchars($house['rules'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Contact Card -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Contact Information</h5>
                
                <?php if (!empty($house['contact_phone'])): ?>
                <div class="d-grid mb-2">
                    <a href="tel:<?= htmlspecialchars($house['contact_phone']) ?>" class="btn btn-outline-primary">
                        <i class="fa-solid fa-phone me-2"></i>Call Now
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($house['contact_email'])): ?>
                <div class="d-grid mb-2">
                    <a href="mailto:<?= htmlspecialchars($house['contact_email']) ?>" class="btn btn-outline-success">
                        <i class="fa-solid fa-envelope me-2"></i>Send Email
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($house['contact_name'])): ?>
                <div class="mb-3">
                    <strong>Contact Person:</strong><br>
                    <span class="text-muted"><?= htmlspecialchars($house['contact_name']) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($house['address'])): ?>
                <div class="d-grid">
                    <a href="https://maps.google.com/?q=<?= urlencode($house['address']) ?>" target="_blank" rel="noopener" class="btn btn-outline-warning">
                        <i class="fa-solid fa-map-marker-alt me-2"></i>Get Directions
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Price Card -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <h5 class="card-title">Rental Price</h5>
                <div class="display-6 text-primary mb-2">
                    $<?= htmlspecialchars(number_format((float)$house['price'], 2)) ?>
                </div>
                <div class="text-muted">
                    per <?= htmlspecialchars(str_replace('per_', '', $house['price_type'])) ?>
                </div>
                <?php if (!empty($house['deposit'])): ?>
                <div class="mt-3">
                    <small class="text-muted">
                        <strong>Deposit:</strong> $<?= htmlspecialchars(number_format((float)$house['deposit'], 2)) ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Property Details -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Property Details</h5>
                <ul class="list-unstyled">
                    <?php if (!empty($house['bedrooms'])): ?>
                    <li class="mb-2">
                        <i class="fa-solid fa-bed text-primary me-2"></i>
                        <strong>Bedrooms:</strong> <?= htmlspecialchars($house['bedrooms']) ?>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($house['bathrooms'])): ?>
                    <li class="mb-2">
                        <i class="fa-solid fa-bath text-primary me-2"></i>
                        <strong>Bathrooms:</strong> <?= htmlspecialchars($house['bathrooms']) ?>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($house['property_type'])): ?>
                    <li class="mb-2">
                        <i class="fa-solid fa-home text-primary me-2"></i>
                        <strong>Type:</strong> <?= htmlspecialchars($house['property_type']) ?>
                    </li>
                    <?php endif; ?>
                    <li class="mb-2">
                        <i class="fa-solid fa-map-marker-alt text-primary me-2"></i>
                        <strong>Location:</strong> <?= htmlspecialchars($house['city'] ?? 'Not specified') ?>
                    </li>
                    <li class="mb-2">
                        <i class="fa-solid fa-calendar text-primary me-2"></i>
                        <strong>Listed:</strong> <?= date('M j, Y', strtotime($house['created_at'])) ?>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Back to Houses -->
        <div class="card">
            <div class="card-body text-center">
                <a href="/houses.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back to Houses
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>