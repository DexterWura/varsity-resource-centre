<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;
use Database\DB;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { header('Location: /admin/login.php'); exit; }
$user = $auth->user();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string)($_POST['current'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');
    if ($new === '' || $new !== $confirm) { $msg = 'New passwords do not match.'; }
    else {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE username = :u');
            $stmt->execute([':u' => $user]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($current, (string)$row['password_hash'])) {
                $msg = 'Current password incorrect.';
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $pdo->prepare('UPDATE admins SET password_hash = :p WHERE username = :u');
                $upd->execute([':p' => $newHash, ':u' => $user]);
                $msg = 'Password updated.';
            }
        } catch (Throwable $e) { $msg = 'Error: ' . $e->getMessage(); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4" style="max-width:560px;">
    <h4>Change Password</h4>
    <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="row g-3">
        <div class="col-12">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" name="current" required>
        </div>
        <div class="col-12">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new" required>
        </div>
        <div class="col-12">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm" required>
        </div>
        <div class="col-12">
            <button class="btn btn-primary" type="submit">Update</button>
            <a class="btn btn-light" href="/admin/">Back</a>
        </div>
    </form>
</div>
</body>
</html>


