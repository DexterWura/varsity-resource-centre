<?php
/**
 * Reset Installation Script
 * This script resets the installation status for testing purposes
 * Run this to test the installation process
 */

echo "Resetting installation status...\n";

// Backup current config if it exists
if (file_exists('storage/app.php')) {
    copy('storage/app.php', 'storage/app.php.backup.' . date('Y-m-d-H-i-s'));
    echo "Backed up current config to storage/app.php.backup." . date('Y-m-d-H-i-s') . "\n";
}

// Copy template to create fresh installation state
if (file_exists('storage/app.php.template')) {
    copy('storage/app.php.template', 'storage/app.php');
    echo "Reset to fresh installation state.\n";
} else {
    // Create a minimal config for fresh installation
    $freshConfig = [
        'installed' => false,
        'db' => [
            'host' => 'localhost',
            'name' => 'varsity_resource_centre',
            'user' => 'root',
            'pass' => ''
        ],
        'site_name' => 'Varsity Resource Centre',
        'theme' => [
            'primary' => '#0d6efd'
        ],
        'features' => [
            'articles' => true,
            'houses' => true,
            'businesses' => true,
            'news' => true,
            'jobs' => true,
            'timetable' => true,
            'plagiarism_checker' => false,
        ],
        'plagiarism_apis' => [
            'copyleaks' => false,
            'quetext' => false,
            'smallseotools' => false,
            'plagiarism_detector' => false,
            'duplichecker' => false,
        ]
    ];
    
    file_put_contents('storage/app.php', '<?php return ' . var_export($freshConfig, true) . ';');
    echo "Created fresh installation config.\n";
}

echo "Installation reset complete! Visit /install/ to start fresh installation.\n";
?>
