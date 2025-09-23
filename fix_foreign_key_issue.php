<?php
/**
 * Fix Foreign Key Constraint Issue
 * 
 * This script fixes the foreign key constraint issue by clearing data instead of dropping tables
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔧 Fixing Foreign Key Constraint Issue\n";
    echo "=====================================\n\n";
    
    echo "⚠️  This will clear all data but keep table structures.\n";
    echo "Press Enter to continue or Ctrl+C to cancel...\n";
    readline();
    
    echo "\n🗑️  Clearing all data...\n";
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Get all table names
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Clear all tables
    foreach ($tables as $table) {
        try {
            $pdo->exec("TRUNCATE TABLE `$table`");
            echo "  ✅ Cleared table: $table\n";
        } catch (Exception $e) {
            echo "  ⚠️  Could not clear table $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n📁 Running migrations...\n";
    
    // Run all migrations
    $migrationDir = __DIR__ . '/db/migration';
    $files = glob($migrationDir . '/*.sql');
    sort($files);
    
    foreach ($files as $file) {
        $filename = basename($file);
        echo "  📄 Running: $filename\n";
        
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
        } catch (Exception $e) {
            echo "    ❌ Error: " . $e->getMessage() . "\n";
            // Continue with other migrations even if one fails
        }
    }
    
    echo "\n🔍 Verifying installation...\n";
    
    // Check if essential tables exist
    $essentialTables = ['users', 'roles', 'user_roles'];
    foreach ($essentialTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ Table '$table' exists\n";
        } else {
            echo "  ❌ Table '$table' missing\n";
        }
    }
    
    // Check if admin user exists
    $stmt = $pdo->query("SELECT id, email FROM users WHERE email = 'admin@varsityresource.com'");
    $adminUser = $stmt->fetch();
    
    if ($adminUser) {
        echo "  ✅ Admin user exists (ID: {$adminUser['id']})\n";
        
        // Check if admin user has admin role
        $stmt = $pdo->prepare("
            SELECT r.name 
            FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.user_id = ? AND r.name = 'admin'
        ");
        $stmt->execute([$adminUser['id']]);
        $adminRole = $stmt->fetch();
        
        if ($adminRole) {
            echo "  ✅ Admin user has admin role\n";
        } else {
            echo "  ❌ Admin user missing admin role\n";
        }
    } else {
        echo "  ❌ Admin user not found\n";
    }
    
    echo "\n🎉 Foreign key issue fixed and installation completed!\n";
    echo "\n📋 Login Information:\n";
    echo "====================\n";
    echo "Email: admin@varsityresource.com\n";
    echo "Password: admin123\n";
    echo "URL: /admin/login.php\n";
    echo "\n⚠️  Remember to change the password after login!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database connection is working.\n";
    exit(1);
}
