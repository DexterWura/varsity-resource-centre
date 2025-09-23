<?php
/**
 * Debug Admin Authentication
 * 
 * This script helps debug why admin pages are redirecting to dashboard.php
 */

require_once __DIR__ . '/bootstrap.php';

use Auth\UserAuth;
use Auth\Auth;

echo "🔍 Debugging Admin Authentication\n";
echo "================================\n\n";

// Test new UserAuth system
echo "1. Testing UserAuth System:\n";
echo "----------------------------\n";

$userAuth = new UserAuth();
echo "UserAuth check(): " . ($userAuth->check() ? '✅ TRUE' : '❌ FALSE') . "\n";

if ($userAuth->check()) {
    $user = $userAuth->user();
    echo "User ID: " . $user->getId() . "\n";
    echo "User Email: " . $user->getEmail() . "\n";
    echo "User Name: " . $user->getName() . "\n";
    echo "Has admin role: " . ($user->hasRole('admin') ? '✅ TRUE' : '❌ FALSE') . "\n";
    
    // Show all roles
    $roles = $user->getRoles();
    echo "All roles: " . (empty($roles) ? 'None' : implode(', ', array_column($roles, 'name'))) . "\n";
} else {
    echo "❌ User not authenticated with UserAuth\n";
}

echo "\n";

// Test old Auth system
echo "2. Testing Old Auth System:\n";
echo "----------------------------\n";

$auth = new Auth(__DIR__ . '/storage/users/admins.json');
echo "Old Auth check(): " . ($auth->check() ? '✅ TRUE' : '❌ FALSE') . "\n";

if ($auth->check()) {
    $user = $auth->user();
    echo "Admin user: " . $user['username'] . "\n";
} else {
    echo "❌ Admin not authenticated with old Auth system\n";
}

echo "\n";

// Check if admin users exist in database
echo "3. Checking Database Admin Users:\n";
echo "----------------------------------\n";

try {
    $pdo = \Database\DB::pdo();
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Users table doesn't exist\n";
    } else {
        echo "✅ Users table exists\n";
        
        // Check if user_roles table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_roles'");
        if ($stmt->rowCount() === 0) {
            echo "❌ User_roles table doesn't exist\n";
        } else {
            echo "✅ User_roles table exists\n";
            
            // Check for admin users
            $stmt = $pdo->query("
                SELECT u.id, u.email, u.name, r.name as role_name 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                LEFT JOIN roles r ON ur.role_id = r.id 
                WHERE r.name = 'admin' OR u.id IN (SELECT user_id FROM user_roles WHERE role_id = (SELECT id FROM roles WHERE name = 'admin'))
            ");
            $adminUsers = $stmt->fetchAll();
            
            if (empty($adminUsers)) {
                echo "❌ No admin users found in database\n";
            } else {
                echo "✅ Found " . count($adminUsers) . " admin user(s):\n";
                foreach ($adminUsers as $admin) {
                    echo "   - ID: {$admin['id']}, Email: {$admin['email']}, Name: {$admin['name']}, Role: {$admin['role_name']}\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// Check admin JSON file
echo "4. Checking Admin JSON File:\n";
echo "-----------------------------\n";

$adminFile = __DIR__ . '/storage/users/admins.json';
if (!file_exists($adminFile)) {
    echo "❌ Admin JSON file doesn't exist: $adminFile\n";
} else {
    echo "✅ Admin JSON file exists\n";
    $adminData = json_decode(file_get_contents($adminFile), true);
    if (empty($adminData)) {
        echo "❌ Admin JSON file is empty\n";
    } else {
        echo "✅ Admin JSON file has " . count($adminData) . " admin(s)\n";
        foreach ($adminData as $username => $admin) {
            echo "   - Username: $username, Email: {$admin['email']}\n";
        }
    }
}

echo "\n";

// Recommendations
echo "5. Recommendations:\n";
echo "-------------------\n";

if (!$userAuth->check()) {
    echo "💡 User not authenticated. Try logging in at /login.php\n";
} elseif (!$userAuth->user()->hasRole('admin')) {
    echo "💡 User authenticated but doesn't have admin role. Check user roles in database.\n";
} else {
    echo "✅ UserAuth authentication is working correctly.\n";
}

if (!$auth->check()) {
    echo "💡 Old Auth system not working. This might be why dashboard.php redirects.\n";
}

echo "\n🔧 To fix the issue:\n";
echo "1. Make sure you're logged in with a user that has admin role\n";
echo "2. Or update dashboard.php to use UserAuth instead of old Auth system\n";
echo "3. Or create an admin user in the old JSON system\n";
