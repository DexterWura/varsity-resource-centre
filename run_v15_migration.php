<?php
/**
 * Run V15 Migration Manually
 * 
 * This script runs the V15 migration to create the user_role_requests table
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔧 Running V15 Migration: user_role_requests table\n";
    echo "================================================\n\n";
    
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_role_requests'");
    if ($stmt->rowCount() > 0) {
        echo "✅ user_role_requests table already exists!\n";
        exit(0);
    }
    
    echo "📋 Creating user_role_requests table...\n";
    
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
        
        echo "  Executing: " . substr($statement, 0, 50) . "...\n";
        
        try {
            $pdo->exec($statement);
            echo "    ✅ Success\n";
        } catch (Exception $e) {
            echo "    ⚠️  Warning: " . $e->getMessage() . "\n";
            // Continue with other statements
        }
    }
    
    // Verify table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_role_requests'");
    if ($stmt->rowCount() > 0) {
        echo "\n✅ user_role_requests table created successfully!\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE user_role_requests");
        $columns = $stmt->fetchAll();
        
        echo "\n📋 Table structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        
        echo "\n🎉 V15 Migration completed successfully!\n";
        echo "\n💡 You can now:\n";
        echo "1. Access the user_roles.php page without errors\n";
        echo "2. Users can request roles from admin\n";
        echo "3. Admin can review role requests (future feature)\n";
        
    } else {
        echo "\n❌ Table creation failed!\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database connection is working.\n";
    exit(1);
}
