<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { header('Location: /admin/login.php'); exit; }

// Simple Flyway runner shell wrapper (expects Flyway CLI installed & configured via env vars)
$command = 'flyway info | cat';
if (isset($_GET['action']) && $_GET['action'] === 'migrate') {
    $command = 'flyway migrate | cat';
}
if (isset($_GET['action']) && $_GET['action'] === 'validate') {
    $command = 'flyway validate | cat';
}

ob_start();
$output = shell_exec($command);
$result = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DB Migrations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h4>Flyway Migrations</h4>
    <div class="mb-3">
        <a class="btn btn-primary btn-sm" href="?action=migrate">Run migrate</a>
        <a class="btn btn-outline-secondary btn-sm" href="?action=validate">Validate</a>
        <a class="btn btn-outline-secondary btn-sm" href="?">Refresh</a>
    </div>
    <pre class="bg-light p-3 border small" style="white-space: pre-wrap;"><?= htmlspecialchars($output ?? 'No output') ?></pre>
</div>
</body>
</html>


