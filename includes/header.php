<?php
// Shared header for Varsity Resource Centre
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($base === '') { $base = ''; }
use Config\Settings;
use Auth\Auth;
require_once __DIR__ . '/../bootstrap.php';
$settings = new Settings(__DIR__ . '/../storage/settings.json');
$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Varsity Resource Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/assets/css/style.css">
    <?php if ($settings->get('adsense_client')): ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($settings->get('adsense_client')) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
    <style>:root{ --bs-primary: <?= htmlspecialchars($settings->get('theme')['primary'] ?? '#0d6efd') ?>; }</style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="<?= htmlspecialchars($base) ?>/index.php">
                <i class="fa-solid fa-graduation-cap me-2"></i>Varsity Resource Centre
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vrcNav" aria-controls="vrcNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="vrcNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/timetable.php">Timetable</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/articles.php">Articles</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/news.php">News</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/resume.php">Resume</a></li>
                    <?php if ($auth->check()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/admin/index.php">Admin</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($base) ?>/admin/login.php">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container py-4">

