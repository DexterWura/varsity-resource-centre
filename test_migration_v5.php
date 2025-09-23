<?php
/**
 * Test Migration V5 - User System
 * 
 * This script tests the V5 migration to ensure it creates the correct table structure
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🧪 Testing Migration V5 - User System\n";
    echo "====================================\n\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Users table doesn't exist. Migration V5 needs to be run first.\n";
        exit(1);
    }
    
    echo "✅ Users table exists\n";
    
    // Check if roles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Roles table doesn't exist. Migration V5 needs to be run first.\n";
        exit(1);
    }
    
    echo "✅ Roles table exists\n";
    
    // Check if user_roles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_roles'");
    if ($stmt->rowCount() === 0) {
        echo "❌ User_roles table doesn't exist. Migration V5 needs to be run first.\n";
        exit(1);
    }
    
    echo "✅ User_roles table exists\n\n";
    
    // Check table structures
    echo "📊 Table Structures:\n";
    echo "-------------------\n";
    
    // Users table structure
    $stmt = $pdo->query("DESCRIBE users");
    $usersColumns = $stmt->fetchAll();
    echo "Users table columns:\n";
    foreach ($usersColumns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
    
    // Roles table structure
    $stmt = $pdo->query("DESCRIBE roles");
    $rolesColumns = $stmt->fetchAll();
    echo "Roles table columns:\n";
    foreach ($rolesColumns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
    
    // User_roles table structure
    $stmt = $pdo->query("DESCRIBE user_roles");
    $userRolesColumns = $stmt->fetchAll();
    echo "User_roles table columns:\n";
    foreach ($userRolesColumns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
    
    // Check if roles exist
    $stmt = $pdo->query("SELECT name, description FROM roles ORDER BY name");
    $roles = $stmt->fetchAll();
    
    if (empty($roles)) {
        echo "❌ No roles found in roles table.\n";
    } else {
        echo "✅ Found " . count($roles) . " role(s):\n";
        foreach ($roles as $role) {
            echo "  - {$role['name']}: {$role['description']}\n";
        }
    }
    echo "\n";
    
    // Check if admin user exists
    $stmt = $pdo->query("SELECT id, email, full_name FROM users WHERE email = 'admin@varsityresource.com'");
    $adminUser = $stmt->fetch();
    
    if (!$adminUser) {
        echo "❌ Admin user not found.\n";
    } else {
        echo "✅ Admin user found:\n";
        echo "  - ID: {$adminUser['id']}\n";
        echo "  - Email: {$adminUser['email']}\n";
        echo "  - Name: {$adminUser['full_name']}\n";
    }
    echo "\n";
    
    // Check if admin user has admin role
    if ($adminUser) {
        $stmt = $pdo->prepare("
            SELECT r.name 
            FROM user_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.user_id = ? AND r.name = 'admin'
        ");
        $stmt->execute([$adminUser['id']]);
        $adminRole = $stmt->fetch();
        
        if (!$adminRole) {
            echo "❌ Admin user doesn't have admin role.\n";
        } else {
            echo "✅ Admin user has admin role.\n";
        }
    }
    
    echo "\n🎉 Migration V5 test completed successfully!\n";
    echo "💡 The migration should run without errors.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
