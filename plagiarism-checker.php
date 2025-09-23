<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Security\Csrf;
use Http\HttpClient;

// Check if plagiarism checker feature is enabled
$siteConfig = is_file(__DIR__ . '/storage/app.php') ? (include __DIR__ . '/storage/app.php') : [];
if (!($siteConfig['features']['plagiarism_checker'] ?? false)) {
    header('Location: /');
    exit;
}

// Check if user has paid for Pro access (for now, redirect to payment page)
// In the future, this will check user's subscription status
$userAuth = new \Auth\UserAuth();
if (!$userAuth->check() || !$userAuth->user()->hasProAccess()) {
    header('Location: /payment.php?plan=plagiarism_checker');
    exit;
}

$pageTitle = 'Pro Plagiarism Checker';
$metaDescription = 'Check your text for plagiarism using multiple free APIs. Ensure originality of your academic work.';
$canonicalUrl = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/plagiarism-checker.php';

include __DIR__ . '/includes/header.php';

$result = null;
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request token.';
    } else {
        $text = trim($_POST['text'] ?? '');
        $api = $_POST['api'] ?? '';
        
        if (empty($text)) {
            $error = 'Please enter text to check.';
        } elseif (strlen($text) < 50) {
            $error = 'Text must be at least 50 characters long.';
        } elseif (strlen($text) > 5000) {
            $error = 'Text must be less than 5000 characters.';
        } else {
            try {
                $result = checkPlagiarism($text, $api, $siteConfig['plagiarism_apis'] ?? []);
                if ($result) {
                    $success = 'Plagiarism check completed successfully.';
                } else {
                    $error = 'Failed to check plagiarism. Please try again.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

function checkPlagiarism(string $text, string $api, array $enabledApis): ?array {
    $httpClient = new HttpClient();
    
    switch ($api) {
        case 'smallseotools':
            return checkWithSmallSEOTools($text, $httpClient);
        case 'plagiarism_detector':
            return checkWithPlagiarismDetector($text, $httpClient);
        case 'duplichecker':
            return checkWithDuplichecker($text, $httpClient);
        case 'quetext':
            return checkWithQuetext($text, $httpClient);
        case 'copyleaks':
            return checkWithCopyleaks($text, $httpClient);
        default:
            // Try all enabled APIs
            foreach ($enabledApis as $apiName => $enabled) {
                if ($enabled) {
                    $result = checkPlagiarism($text, $apiName, $enabledApis);
                    if ($result) return $result;
                }
            }
            return null;
    }
}

function checkWithSmallSEOTools(string $text, HttpClient $client): ?array {
    try {
        $data = [
            'text' => $text,
            'lang' => 'en'
        ];
        
        $response = $client->post('https://smallseotools.com/api/plagiarism-checker', $data);
        
        if ($response && isset($response['percentage'])) {
            return [
                'api' => 'Small SEO Tools',
                'percentage' => (float)$response['percentage'],
                'sources' => $response['sources'] ?? [],
                'status' => $response['percentage'] > 20 ? 'high' : ($response['percentage'] > 10 ? 'medium' : 'low')
            ];
        }
    } catch (Exception $e) {
        error_log('Small SEO Tools API error: ' . $e->getMessage());
    }
    return null;
}

function checkWithPlagiarismDetector(string $text, HttpClient $client): ?array {
    try {
        $data = [
            'text' => $text,
            'language' => 'en'
        ];
        
        $response = $client->post('https://plagiarism-detector.com/api/check', $data);
        
        if ($response && isset($response['similarity'])) {
            return [
                'api' => 'Plagiarism Detector',
                'percentage' => (float)$response['similarity'],
                'sources' => $response['matches'] ?? [],
                'status' => $response['similarity'] > 20 ? 'high' : ($response['similarity'] > 10 ? 'medium' : 'low')
            ];
        }
    } catch (Exception $e) {
        error_log('Plagiarism Detector API error: ' . $e->getMessage());
    }
    return null;
}

function checkWithDuplichecker(string $text, HttpClient $client): ?array {
    try {
        $data = [
            'text' => $text,
            'lang' => 'en'
        ];
        
        $response = $client->post('https://duplichecker.com/api/check', $data);
        
        if ($response && isset($response['plagiarism_percentage'])) {
            return [
                'api' => 'Duplichecker',
                'percentage' => (float)$response['plagiarism_percentage'],
                'sources' => $response['sources'] ?? [],
                'status' => $response['plagiarism_percentage'] > 20 ? 'high' : ($response['plagiarism_percentage'] > 10 ? 'medium' : 'low')
            ];
        }
    } catch (Exception $e) {
        error_log('Duplichecker API error: ' . $e->getMessage());
    }
    return null;
}

function checkWithQuetext(string $text, HttpClient $client): ?array {
    try {
        // Quetext free API (limited)
        $data = [
            'text' => substr($text, 0, 500), // Limit for free tier
            'language' => 'en'
        ];
        
        $response = $client->post('https://api.quetext.com/v1/plagiarism-check', $data);
        
        if ($response && isset($response['score'])) {
            return [
                'api' => 'Quetext',
                'percentage' => (float)$response['score'],
                'sources' => $response['matches'] ?? [],
                'status' => $response['score'] > 20 ? 'high' : ($response['score'] > 10 ? 'medium' : 'low')
            ];
        }
    } catch (Exception $e) {
        error_log('Quetext API error: ' . $e->getMessage());
    }
    return null;
}

function checkWithCopyleaks(string $text, HttpClient $client): ?array {
    try {
        // Copyleaks free API (very limited)
        $data = [
            'text' => substr($text, 0, 200), // Very limited for free tier
            'language' => 'en'
        ];
        
        $response = $client->post('https://api.copyleaks.com/v1/plagiarism-check', $data);
        
        if ($response && isset($response['similarity'])) {
            return [
                'api' => 'Copyleaks',
                'percentage' => (float)$response['similarity'],
                'sources' => $response['results'] ?? [],
                'status' => $response['similarity'] > 20 ? 'high' : ($response['similarity'] > 10 ? 'medium' : 'low')
            ];
        }
    } catch (Exception $e) {
        error_log('Copyleaks API error: ' . $e->getMessage());
    }
    return null;
}

$enabledApis = $siteConfig['plagiarism_apis'] ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">
                    <i class="fa-solid fa-search text-primary me-2"></i>Pro Plagiarism Checker
                </h1>
                <p class="text-muted">Check your text for originality using multiple free APIs</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa-solid fa-edit me-2"></i>Enter Text to Check
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                                
                                <div class="mb-3">
                                    <label for="text" class="form-label">Text Content</label>
                                    <textarea 
                                        class="form-control" 
                                        id="text" 
                                        name="text" 
                                        rows="10" 
                                        placeholder="Paste your text here to check for plagiarism..." 
                                        required
                                        maxlength="5000"
                                    ><?= htmlspecialchars($_POST['text'] ?? '') ?></textarea>
                                    <div class="form-text">
                                        <span id="charCount">0</span>/5000 characters
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="api" class="form-label">Select API (Optional)</label>
                                    <select class="form-select" id="api" name="api">
                                        <option value="">Auto-select best available API</option>
                                        <?php foreach ($enabledApis as $apiName => $enabled): ?>
                                            <?php if ($enabled): ?>
                                                <option value="<?= htmlspecialchars($apiName) ?>" <?= ($_POST['api'] ?? '') === $apiName ? 'selected' : '' ?>>
                                                    <?= ucfirst(str_replace('_', ' ', $apiName)) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fa-solid fa-search me-2"></i>Check for Plagiarism
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fa-solid fa-info-circle me-2"></i>How it Works
                            </h6>
                        </div>
                        <div class="card-body">
                            <ol class="small">
                                <li>Paste your text (50-5000 characters)</li>
                                <li>Select an API or let us choose the best one</li>
                                <li>Get instant plagiarism percentage</li>
                                <li>View sources if plagiarism is detected</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fa-solid fa-shield-alt me-2"></i>Privacy Notice
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-0">
                                Your text is processed securely and is not stored on our servers. 
                                We use trusted third-party APIs for plagiarism detection.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($result): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fa-solid fa-chart-bar me-2"></i>Plagiarism Check Results
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <div class="display-4 fw-bold 
                                                <?= $result['status'] === 'high' ? 'text-danger' : 
                                                   ($result['status'] === 'medium' ? 'text-warning' : 'text-success') ?>">
                                                <?= number_format($result['percentage'], 1) ?>%
                                            </div>
                                            <p class="text-muted mb-0">Plagiarism Score</p>
                                            <span class="badge 
                                                <?= $result['status'] === 'high' ? 'bg-danger' : 
                                                   ($result['status'] === 'medium' ? 'bg-warning' : 'bg-success') ?>">
                                                <?= ucfirst($result['status']) ?> Risk
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <h6>API Used: <?= htmlspecialchars($result['api']) ?></h6>
                                        
                                        <?php if ($result['percentage'] > 10): ?>
                                            <div class="alert alert-warning">
                                                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                                <strong>Plagiarism Detected!</strong> Your text shows <?= number_format($result['percentage'], 1) ?>% similarity with other sources.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-success">
                                                <i class="fa-solid fa-check-circle me-2"></i>
                                                <strong>Good News!</strong> Your text appears to be original with only <?= number_format($result['percentage'], 1) ?>% similarity.
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($result['sources'])): ?>
                                            <h6>Similar Sources Found:</h6>
                                            <div class="list-group">
                                                <?php foreach (array_slice($result['sources'], 0, 5) as $source): ?>
                                                    <div class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h6 class="mb-1"><?= htmlspecialchars($source['title'] ?? 'Unknown Source') ?></h6>
                                                            <small><?= number_format($source['similarity'] ?? 0, 1) ?>%</small>
                                                        </div>
                                                        <?php if (!empty($source['url'])): ?>
                                                            <a href="<?= htmlspecialchars($source['url']) ?>" target="_blank" rel="noopener" class="text-decoration-none">
                                                                <small class="text-muted"><?= htmlspecialchars($source['url']) ?></small>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Character counter
document.getElementById('text').addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('charCount').textContent = count;
    
    if (count > 5000) {
        this.value = this.value.substring(0, 5000);
        document.getElementById('charCount').textContent = '5000';
    }
});

// Auto-resize textarea
document.getElementById('text').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
