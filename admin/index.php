<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;
use Config\Settings;
use Database\DB;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { header('Location: /admin/login.php'); exit; }
$settings = new Settings(__DIR__ . '/../storage/settings.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        $auth->logout();
        header('Location: /admin/login.php');
        exit;
    }
    $updates = [
        'adsense_client' => trim((string)($_POST['adsense_client'] ?? '')),
        'adsense_slot_header' => trim((string)($_POST['adsense_slot_header'] ?? '')),
        'adsense_slot_sidebar' => trim((string)($_POST['adsense_slot_sidebar'] ?? '')),
        'donate_url' => trim((string)($_POST['donate_url'] ?? '')),
        'theme' => [
            'primary' => (string)($_POST['theme_primary'] ?? '#0d6efd'),
            'secondary' => (string)($_POST['theme_secondary'] ?? '#6c757d'),
            'background' => (string)($_POST['theme_background'] ?? '#f8f9fa'),
        ],
    ];
    $notificationsJson = (string)($_POST['notifications_json'] ?? '[]');
    $notifications = json_decode($notificationsJson, true);
    if (is_array($notifications)) { $updates['notifications'] = $notifications; }
    // DB-backed notifications CRUD
    if (!empty($_POST['new_message'])) {
        try { $pdo = DB::pdo(); $stmt = $pdo->prepare('INSERT INTO notifications (message, type, is_active) VALUES (:m, :t, 1)'); $stmt->execute([':m' => (string)$_POST['new_message'], ':t' => (string)($_POST['new_type'] ?? 'info')]); } catch (\Throwable $e) {}
    }
    if (!empty($_POST['toggle_id'])) {
        try { $pdo = DB::pdo(); $stmt = $pdo->prepare('UPDATE notifications SET is_active = 1 - is_active WHERE id = :id'); $stmt->execute([':id' => (int)$_POST['toggle_id']]); } catch (\Throwable $e) {}
    }
    if (!empty($_POST['delete_id'])) {
        try { $pdo = DB::pdo(); $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = :id'); $stmt->execute([':id' => (int)$_POST['delete_id']]); } catch (\Throwable $e) {}
    }
    $settings->setMany($updates);
    $saved = true;
}

$data = $settings->all();
// fetch notifications list
$rows = [];
try { $pdo = DB::pdo(); $rows = $pdo->query('SELECT id, message, type, is_active, created_at FROM notifications ORDER BY id DESC LIMIT 25')->fetchAll(); } catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Super Admin Dashboard</h4>
        <form method="post">
            <input type="hidden" name="action" value="logout">
            <button class="btn btn-outline-secondary btn-sm" type="submit">Logout</button>
        </form>
    </div>
    <?php if (!empty($saved)): ?><div class="alert alert-success">Settings saved.</div><?php endif; ?>
    <form method="post" class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>Google AdSense</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Adsense Client ID</label>
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
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>Donations</h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Donate URL (PayPal/Paynow/BuyMeACoffee/etc)</label>
                            <input class="form-control" name="donate_url" value="<?= htmlspecialchars($data['donate_url'] ?? '') ?>" placeholder="https://...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>Theme</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Primary</label>
                            <input type="color" class="form-control form-control-color" name="theme_primary" value="<?= htmlspecialchars($data['theme']['primary'] ?? '#0d6efd') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Secondary</label>
                            <input type="color" class="form-control form-control-color" name="theme_secondary" value="<?= htmlspecialchars($data['theme']['secondary'] ?? '#6c757d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Background</label>
                            <input type="color" class="form-control form-control-color" name="theme_background" value="<?= htmlspecialchars($data['theme']['background'] ?? '#f8f9fa') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>Visitor Notifications (JSON array of {message,type})</h6>
                    <textarea class="form-control" name="notifications_json" rows="5"><?= htmlspecialchars(json_encode($data['notifications'] ?? [], JSON_PRETTY_PRINT)) ?></textarea>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>Manage Live Notifications (Database)</h6>
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-9">
                            <label class="form-label">Message</label>
                            <input name="new_message" class="form-control" placeholder="e.g. Good day students, we are live now!">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="new_type" class="form-select">
                                <option value="info">info</option>
                                <option value="success">success</option>
                                <option value="warning">warning</option>
                                <option value="danger">danger</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-primary w-100" type="submit">Add</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>ID</th><th>Message</th><th>Type</th><th>Active</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td class="text-truncate" style="max-width:400px;" title="<?= htmlspecialchars($r['message']) ?>"><?= htmlspecialchars($r['message']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['type']) ?></span></td>
                                    <td><?= ((int)$r['is_active']===1 ? 'Yes' : 'No') ?></td>
                                    <td class="d-flex gap-2">
                                        <button name="toggle_id" value="<?= (int)$r['id'] ?>" class="btn btn-sm btn-light">Toggle</button>
                                        <button name="delete_id" value="<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-primary" type="submit">Save Settings</button>
        </div>
    </form>
</div>
</body>
</html>


