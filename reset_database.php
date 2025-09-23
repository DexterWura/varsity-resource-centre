<?php
/**
 * Database Reset Script
 * This script helps reset the database to a clean state for fresh installation
 * WARNING: This will delete all data in the database!
 */

echo "=== Database Reset Script ===\n";
echo "WARNING: This will delete ALL data in your database!\n";
echo "Only use this if you're having migration conflicts.\n\n";

// Check if we have database configuration
if (!file_exists('storage/app.php')) {
    echo "No configuration file found. Run fresh installation instead.\n";
    exit(1);
}

$config = include 'storage/app.php';
if (empty($config['db'])) {
    echo "No database configuration found. Run fresh installation instead.\n";
    exit(1);
}

$dbConfig = $config['db'];
echo "Database Configuration:\n";
echo "  Host: " . $dbConfig['host'] . "\n";
echo "  Database: " . $dbConfig['name'] . "\n";
echo "  User: " . $dbConfig['user'] . "\n\n";

echo "Are you sure you want to reset the database? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$confirmation = trim($line);
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "Database reset cancelled.\n";
    exit(0);
}

try {
    // Connect to database
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to database successfully.\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "Database is already empty.\n";
    } else {
        echo "Found " . count($tables) . " tables to drop:\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
        
        // Disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Drop all tables
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "Dropped table: $table\n";
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "All tables dropped successfully.\n";
    }
    
    // Reset installation status
    $config['installed'] = false;
    file_put_contents('storage/app.php', '<?php return ' . var_export($config, true) . ';');
    echo "Installation status reset to false.\n";
    
    echo "\nDatabase reset complete!\n";
    echo "You can now run a fresh installation:\n";
    echo "1. Visit your site - it should redirect to /install/\n";
    echo "2. Fill in the installation form\n";
    echo "3. Click Install\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and try again.\n";
    exit(1);
}
?>
