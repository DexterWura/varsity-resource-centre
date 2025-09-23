<?php
// Database configuration helper
// This script helps you set up your database configuration securely

echo "🔧 Database Configuration Helper\n";
echo "================================\n\n";

$configFile = __DIR__ . '/storage/app.php';

if (!file_exists($configFile)) {
    echo "❌ Configuration file not found: storage/app.php\n";
    echo "Please run the installer first at /install/\n";
    exit(1);
}

echo "📋 Current database configuration:\n";
$config = include $configFile;
echo "   Host: " . ($config['db']['host'] ?? 'not set') . "\n";
echo "   Database: " . ($config['db']['name'] ?? 'not set') . "\n";
echo "   User: " . ($config['db']['user'] ?? 'not set') . "\n";
echo "   Password: " . (isset($config['db']['pass']) && $config['db']['pass'] !== '' ? '***set***' : 'not set') . "\n\n";

echo "🔒 SECURITY NOTICE:\n";
echo "   Never commit database credentials to version control!\n";
echo "   The storage/app.php file should be in your .gitignore\n\n";

echo "📝 To update your database configuration:\n";
echo "   1. Edit storage/app.php\n";
echo "   2. Update the 'db' section with your actual credentials\n";
echo "   3. Make sure storage/app.php is in your .gitignore file\n\n";

echo "🌐 For shared hosting, your configuration should look like:\n";
echo "   'db' => [\n";
echo "       'host' => 'localhost',\n";
echo "       'name' => 'your_database_name',\n";
echo "       'user' => 'your_username',\n";
echo "       'pass' => 'your_password'\n";
echo "   ]\n\n";

echo "✅ After updating, test your connection with: php test_db_connection.php\n";
?>
