<?php include __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/bootstrap.php'; ?>
<?php use Http\HttpClient; use Database\DB; use Jobs\JobAPIs; use Jobs\Job; use Config\Settings; ?>

<?php
// Get jobs from database and APIs, then shuffle them
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$jobs = [];

try {
    // Get database jobs using Job class
    $dbJobs = Job::getAll($perPage, ($page-1)*$perPage);
    
    // Get API jobs
    $settings = new Settings(__DIR__ . '/storage/settings.json');
    $activeAPIs = $settings->get('job_apis', [
        'open_skills' => true,
        'devitjobs' => true,
        'arbeitnow' => true
    ]);
    
    $jobAPIs = new JobAPIs($activeAPIs);
    $apiJobs = $jobAPIs->fetchShuffledJobs($perPage);
    
    // Combine and shuffle all jobs
    $allJobs = array_merge($dbJobs, $apiJobs);
    shuffle($allJobs);
    $jobs = array_slice($allJobs, 0, $perPage);
    
} catch (Throwable $e) {
    // Fallback to old method if new system fails
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
            <?php 
            // Handle both Job objects and array data
            if ($job instanceof Job) {
                $jobObj = $job;
                $jobData = $job->toArray();
            } else {
                $jobObj = new Job($job);
                $jobData = $job;
            }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-1"><?= htmlspecialchars($jobObj->getTitle() ?: 'Job') ?></h5>
                        <div class="text-muted small mb-2">
                            <?= htmlspecialchars($jobObj->getCompany() ?: 'Company') ?> · <?= htmlspecialchars($jobObj->getLocation() ?: 'Remote/On-site') ?>
                            <?php if (isset($jobData['source'])): ?>
                                <span class="badge bg-info ms-1"><?= htmlspecialchars($jobData['source_display']) ?></span>
                            <?php endif; ?>
                            <?php if ($jobObj->getDaysUntilExpiry() !== null && $jobObj->getDaysUntilExpiry() <= 7): ?>
                                <span class="badge bg-warning ms-1">Expires in <?= $jobObj->getDaysUntilExpiry() ?> days</span>
                            <?php endif; ?>
                        </div>
                        <?php $desc = $jobObj->getDescription(); list($short, $truncated) = truncateWords($desc, 45); ?>
                        <p class="card-text small flex-grow-1"><?= htmlspecialchars($short) ?></p>
                        <div class="d-flex gap-2 mt-auto">
                            <?php if ($truncated): ?>
                                <button class="btn btn-sm btn-light pill-btn" data-bs-toggle="modal" data-bs-target="#jobModal" 
                                        data-title="<?= htmlspecialchars($jobObj->getTitle()) ?>" 
                                        data-company="<?= htmlspecialchars($jobObj->getCompany()) ?>" 
                                        data-location="<?= htmlspecialchars($jobObj->getLocation()) ?>" 
                                        data-desc="<?= htmlspecialchars($desc) ?>" 
                                        data-url="<?= htmlspecialchars($jobObj->getApplicationUrl()) ?>">See more</button>
                            <?php endif; ?>
                            <?php if ($jobObj->getApplicationUrl() !== '#'): ?>
                                <a href="<?= htmlspecialchars($jobObj->getApplicationUrl()) ?>" 
                                   class="btn btn-sm <?= $jobObj->getApplicationClass() ?> pill-btn"
                                   <?= $jobObj->getContactMethod() === 'url' ? 'data-bs-toggle="modal" data-bs-target="#applyModal" data-url="' . htmlspecialchars($jobObj->getApplicationUrl()) . '" data-title="' . htmlspecialchars($jobObj->getTitle()) . '" data-company="' . htmlspecialchars($jobObj->getCompany()) . '"' : '' ?>
                                   <?= in_array($jobObj->getContactMethod(), ['whatsapp', 'email']) ? 'target="_blank"' : '' ?>>
                                    <i class="<?= $jobObj->getApplicationIcon() ?> me-1"></i>
                                    <?= htmlspecialchars($jobObj->getApplicationText()) ?>
                                </a>
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


