<?php
// Shared header for Varsity Resource Centre
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($base === '') { $base = ''; }
use Config\Settings;
use Auth\Auth;
require_once __DIR__ . '/../bootstrap.php';
$settings = new Settings(__DIR__ . '/../storage/settings.json');
$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
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
	<?php if ($settings->get('adsense_client')): ?>
		<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($settings->get('adsense_client')) ?>" crossorigin="anonymous"></script>
	<?php endif; ?>
	<style>:root{ --bs-primary: <?= htmlspecialchars($primaryColor) ?>; }</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
		<div class="container">
			<a class="navbar-brand fw-semibold" href="<?= htmlspecialchars($base) ?>/index.php">
				<i class="fa-solid fa-graduation-cap me-2"></i><?= htmlspecialchars($siteName) ?>
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vrcNav" aria-controls="vrcNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="vrcNav">
				<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
					<li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/index.php">Home</a></li>
					<li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/timetable.php">Timetable</a></li>
					<?php if (($siteConfig['features']['houses'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/houses.php">Houses</a></li><?php endif; ?>
					<?php if (($siteConfig['features']['businesses'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/businesses.php">Businesses</a></li><?php endif; ?>
					<?php if (($siteConfig['features']['jobs'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/jobs.php">Jobs</a></li><?php endif; ?>
					<?php if (($siteConfig['features']['articles'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/articles.php">Articles</a></li><?php endif; ?>
					<?php if (($siteConfig['features']['news'] ?? true)): ?><li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/news.php">News</a></li><?php endif; ?>
					<li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/resume.php">Resume</a></li>
				</ul>
			</div>
		</div>
	</nav>
	<main class="container py-4">

