<?php
/**
 * Check Database Status
 * 
 * Simple script to check what's in the database
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔍 Database Status Check\n";
    echo "=======================\n\n";
    
    // Check if we can connect
    echo "✅ Database connection successful\n";
    
    // Check what tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📋 Tables in database:\n";
    if (empty($tables)) {
        echo "  (No tables found - database is empty)\n";
    } else {
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }
    
    // Check if jobs table exists
    if (in_array('jobs', $tables)) {
        echo "\n✅ Jobs table exists!\n";
        
        // Check structure
        $stmt = $pdo->query("DESCRIBE jobs");
        $columns = $stmt->fetchAll();
        
        echo "Jobs table structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check if there are any jobs
        $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
        $count = $stmt->fetchColumn();
        echo "Number of jobs: $count\n";
        
    } else {
        echo "\n❌ Jobs table does NOT exist!\n";
        echo "This is why you're getting the error.\n";
    }
    
    // Check migration files
    echo "\n📁 Migration files:\n";
    $migrationDir = __DIR__ . '/db/migration';
    $files = glob($migrationDir . '/*.sql');
    sort($files);
    
    foreach ($files as $file) {
        $filename = basename($file);
        echo "  - $filename\n";
    }
    
    echo "\n💡 Next steps:\n";
    if (!in_array('jobs', $tables)) {
        echo "1. Run: php test_migrations.php (to test all migrations)\n";
        echo "2. Or run the installation again\n";
        echo "3. Check if there are any permission issues\n";
    } else {
        echo "1. Jobs table exists - the error might be elsewhere\n";
        echo "2. Check if the application is looking in the right database\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database connection is working.\n";
    exit(1);
}
