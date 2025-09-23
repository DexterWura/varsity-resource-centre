<?php
/**
 * Check Admin Users
 * 
 * This script shows what admin users are available for login
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔍 Checking Admin Users for Login\n";
    echo "================================\n\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Users table doesn't exist. You need to run the installation first.\n";
        exit(1);
    }
    
    echo "✅ Users table exists\n\n";
    
    // Check if user_roles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_roles'");
    if ($stmt->rowCount() === 0) {
        echo "❌ User_roles table doesn't exist. You need to run the installation first.\n";
        exit(1);
    }
    
    echo "✅ User_roles table exists\n\n";
    
    // Check if roles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Roles table doesn't exist. You need to run the installation first.\n";
        exit(1);
    }
    
    echo "✅ Roles table exists\n\n";
    
    // Get all admin users
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.name, u.is_active, r.name as role_name 
        FROM users u 
        LEFT JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN roles r ON ur.role_id = r.id 
        WHERE r.name = 'admin' AND u.is_active = 1
        ORDER BY u.id
    ");
    $adminUsers = $stmt->fetchAll();
    
    if (empty($adminUsers)) {
        echo "❌ No admin users found in the database.\n\n";
        
        // Check if there are any users at all
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        
        if ($userCount == 0) {
            echo "💡 No users exist in the database. You need to:\n";
            echo "   1. Run the installation process\n";
            echo "   2. Or create a user account first\n";
        } else {
            echo "💡 There are {$userCount} user(s) in the database, but none have admin role.\n";
            echo "   You need to assign admin role to an existing user.\n";
        }
        
        echo "\n🔧 To create an admin user:\n";
        echo "   1. Go to /register.php and create a user account\n";
        echo "   2. Then assign admin role to that user in the database\n";
        echo "   3. Or run the installation which should create an admin user\n";
        
    } else {
        echo "✅ Found " . count($adminUsers) . " admin user(s):\n\n";
        foreach ($adminUsers as $admin) {
            echo "📧 Email: {$admin['email']}\n";
            echo "👤 Name: {$admin['name']}\n";
            echo "🆔 ID: {$admin['id']}\n";
            echo "🔑 Role: {$admin['role_name']}\n";
            echo "✅ Status: " . ($admin['is_active'] ? 'Active' : 'Inactive') . "\n";
            echo "---\n";
        }
        
        echo "\n💡 Use any of these email addresses to login to the admin panel.\n";
        echo "   Go to: /admin/login.php\n";
    }
    
    echo "\n";
    
    // Also check the old JSON admin file for reference
    echo "📁 Checking Old Admin JSON File (for reference):\n";
    echo "-----------------------------------------------\n";
    
    $adminFile = __DIR__ . '/storage/users/admins.json';
    if (!file_exists($adminFile)) {
        echo "❌ Old admin JSON file doesn't exist: $adminFile\n";
    } else {
        echo "✅ Old admin JSON file exists\n";
        $adminData = json_decode(file_get_contents($adminFile), true);
        if (empty($adminData)) {
            echo "❌ Old admin JSON file is empty\n";
        } else {
            echo "📊 Old admin JSON file has " . count($adminData) . " admin(s):\n";
            foreach ($adminData as $username => $admin) {
                echo "   - Username: $username, Email: {$admin['email']}\n";
            }
            echo "\n💡 Note: The system now uses database authentication, not this JSON file.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database is set up and accessible.\n";
    exit(1);
}
