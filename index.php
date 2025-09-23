<?php include __DIR__ . '/includes/header.php'; ?>
<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); if ($base === '') { $base = ''; }
// Collect logos from assets/images for hero animation
$logoFiles = glob(__DIR__ . '/assets/images/*.{png,jpg,jpeg,svg,gif}', GLOB_BRACE) ?: [];
// Prepare up to 6 logos with random positions and delays
$heroLogos = [];
foreach (array_slice($logoFiles, 0, 6) as $i => $path) {
    $heroLogos[] = [
        'src' => $base . '/assets/images/' . basename($path),
        'alt' => pathinfo($path, PATHINFO_FILENAME),
        'left' => rand(5, 80) . '%',
        'top' => rand(5, 80) . '%',
        'delay' => (0.2 * $i) . 's',
        'duration' => (6 + $i) . 's',
    ];
}
?>

    <section class="p-4 p-md-5 mb-4 rounded-3 hero-gradient fade-in position-relative overflow-hidden">
        <!-- Animated background elements -->
        <div class="hero-bg-animation">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>
        
        <div class="container-fluid py-2 position-relative">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <h1 class="display-6 mb-2 hero-title">Everything students need, in one place</h1>
                    <p class="col-lg-10 fs-6 text-muted mb-3 hero-subtitle">Timetables, jobs, articles, and campus news tailored for Zimbabwean universities.</p>
                    <form class="hero-search" action="<?= htmlspecialchars($base) ?>/search.php" method="get">
                        <div class="input-group input-group-lg hero-search-box">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Search everything: houses, jobs, articles, businesses..." aria-label="Search">
                            <button class="btn btn-primary hero-search-btn" type="submit">Search</button>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2 small hero-pills">
                            <span class="text-muted">Popular:</span>
                            <?php if (($siteConfig['features']['jobs'] ?? true)): ?><a href="<?= htmlspecialchars($base) ?>/jobs.php" class="category-pill pill-animate">Jobs</a><?php endif; ?>
                            <?php if (($siteConfig['features']['businesses'] ?? true)): ?><a href="<?= htmlspecialchars($base) ?>/businesses.php" class="category-pill pill-animate">Businesses</a><?php endif; ?>
                            <?php if (($siteConfig['features']['houses'] ?? true)): ?><a href="<?= htmlspecialchars($base) ?>/houses.php" class="category-pill pill-animate">Accommodation</a><?php endif; ?>
                            <?php if (($siteConfig['features']['articles'] ?? true)): ?><a href="<?= htmlspecialchars($base) ?>/articles.php" class="category-pill pill-animate">Articles</a><?php endif; ?>
                            <?php if (($siteConfig['features']['news'] ?? true)): ?><a href="<?= htmlspecialchars($base) ?>/news.php" class="category-pill pill-animate">News</a><?php endif; ?>
                            
                            <!-- Tools Dropdown -->
                            <div class="dropdown d-inline-block">
                                <a class="category-pill pill-animate dropdown-toggle" href="#" role="button" id="toolsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-tools me-1"></i>Tools
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="toolsDropdown">
                                    <?php if (($siteConfig['features']['timetable'] ?? true)): ?>
                                    <li><a class="dropdown-item" href="<?= htmlspecialchars($base) ?>/timetable.php">
                                        <i class="fa-regular fa-calendar me-2"></i>Timetable
                                    </a></li>
                                    <?php endif; ?>
                                    
                                    <?php if (($siteConfig['features']['plagiarism_checker'] ?? false)): ?>
                                    <li><a class="dropdown-item" href="<?= htmlspecialchars($base) ?>/plagiarism-checker.php">
                                        <i class="fa-solid fa-search me-2"></i>Pro Plagiarism Checker
                                    </a></li>
                                    <?php endif; ?>
                                    
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= htmlspecialchars($base) ?>/resume.php">
                                        <i class="fa-regular fa-file-lines me-2"></i>Resume Builder
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center logo-cloud position-relative">
                    <?php foreach ($heroLogos as $logo): ?>
                        <img class="logo-float" src="<?= htmlspecialchars($logo['src']) ?>" alt="<?= htmlspecialchars($logo['alt']) ?>" style="left: <?= htmlspecialchars($logo['left']) ?>; top: <?= htmlspecialchars($logo['top']) ?>; animation-delay: <?= htmlspecialchars($logo['delay']) ?>; animation-duration: <?= htmlspecialchars($logo['duration']) ?>;">
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section (only for non-logged-in users) -->
    <?php 
    use Auth\UserAuth;
    $userAuth = new UserAuth();
    if (!$userAuth->check()): 
    ?>
    <section class="mb-4 section-animate">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <h3 class="h4 mb-3">Join the Varsity Community</h3>
                <p class="text-muted mb-4">Get personalized job recommendations, save your favorite articles, and connect with other students.</p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="<?= htmlspecialchars($base) ?>/register.php" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-user-plus me-2"></i>Create Free Account
                    </a>
                    <a href="<?= htmlspecialchars($base) ?>/login.php" class="btn btn-outline-primary btn-lg">
                        <i class="fa-solid fa-sign-in-alt me-2"></i>Already have an account?
                    </a>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fa-solid fa-shield-alt me-1"></i>
                        Free to join • No spam • Unsubscribe anytime
                    </small>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
    <?php if (($siteConfig['features']['articles'] ?? true)): ?>
    <section class="mb-4 section-animate">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Explore categories</h2>
            <a href="<?= htmlspecialchars($base) ?>/articles.php" class="btn btn-sm pill-outline pill-btn">See more</a>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php $siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : []; ?>
            <?php if (($siteConfig['features']['timetable'] ?? true)): ?><a class="btn btn-light pill-btn category-btn" href="<?= htmlspecialchars($base) ?>/timetable.php"><i class="fa-regular fa-calendar me-1"></i> Timetables</a><?php endif; ?>
            <?php if (($siteConfig['features']['jobs'] ?? true)): ?><a class="btn btn-light pill-btn category-btn" href="<?= htmlspecialchars($base) ?>/jobs.php"><i class="fa-solid fa-briefcase me-1"></i> Jobs</a><?php endif; ?>
            <?php if (($siteConfig['features']['articles'] ?? true)): ?><a class="btn btn-light pill-btn category-btn" href="<?= htmlspecialchars($base) ?>/articles.php"><i class="fa-regular fa-newspaper me-1"></i> Articles</a><?php endif; ?>
            <?php if (($siteConfig['features']['news'] ?? true)): ?><a class="btn btn-light pill-btn category-btn" href="<?= htmlspecialchars($base) ?>/news.php"><i class="fa-solid fa-bolt me-1"></i> Student News</a><?php endif; ?>
            <?php if (($siteConfig['features']['houses'] ?? true)): ?><a class="btn btn-light pill-btn category-btn" href="<?= htmlspecialchars($base) ?>/houses.php"><i class="fa-solid fa-house me-1"></i> Accommodation</a><?php endif; ?>
            <?php if (($siteConfig['features']['businesses'] ?? true)): ?><a class="btn btn-light pill-btn category-btn" href="<?= htmlspecialchars($base) ?>/businesses.php"><i class="fa-solid fa-store me-1"></i> Businesses</a><?php endif; ?>
            <a class="btn btn-light pill-btn category-btn" href="<?= htmlspecialchars($base) ?>/resume.php"><i class="fa-regular fa-file-lines me-1"></i> Resume</a>
        </div>
    </section>
    <?php endif; ?>

    <?php if (($siteConfig['features']['houses'] ?? true)): ?>
    <section class="mb-4 section-animate">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Popular now</h2>
            <a href="<?= htmlspecialchars($base) ?>/articles.php" class="btn btn-sm pill-outline pill-btn">See all articles</a>
        </div>
        <div class="row g-3">
            <?php
            try {
                $pdo = \Database\DB::pdo();
                // Popular articles (internal published)
                $popularArticles = $pdo->query('SELECT title, slug, excerpt, published_at FROM articles WHERE status = "published" ORDER BY published_at DESC LIMIT 6')->fetchAll() ?: [];
                // Popular houses (active)
                $popularHouses = $pdo->query('SELECT id, title, city, price, price_type FROM houses WHERE is_active = 1 ORDER BY updated_at DESC, created_at DESC LIMIT 4')->fetchAll() ?: [];
                // Local businesses (active)
                $popularBusinesses = $pdo->query('SELECT id, name, category, city FROM businesses WHERE is_active = 1 ORDER BY updated_at DESC, created_at DESC LIMIT 4')->fetchAll() ?: [];
                // Popular locations by house count
                $popularLocations = $pdo->query('SELECT city, COUNT(*) as cnt FROM houses WHERE is_active = 1 AND city IS NOT NULL AND city <> "" GROUP BY city ORDER BY cnt DESC LIMIT 6')->fetchAll() ?: [];
            } catch (\Throwable $e) {
                $popularArticles = $popularHouses = $popularBusinesses = $popularLocations = [];
            }
            ?>

            <?php foreach ($popularArticles as $a): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card gig-card h-100 article-card">
                    <div class="card-body">
                        <div class="small text-muted mb-1">Article</div>
                        <h6 class="mb-1 text-truncate" title="<?= htmlspecialchars($a['title']) ?>"><?= htmlspecialchars($a['title']) ?></h6>
                        <?php if (!empty($a['excerpt'])): ?>
                        <div class="text-muted small text-truncate"><?= htmlspecialchars($a['excerpt']) ?></div>
                        <?php endif; ?>
                    </div>
                    <a class="stretched-link" href="<?= htmlspecialchars($base) ?>/article.php?slug=<?= urlencode($a['slug']) ?>"></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (($siteConfig['features']['houses'] ?? true)): ?>
    <section class="mb-4 section-animate">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Popular houses</h2>
            <a href="<?= htmlspecialchars($base) ?>/houses.php" class="btn btn-sm pill-outline pill-btn">Browse all</a>
        </div>
        <div class="row g-3">
            <?php foreach ($popularHouses as $h): ?>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card gig-card h-100 house-card">
                    <div class="card-body">
                        <div class="small text-muted mb-1">Off-campus</div>
                        <h6 class="mb-2 text-truncate" title="<?= htmlspecialchars($h['title']) ?>"><?= htmlspecialchars($h['title']) ?></h6>
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($h['city'] ?? '') ?></span>
                            <span>
                                <?= htmlspecialchars(number_format((float)$h['price'], 2)) ?>
                                <span class="text-lowercase">/ <?= htmlspecialchars(str_replace('per_', '', $h['price_type'])) ?></span>
                            </span>
                        </div>
                    </div>
                    <a class="stretched-link" href="<?= htmlspecialchars($base) ?>/house.php?id=<?= (int)$h['id'] ?>"></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (($siteConfig['features']['businesses'] ?? true)): ?>
    <section class="mb-4 section-animate">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Local businesses</h2>
            <a href="<?= htmlspecialchars($base) ?>/businesses.php" class="btn btn-sm pill-outline pill-btn">Browse all</a>
        </div>
        <div class="row g-3">
            <?php foreach ($popularBusinesses as $b): ?>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card gig-card h-100 business-card">
                    <div class="card-body">
                        <div class="small text-muted mb-1">Business</div>
                        <h6 class="mb-1 text-truncate" title="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></h6>
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($b['city'] ?? '') ?></span>
                            <span><?= htmlspecialchars($b['category'] ?? '') ?></span>
                        </div>
                    </div>
                    <a class="stretched-link" href="<?= htmlspecialchars($base) ?>/business.php?id=<?= (int)$b['id'] ?>"></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="mb-4 section-animate">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Popular locations</h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($popularLocations as $loc): ?>
                <span class="badge bg-light text-dark location-badge"><?= htmlspecialchars($loc['city']) ?> (<?= (int)$loc['cnt'] ?>)</span>
            <?php endforeach; ?>
            <?php if (empty($popularLocations)): ?>
                <span class="text-muted">No data yet.</span>
            <?php endif; ?>
        </div>
    </section>

    <section class="mb-4 section-animate">
        <div class="row g-3 align-items-center">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span class="text-muted">Trusted by students at</span>
                    <span class="badge bg-light text-dark university-badge">MSU</span>
                    <span class="badge bg-light text-dark university-badge">UZ</span>
                    <span class="badge bg-light text-dark university-badge">CUT</span>
                    <span class="badge bg-light text-dark university-badge">NUST</span>
                </div>
            </div>
        </div>
    </section>

<?php include __DIR__ . '/includes/footer.php'; ?>

