<?php
// Simple migration runner
require_once __DIR__ . '/bootstrap.php';
use Database\DB;

try {
    $pdo = DB::pdo();
    $sql = file_get_contents('db/migration/V6__reviews_system.sql');
    $pdo->exec($sql);
    echo "Reviews system migration completed successfully!<br>";
    echo "You can now delete this file (run_migration.php) as it's no longer needed.";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage();
}
?>