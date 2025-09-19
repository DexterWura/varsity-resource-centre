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
    .admin-wrap { min-height: 100vh; }
    .sidebar { width: 240px; border-right: 1px solid #e9ecef; }
    .sidebar .nav-link { border-radius: 8px; color: #343a40; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #f1f3f5; }
    @media (max-width: 991.98px) { .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #e9ecef; } }
    </style>
}</head>
<body>
<div class="admin-wrap d-lg-flex">
    <aside class="sidebar p-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <strong>Admin</strong>
            <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"><i class="fa-solid fa-bars"></i></button>
        </div>
        <div class="collapse d-lg-block" id="adminNav">
            <nav class="nav flex-column gap-1">
                <a class="nav-link <?= activeClass('index.php',$current) ?>" href="/admin/index.php"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
                <a class="nav-link <?= activeClass('settings.php',$current) ?>" href="/admin/settings.php"><i class="fa-solid fa-sliders me-2"></i> Settings</a>
                <a class="nav-link <?= activeClass('notifications.php',$current) ?>" href="/admin/notifications.php"><i class="fa-regular fa-bell me-2"></i> Notifications</a>
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
        <h4 class="mb-3"><?= htmlspecialchars($pageTitle) ?></h4>

