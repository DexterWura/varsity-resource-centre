<?php
/**
 * Create Jobs Table
 * 
 * This script creates the jobs table directly
 */

require_once __DIR__ . '/bootstrap.php';

use Database\DB;

try {
    $pdo = DB::pdo();
    
    echo "🔧 Creating Jobs Table\n";
    echo "=====================\n\n";
    
    // Check if jobs table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'jobs'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Jobs table already exists!\n";
        
        // Show structure
        $stmt = $pdo->query("DESCRIBE jobs");
        $columns = $stmt->fetchAll();
        
        echo "Current structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        exit(0);
    }
    
    echo "📋 Creating jobs table...\n";
    
    // Create the jobs table (from V2 migration)
    $sql = "
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
    ";
    
    $pdo->exec($sql);
    echo "✅ Jobs table created successfully!\n";
    
    // Verify the table was created
    $stmt = $pdo->query("DESCRIBE jobs");
    $columns = $stmt->fetchAll();
    
    echo "\n📋 Jobs table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Test inserting a sample job
    echo "\n🧪 Testing with sample data...\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO jobs (title, company_name, location, description, url, is_active) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        'Sample Job',
        'Sample Company',
        'Sample Location',
        'This is a test job to verify the table works.',
        'https://example.com/job',
        1
    ]);
    
    if ($result) {
        echo "✅ Sample job inserted successfully!\n";
        
        // Check the count
        $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
        $count = $stmt->fetchColumn();
        echo "📊 Total jobs in table: $count\n";
        
        // Show the sample job
        $stmt = $pdo->query("SELECT * FROM jobs LIMIT 1");
        $job = $stmt->fetch();
        echo "\n📋 Sample job data:\n";
        echo "  - ID: {$job['id']}\n";
        echo "  - Title: {$job['title']}\n";
        echo "  - Company: {$job['company_name']}\n";
        echo "  - Location: {$job['location']}\n";
        echo "  - Active: {$job['is_active']}\n";
        
    } else {
        echo "❌ Failed to insert sample job\n";
    }
    
    echo "\n🎉 Jobs table is ready!\n";
    echo "\n💡 You can now:\n";
    echo "1. Try running the installation again\n";
    echo "2. Or continue with other migrations\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure the database connection is working.\n";
    exit(1);
}
