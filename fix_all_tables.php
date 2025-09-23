<?php
/**
 * Fix All Tables
 * 
 * This script creates all essential tables in the correct order
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔧 Creating All Essential Tables\n";
    echo "===============================\n\n";
    
    // Check current tables
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Existing tables: " . (empty($existingTables) ? "None" : implode(", ", $existingTables)) . "\n\n";
    
    // Define essential tables in order
    $essentialTables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                full_name VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'roles' => "
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                permissions JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'user_roles' => "
            CREATE TABLE IF NOT EXISTS user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_role (user_id, role_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'jobs' => "
            CREATE TABLE IF NOT EXISTS jobs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                company_name VARCHAR(255) DEFAULT NULL,
                location VARCHAR(255) DEFAULT NULL,
                description MEDIUMTEXT,
                url VARCHAR(1024) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'houses' => "
            CREATE TABLE IF NOT EXISTS houses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) DEFAULT NULL,
                location VARCHAR(255) DEFAULT NULL,
                bedrooms INT DEFAULT NULL,
                bathrooms INT DEFAULT NULL,
                area_sqft INT DEFAULT NULL,
                property_type VARCHAR(100) DEFAULT 'House',
                status VARCHAR(50) DEFAULT 'For Rent',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'businesses' => "
            CREATE TABLE IF NOT EXISTS businesses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(100) DEFAULT NULL,
                location VARCHAR(255) DEFAULT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                email VARCHAR(255) DEFAULT NULL,
                website VARCHAR(500) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'articles' => "
            CREATE TABLE IF NOT EXISTS articles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT,
                author VARCHAR(255) DEFAULT NULL,
                published_at TIMESTAMP NULL DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        "
    ];
    
    $createdCount = 0;
    $skippedCount = 0;
    
    // Create tables in order
    foreach ($essentialTables as $tableName => $sql) {
        echo "📋 Creating table: $tableName\n";
        
        if (in_array($tableName, $existingTables)) {
            echo "    ⏭️  Already exists, skipping\n";
            $skippedCount++;
            continue;
        }
        
        try {
            $pdo->exec($sql);
            echo "    ✅ Created successfully\n";
            $createdCount++;
        } catch (Exception $e) {
            echo "    ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 Results:\n";
    echo "✅ Created: $createdCount tables\n";
    echo "⏭️  Skipped: $skippedCount tables\n";
    
    // Insert essential data
    echo "\n🔧 Inserting essential data...\n";
    
    // Insert default roles
    try {
        $pdo->exec("INSERT IGNORE INTO roles (name, permissions) VALUES 
            ('admin', '[\"admin\", \"manage_users\", \"manage_content\", \"manage_settings\"]'),
            ('user', '[\"view_content\", \"create_content\"]')
        ");
        echo "✅ Default roles inserted\n";
    } catch (Exception $e) {
        echo "❌ Error inserting roles: " . $e->getMessage() . "\n";
    }
    
    // Insert admin user
    try {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT IGNORE INTO users (email, full_name, password_hash, is_active) VALUES 
            ('admin@varsityresource.com', 'Super Admin', '$adminPassword', 1)
        ");
        echo "✅ Admin user inserted\n";
    } catch (Exception $e) {
        echo "❌ Error inserting admin user: " . $e->getMessage() . "\n";
    }
    
    // Assign admin role to admin user
    try {
        $pdo->exec("INSERT IGNORE INTO user_roles (user_id, role_id) 
            SELECT u.id, r.id 
            FROM users u, roles r 
            WHERE u.email = 'admin@varsityresource.com' AND r.name = 'admin'
        ");
        echo "✅ Admin role assigned\n";
    } catch (Exception $e) {
        echo "❌ Error assigning admin role: " . $e->getMessage() . "\n";
    }
    
    // Final verification
    echo "\n🔍 Final verification:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $finalTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 All tables: " . implode(", ", $finalTables) . "\n";
    
    // Check essential tables
    $essentialTableNames = array_keys($essentialTables);
    $missingTables = array_diff($essentialTableNames, $finalTables);
    
    if (empty($missingTables)) {
        echo "✅ All essential tables created!\n";
    } else {
        echo "❌ Missing tables: " . implode(", ", $missingTables) . "\n";
    }
    
    // Test jobs table specifically
    if (in_array('jobs', $finalTables)) {
        echo "✅ Jobs table exists and ready!\n";
        
        // Test insert
        try {
            $stmt = $pdo->prepare("INSERT INTO jobs (title, company_name, location, description, url, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['Test Job', 'Test Company', 'Test Location', 'Test description', 'https://example.com', 1]);
            echo "✅ Jobs table is working (test insert successful)\n";
        } catch (Exception $e) {
            echo "❌ Jobs table test failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Database setup complete!\n";
    echo "\n💡 You can now:\n";
    echo "1. Try running the installation again\n";
    echo "2. Login with: admin@varsityresource.com / admin123\n";
    echo "3. Go to: /admin/login.php\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database connection is working.\n";
    exit(1);
}
