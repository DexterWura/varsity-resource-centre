<?php
/**
 * Fix User Roles Table Now
 * 
 * This script immediately fixes the user_roles table structure issue
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔧 Fixing User Roles Table Structure\n";
    echo "===================================\n\n";
    
    echo "This will fix the 'Unknown column user_id' error.\n";
    echo "Press Enter to continue or Ctrl+C to cancel...\n";
    readline();
    
    echo "\n🔍 Checking current user_roles table structure...\n";
    
    // Check if user_roles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_roles'");
    if ($stmt->rowCount() === 0) {
        echo "❌ user_roles table doesn't exist. Creating it...\n";
    } else {
        echo "✅ user_roles table exists. Checking structure...\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE user_roles");
        $columns = $stmt->fetchAll();
        
        echo "Current columns:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check if it has the wrong structure
        $hasUserId = false;
        $hasName = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'user_id') {
                $hasUserId = true;
            }
            if ($column['Field'] === 'name') {
                $hasName = true;
            }
        }
        
        if ($hasName && !$hasUserId) {
            echo "\n⚠️  Found wrong table structure (has 'name' but no 'user_id').\n";
            echo "This is causing the error. Fixing it...\n";
        } elseif ($hasUserId) {
            echo "\n✅ Table structure looks correct.\n";
        }
    }
    
    echo "\n🔧 Applying fix...\n";
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "  ✅ Disabled foreign key checks\n";
    
    // Drop the table
    $pdo->exec("DROP TABLE IF EXISTS user_roles");
    echo "  ✅ Dropped user_roles table\n";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "  ✅ Re-enabled foreign key checks\n";
    
    // Create the table with correct structure
    $pdo->exec("
        CREATE TABLE user_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_role (user_id, role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "  ✅ Created user_roles table with correct structure\n";
    
    // Check if admin user exists
    $stmt = $pdo->query("SELECT id FROM users WHERE email = 'admin@varsityresource.com'");
    $adminUser = $stmt->fetch();
    
    if ($adminUser) {
        echo "  ✅ Admin user found (ID: {$adminUser['id']})\n";
        
        // Check if admin role exists
        $stmt = $pdo->query("SELECT id FROM roles WHERE name = 'admin'");
        $adminRole = $stmt->fetch();
        
        if ($adminRole) {
            echo "  ✅ Admin role found (ID: {$adminRole['id']})\n";
            
            // Assign admin role to admin user
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO user_roles (user_id, role_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$adminUser['id'], $adminRole['id']]);
            echo "  ✅ Assigned admin role to admin user\n";
        } else {
            echo "  ❌ Admin role not found\n";
        }
    } else {
        echo "  ❌ Admin user not found\n";
    }
    
    echo "\n🔍 Verifying fix...\n";
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE user_roles");
    $columns = $stmt->fetchAll();
    
    echo "New table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Test the INSERT that was failing
    try {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO user_roles (user_id, role_id) 
            SELECT u.id, r.id 
            FROM users u, roles r 
            WHERE u.email = 'admin@varsityresource.com' AND r.name = 'admin'
        ");
        $stmt->execute();
        echo "\n✅ Test INSERT successful - the error is fixed!\n";
    } catch (Exception $e) {
        echo "\n❌ Test INSERT failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 User roles table structure fixed!\n";
    echo "\n📋 You can now:\n";
    echo "1. Try running the installation again\n";
    echo "2. Or login with: admin@varsityresource.com / admin123\n";
    echo "3. Go to: /admin/login.php\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database connection is working.\n";
    exit(1);
}
