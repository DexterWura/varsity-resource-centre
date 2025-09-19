<?php include __DIR__ . '/includes/header.php'; ?>
<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); if ($base === '') { $base = ''; } ?>

    <section class="p-4 p-md-5 mb-4 rounded-3 hero-gradient fade-in">
        <div class="container-fluid py-2">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <h1 class="display-6 mb-2">Everything students need, in one place</h1>
                    <p class="col-lg-10 fs-6 text-muted mb-3">Timetables, jobs, articles, and campus news tailored for Zimbabwean universities.</p>
                    <form class="hero-search" action="<?= htmlspecialchars($base) ?>/timetable.php" method="get">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control border-start-0" placeholder="Search modules, faculties, or universities..." aria-label="Search">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2 small">
                            <span class="text-muted">Popular:</span>
                            <a href="<?= htmlspecialchars($base) ?>/timetable.php" class="category-pill">Computer Science</a>
                            <a href="<?= htmlspecialchars($base) ?>/timetable.php" class="category-pill">Accounting</a>
                            <a href="<?= htmlspecialchars($base) ?>/timetable.php" class="category-pill">Engineering</a>
                        </div>
                    </form>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center logo-cloud position-relative">
                    <img src="assets/images/mymsu.png" alt="Students" class="img-fluid" style="max-height:220px;">
                    <div class="logo-float" style="left:10%;top:10%">MSU</div>
                    <div class="logo-float" style="left:70%;top:20%;animation-delay:.6s">UZ</div>
                    <div class="logo-float" style="left:20%;top:70%;animation-delay:.3s">CUT</div>
                    <div class="logo-float" style="left:75%;top:65%;animation-delay:1s">NUST</div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Explore categories</h2>
            <a href="<?= htmlspecialchars($base) ?>/articles.php" class="btn btn-sm pill-outline pill-btn">See more</a>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-light pill-btn" href="<?= htmlspecialchars($base) ?>/timetable.php"><i class="fa-regular fa-calendar me-1"></i> Timetables</a>
            <a class="btn btn-light pill-btn" href="<?= htmlspecialchars($base) ?>/jobs.php"><i class="fa-solid fa-briefcase me-1"></i> Jobs</a>
            <a class="btn btn-light pill-btn" href="<?= htmlspecialchars($base) ?>/articles.php"><i class="fa-regular fa-newspaper me-1"></i> Articles</a>
            <a class="btn btn-light pill-btn" href="<?= htmlspecialchars($base) ?>/news.php"><i class="fa-solid fa-bolt me-1"></i> Student News</a>
            <a class="btn btn-light pill-btn" href="<?= htmlspecialchars($base) ?>/resume.php"><i class="fa-regular fa-file-lines me-1"></i> Resume</a>
        </div>
    </section>

    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Popular now</h2>
            <a href="<?= htmlspecialchars($base) ?>/timetable.php" class="btn btn-sm pill-outline pill-btn">View more</a>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card gig-card h-100">
                    <div class="card-body">
                        <div class="small text-muted mb-1">Timetables</div>
                        <h6 class="mb-2">MSU Computing First-Year Timetable</h6>
                        <div class="d-flex align-items-center text-muted small"><i class="fa-regular fa-clock me-1"></i> Weekly schedule</div>
                    </div>
                    <a class="stretched-link" href="<?= htmlspecialchars($base) ?>/timetable.php"></a>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card gig-card h-100">
                    <div class="card-body">
                        <div class="small text-muted mb-1">Jobs</div>
                        <h6 class="mb-2">Student Assistant Roles</h6>
                        <div class="d-flex align-items-center text-muted small"><i class="fa-solid fa-location-dot me-1"></i> Harare</div>
                    </div>
                    <a class="stretched-link" href="<?= htmlspecialchars($base) ?>/jobs.php"></a>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card gig-card h-100">
                    <div class="card-body">
                        <div class="small text-muted mb-1">Articles</div>
                        <h6 class="mb-2">Study Techniques That Work</h6>
                        <div class="d-flex align-items-center text-muted small"><i class="fa-regular fa-bookmark me-1"></i> Tips & Guides</div>
                    </div>
                    <a class="stretched-link" href="<?= htmlspecialchars($base) ?>/articles.php"></a>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card gig-card h-100">
                    <div class="card-body">
                        <div class="small text-muted mb-1">News</div>
                        <h6 class="mb-2">Campus Events This Week</h6>
                        <div class="d-flex align-items-center text-muted small"><i class="fa-regular fa-bell me-1"></i> Stay updated</div>
                    </div>
                    <a class="stretched-link" href="<?= htmlspecialchars($base) ?>/news.php"></a>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span class="text-muted">Trusted by students at</span>
                    <img src="assets/images/mymsu.png" alt="MSU" height="32">
                    <span class="badge bg-light text-dark">UZ</span>
                    <span class="badge bg-light text-dark">CUT</span>
                    <span class="badge bg-light text-dark">NUST</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?= htmlspecialchars($base) ?>/timetable.php" class="btn btn-success"><i class="fa-regular fa-calendar-plus me-1"></i> Get your timetable</a>
            </div>
        </div>
    </section>

<?php include __DIR__ . '/includes/footer.php'; ?>

