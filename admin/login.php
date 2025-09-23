<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');

// Handle logout
if (isset($_GET['logout']) || isset($_POST['logout'])) {
    $auth->logout();
    header('Location: /admin/login.php?logged_out=1');
    exit;
}

// If already logged in, redirect to dashboard
if ($auth->check()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($auth->login($username, $password)) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    $error = 'Invalid credentials';
}

// Check for logout message
if (isset($_GET['logged_out'])) {
    $success = 'You have been successfully logged out.';
} elseif (isset($_GET['timeout'])) {
    $error = 'Your session has expired due to inactivity. Please log in again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:420px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Super Admin Login</h5>
            <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Login</button>
            </form>
        </div>
    </div>
    <div class="text-center small text-muted mt-3">Default user: superadmin / ChangeMe123!</div>
    </div>
</body>
</html>


