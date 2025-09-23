<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Config\AppConfig;

$config = new AppConfig(__DIR__ . '/../storage/app.php');
$installed = (bool)($config->get('installed', false));
if ($installed) { header('Location: /index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim((string)($_POST['db_host'] ?? ''));
    $name = trim((string)($_POST['db_name'] ?? ''));
    $user = trim((string)($_POST['db_user'] ?? ''));
    $pass = (string)($_POST['db_pass'] ?? '');

    $site = trim((string)($_POST['site_name'] ?? 'Varsity Resource Centre'));
    $primary = trim((string)($_POST['theme_primary'] ?? '#0d6efd'));
    if ($host === '' || $name === '' || $user === '') {
        $error = 'Please fill all required fields.';
    } else {
        // Try connecting and run migrations
        try {
            putenv('DB_HOST=' . $host);
            putenv('DB_NAME=' . $name);
            putenv('DB_USER=' . $user);
            putenv('DB_PASS=' . $pass);
            // First connect without dbname to attempt database creation (if privileges allow)
            $serverDsn = "mysql:host=$host;charset=utf8mb4";
            $pdoServer = new PDO($serverDsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            try {
                $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','``',$name) . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (Throwable $e) {
                // Ignore if user has no privilege; require DB to exist
            }
            // Now connect to target database
            $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            // Run each .sql in db/migration, skipping CREATE DATABASE/USE statements
            $dir = __DIR__ . '/../db/migration';
            $files = glob($dir . '/*.sql');
            sort($files);
            
            $migrationErrors = [];
            $successfulMigrations = [];
            
            foreach ($files as $file) {
                $filename = basename($file);
                $sql = file_get_contents($file) ?: '';
                if ($sql === '') { continue; }
                
                // Remove CREATE DATABASE/USE lines for shared hosts
                $sql = preg_replace('/^\s*CREATE\s+DATABASE[\s\S]*?;\s*/im', '', $sql);
                $sql = preg_replace('/^\s*USE\s+[^;]+;\s*/im', '', $sql);
                
                try {
                    $pdo->exec($sql);
                    $successfulMigrations[] = $filename;
                } catch (Exception $e) {
                    $migrationErrors[] = "$filename: " . $e->getMessage();
                    // Continue with other migrations even if one fails
                }
            }
            
            // If there were migration errors, log them but continue
            if (!empty($migrationErrors)) {
                error_log("Migration errors during installation: " . implode("; ", $migrationErrors));
            }
            
            // Ensure essential tables exist (fallback if migrations failed)
            $essentialTables = [
                'users' => "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    full_name VARCHAR(255) NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                'roles' => "CREATE TABLE IF NOT EXISTS roles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) NOT NULL UNIQUE,
                    permissions JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                'user_roles' => "CREATE TABLE IF NOT EXISTS user_roles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    role_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_role (user_id, role_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                'jobs' => "CREATE TABLE IF NOT EXISTS jobs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    company_name VARCHAR(255) DEFAULT NULL,
                    location VARCHAR(255) DEFAULT NULL,
                    description MEDIUMTEXT,
                    url VARCHAR(1024) DEFAULT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                'houses' => "CREATE TABLE IF NOT EXISTS houses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    price DECIMAL(10,2) DEFAULT NULL,
                    location VARCHAR(255) DEFAULT NULL,
                    bedrooms INT DEFAULT NULL,
                    bathrooms INT DEFAULT NULL,
                    area_sqft INT DEFAULT NULL,
                    property_type VARCHAR(100) DEFAULT 'House',
                    status VARCHAR(50) DEFAULT 'For Rent',
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                'businesses' => "CREATE TABLE IF NOT EXISTS businesses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    category VARCHAR(100) DEFAULT NULL,
                    location VARCHAR(255) DEFAULT NULL,
                    phone VARCHAR(20) DEFAULT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    website VARCHAR(500) DEFAULT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                
                'articles' => "CREATE TABLE IF NOT EXISTS articles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content LONGTEXT,
                    author VARCHAR(255) DEFAULT NULL,
                    published_at TIMESTAMP NULL DEFAULT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            ];
            
            // Create essential tables if they don't exist
            foreach ($essentialTables as $tableName => $sql) {
                try {
                    $pdo->exec($sql);
                } catch (Exception $e) {
                    error_log("Failed to create essential table $tableName: " . $e->getMessage());
                }
            }
            
            // Insert essential data
            try {
                // Insert default roles
                $pdo->exec("INSERT IGNORE INTO roles (name, permissions) VALUES 
                    ('admin', '[\"admin\", \"manage_users\", \"manage_content\", \"manage_settings\"]'),
                    ('user', '[\"view_content\", \"create_content\"]')
                ");
                
                // Insert default admin user (will be updated later with custom credentials)
                $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $pdo->exec("INSERT IGNORE INTO users (email, full_name, password_hash, is_active) VALUES 
                    ('admin@varsityresource.com', 'Super Admin', '$defaultPassword', 1)
                ");
                
                // Assign admin role to default admin user
                $pdo->exec("INSERT IGNORE INTO user_roles (user_id, role_id) 
                    SELECT u.id, r.id 
                    FROM users u, roles r 
                    WHERE u.email = 'admin@varsityresource.com' AND r.name = 'admin'
                ");
            } catch (Exception $e) {
                error_log("Failed to insert essential data: " . $e->getMessage());
            }
            // Load template and update with user settings
            $templateFile = __DIR__ . '/../storage/app.php.template';
            $configData = include $templateFile;
            
            $configData['installed'] = true;
            $configData['db'] = ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass];
            $configData['site_name'] = $site;
            $configData['theme']['primary'] = $primary;
            
            // Update features based on user selection
            $configData['features']['articles'] = isset($_POST['enable_articles']);
            $configData['features']['houses'] = isset($_POST['enable_houses']);
            $configData['features']['businesses'] = isset($_POST['enable_businesses']);
            $configData['features']['news'] = isset($_POST['enable_news']);
            $configData['features']['jobs'] = isset($_POST['enable_jobs']);
            $configData['features']['timetable'] = isset($_POST['enable_timetable']);
            $configData['features']['plagiarism_checker'] = isset($_POST['enable_plagiarism']);
            
            // Create custom admin user
            $adminEmail = trim((string)($_POST['admin_email'] ?? 'admin@varsityresource.com'));
            $adminPassword = (string)($_POST['admin_password'] ?? 'admin123');
            
            // Ensure we have valid credentials
            if (empty($adminEmail) || empty($adminPassword)) {
                throw new Exception('Admin email and password are required');
            }
            
            // Update the default admin user with custom credentials
            $stmt = $pdo->prepare('
                UPDATE users 
                SET email = ?, full_name = ?, password_hash = ? 
                WHERE email = "admin@varsityresource.com"
            ');
            $stmt->execute([
                $adminEmail,
                'Super Admin',
                password_hash($adminPassword, PASSWORD_DEFAULT)
            ]);
            
            $config->setMany($configData);
            header('Location: /index.php');
            exit;
        } catch (Throwable $e) {
            $error = 'Install failed: ' . $e->getMessage() . ' - Ensure the database exists and the user has privileges.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Varsity Resource Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:640px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-3">Install Varsity Resource Centre</h4>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">DB Host</label>
                        <input class="form-control" name="db_host" placeholder="localhost" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DB Name</label>
                        <input class="form-control" name="db_name" placeholder="varsity_resource_centre" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DB User</label>
                        <input class="form-control" name="db_user" placeholder="root" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DB Password</label>
                        <input type="password" class="form-control" name="db_pass" placeholder="(blank if none)">
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <h6 class="text-primary">Site Configuration</h6>
                </div>
                <div class="col-12">
                    <label class="form-label">Site Name</label>
                    <input class="form-control" name="site_name" placeholder="Varsity Resource Centre" value="Varsity Resource Centre">
                </div>
                <div class="col-12">
                    <label class="form-label">Theme Primary Color</label>
                    <input type="color" class="form-control form-control-color" name="theme_primary" value="#0d6efd">
                </div>
                
                <div class="col-12 mt-3">
                    <h6 class="text-primary">Admin Account</h6>
                    <p class="text-muted small">Create your admin account for accessing the admin panel.</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admin Email</label>
                    <input type="email" class="form-control" name="admin_email" placeholder="admin@yourdomain.com" value="admin@varsityresource.com" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admin Password</label>
                    <input type="password" class="form-control" name="admin_password" placeholder="Enter a secure password" value="admin123" required>
                    <small class="form-text text-muted">⚠️ Change this password after installation!</small>
                </div>
                
                <div class="col-12 mt-3">
                    <h6 class="text-primary">Feature Configuration</h6>
                    <p class="text-muted small">You can enable/disable features after installation in the admin panel.</p>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_articles" id="enable_articles" checked>
                        <label class="form-check-label" for="enable_articles">
                            <i class="fa-solid fa-newspaper me-2"></i>Articles
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_houses" id="enable_houses" checked>
                        <label class="form-check-label" for="enable_houses">
                            <i class="fa-solid fa-home me-2"></i>Houses
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_businesses" id="enable_businesses" checked>
                        <label class="form-check-label" for="enable_businesses">
                            <i class="fa-solid fa-store me-2"></i>Businesses
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_news" id="enable_news" checked>
                        <label class="form-check-label" for="enable_news">
                            <i class="fa-solid fa-newspaper me-2"></i>News
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_jobs" id="enable_jobs" checked>
                        <label class="form-check-label" for="enable_jobs">
                            <i class="fa-solid fa-briefcase me-2"></i>Jobs
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_timetable" id="enable_timetable" checked>
                        <label class="form-check-label" for="enable_timetable">
                            <i class="fa-solid fa-calendar-alt me-2"></i>Timetable Builder
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_plagiarism" id="enable_plagiarism">
                        <label class="form-check-label" for="enable_plagiarism">
                            <i class="fa-solid fa-search me-2"></i>Pro Plagiarism Checker
                            <span class="badge bg-warning text-dark ms-2">Premium</span>
                        </label>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Install</button>
            </form>
        </div>
    </div>
    <p class="text-muted small mt-3">Setup will create database tables and a default superadmin account.</p>
    </div>
</body>
</html>


