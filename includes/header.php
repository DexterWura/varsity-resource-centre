<?php
// Shared header for Varsity Resource Centre
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($base === '') { $base = ''; }
use Config\Settings;
use Auth\Auth;
use Auth\UserAuth;
require_once __DIR__ . '/../bootstrap.php';
$settings = new Settings(__DIR__ . '/../storage/settings.json');
$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
$userAuth = new UserAuth();
$siteConfig = is_file(__DIR__ . '/../storage/app.php') ? (include __DIR__ . '/../storage/app.php') : [];
$siteName = $siteConfig['site_name'] ?? 'Varsity Resource Centre';
$primaryColor = $siteConfig['theme']['primary'] ?? ($settings->get('theme')['primary'] ?? '#0d6efd');

// Page meta (allow pages to predefine: $pageTitle, $metaDescription, $metaImage)
$documentTitle = isset($pageTitle) && $pageTitle !== '' ? ($pageTitle . ' - ' . $siteName) : $siteName;
$metaDescription = $metaDescription ?? '';
$metaImage = $metaImage ?? '';
$noIndex = $noIndex ?? false;
$canonicalUrl = $canonicalUrl ?? '';
$structuredDataJson = $structuredDataJson ?? '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$currentUrl = $scheme . '://' . $host . $requestUri;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($documentTitle) ?></title>
    <?php if (!empty($metaDescription)): ?>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>
    <?php if ($noIndex): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <?php if (!empty($canonicalUrl)): ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <?php else: ?>
    <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>">
    <?php endif; ?>
    <!-- Open Graph -->
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($documentTitle) ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($metaDescription)): ?>
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">
    <?php if (!empty($metaImage)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($metaImage) ?>">
    <?php endif; ?>
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($documentTitle) ?>">
    <?php if (!empty($metaDescription)): ?>
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>
    <?php if (!empty($metaImage)): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($metaImage) ?>">
    <?php endif; ?>
    <?php if (!empty($structuredDataJson)): ?>
    <script type="application/ld+json">
    <?= $structuredDataJson ?>
    </script>
    <?php endif; ?>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
	<link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/assets/css/style.css">
	<link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/assets/css/navbar.css">
	<script src="<?= htmlspecialchars($base) ?>/assets/js/animations.js"></script>
	<script>
		// Ensure dropdowns work properly
		document.addEventListener('DOMContentLoaded', function() {
			// Initialize Bootstrap dropdown manually (required for this site)
			var toolsDropdown = document.getElementById('toolsDropdown');
			if (toolsDropdown) {
				// Manually initialize Bootstrap dropdown
				var dropdown = new bootstrap.Dropdown(toolsDropdown);
			}
			
			// Sticky Navigation Functionality
			var navbar = document.querySelector('.navbar');
			var body = document.body;
			var lastScrollTop = 0;
			var scrollThreshold = 100; // Pixels to scroll before navbar becomes sticky
			
			function handleScroll() {
				var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
				
				if (scrollTop > scrollThreshold) {
					// Add sticky class
					if (!navbar.classList.contains('sticky')) {
						navbar.classList.add('sticky');
						body.classList.add('navbar-sticky');
					}
				} else {
					// Remove sticky class
					if (navbar.classList.contains('sticky')) {
						navbar.classList.remove('sticky');
						body.classList.remove('navbar-sticky');
					}
				}
				
				lastScrollTop = scrollTop;
			}
			
			// Throttle scroll events for better performance
			var scrollTimeout;
			window.addEventListener('scroll', function() {
				if (scrollTimeout) {
					clearTimeout(scrollTimeout);
				}
				scrollTimeout = setTimeout(handleScroll, 10);
			});
			
			// Initial check
			handleScroll();
		});
	</script>
	<?php if ($settings->get('adsense_client')): ?>
		<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($settings->get('adsense_client')) ?>" crossorigin="anonymous"></script>
	<?php endif; ?>
	<style>
		:root{ --bs-primary: <?= htmlspecialchars($primaryColor) ?>; }
	</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
		<div class="container-fluid">
			<a class="navbar-brand fw-semibold" href="<?= htmlspecialchars($base) ?>/index.php">
				<i class="fa-solid fa-graduation-cap me-2"></i><?= htmlspecialchars($siteName) ?>
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
					<li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/index.php">Home</a></li>
					<?php if (($siteConfig['features']['houses'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/houses.php">Houses</a></li><?php endif; ?>
					<?php if (($siteConfig['features']['businesses'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/businesses.php">Businesses</a></li><?php endif; ?>
					<?php if (($siteConfig['features']['jobs'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/jobs.php">Jobs</a></li><?php endif; ?>
					<?php if (($siteConfig['features']['articles'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/articles.php">Articles</a></li><?php endif; ?>
					<?php if (($siteConfig['features']['news'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/news.php">News</a></li><?php endif; ?>
					
					<!-- Tools Dropdown -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="#" id="toolsDropdown" role="button"
						   data-bs-toggle="dropdown" aria-expanded="false">
							<i class="fa-solid fa-tools me-1"></i>Tools
						</a>
						<ul class="dropdown-menu" aria-labelledby="toolsDropdown">
							<li>
								<a class="dropdown-item" href="<?= htmlspecialchars($base) ?>/timetable.php">
									<i class="fa-regular fa-calendar me-2"></i>Timetable
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="<?= htmlspecialchars($base) ?>/plagiarism-checker.php">
									<i class="fa-solid fa-search me-2"></i>Pro Plagiarism Checker
								</a>
							</li>
							<li><hr class="dropdown-divider"></li>
							<li>
								<a class="dropdown-item" href="<?= htmlspecialchars($base) ?>/resume.php">
									<i class="fa-regular fa-file-lines me-2"></i>Resume Builder
								</a>
							</li>
						</ul>
					</li>
					
					<!-- User Authentication Links -->
					<?php if ($userAuth->check()): ?>
						<!-- Logged in user menu -->
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
								<i class="fa-solid fa-user-circle me-2"></i>
								<span class="d-none d-md-inline"><?= htmlspecialchars($userAuth->user()->getFullName()) ?></span>
								<span class="d-md-none"><?= htmlspecialchars(substr($userAuth->user()->getFullName(), 0, 10)) ?>...</span>
							</a>
							<ul class="dropdown-menu dropdown-menu-end shadow">
								<li class="dropdown-header">
									<div class="fw-bold"><?= htmlspecialchars($userAuth->user()->getFullName()) ?></div>
									<small class="text-muted"><?= htmlspecialchars($userAuth->user()->getEmail()) ?></small>
								</li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="<?= htmlspecialchars($base) ?>/dashboard.php">
									<i class="fa-solid fa-tachometer-alt me-2"></i>Dashboard
								</a></li>
								<li><a class="dropdown-item" href="<?= htmlspecialchars($base) ?>/user_roles.php">
									<i class="fa-solid fa-user-cog me-2"></i>Profile
								</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item text-danger" href="<?= htmlspecialchars($base) ?>/logout.php">
									<i class="fa-solid fa-sign-out-alt me-2"></i>Logout
								</a></li>
							</ul>
						</li>
					<?php else: ?>
						<!-- Not logged in -->
						<li class="nav-item">
							<a class="nav-link" href="<?= htmlspecialchars($base) ?>/login.php">
								<i class="fa-solid fa-sign-in-alt me-1"></i>Login
							</a>
						</li>
						<li class="nav-item">
							<a class="btn btn-primary btn-sm ms-2" href="<?= htmlspecialchars($base) ?>/register.php">
								<i class="fa-solid fa-user-plus me-1"></i>Sign Up
							</a>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
	</nav>
	<main class="container py-4">

