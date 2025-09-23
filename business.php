<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Database\DB;

$businessId = (int)($_GET['id'] ?? 0);
if ($businessId <= 0) {
    header('Location: /businesses.php');
    exit;
}

$business = null;
try {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('SELECT * FROM businesses WHERE id = ? AND is_active = 1');
    $stmt->execute([$businessId]);
    $business = $stmt->fetch();
} catch (\Throwable $e) {
    // Handle error
}

if (!$business) {
    header('Location: /businesses.php');
    exit;
}

$pageTitle = $business['name'];
$metaDescription = $business['description'] ? substr(strip_tags($business['description']), 0, 160) : 'Local business information and details.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/business.php?id=' . $businessId;

include __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Business Images -->
        <?php if (!empty($business['images'])): 
            $images = json_decode($business['images'], true);
            if (is_array($images) && !empty($images)):
        ?>
        <div class="card mb-4">
            <div class="card-body p-0">
                <div id="businessCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($image) ?>" class="d-block w-100" alt="<?= htmlspecialchars($business['name']) ?>" style="height: 400px; object-fit: cover;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#businessCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#businessCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; endif; ?>

        <!-- Business Details -->
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="h3 mb-3"><?= htmlspecialchars($business['name']) ?></h1>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-map-marker-alt text-primary me-2"></i>
                            <span><strong>Location:</strong> <?= htmlspecialchars($business['city'] ?? 'Not specified') ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-tags text-primary me-2"></i>
                            <span><strong>Category:</strong> <?= htmlspecialchars($business['category'] ?? 'Not specified') ?></span>
                        </div>
                        <?php if (!empty($business['phone'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-phone text-primary me-2"></i>
                            <span><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($business['phone']) ?>"><?= htmlspecialchars($business['phone']) ?></a></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($business['email'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-envelope text-primary me-2"></i>
                            <span><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($business['email']) ?>"><?= htmlspecialchars($business['email']) ?></a></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($business['website'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-globe text-primary me-2"></i>
                            <span><strong>Website:</strong> <a href="<?= htmlspecialchars($business['website']) ?>" target="_blank" rel="noopener">Visit Website</a></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($business['address'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-location-dot text-primary me-2"></i>
                            <span><strong>Address:</strong> <?= htmlspecialchars($business['address']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-clock text-primary me-2"></i>
                            <span><strong>Listed:</strong> <?= date('M j, Y', strtotime($business['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($business['description'])): ?>
                <div class="mb-4">
                    <h5>About This Business</h5>
                    <div class="text-muted">
                        <?= nl2br(htmlspecialchars($business['description'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($business['services'])): ?>
                <div class="mb-4">
                    <h5>Services Offered</h5>
                    <div class="text-muted">
                        <?= nl2br(htmlspecialchars($business['services'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($business['hours'])): ?>
                <div class="mb-4">
                    <h5>Business Hours</h5>
                    <div class="text-muted">
                        <?= nl2br(htmlspecialchars($business['hours'])) ?>
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
                
                <?php if (!empty($business['phone'])): ?>
                <div class="d-grid mb-2">
                    <a href="tel:<?= htmlspecialchars($business['phone']) ?>" class="btn btn-outline-primary">
                        <i class="fa-solid fa-phone me-2"></i>Call Now
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($business['email'])): ?>
                <div class="d-grid mb-2">
                    <a href="mailto:<?= htmlspecialchars($business['email']) ?>" class="btn btn-outline-success">
                        <i class="fa-solid fa-envelope me-2"></i>Send Email
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($business['website'])): ?>
                <div class="d-grid mb-2">
                    <a href="<?= htmlspecialchars($business['website']) ?>" target="_blank" rel="noopener" class="btn btn-outline-info">
                        <i class="fa-solid fa-globe me-2"></i>Visit Website
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($business['address'])): ?>
                <div class="d-grid">
                    <a href="https://maps.google.com/?q=<?= urlencode($business['address']) ?>" target="_blank" rel="noopener" class="btn btn-outline-warning">
                        <i class="fa-solid fa-map-marker-alt me-2"></i>Get Directions
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Info -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Quick Info</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fa-solid fa-tags text-primary me-2"></i>
                        <strong>Category:</strong> <?= htmlspecialchars($business['category'] ?? 'Not specified') ?>
                    </li>
                    <li class="mb-2">
                        <i class="fa-solid fa-map-marker-alt text-primary me-2"></i>
                        <strong>Location:</strong> <?= htmlspecialchars($business['city'] ?? 'Not specified') ?>
                    </li>
                    <li class="mb-2">
                        <i class="fa-solid fa-calendar text-primary me-2"></i>
                        <strong>Listed:</strong> <?= date('M j, Y', strtotime($business['created_at'])) ?>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Back to Businesses -->
        <div class="card">
            <div class="card-body text-center">
                <a href="/businesses.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back to Businesses
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>