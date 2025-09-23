<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;
use Database\MigrationRunner;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { header('Location: /admin/login.php'); exit; }

$migrationRunner = new MigrationRunner();
$message = '';
$error = '';
$results = [];

// Handle actions
if (isset($_GET['action'])) {
    try {
        switch ($_GET['action']) {
            case 'migrate':
                $results = $migrationRunner->runAllPendingMigrations();
                if (empty($results)) {
                    $message = 'No pending migrations to run.';
                } else {
                    $successCount = count(array_filter($results, fn($r) => $r['success']));
                    $message = "Migration completed. {$successCount}/" . count($results) . " migrations successful.";
                }
                break;
                
            case 'validate':
                $issues = $migrationRunner->validateMigrations();
                if (empty($issues)) {
                    $message = 'All migrations are valid.';
                } else {
                    $error = 'Validation issues found: ' . implode(', ', $issues);
                }
                break;
                
            case 'info':
            default:
                // Just show info
                break;
        }
    } catch (\Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

$pendingMigrations = $migrationRunner->getPendingMigrations();
$migrationHistory = $migrationRunner->getMigrationHistory();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DB Migrations - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .migration-status { font-size: 0.9rem; }
        .migration-success { color: #198754; }
        .migration-error { color: #dc3545; }
        .migration-pending { color: #ffc107; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-database-gear me-2"></i>Database Migrations</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="mb-4">
        <div class="btn-group" role="group">
            <a class="btn btn-primary" href="?action=migrate">
                <i class="bi bi-play-circle me-1"></i>Run All Migrations
            </a>
            <a class="btn btn-outline-info" href="?action=validate">
                <i class="bi bi-check-circle me-1"></i>Validate
            </a>
            <a class="btn btn-outline-secondary" href="?action=info">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </a>
        </div>
    </div>

    <!-- Migration Results -->
    <?php if (!empty($results)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Migration Results</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Description</th>
                                <th>File</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><code>V<?= htmlspecialchars($result['version']) ?></code></td>
                                    <td><?= htmlspecialchars($result['description']) ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($result['file']) ?></small></td>
                                    <td>
                                        <?php if ($result['success']): ?>
                                            <span class="badge bg-success">Success</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $result['execution_time'] ?>ms</td>
                                </tr>
                                <?php if (!$result['success'] && $result['error']): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="alert alert-danger alert-sm mb-0">
                                                <strong>Error:</strong> <?= htmlspecialchars($result['error']) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Pending Migrations -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Pending Migrations</h6>
                    <span class="badge bg-warning"><?= count($pendingMigrations) ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingMigrations)): ?>
                        <p class="text-muted mb-0">No pending migrations. Database is up to date!</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendingMigrations as $migration): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <code class="migration-status">V<?= htmlspecialchars($migration['version']) ?></code>
                                            <div class="small text-muted"><?= htmlspecialchars($migration['description']) ?></div>
                                        </div>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Migration History -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-history me-2"></i>Migration History</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($migrationHistory)): ?>
                        <p class="text-muted mb-0">No migration history found.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($migrationHistory, 0, 10) as $history): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <code class="migration-status">V<?= htmlspecialchars($history['version']) ?></code>
                                            <div class="small text-muted"><?= htmlspecialchars($history['description']) ?></div>
                                            <div class="small text-muted">
                                                <i class="bi bi-calendar me-1"></i>
                                                <?= date('M j, Y H:i', strtotime($history['installed_on'])) ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($history['success']): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                            <div class="small text-muted"><?= $history['execution_time'] ?>ms</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($migrationHistory) > 10): ?>
                            <div class="text-center mt-2">
                                <small class="text-muted">Showing latest 10 migrations</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


