<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\Auth;

$auth = new Auth(__DIR__ . '/../storage/users/admins.json');
if (!$auth->check()) { 
    header('Location: /admin/login.php'); 
    exit; 
}

// Redirect to the new integrated migrations section in the dashboard
header('Location: /admin/dashboard.php#migrations');
exit;