<?php
/**
 * Installation Test Script
 * This script tests the installation process without actually installing
 */

echo "Testing Installation Process...\n\n";

// Test 1: Check if template file exists
echo "1. Checking installation template...\n";
if (file_exists('storage/app.php.template')) {
    echo "   ✓ Template file exists\n";
    $template = include 'storage/app.php.template';
    if (isset($template['installed']) && $template['installed'] === false) {
        echo "   ✓ Template has correct initial state\n";
    } else {
        echo "   ✗ Template has incorrect initial state\n";
    }
} else {
    echo "   ✗ Template file missing\n";
}

// Test 2: Check if install directory exists
echo "\n2. Checking install directory...\n";
if (is_dir('install/')) {
    echo "   ✓ Install directory exists\n";
    if (file_exists('install/index.php')) {
        echo "   ✓ Install script exists\n";
    } else {
        echo "   ✗ Install script missing\n";
    }
} else {
    echo "   ✗ Install directory missing\n";
}

// Test 3: Check migration files
echo "\n3. Checking migration files...\n";
$migrationDir = 'db/migration/';
if (is_dir($migrationDir)) {
    echo "   ✓ Migration directory exists\n";
    $migrations = glob($migrationDir . '*.sql');
    if (count($migrations) > 0) {
        echo "   ✓ Found " . count($migrations) . " migration files\n";
        foreach ($migrations as $migration) {
            echo "     - " . basename($migration) . "\n";
        }
    } else {
        echo "   ✗ No migration files found\n";
    }
} else {
    echo "   ✗ Migration directory missing\n";
}

// Test 4: Check bootstrap installation guard
echo "\n4. Checking bootstrap installation guard...\n";
if (file_exists('bootstrap.php')) {
    echo "   ✓ Bootstrap file exists\n";
    $bootstrapContent = file_get_contents('bootstrap.php');
    if (strpos($bootstrapContent, '/install') !== false) {
        echo "   ✓ Installation guard code found\n";
    } else {
        echo "   ✗ Installation guard code missing\n";
    }
} else {
    echo "   ✗ Bootstrap file missing\n";
}

// Test 5: Check storage directory
echo "\n5. Checking storage directory...\n";
if (is_dir('storage/')) {
    echo "   ✓ Storage directory exists\n";
    if (is_writable('storage/')) {
        echo "   ✓ Storage directory is writable\n";
    } else {
        echo "   ✗ Storage directory is not writable\n";
    }
} else {
    echo "   ✗ Storage directory missing\n";
}

// Test 6: Check current installation state
echo "\n6. Checking current installation state...\n";
if (file_exists('storage/app.php')) {
    $config = include 'storage/app.php';
    if (isset($config['installed'])) {
        if ($config['installed']) {
            echo "   ⚠ Site is currently installed\n";
            echo "   Run 'php reset_installation.php' to test fresh installation\n";
        } else {
            echo "   ✓ Site is ready for installation\n";
        }
    } else {
        echo "   ✗ Invalid configuration file\n";
    }
} else {
    echo "   ✓ No configuration file (ready for fresh installation)\n";
}

echo "\nInstallation Test Complete!\n";
echo "\nTo test the installation process:\n";
echo "1. Run: php reset_installation.php\n";
echo "2. Visit: http://localhost/install/\n";
echo "3. Fill in the installation form\n";
echo "4. Click Install\n";
?>
