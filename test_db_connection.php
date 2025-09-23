<?php
// Test database connection
require_once __DIR__ . '/bootstrap.php';

echo "Testing database connection...\n\n";

try {
    // Check if config file exists
    $configFile = __DIR__ . '/storage/app.php';
    if (!file_exists($configFile)) {
        echo "❌ Configuration file not found: storage/app.php\n";
        echo "Please create the configuration file first.\n";
        exit(1);
    }
    
    $config = include $configFile;
    echo "✅ Configuration file found\n";
    echo "Database: " . ($config['db']['name'] ?? 'not set') . "\n";
    echo "User: " . ($config['db']['user'] ?? 'not set') . "\n";
    echo "Host: " . ($config['db']['host'] ?? 'not set') . "\n\n";
    
    // Test connection
    $pdo = \Database\DB::pdo();
    echo "✅ Database connection successful!\n";
    
    // Test query
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "✅ Connected to database: " . $result['current_db'] . "\n";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    echo "✅ Found " . count($tables) . " tables in database\n";
    
    if (count($tables) > 0) {
        echo "Tables: " . implode(', ', $tables) . "\n";
    } else {
        echo "⚠️  No tables found. You may need to run migrations.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed:\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting:\n";
    echo "1. Check your database credentials in storage/app.php\n";
    echo "2. Make sure the database 'dexterso_vrc' exists\n";
    echo "3. Verify the user 'dexterso_vrc' has access to the database\n";
    echo "4. Check if the password is correct\n";
}
?>
