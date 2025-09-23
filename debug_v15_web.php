<?php
/**
 * Debug V15 Migration - Web Version
 * 
 * This script will help diagnose why the V15 migration is failing
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

$results = [];
$error = '';

try {
    $pdo = DB::pdo();
    
    $results[] = "🔍 Debugging V15 Migration Issues";
    $results[] = "================================\n";
    
    // Check if required tables exist
    $results[] = "1. Checking required tables:";
    
    $requiredTables = ['users', 'roles'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $results[] = "   ✅ $table table exists";
        } else {
            $results[] = "   ❌ $table table MISSING!";
        }
    }
    
    // Check if user_role_requests already exists
    $results[] = "\n2. Checking if user_role_requests exists:";
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_role_requests'");
    if ($stmt->rowCount() > 0) {
        $results[] = "   ✅ user_role_requests table already exists";
        $results[] = "   📋 Table structure:";
        $stmt = $pdo->query("DESCRIBE user_role_requests");
        $columns = $stmt->fetchAll();
        foreach ($columns as $column) {
            $results[] = "      - {$column['Field']} ({$column['Type']})";
        }
    } else {
        $results[] = "   ❌ user_role_requests table does not exist";
    }
    
    // Test the SQL step by step
    $results[] = "\n3. Testing SQL step by step:";
    
    // Read the migration file
    $migrationFile = __DIR__ . '/db/migration/V15__user_role_requests.sql';
    $sql = file_get_contents($migrationFile);
    
    if ($sql === false) {
        throw new Exception("Could not read migration file: $migrationFile");
    }
    
    $results[] = "   📄 Migration file content:";
    $results[] = "   " . str_replace("\n", "\n   ", $sql);
    
    // Try to execute the SQL
    $results[] = "\n4. Attempting to execute SQL:";
    
    try {
        $pdo->exec($sql);
        $results[] = "   ✅ SQL executed successfully!";
        
        // Verify table was created
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_role_requests'");
        if ($stmt->rowCount() > 0) {
            $results[] = "   ✅ user_role_requests table created successfully!";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE user_role_requests");
            $columns = $stmt->fetchAll();
            
            $results[] = "   📋 Final table structure:";
            foreach ($columns as $column) {
                $results[] = "      - {$column['Field']} ({$column['Type']})";
            }
        } else {
            $results[] = "   ❌ Table was not created despite successful execution";
        }
        
    } catch (Exception $e) {
        $results[] = "   ❌ SQL execution failed: " . $e->getMessage();
        $results[] = "   🔍 Error details:";
        $results[] = "      - Error Code: " . $e->getCode();
        $results[] = "      - SQL State: " . ($pdo->errorInfo()[0] ?? 'Unknown');
        $results[] = "      - Driver Error: " . ($pdo->errorInfo()[1] ?? 'Unknown');
        $results[] = "      - Driver Message: " . ($pdo->errorInfo()[2] ?? 'Unknown');
    }
    
    // Check database engine and version
    $results[] = "\n5. Database information:";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    $results[] = "   📊 Database Version: " . $version['version'];
    
    $stmt = $pdo->query("SHOW ENGINES");
    $engines = $stmt->fetchAll();
    $results[] = "   🔧 Available Engines:";
    foreach ($engines as $engine) {
        $status = $engine['Support'] === 'YES' ? '✅' : '❌';
        $results[] = "      $status {$engine['Engine']} - {$engine['Support']}";
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
    <title>Debug V15 Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="fas fa-bug me-2"></i>
                            Debug V15 Migration
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="bg-dark text-light p-3 rounded">
                            <h6 class="mb-3">Debug Output:</h6>
                            <pre class="mb-0" style="white-space: pre-wrap; font-family: monospace; font-size: 0.9em;"><?= htmlspecialchars(implode("\n", $results)) ?></pre>
                        </div>
                        
                        <div class="mt-4">
                            <a href="/run_migration_web.php" class="btn btn-primary">
                                <i class="fas fa-play me-2"></i>Try Migration Again
                            </a>
                            <a href="/user_roles.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-cog me-2"></i>Test User Roles Page
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
