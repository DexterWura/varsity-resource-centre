<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Database\DB;

// Get search query
$query = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? ''); // Optional: force search type

if (empty($query)) {
    header('Location: /');
    exit;
}

// Load feature configuration
$siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : [];
$features = $siteConfig['features'] ?? [
    'articles' => true,
    'houses' => true,
    'businesses' => true,
    'news' => true,
    'jobs' => true,
    'timetable' => true,
];

// If a specific type is requested and that feature is enabled, redirect directly
if (!empty($type) && isset($features[$type]) && $features[$type]) {
    $redirectUrl = match($type) {
        'articles' => '/articles.php?q=' . urlencode($query),
        'houses' => '/houses.php?q=' . urlencode($query),
        'businesses' => '/businesses.php?q=' . urlencode($query),
        'news' => '/news.php?q=' . urlencode($query),
        'jobs' => '/jobs.php?q=' . urlencode($query),
        'timetable' => '/timetable.php?q=' . urlencode($query),
        default => null
    };
    
    if ($redirectUrl) {
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// Search across all enabled features
$results = [
    'articles' => [],
    'houses' => [],
    'businesses' => [],
    'news' => [],
    'jobs' => [],
    'timetable' => []
];

try {
    $pdo = DB::pdo();
    
    // Search articles
    if ($features['articles'] ?? true) {
        $stmt = $pdo->prepare('
            SELECT a.*, u.full_name as author_name
            FROM articles a
            JOIN users u ON a.author_id = u.id
            WHERE a.status = "published" AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?)
            ORDER BY a.published_at DESC
            LIMIT 5
        ');
        $like = '%' . $query . '%';
        $stmt->execute([$like, $like, $like]);
        $results['articles'] = $stmt->fetchAll();
    }
    
    // Search houses
    if ($features['houses'] ?? true) {
        $stmt = $pdo->prepare('
            SELECT * FROM houses 
            WHERE is_active = 1 AND (title LIKE ? OR address LIKE ? OR city LIKE ?)
            ORDER BY updated_at DESC, created_at DESC
            LIMIT 5
        ');
        $like = '%' . $query . '%';
        $stmt->execute([$like, $like, $like]);
        $results['houses'] = $stmt->fetchAll();
    }
    
    // Search businesses
    if ($features['businesses'] ?? true) {
        $stmt = $pdo->prepare('
            SELECT * FROM businesses 
            WHERE is_active = 1 AND (name LIKE ? OR description LIKE ? OR category LIKE ? OR city LIKE ?)
            ORDER BY updated_at DESC, created_at DESC
            LIMIT 5
        ');
        $like = '%' . $query . '%';
        $stmt->execute([$like, $like, $like, $like]);
        $results['businesses'] = $stmt->fetchAll();
    }
    
    // Search jobs (from database)
    if ($features['jobs'] ?? true) {
        $stmt = $pdo->prepare('
            SELECT * FROM jobs 
            WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) 
            AND (title LIKE ? OR company_name LIKE ? OR location LIKE ? OR description LIKE ?)
            ORDER BY id DESC
            LIMIT 5
        ');
        $like = '%' . $query . '%';
        $stmt->execute([$like, $like, $like, $like]);
        $results['jobs'] = $stmt->fetchAll();
    }
    
} catch (\Throwable $e) {
    // Handle database errors gracefully
    error_log('Search error: ' . $e->getMessage());
}

// For news, we'll show a link to search news directly since it uses external API
// For timetable, we'll show a link to the timetable page

$pageTitle = 'Search Results for "' . htmlspecialchars($query) . '"';
$metaDescription = 'Search results across all available features for: ' . htmlspecialchars($query);
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/search.php?q=' . urlencode($query);

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h4 mb-3">Search Results for "<?= htmlspecialchars($query) ?>"</h1>
            
            <!-- Search again -->
            <form class="mb-4" method="get" action="/search.php">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" class="form-control" placeholder="Search again...">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
            
            <?php
            $totalResults = 0;
            foreach ($results as $type => $items) {
                if (!empty($items)) {
                    $totalResults += count($items);
                }
            }
            
            // Add news and timetable as available options
            if ($features['news'] ?? true) $totalResults++;
            if ($features['timetable'] ?? true) $totalResults++;
            ?>
            
            <p class="text-muted mb-4">Found <?= $totalResults ?> results across all features</p>
            
            <!-- Articles Results -->
            <?php if (!empty($results['articles'])): ?>
            <div class="mb-4">
                <h5 class="mb-3">
                    <i class="fa-regular fa-newspaper me-2"></i>Articles
                    <span class="badge bg-primary ms-2"><?= count($results['articles']) ?></span>
                </h5>
                <div class="row g-3">
                    <?php foreach ($results['articles'] as $article): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2"><?= htmlspecialchars($article['title']) ?></h6>
                                <?php if (!empty($article['excerpt'])): ?>
                                <p class="card-text small text-muted"><?= htmlspecialchars(substr($article['excerpt'], 0, 100)) ?>...</p>
                                <?php endif; ?>
                                <div class="small text-muted">
                                    By <?= htmlspecialchars($article['author_name'] ?? 'Unknown') ?>
                                    • <?= date('M j, Y', strtotime($article['published_at'])) ?>
                                </div>
                            </div>
                            <a class="stretched-link" href="/article.php?slug=<?= urlencode($article['slug']) ?>"></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <a href="/articles.php?q=<?= urlencode($query) ?>" class="btn btn-outline-primary btn-sm">
                        View all articles for "<?= htmlspecialchars($query) ?>"
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Houses Results -->
            <?php if (!empty($results['houses'])): ?>
            <div class="mb-4">
                <h5 class="mb-3">
                    <i class="fa-solid fa-house me-2"></i>Houses
                    <span class="badge bg-success ms-2"><?= count($results['houses']) ?></span>
                </h5>
                <div class="row g-3">
                    <?php foreach ($results['houses'] as $house): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2"><?= htmlspecialchars($house['title']) ?></h6>
                                <div class="d-flex justify-content-between align-items-center text-muted small">
                                    <span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($house['city'] ?? '') ?></span>
                                    <span>
                                        <?= htmlspecialchars(number_format((float)$house['price'], 2)) ?>
                                        <span class="text-lowercase">/ <?= htmlspecialchars(str_replace('per_', '', $house['price_type'])) ?></span>
                                    </span>
                                </div>
                            </div>
                            <a class="stretched-link" href="/house.php?id=<?= (int)$house['id'] ?>"></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <a href="/houses.php?q=<?= urlencode($query) ?>" class="btn btn-outline-success btn-sm">
                        View all houses for "<?= htmlspecialchars($query) ?>"
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Businesses Results -->
            <?php if (!empty($results['businesses'])): ?>
            <div class="mb-4">
                <h5 class="mb-3">
                    <i class="fa-solid fa-store me-2"></i>Businesses
                    <span class="badge bg-info ms-2"><?= count($results['businesses']) ?></span>
                </h5>
                <div class="row g-3">
                    <?php foreach ($results['businesses'] as $business): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2"><?= htmlspecialchars($business['name']) ?></h6>
                                <div class="d-flex justify-content-between align-items-center text-muted small">
                                    <span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($business['city'] ?? '') ?></span>
                                    <span><?= htmlspecialchars($business['category'] ?? '') ?></span>
                                </div>
                            </div>
                            <a class="stretched-link" href="/business.php?id=<?= (int)$business['id'] ?>"></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <a href="/businesses.php?q=<?= urlencode($query) ?>" class="btn btn-outline-info btn-sm">
                        View all businesses for "<?= htmlspecialchars($query) ?>"
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Jobs Results -->
            <?php if (!empty($results['jobs'])): ?>
            <div class="mb-4">
                <h5 class="mb-3">
                    <i class="fa-solid fa-briefcase me-2"></i>Jobs
                    <span class="badge bg-warning ms-2"><?= count($results['jobs']) ?></span>
                </h5>
                <div class="row g-3">
                    <?php foreach ($results['jobs'] as $job): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2"><?= htmlspecialchars($job['title']) ?></h6>
                                <div class="text-muted small">
                                    <?= htmlspecialchars($job['company_name'] ?? 'Company') ?> • <?= htmlspecialchars($job['location'] ?? 'Location') ?>
                                </div>
                            </div>
                            <?php if (!empty($job['url'])): ?>
                            <a class="stretched-link" href="<?= htmlspecialchars($job['url']) ?>" target="_blank" rel="noopener"></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <a href="/jobs.php" class="btn btn-outline-warning btn-sm">
                        View all jobs
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Additional Search Options -->
            <div class="mb-4">
                <h5 class="mb-3">More Search Options</h5>
                <div class="row g-3">
                    <?php if ($features['news'] ?? true): ?>
                    <div class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa-solid fa-bolt text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6 class="card-title">Student News</h6>
                                <p class="card-text small text-muted">Search for student news and updates</p>
                                <a href="/news.php?q=<?= urlencode($query) ?>" class="btn btn-outline-primary btn-sm">
                                    Search News
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($features['timetable'] ?? true): ?>
                    <div class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fa-regular fa-calendar text-success mb-2" style="font-size: 2rem;"></i>
                                <h6 class="card-title">Timetables</h6>
                                <p class="card-text small text-muted">Find module timetables and schedules</p>
                                <a href="/timetable.php" class="btn btn-outline-success btn-sm">
                                    Search Timetables
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($totalResults === 0): ?>
            <div class="text-center py-5">
                <i class="fa-solid fa-search text-muted mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted">No results found</h5>
                <p class="text-muted">Try different keywords or check the spelling of your search terms.</p>
                <a href="/" class="btn btn-primary">Back to Home</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
