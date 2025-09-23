<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use Auth\UserAuth;

$userAuth = new UserAuth();
if (!$userAuth->check() || !$userAuth->user()->hasRole('admin')) { 
    header('Location: /admin/login.php'); 
    exit; 
}

// Redirect to new dashboard
header('Location: /admin/dashboard.php');
exit;


