<?php
/**
 * Create Admin User
 * 
 * This script creates an admin user in the new database system
 */

require_once __DIR__ . '/bootstrap.php';

use Auth\User;
use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔧 Create Admin User\n";
    echo "==================\n\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Users table doesn't exist. You need to run the installation first.\n";
        exit(1);
    }
    
    // Check if roles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Roles table doesn't exist. You need to run the installation first.\n";
        exit(1);
    }
    
    // Check if admin role exists
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin'");
    $stmt->execute();
    $adminRole = $stmt->fetch();
    
    if (!$adminRole) {
        echo "❌ Admin role doesn't exist. Creating it...\n";
        
        // Create admin role
        $stmt = $pdo->prepare("INSERT INTO roles (name, description, permissions) VALUES (?, ?, ?)");
        $stmt->execute([
            'admin',
            'Administrator with full access',
            json_encode(['*']) // All permissions
        ]);
        
        $adminRoleId = $pdo->lastInsertId();
        echo "✅ Admin role created with ID: $adminRoleId\n";
    } else {
        $adminRoleId = $adminRole['id'];
        echo "✅ Admin role exists with ID: $adminRoleId\n";
    }
    
    echo "\n";
    
    // Get admin user details
    echo "Please provide admin user details:\n";
    echo "=================================\n";
    
    // For demo purposes, let's create a default admin user
    $email = 'admin@varsityresource.com';
    $name = 'Super Admin';
    $password = 'admin123'; // You should change this!
    
    echo "📧 Email: $email\n";
    echo "👤 Name: $name\n";
    echo "🔑 Password: $password\n";
    echo "\n⚠️  WARNING: This is a default password. Please change it after login!\n\n";
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "⚠️  User with email '$email' already exists.\n";
        echo "💡 You can use this email to login, or create a different user.\n";
        
        // Check if user has admin role
        $stmt = $pdo->prepare("
            SELECT ur.id FROM user_roles ur 
            WHERE ur.user_id = ? AND ur.role_id = ?
        ");
        $stmt->execute([$existingUser['id'], $adminRoleId]);
        $hasAdminRole = $stmt->fetch();
        
        if ($hasAdminRole) {
            echo "✅ User already has admin role.\n";
            echo "🎉 You can login with email: $email\n";
        } else {
            echo "❌ User doesn't have admin role. Adding it...\n";
            
            // Add admin role to existing user
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$existingUser['id'], $adminRoleId]);
            
            echo "✅ Admin role added to existing user.\n";
            echo "🎉 You can now login with email: $email\n";
        }
    } else {
        echo "Creating new admin user...\n";
        
        // Create new user
        $user = User::create([
            'email' => $email,
            'name' => $name,
            'password' => $password
        ]);
        
        if ($user) {
            echo "✅ User created successfully with ID: " . $user->getId() . "\n";
            
            // Add admin role
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$user->getId(), $adminRoleId]);
            
            echo "✅ Admin role assigned to user.\n";
            echo "🎉 Admin user created successfully!\n";
        } else {
            echo "❌ Failed to create user.\n";
            exit(1);
        }
    }
    
    echo "\n";
    echo "🚀 Login Instructions:\n";
    echo "=====================\n";
    echo "1. Go to: /admin/login.php\n";
    echo "2. Email: $email\n";
    echo "3. Password: $password\n";
    echo "4. ⚠️  Change the password after login!\n";
    
    echo "\n";
    echo "🔧 Alternative: Use the old admin system temporarily\n";
    echo "===================================================\n";
    echo "If you prefer to use the old system temporarily:\n";
    echo "1. Go to: /admin/login.php (old system)\n";
    echo "2. Username: superadmin\n";
    echo "3. Password: admin123 (or check the hash in V1__init.sql)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database is set up and accessible.\n";
    exit(1);
}
