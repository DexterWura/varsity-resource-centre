<?php
/**
 * Web-based Migration Runner
 * 
 * Run this in your browser to execute the V15 migration
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

$success = false;
$error = '';
$output = [];

try {
    $pdo = DB::pdo();
    
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_role_requests'");
    if ($stmt->rowCount() > 0) {
        $success = true;
        $output[] = "✅ user_role_requests table already exists!";
    } else {
        $output[] = "📋 Creating user_role_requests table...";
        
        // Read and execute the V15 migration
        $migrationFile = __DIR__ . '/db/migration/V15__user_role_requests.sql';
        $sql = file_get_contents($migrationFile);
        
        if ($sql === false) {
            throw new Exception("Could not read migration file: $migrationFile");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue; // Skip empty statements and comments
            }
            
            $output[] = "  Executing: " . substr($statement, 0, 50) . "...";
            
            try {
                $pdo->exec($statement);
                $output[] = "    ✅ Success";
            } catch (Exception $e) {
                $output[] = "    ⚠️  Warning: " . $e->getMessage();
                // Continue with other statements
            }
        }
        
        // Verify table was created
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_role_requests'");
        if ($stmt->rowCount() > 0) {
            $success = true;
            $output[] = "✅ user_role_requests table created successfully!";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE user_role_requests");
            $columns = $stmt->fetchAll();
            
            $output[] = "📋 Table structure:";
            foreach ($columns as $column) {
                $output[] = "  - {$column['Field']} ({$column['Type']})";
            }
        } else {
            throw new Exception("Table creation failed!");
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Runner - V15</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Migration Runner - V15
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Migration Completed Successfully!</strong>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="bg-dark text-light p-3 rounded">
                            <h6 class="mb-3">Migration Output:</h6>
                            <pre class="mb-0" style="white-space: pre-wrap; font-family: monospace; font-size: 0.9em;"><?= htmlspecialchars(implode("\n", $output)) ?></pre>
                        </div>
                        
                        <?php if ($success): ?>
                            <div class="mt-4">
                                <h6>Next Steps:</h6>
                                <ul>
                                    <li>✅ The <code>user_role_requests</code> table has been created</li>
                                    <li>✅ You can now access <a href="/user_roles.php" class="btn btn-sm btn-primary">user_roles.php</a> without errors</li>
                                    <li>✅ Users can request roles from admin</li>
                                    <li>⏳ Admin role review system (future feature)</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="/user_roles.php" class="btn btn-primary">
                                <i class="fas fa-user-cog me-2"></i>Go to User Roles Page
                            </a>
                            <a href="/admin/migrations.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>View All Migrations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
