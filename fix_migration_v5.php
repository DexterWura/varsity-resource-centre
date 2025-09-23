<?php
/**
 * Fix Migration V5 Issues
 * 
 * This script helps fix issues with the V5 migration by cleaning up conflicting tables
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔧 Fixing Migration V5 Issues\n";
    echo "============================\n\n";
    
    // Check if there's a conflicting user_roles table structure
    $stmt = $pdo->query("DESCRIBE user_roles");
    $columns = $stmt->fetchAll();
    
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
    
    echo "📊 Current user_roles table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
    
    if ($hasName && !$hasUserId) {
        echo "⚠️  Found conflicting user_roles table structure.\n";
        echo "   The table has 'name' column but no 'user_id' column.\n";
        echo "   This suggests it was created as a roles table instead of user-role assignments.\n\n";
        
        echo "🔧 Fixing the issue...\n";
        
        // Drop the conflicting table
        $pdo->exec("DROP TABLE IF EXISTS user_roles");
        echo "✅ Dropped conflicting user_roles table\n";
        
        // Recreate the correct table structure
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
        echo "✅ Created correct user_roles table structure\n";
        
        // Reassign admin role to admin user
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO user_roles (user_id, role_id) 
            SELECT u.id, r.id 
            FROM users u, roles r 
            WHERE u.email = 'admin@varsityresource.com' AND r.name = 'admin'
        ");
        $stmt->execute();
        echo "✅ Reassigned admin role to admin user\n";
        
    } elseif ($hasUserId) {
        echo "✅ user_roles table structure is correct.\n";
    } else {
        echo "❌ user_roles table doesn't exist or has unexpected structure.\n";
    }
    
    echo "\n";
    
    // Verify the fix
    echo "🔍 Verifying the fix...\n";
    
    $stmt = $pdo->query("DESCRIBE user_roles");
    $columns = $stmt->fetchAll();
    
    echo "📊 Updated user_roles table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Check if admin user has admin role
    $stmt = $pdo->query("
        SELECT u.email, r.name as role_name
        FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE u.email = 'admin@varsityresource.com' AND r.name = 'admin'
    ");
    $adminRole = $stmt->fetch();
    
    if ($adminRole) {
        echo "\n✅ Admin user has admin role assigned.\n";
    } else {
        echo "\n❌ Admin user doesn't have admin role. Assigning it...\n";
        
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO user_roles (user_id, role_id) 
            SELECT u.id, r.id 
            FROM users u, roles r 
            WHERE u.email = 'admin@varsityresource.com' AND r.name = 'admin'
        ");
        $stmt->execute();
        echo "✅ Admin role assigned to admin user.\n";
    }
    
    echo "\n🎉 Migration V5 issues have been fixed!\n";
    echo "💡 You can now try running the installation again.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Try running: php reset_database.php\n";
    exit(1);
}
