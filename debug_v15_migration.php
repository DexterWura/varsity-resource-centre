<?php
/**
 * Debug V15 Migration
 * 
 * This script will help diagnose why the V15 migration is failing
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔍 Debugging V15 Migration Issues\n";
    echo "================================\n\n";
    
    // Check if required tables exist
    echo "1. Checking required tables:\n";
    
    $requiredTables = ['users', 'roles'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ $table table exists\n";
        } else {
            echo "   ❌ $table table MISSING!\n";
        }
    }
    
    // Check if user_role_requests already exists
    echo "\n2. Checking if user_role_requests exists:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_role_requests'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ user_role_requests table already exists\n";
        echo "   📋 Table structure:\n";
        $stmt = $pdo->query("DESCRIBE user_role_requests");
        $columns = $stmt->fetchAll();
        foreach ($columns as $column) {
            echo "      - {$column['Field']} ({$column['Type']})\n";
        }
        exit(0);
    } else {
        echo "   ❌ user_role_requests table does not exist\n";
    }
    
    // Test the SQL step by step
    echo "\n3. Testing SQL step by step:\n";
    
    // Read the migration file
    $migrationFile = __DIR__ . '/db/migration/V15__user_role_requests.sql';
    $sql = file_get_contents($migrationFile);
    
    if ($sql === false) {
        throw new Exception("Could not read migration file: $migrationFile");
    }
    
    echo "   📄 Migration file content:\n";
    echo "   " . str_replace("\n", "\n   ", $sql) . "\n";
    
    // Try to execute the SQL
    echo "\n4. Attempting to execute SQL:\n";
    
    try {
        $pdo->exec($sql);
        echo "   ✅ SQL executed successfully!\n";
        
        // Verify table was created
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_role_requests'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ user_role_requests table created successfully!\n";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE user_role_requests");
            $columns = $stmt->fetchAll();
            
            echo "   📋 Final table structure:\n";
            foreach ($columns as $column) {
                echo "      - {$column['Field']} ({$column['Type']})\n";
            }
        } else {
            echo "   ❌ Table was not created despite successful execution\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ SQL execution failed: " . $e->getMessage() . "\n";
        echo "   🔍 Error details:\n";
        echo "      - Error Code: " . $e->getCode() . "\n";
        echo "      - SQL State: " . ($pdo->errorInfo()[0] ?? 'Unknown') . "\n";
        echo "      - Driver Error: " . ($pdo->errorInfo()[1] ?? 'Unknown') . "\n";
        echo "      - Driver Message: " . ($pdo->errorInfo()[2] ?? 'Unknown') . "\n";
    }
    
    // Check database engine and version
    echo "\n5. Database information:\n";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "   📊 Database Version: " . $version['version'] . "\n";
    
    $stmt = $pdo->query("SHOW ENGINES");
    $engines = $stmt->fetchAll();
    echo "   🔧 Available Engines:\n";
    foreach ($engines as $engine) {
        $status = $engine['Support'] === 'YES' ? '✅' : '❌';
        echo "      $status {$engine['Engine']} - {$engine['Support']}\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Debug script failed: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database connection is working.\n";
}
