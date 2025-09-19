<?php include __DIR__ . '/includes/header.php'; ?>
<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); if ($base === '') { $base = ''; } ?>

    <div class="p-4 p-md-5 mb-4 rounded-3 hero-gradient fade-in">
        <div class="container-fluid py-2">
            <h1 class="display-6">Varsity Resource Centre</h1>
            <p class="col-md-8 fs-6 text-muted">Find timetables, student jobs, articles, and student news in one place.</p>
            <a href="<?= htmlspecialchars($base) ?>/timetable.php" class="btn btn-primary btn-sm"><i class="fa-regular fa-calendar me-1"></i> View Timetables</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 fade-in delay-1">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2"><i class="fa-regular fa-calendar me-2"></i><h5 class="card-title mb-0">Timetables</h5></div>
                    <p class="card-text text-muted">Search by university and module codes, and export as calendar.</p>
                    <a href="<?= htmlspecialchars($base) ?>/timetable.php" class="stretched-link">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 fade-in delay-2">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2"><i class="fa-solid fa-briefcase me-2"></i><h5 class="card-title mb-0">Jobs</h5></div>
                    <p class="card-text text-muted">Latest internships and entry-level roles curated for students.</p>
                    <a href="<?= htmlspecialchars($base) ?>/jobs.php" class="stretched-link">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 fade-in delay-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2"><i class="fa-regular fa-newspaper me-2"></i><h5 class="card-title mb-0">Articles</h5></div>
                    <p class="card-text text-muted">Academic and campus-relevant reads from free public sources.</p>
                    <a href="<?= htmlspecialchars($base) ?>/articles.php" class="stretched-link">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 fade-in delay-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2"><i class="fa-solid fa-bolt me-2"></i><h5 class="card-title mb-0">Student News</h5></div>
                    <p class="card-text text-muted">Trending student and campus tech news aggregated for you.</p>
                    <a href="<?= htmlspecialchars($base) ?>/news.php" class="stretched-link">Open</a>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

