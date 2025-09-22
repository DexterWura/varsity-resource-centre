<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { header('Location: /admin/login.php'); exit; }
$user = $auth->user();

$pageTitle = $pageTitle ?? 'Admin';
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
function activeClass(string $file, string $current): string { return $file === $current ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> · Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --bg: #f8f9fb;
        --card: #ffffff;
        --text: #212529;
        --muted: #6c757d;
        --border: #e9ecef;
        --sidebar-bg: #ffffff;
        --sidebar-text: #343a40;
        --sidebar-active: #f1f3f5;
    }
    [data-theme="dark"] {
        --bg: #0f1521;
        --card: #121a2a;
        --text: #e9ecef;
        --muted: #aab1b9;
        --border: #243147;
        --sidebar-bg: #0f1521;
        --sidebar-text: #cfd6dd;
        --sidebar-active: #1a2335;
    }
    body { background: var(--bg); color: var(--text); }
    .admin-wrap { min-height: 100vh; }
    .sidebar { width: 240px; border-right: 1px solid var(--border); background: var(--sidebar-bg); }
    .sidebar .nav-link { border-radius: 8px; color: var(--sidebar-text); }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: var(--sidebar-active); }
    .card { background: var(--card); border: 1px solid var(--border); }
    .text-muted { color: var(--muted) !important; }
    .btn-toggle-theme { border: 1px solid var(--border); color: var(--text); }
    @media (max-width: 991.98px) { .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid var(--border); } }
    </style>
    <script>
    (function(){
        try {
            var t = localStorage.getItem('admin-theme');
            if (t === 'dark') document.documentElement.setAttribute('data-theme','dark');
        } catch(e) {}
    })();
    </script>
</head>
<body>
<div class="admin-wrap d-lg-flex">
    <aside class="sidebar p-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <strong>Admin</strong>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-toggle-theme" type="button" id="themeToggle" title="Toggle theme"><i class="fa-solid fa-moon"></i></button>
                <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
        <div class="collapse d-lg-block" id="adminNav">
            <nav class="nav flex-column gap-1">
                <a class="nav-link <?= activeClass('index.php',$current) ?>" href="/admin/index.php"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
                <a class="nav-link <?= activeClass('settings.php',$current) ?>" href="/admin/settings.php"><i class="fa-solid fa-sliders me-2"></i> Settings</a>
                
                <a class="nav-link <?= activeClass('jobs.php',$current) ?>" href="/admin/jobs.php"><i class="fa-solid fa-briefcase me-2"></i> Jobs</a>
                <a class="nav-link <?= activeClass('migrations.php',$current) ?>" href="/admin/migrations.php"><i class="fa-solid fa-database me-2"></i> Migrations</a>
                <a class="nav-link <?= activeClass('password.php',$current) ?>" href="/admin/password.php"><i class="fa-solid fa-key me-2"></i> Password</a>
                <form method="post" action="/admin/index.php" class="mt-2">
                    <input type="hidden" name="action" value="logout">
                    <button class="btn btn-sm btn-outline-secondary w-100" type="submit"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout (<?= htmlspecialchars($user ?? '') ?>)</button>
                </form>
            </nav>
        </div>
    </aside>
    <main class="flex-grow-1 p-3">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <h4 class="mb-0 me-auto"><?= htmlspecialchars($pageTitle) ?></h4>
            <div class="d-none d-md-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="max-width: 280px;">
                    <span class="input-group-text bg-transparent border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="search" class="form-control border-start-0" placeholder="Search..." oninput="window.adminQuickSearch && window.adminQuickSearch(this.value)">
                </div>
                <button class="btn btn-sm btn-outline-secondary position-relative" title="Notifications">
                    <i class="fa-regular fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="adminNotifBadge" style="display:none;">0</span>
                </button>
            </div>
        </div>
        <ul class="nav nav-pills gap-1 mb-3">
            <li class="nav-item"><span class="badge rounded-pill text-bg-primary">Admin</span></li>
            <li class="nav-item"><span class="badge rounded-pill text-bg-secondary">Secure</span></li>
            <li class="nav-item"><span class="badge rounded-pill text-bg-info">v1</span></li>
        </ul>

