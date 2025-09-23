<?php
/**
 * Test Installation Redirect
 * This script tests if the installation redirect is working properly
 */

echo "Testing installation redirect logic...\n\n";

// Test 1: Check if storage/app.php exists
echo "1. Checking storage/app.php file...\n";
if (file_exists('storage/app.php')) {
    echo "   ✓ storage/app.php exists\n";
    
    // Test 2: Check if it's a valid PHP file
    $config = include 'storage/app.php';
    if (is_array($config)) {
        echo "   ✓ Configuration file is valid PHP array\n";
        
        // Test 3: Check installed status
        if (isset($config['installed'])) {
            if ($config['installed']) {
                echo "   ⚠ Site is marked as INSTALLED - will NOT redirect to install\n";
                echo "   To test installation, run: php fresh_install.php\n";
            } else {
                echo "   ✓ Site is marked as NOT INSTALLED - will redirect to install\n";
            }
        } else {
            echo "   ✗ 'installed' key missing from configuration\n";
        }
    } else {
        echo "   ✗ Configuration file is not a valid PHP array\n";
    }
} else {
    echo "   ✓ storage/app.php does NOT exist - will redirect to install\n";
}

// Test 4: Check install directory
echo "\n2. Checking install directory...\n";
if (is_dir('install/')) {
    echo "   ✓ install/ directory exists\n";
    if (file_exists('install/index.php')) {
        echo "   ✓ install/index.php exists\n";
    } else {
        echo "   ✗ install/index.php missing\n";
    }
} else {
    echo "   ✗ install/ directory missing\n";
}

// Test 5: Check bootstrap logic
echo "\n3. Testing bootstrap redirect logic...\n";
$appConfigFile = 'storage/app.php';
$isInstaller = false; // Simulate not being on install page

if (!$isInstaller) {
    if (!is_file($appConfigFile)) {
        echo "   ✓ Would redirect to /install/ (no config file)\n";
    } else {
        $config = include $appConfigFile;
        if (!is_array($config) || empty($config['installed'])) {
            echo "   ✓ Would redirect to /install/ (not installed)\n";
        } else {
            echo "   ⚠ Would NOT redirect (already installed)\n";
        }
    }
}

echo "\nTest complete!\n";
echo "\nTo test fresh installation:\n";
echo "1. Run: php fresh_install.php\n";
echo "2. Visit your site - it should redirect to /install/\n";
echo "3. Fill in the installation form\n";
echo "4. Click Install\n";
?>
