<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\UserAuth;

$userAuth = new UserAuth();

// Perform logout
$userAuth->logout();

// Determine redirect message
$message = 'logged_out=1';
if (isset($_GET['timeout'])) {
    $message = 'timeout=1';
}

// Redirect to login page with appropriate message
header('Location: /admin/login.php?' . $message);
exit;
