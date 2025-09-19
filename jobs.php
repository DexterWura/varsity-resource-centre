<?php include __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php use Http\HttpClient; ?>

<?php
// Arbeitnow Jobs API (public)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$apiUrl = 'https://arbeitnow.com/api/job-board-api?page=' . $page;
$client = new HttpClient();
$data = $client->getJson($apiUrl) ?: ['data' => []];
$jobs = $data['data'] ?? [];
?>

    <h1 class="h4 mb-3">Student Jobs</h1>
    <p class="text-muted">Latest internships and entry-level roles from public sources.</p>

    <div class="row g-3">
        <?php foreach ($jobs as $job): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-1"><?= htmlspecialchars($job['title'] ?? 'Job') ?></h5>
                        <div class="text-muted small mb-2">
                            <?= htmlspecialchars($job['company_name'] ?? 'Company') ?> · <?= htmlspecialchars($job['location'] ?? 'Remote/On-site') ?>
                        </div>
                        <p class="card-text small flex-grow-1"><?= htmlspecialchars($job['description'] ? strip_tags($job['description']) : '') ?></p>
                        <?php if (!empty($job['url'])): ?>
                            <a class="btn btn-sm btn-primary mt-auto" target="_blank" rel="noopener" href="<?= htmlspecialchars($job['url']) ?>">View</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <a class="btn btn-outline-secondary btn-sm<?= $page <= 1 ? ' disabled' : '' ?>" href="?page=<?= max(1, $page-1) ?>">Previous</a>
        <a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page+1 ?>">Next</a>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>


