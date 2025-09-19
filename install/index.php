<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Config\AppConfig;

$config = new AppConfig(__DIR__ . '/../storage/app.php');
$installed = (bool)($config->get('installed', false));
if ($installed) { header('Location: /index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim((string)($_POST['db_host'] ?? ''));
    $name = trim((string)($_POST['db_name'] ?? ''));
    $user = trim((string)($_POST['db_user'] ?? ''));
    $pass = (string)($_POST['db_pass'] ?? '');

    $site = trim((string)($_POST['site_name'] ?? 'Varsity Resource Centre'));
    $primary = trim((string)($_POST['theme_primary'] ?? '#0d6efd'));
    if ($host === '' || $name === '' || $user === '') {
        $error = 'Please fill all required fields.';
    } else {
        // Try connecting and run migrations
        try {
            putenv('DB_HOST=' . $host);
            putenv('DB_NAME=' . $name);
            putenv('DB_USER=' . $user);
            putenv('DB_PASS=' . $pass);
            // First connect without dbname to attempt database creation (if privileges allow)
            $serverDsn = "mysql:host=$host;charset=utf8mb4";
            $pdoServer = new PDO($serverDsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            try {
                $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','``',$name) . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (Throwable $e) {
                // Ignore if user has no privilege; require DB to exist
            }
            // Now connect to target database
            $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            // Run each .sql in db/migration, skipping CREATE DATABASE/USE statements
            $dir = __DIR__ . '/../db/migration';
            $files = glob($dir . '/*.sql');
            sort($files);
            foreach ($files as $file) {
                $sql = file_get_contents($file) ?: '';
                if ($sql === '') { continue; }
                // Remove CREATE DATABASE/USE lines for shared hosts
                $sql = preg_replace('/^\s*CREATE\s+DATABASE[\s\S]*?;\s*/im', '', $sql);
                $sql = preg_replace('/^\s*USE\s+[^;]+;\s*/im', '', $sql);
                $pdo->exec($sql);
            }
            $config->setMany([
                'installed' => true,
                'db' => ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass],
                'site_name' => $site,
                'theme' => ['primary' => $primary],
            ]);
            header('Location: /index.php');
            exit;
        } catch (Throwable $e) {
            $error = 'Install failed: ' . $e->getMessage() . ' - Ensure the database exists and the user has privileges.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Varsity Resource Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:640px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-3">Install Varsity Resource Centre</h4>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">DB Host</label>
                        <input class="form-control" name="db_host" placeholder="localhost" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DB Name</label>
                        <input class="form-control" name="db_name" placeholder="varsity_resource_centre" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DB User</label>
                        <input class="form-control" name="db_user" placeholder="root" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DB Password</label>
                        <input type="password" class="form-control" name="db_pass" placeholder="(blank if none)">
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <label class="form-label">Site Name</label>
                    <input class="form-control" name="site_name" placeholder="Varsity Resource Centre">
                </div>
                <div class="col-12">
                    <label class="form-label">Theme Primary Color</label>
                    <input type="color" class="form-control form-control-color" name="theme_primary" value="#0d6efd">
                </div>
                <button class="btn btn-primary mt-3" type="submit">Install</button>
            </form>
        </div>
    </div>
    <p class="text-muted small mt-3">Setup will create database tables and a default superadmin account.</p>
    </div>
</body>
</html>


