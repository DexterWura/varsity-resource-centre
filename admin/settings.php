<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\UserAuth;
use Config\Settings;
use Security\Csrf;
use Jobs\JobAPIs;

$userAuth = new UserAuth();
if (!$userAuth->check() || !$userAuth->user()->hasRole('admin')) { 
    header('Location: /admin/login.php'); 
    exit; 
}

$user = $userAuth->user();
$successMessage = '';
$errorMessage = '';

// Load settings
$settings = new Settings(__DIR__ . '/../storage/settings.json');
$data = $settings->all();

// Job API settings
$jobAPIs = $settings->get('job_apis', [
    'open_skills' => true,
    'devitjobs' => true,
    'arbeitnow' => true
]);

// Feature flags
$siteConfig = is_file(__DIR__ . '/../storage/app.php') ? (include __DIR__ . '/../storage/app.php') : [];
$features = $siteConfig['features'] ?? [
	'articles' => true,
	'houses' => true,
	'businesses' => true,
	'news' => true,
	'jobs' => true,
];

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!Csrf::validate($csrf)) {
        $errorMessage = 'Invalid request token.';
    } else {
        try {
            if ($action === 'update_settings') {
                $updates = [
                    'site_name' => trim($_POST['site_name'] ?? ''),
                    'site_description' => trim($_POST['site_description'] ?? ''),
                    'adsense_client' => trim($_POST['adsense_client'] ?? ''),
                    'adsense_slot_header' => trim($_POST['adsense_slot_header'] ?? ''),
                    'adsense_slot_sidebar' => trim($_POST['adsense_slot_sidebar'] ?? ''),
                    'donate_url' => trim($_POST['donate_url'] ?? ''),
                    'theme' => [
                        'primary' => $_POST['theme_primary'] ?? '#7367f0',
                        'secondary' => $_POST['theme_secondary'] ?? '#6c757d',
                        'background' => $_POST['theme_background'] ?? '#f8f9fa',
                    ],
                    'features' => [
                        'articles' => isset($_POST['feature_articles']),
                        'houses' => isset($_POST['feature_houses']),
                        'businesses' => isset($_POST['feature_businesses']),
                        'news' => isset($_POST['feature_news']),
                        'jobs' => isset($_POST['feature_jobs']),
                        'plagiarism_checker' => isset($_POST['feature_plagiarism_checker']),
                    ]
                ];
                $settings->setMany($updates);
                
                // Update app config features
                $appConfig = include __DIR__ . '/../storage/app.php';
                $appConfig['features'] = $updates['features'];
                
                // Update job API settings
                $jobAPIs = [
                    'open_skills' => isset($_POST['job_api_open_skills']),
                    'devitjobs' => isset($_POST['job_api_devitjobs']),
                    'arbeitnow' => isset($_POST['job_api_arbeitnow'])
                ];
                $settings->set('job_apis', $jobAPIs);
                
                // Update plagiarism API settings
                $plagiarismAPIs = [
                    'copyleaks' => isset($_POST['plagiarism_api_copyleaks']),
                    'quetext' => isset($_POST['plagiarism_api_quetext']),
                    'smallseotools' => isset($_POST['plagiarism_api_smallseotools']),
                    'plagiarism_detector' => isset($_POST['plagiarism_api_plagiarism_detector']),
                    'duplichecker' => isset($_POST['plagiarism_api_duplichecker'])
                ];
                $appConfig['plagiarism_apis'] = $plagiarismAPIs;
                
                // Save updated app config
                file_put_contents(__DIR__ . '/../storage/app.php', '<?php' . PHP_EOL . 'return ' . var_export($appConfig, true) . ';');
                
                $successMessage = 'Settings updated successfully.';
            }
            
	} catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --dash-bg: #f8f9fb;
            --dash-card: #ffffff;
            --dash-text: #212529;
            --dash-muted: #6c757d;
            --dash-accent: #7367f0;
            --dash-border: #e9ecef;
            --dash-sidebar: #0f1521;
            --dash-sidebar-text: #cfd6dd;
            --dash-sidebar-active: #1a2335;
        }
        [data-theme="dark"] {
            --dash-bg: #0f1521;
            --dash-card: #121a2a;
            --dash-text: #e9ecef;
            --dash-muted: #aab1b9;
            --dash-accent: #7367f0;
            --dash-border: #243147;
            --dash-sidebar: #0f1521;
            --dash-sidebar-text: #cfd6dd;
            --dash-sidebar-active: #1a2335;
        }
        body { background: var(--dash-bg); color: var(--dash-text); }
        .sidebar { min-height: 100vh; background: var(--dash-sidebar); transition: all 0.3s ease; }
        .sidebar.collapsed { width: 70px; }
        .sidebar .nav-link { color: var(--dash-sidebar-text); padding: 12px 20px; border-radius: 10px; margin: 5px 10px; transition: all 0.3s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: var(--dash-sidebar-active); transform: translateX(5px); }
        .sidebar .nav-link i { width: 20px; text-align: center; }
        .main-content { background: var(--dash-bg); min-height: 100vh; }
        .card { background: var(--dash-card); border: 1px solid var(--dash-border); border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s ease; }
        .card:hover { transform: translateY(-5px); }
        .text-muted { color: var(--dash-muted) !important; }
        .btn-theme { border: 1px solid var(--dash-border); color: var(--dash-text); }
        .admin-badge {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                z-index: 1000;
                width: 250px;
            }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 px-0 sidebar" id="sidebar">
                <div class="p-3">
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-shield-fill text-danger fs-3 me-2"></i>
                        <span class="text-white fw-bold" id="brand-text">ADMIN</span>
                        <button class="btn btn-sm btn-theme ms-auto" id="dashThemeToggle" title="Toggle theme"><i class="bi bi-moon"></i></button>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i>
                                <span class="ms-2">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="review.php">
                                <i class="bi bi-check-circle"></i>
                                <span class="ms-2">Review Articles</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i>
                                <span class="ms-2">User Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="jobs.php">
                                <i class="bi bi-briefcase"></i>
                                <span class="ms-2">Job Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="settings.php">
                                <i class="bi bi-gear"></i>
                                <span class="ms-2">Site Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="migrations.php">
                                <i class="bi bi-database-gear"></i>
                                <span class="ms-2">Migrations</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="semantic-scholar.php">
                                <i class="bi bi-search"></i>
                                <span class="ms-2">Academic Search</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="mt-auto p-3">
                    <a href="/" class="nav-link text-info mb-2">
                        <i class="bi bi-house"></i>
                        <span class="ms-2">Visit Site</span>
                    </a>
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="ms-2">Logout</span>
                    </a>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Site Settings</h2>
                            <p class="text-muted">Configure your Varsity Resource Centre</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="admin-badge">Administrator</span>
                            <button class="btn btn-outline-secondary d-md-none" id="sidebarToggle">
                                <i class="bi bi-list"></i>
                            </button>
                        </div>
                    </div>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($errorMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::issueToken()) ?>">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <!-- Site Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-globe me-2"></i>Site Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Site Name</label>
                                        <input class="form-control" name="site_name" value="<?= htmlspecialchars($data['site_name'] ?? 'Varsity Resource Centre') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Site Description</label>
                                        <input class="form-control" name="site_description" value="<?= htmlspecialchars($data['site_description'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feature Toggles -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-toggles me-2"></i>Feature Management</h5>
                            </div>
	<div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="feature_articles" <?= ($features['articles'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Articles System</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="feature_houses" <?= ($features['houses'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label">House Rentals</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="feature_businesses" <?= ($features['businesses'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Business Listings</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="feature_news" <?= ($features['news'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label">News Section</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="feature_jobs" <?= ($features['jobs'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Job Postings</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="feature_plagiarism_checker" <?= ($features['plagiarism_checker'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label">Pro Plagiarism Checker</label>
                                        </div>
                                    </div>
                                </div>
			</div>
			</div>

                        <!-- Job API Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Job API Sources</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Select which job APIs to use. Jobs from active APIs will be shuffled and displayed together.</p>
                                <div class="row g-3">
                                    <?php 
                                    $availableAPIs = JobAPIs::getAvailableAPIs();
                                    foreach ($availableAPIs as $apiKey => $apiInfo): 
                                    ?>
                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-body">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="job_api_<?= $apiKey ?>" 
                                                           <?= ($jobAPIs[$apiKey] ?? true) ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold"><?= htmlspecialchars($apiInfo['name']) ?></label>
                                                </div>
                                                <p class="small text-muted mb-2"><?= htmlspecialchars($apiInfo['description']) ?></p>
                                                <div class="small">
                                                    <strong>Features:</strong>
                                                    <ul class="mb-0">
                                                        <?php foreach ($apiInfo['features'] as $feature): ?>
                                                            <li><?= htmlspecialchars($feature) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                                <a href="<?= htmlspecialchars($apiInfo['website']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i>Visit Website
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Plagiarism Checker API Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-search me-2"></i>Plagiarism Checker APIs</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Select which plagiarism detection APIs to use. Multiple APIs provide better coverage and accuracy.</p>
                                <div class="row g-3">
                                    <?php 
                                    $plagiarismAPIs = [
                                        'copyleaks' => [
                                            'name' => 'Copyleaks',
                                            'description' => 'Professional plagiarism detection with high accuracy',
                                            'features' => ['High accuracy', 'Multiple languages', 'API access'],
                                            'website' => 'https://copyleaks.com',
                                            'free_limit' => '200 chars'
                                        ],
                                        'quetext' => [
                                            'name' => 'Quetext',
                                            'description' => 'Advanced plagiarism checker with deep search',
                                            'features' => ['Deep search', 'Citation assistant', 'Color-coded results'],
                                            'website' => 'https://quetext.com',
                                            'free_limit' => '500 chars'
                                        ],
                                        'smallseotools' => [
                                            'name' => 'Small SEO Tools',
                                            'description' => 'Free plagiarism checker with good coverage',
                                            'features' => ['Free tier', 'Multiple sources', 'Easy integration'],
                                            'website' => 'https://smallseotools.com',
                                            'free_limit' => '1000 words'
                                        ],
                                        'plagiarism_detector' => [
                                            'name' => 'Plagiarism Detector',
                                            'description' => 'Comprehensive plagiarism detection service',
                                            'features' => ['Multiple databases', 'Real-time checking', 'Detailed reports'],
                                            'website' => 'https://plagiarism-detector.com',
                                            'free_limit' => '1000 words'
                                        ],
                                        'duplichecker' => [
                                            'name' => 'Duplichecker',
                                            'description' => 'Fast and reliable plagiarism detection',
                                            'features' => ['Fast processing', 'Multiple formats', 'Bulk checking'],
                                            'website' => 'https://duplichecker.com',
                                            'free_limit' => '1000 words'
                                        ]
                                    ];
                                    
                                    $plagiarismAPIsConfig = $siteConfig['plagiarism_apis'] ?? [];
                                    
                                    foreach ($plagiarismAPIs as $apiKey => $apiInfo): 
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card border">
                                            <div class="card-body">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="plagiarism_api_<?= $apiKey ?>" 
                                                           <?= ($plagiarismAPIsConfig[$apiKey] ?? false) ? 'checked' : '' ?>>
                                                    <label class="form-check-label fw-bold"><?= htmlspecialchars($apiInfo['name']) ?></label>
                                                </div>
                                                <p class="small text-muted mb-2"><?= htmlspecialchars($apiInfo['description']) ?></p>
                                                <div class="small mb-2">
                                                    <strong>Free Limit:</strong> <?= htmlspecialchars($apiInfo['free_limit']) ?>
                                                </div>
                                                <div class="small">
                                                    <strong>Features:</strong>
                                                    <ul class="mb-0">
                                                        <?php foreach ($apiInfo['features'] as $feature): ?>
                                                            <li><?= htmlspecialchars($feature) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                                <a href="<?= htmlspecialchars($apiInfo['website']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i>Visit Website
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Theme Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-palette me-2"></i>Theme Settings</h5>
				</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Primary Color</label>
                                        <input type="color" class="form-control form-control-color" name="theme_primary" value="<?= htmlspecialchars($data['theme']['primary'] ?? '#7367f0') ?>">
				</div>
                                    <div class="col-md-4">
                                        <label class="form-label">Secondary Color</label>
                                        <input type="color" class="form-control form-control-color" name="theme_secondary" value="<?= htmlspecialchars($data['theme']['secondary'] ?? '#6c757d') ?>">
				</div>
                                    <div class="col-md-4">
                                        <label class="form-label">Background Color</label>
                                        <input type="color" class="form-control form-control-color" name="theme_background" value="<?= htmlspecialchars($data['theme']['background'] ?? '#f8f9fa') ?>">
				</div>
				</div>
				</div>
			</div>

                        <!-- AdSense Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-cash me-2"></i>Google AdSense</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Client ID</label>
                                        <input class="form-control" name="adsense_client" value="<?= htmlspecialchars($data['adsense_client'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Header Slot</label>
                                        <input class="form-control" name="adsense_slot_header" value="<?= htmlspecialchars($data['adsense_slot_header'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Sidebar Slot</label>
                                        <input class="form-control" name="adsense_slot_sidebar" value="<?= htmlspecialchars($data['adsense_slot_sidebar'] ?? '') ?>">
                                    </div>
			</div>
	</div>
</div>

                        <!-- Donations -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-heart me-2"></i>Donations</h5>
                            </div>
                            <div class="card-body">
                                <label class="form-label">Donate URL</label>
                                <input class="form-control" name="donate_url" value="<?= htmlspecialchars($data['donate_url'] ?? '') ?>" placeholder="https://paypal.me/yourlink">
                            </div>
                        </div>

                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="bi bi-check-circle me-2"></i>Save All Settings
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('dashThemeToggle');
        const body = document.body;
        
        // Load saved theme
        const savedTheme = localStorage.getItem('admin-theme') || 'light';
        body.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
        }
        
        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>