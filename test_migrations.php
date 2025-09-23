<?php
/**
 * Test Migrations
 * 
 * This script tests all migrations to see which ones are working and which ones are failing
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🧪 Testing All Migrations\n";
    echo "========================\n\n";
    
    // Check if database is empty
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "📊 Database is empty - no tables found.\n";
    } else {
        echo "📊 Found " . count($tables) . " existing table(s):\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }
    
    echo "\n📁 Running all migrations...\n";
    
    // Run all migrations
    $migrationDir = __DIR__ . '/db/migration';
    $files = glob($migrationDir . '/*.sql');
    sort($files);
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($files as $file) {
        $filename = basename($file);
        echo "\n📄 Running: $filename\n";
        
        $sql = file_get_contents($file);
        if ($sql === '') {
            echo "    ⚠️  Empty file, skipping\n";
            continue;
        }
        
        // Remove CREATE DATABASE/USE lines for shared hosts
        $sql = preg_replace('/^\s*CREATE\s+DATABASE[\s\S]*?;\s*/im', '', $sql);
        $sql = preg_replace('/^\s*USE\s+[^;]+;\s*/im', '', $sql);
        
        try {
            $pdo->exec($sql);
            echo "    ✅ Success\n";
            $successCount++;
        } catch (Exception $e) {
            echo "    ❌ Error: " . $e->getMessage() . "\n";
            $failCount++;
            
            // Show the first few lines of the SQL for debugging
            $lines = explode("\n", $sql);
            echo "    📝 SQL preview (first 3 lines):\n";
            for ($i = 0; $i < min(3, count($lines)); $i++) {
                echo "       " . trim($lines[$i]) . "\n";
            }
        }
    }
    
    echo "\n📊 Migration Results:\n";
    echo "====================\n";
    echo "✅ Successful: $successCount\n";
    echo "❌ Failed: $failCount\n";
    
    // Check what tables were created
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📋 Tables created:\n";
    if (empty($tables)) {
        echo "  (No tables found)\n";
    } else {
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }
    
    // Check specifically for jobs table
    if (in_array('jobs', $tables)) {
        echo "\n✅ Jobs table exists!\n";
        
        // Check jobs table structure
        $stmt = $pdo->query("DESCRIBE jobs");
        $columns = $stmt->fetchAll();
        
        echo "Jobs table structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        echo "\n❌ Jobs table missing!\n";
    }
    
    // Check for other essential tables
    $essentialTables = ['users', 'roles', 'user_roles'];
    echo "\n🔍 Essential tables check:\n";
    foreach ($essentialTables as $table) {
        if (in_array($table, $tables)) {
            echo "  ✅ $table exists\n";
        } else {
            echo "  ❌ $table missing\n";
        }
    }
    
    if ($failCount > 0) {
        echo "\n⚠️  Some migrations failed. Check the errors above.\n";
        echo "💡 You may need to fix the migration files or database permissions.\n";
    } else {
        echo "\n🎉 All migrations completed successfully!\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database connection is working.\n";
    exit(1);
}
