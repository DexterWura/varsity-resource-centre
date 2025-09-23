<?php
/**
 * Installation Verification Script
 * Run this to verify the installation system is working correctly
 */

echo "=== Varsity Resource Centre - Installation Verification ===\n\n";

// Check current installation state
echo "1. Checking current installation state...\n";
if (file_exists('storage/app.php')) {
    $config = include 'storage/app.php';
    if (isset($config['installed'])) {
        if ($config['installed']) {
            echo "   ⚠ Site is currently INSTALLED\n";
            echo "   To test fresh installation, run: php fresh_install.php\n";
        } else {
            echo "   ✓ Site is ready for installation (not installed)\n";
        }
    } else {
        echo "   ✗ Invalid configuration - missing 'installed' key\n";
    }
} else {
    echo "   ✓ No configuration file - ready for fresh installation\n";
}

// Check bootstrap redirect logic
echo "\n2. Testing bootstrap redirect logic...\n";
$appConfigFile = 'storage/app.php';

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

// Check install directory
echo "\n3. Checking installation files...\n";
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

// Check migration files
echo "\n4. Checking migration files...\n";
if (is_dir('db/migration/')) {
    $migrations = glob('db/migration/V*.sql');
    echo "   ✓ Found " . count($migrations) . " migration files\n";
    foreach ($migrations as $migration) {
        echo "     - " . basename($migration) . "\n";
    }
} else {
    echo "   ✗ db/migration/ directory missing\n";
}

// Check storage directory
echo "\n5. Checking storage directory...\n";
if (is_dir('storage/')) {
    echo "   ✓ storage/ directory exists\n";
    if (is_writable('storage/')) {
        echo "   ✓ storage/ directory is writable\n";
    } else {
        echo "   ⚠ storage/ directory is not writable\n";
    }
    
    if (!is_dir('storage/logs')) {
        echo "   ⚠ storage/logs directory missing (will be created)\n";
    } else {
        echo "   ✓ storage/logs directory exists\n";
    }
} else {
    echo "   ✗ storage/ directory missing\n";
}

echo "\n=== Verification Complete ===\n\n";

// Provide next steps
if (file_exists('storage/app.php')) {
    $config = include 'storage/app.php';
    if (isset($config['installed']) && $config['installed']) {
        echo "CURRENT STATE: Site is installed and ready to use\n";
        echo "To test fresh installation:\n";
        echo "  1. Run: php fresh_install.php\n";
        echo "  2. Visit your site - it should redirect to /install/\n";
    } else {
        echo "CURRENT STATE: Ready for installation\n";
        echo "Next steps:\n";
        echo "  1. Visit your site - it should redirect to /install/\n";
        echo "  2. Fill in the installation form\n";
        echo "  3. Click Install\n";
    }
} else {
    echo "CURRENT STATE: Fresh installation ready\n";
    echo "Next steps:\n";
    echo "  1. Visit your site - it should redirect to /install/\n";
    echo "  2. Fill in the installation form\n";
    echo "  3. Click Install\n";
}

echo "\nFor detailed installation instructions, see INSTALLATION.md\n";
?>
