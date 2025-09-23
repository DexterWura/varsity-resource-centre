<?php
/**
 * Migration Conflict Resolver
 * This script helps identify and fix common migration conflicts
 */

echo "=== Migration Conflict Resolver ===\n\n";

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

try {
    // Connect to database
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to database successfully.\n\n";
    
    // Check for common conflicts
    echo "1. Checking for existing tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "   ✓ Database is empty - no conflicts expected\n";
    } else {
        echo "   ⚠ Found " . count($tables) . " existing tables:\n";
        foreach ($tables as $table) {
            echo "     - $table\n";
        }
    }
    
    // Check for flyway_schema_history table
    echo "\n2. Checking migration history...\n";
    if (in_array('flyway_schema_history', $tables)) {
        $stmt = $pdo->query("SELECT version, description, success FROM flyway_schema_history ORDER BY installed_rank");
        $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Found " . count($migrations) . " executed migrations:\n";
        foreach ($migrations as $migration) {
            $status = $migration['success'] ? '✓' : '✗';
            echo "     $status V{$migration['version']} - {$migration['description']}\n";
        }
    } else {
        echo "   ✓ No migration history found - fresh installation\n";
    }
    
    // Check for specific column conflicts
    echo "\n3. Checking for column conflicts...\n";
    $conflictColumns = [];
    
    if (in_array('articles', $tables)) {
        $stmt = $pdo->query("SHOW COLUMNS FROM articles LIKE 'featured_image'");
        if ($stmt->rowCount() > 0) {
            $conflictColumns[] = 'articles.featured_image';
        }
    }
    
    if (in_array('houses', $tables)) {
        $stmt = $pdo->query("SHOW COLUMNS FROM houses LIKE 'featured_image'");
        if ($stmt->rowCount() > 0) {
            $conflictColumns[] = 'houses.featured_image';
        }
    }
    
    if (in_array('businesses', $tables)) {
        $stmt = $pdo->query("SHOW COLUMNS FROM businesses LIKE 'featured_image'");
        if ($stmt->rowCount() > 0) {
            $conflictColumns[] = 'businesses.featured_image';
        }
    }
    
    if (empty($conflictColumns)) {
        echo "   ✓ No column conflicts detected\n";
    } else {
        echo "   ⚠ Potential column conflicts detected:\n";
        foreach ($conflictColumns as $column) {
            echo "     - $column\n";
        }
    }
    
    // Provide solutions
    echo "\n=== Solutions ===\n";
    
    if (!empty($tables) && !in_array('flyway_schema_history', $tables)) {
        echo "1. Database has tables but no migration history:\n";
        echo "   - Run: php reset_database.php (WARNING: deletes all data)\n";
        echo "   - Or manually create flyway_schema_history table\n";
    } elseif (!empty($conflictColumns)) {
        echo "1. Column conflicts detected:\n";
        echo "   - The migration files have been updated to use IF NOT EXISTS\n";
        echo "   - Try running the installation again\n";
        echo "   - If still failing, run: php reset_database.php\n";
    } elseif (empty($tables)) {
        echo "1. Database is empty:\n";
        echo "   - Ready for fresh installation\n";
        echo "   - Visit your site to start installation\n";
    } else {
        echo "1. Database appears to be in good state:\n";
        echo "   - Try running the installation again\n";
        echo "   - Check error logs for specific issues\n";
    }
    
    echo "\n2. General troubleshooting:\n";
    echo "   - Check storage/logs/app.log for detailed error messages\n";
    echo "   - Ensure database user has CREATE, ALTER, DROP privileges\n";
    echo "   - Verify database connection settings\n";
    
    echo "\n3. Fresh start option:\n";
    echo "   - Run: php reset_database.php (deletes all data)\n";
    echo "   - Run: php fresh_install.php (resets config)\n";
    echo "   - Visit your site for fresh installation\n";
    
} catch (Exception $e) {
    echo "Error connecting to database: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in storage/app.php\n";
    exit(1);
}
?>
