<?php include __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php use Http\HttpClient; use Database\DB; ?>

<?php
// Prefer DB-backed Zimbabwe-focused jobs; fallback to public API
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$jobs = [];
try {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('SELECT title, company_name, location, description, url FROM jobs WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT :lim OFFSET :off');
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', ($page-1)*$perPage, PDO::PARAM_INT);
    $stmt->execute();
    $jobs = $stmt->fetchAll() ?: [];
} catch (Throwable $e) {
    $client = new HttpClient();
    $apiUrl = 'https://arbeitnow.com/api/job-board-api?page=' . $page;
    $data = $client->getJson($apiUrl) ?: ['data' => []];
    $jobs = $data['data'] ?? [];
}
function truncateWords(string $text, int $maxWords = 45): array {
    $text = trim($text);
    if ($text === '') { return ['', false]; }
    $words = preg_split('/\s+/', $text);
    if (!$words) { return [$text, false]; }
    if (count($words) <= $maxWords) { return [$text, false]; }
    $short = implode(' ', array_slice($words, 0, $maxWords)) . '…';
    return [$short, true];
}
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
                        <?php $desc = isset($job['description']) ? trim(strip_tags($job['description'])) : ''; list($short, $truncated) = truncateWords($desc, 45); ?>
                        <p class="card-text small flex-grow-1"><?= htmlspecialchars($short) ?></p>
                        <div class="d-flex gap-2 mt-auto">
                            <?php if ($truncated): ?>
                                <button class="btn btn-sm btn-light pill-btn" data-bs-toggle="modal" data-bs-target="#jobModal" data-title="<?= htmlspecialchars($job['title'] ?? '') ?>" data-company="<?= htmlspecialchars($job['company_name'] ?? '') ?>" data-location="<?= htmlspecialchars($job['location'] ?? '') ?>" data-desc="<?= htmlspecialchars($desc) ?>" data-url="<?= htmlspecialchars($job['url'] ?? '') ?>">See more</button>
                            <?php endif; ?>
                            <?php if (!empty($job['url'])): ?>
                                <button class="btn btn-sm btn-primary pill-btn track-job" data-bs-toggle="modal" data-bs-target="#applyModal" data-url="<?= htmlspecialchars($job['url']) ?>" data-title="<?= htmlspecialchars($job['title'] ?? '') ?>" data-company="<?= htmlspecialchars($job['company_name'] ?? '') ?>">Apply now</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <a class="btn btn-light pill-btn<?= $page <= 1 ? ' disabled' : '' ?>" href="?page=<?= max(1, $page-1) ?>">Previous</a>
        <a class="btn btn-light pill-btn" href="?page=<?= $page+1 ?>">Next</a>
    </div>

    <!-- Job Details Modal -->
    <div class="modal fade" id="jobModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="text-muted small mb-2" id="jobMeta"></div>
            <div id="jobDesc" class="small"></div>
          </div>
          <div class="modal-footer">
            <a id="jobApplyLink" target="_blank" class="btn btn-primary pill-btn">Apply</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Apply In-App Modal (iframe) -->
    <div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-fullscreen-sm-down">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Apply Now</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0" style="height:70vh;">
            <iframe id="applyFrame" src="about:blank" style="border:0;width:100%;height:100%;"></iframe>
          </div>
        </div>
      </div>
    </div>

    <script>
    var jobModal = document.getElementById('jobModal');
    if (jobModal) {
      jobModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var title = button.getAttribute('data-title') || '';
        var company = button.getAttribute('data-company') || '';
        var location = button.getAttribute('data-location') || '';
        var desc = button.getAttribute('data-desc') || '';
        var url = button.getAttribute('data-url') || '';
        jobModal.querySelector('.modal-title').textContent = title;
        jobModal.querySelector('#jobMeta').textContent = company + (location ? ' · ' + location : '');
        jobModal.querySelector('#jobDesc').textContent = desc;
        jobModal.querySelector('#jobApplyLink').href = url;
      });
    }
    var applyModal = document.getElementById('applyModal');
    if (applyModal) {
      applyModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var url = button.getAttribute('data-url') || 'about:blank';
        applyModal.querySelector('#applyFrame').src = url;
        // Track popular job click
        try {
          var title = button.getAttribute('data-title') || '';
          var company = button.getAttribute('data-company') || '';
          navigator.sendBeacon && navigator.sendBeacon('track.php?type=job', new Blob([new URLSearchParams({title:title,url:url,company:company}).toString()], {type:'application/x-www-form-urlencoded'}));
        } catch (e) {}
      });
      applyModal.addEventListener('hidden.bs.modal', function () {
        applyModal.querySelector('#applyFrame').src = 'about:blank';
      });
    }
    </script>

<?php include __DIR__ . '/includes/footer.php'; ?>


