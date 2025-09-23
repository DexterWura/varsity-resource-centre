<?php
/**
 * Fresh Installation Setup Script
 * This script ensures the site is in a fresh installation state
 */

echo "Setting up fresh installation state...\n";

// Remove existing config if it exists
if (file_exists('storage/app.php')) {
    unlink('storage/app.php');
    echo "Removed existing configuration file.\n";
}

// Copy template to create fresh installation state
if (file_exists('storage/app.php.template')) {
    copy('storage/app.php.template', 'storage/app.php');
    echo "Created fresh installation configuration.\n";
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
    echo "Created fresh installation configuration.\n";
}

// Ensure storage/logs directory exists
if (!is_dir('storage/logs')) {
    mkdir('storage/logs', 0755, true);
    echo "Created storage/logs directory.\n";
}

echo "Fresh installation setup complete!\n";
echo "Now visit your site - it should redirect to /install/\n";
?>
