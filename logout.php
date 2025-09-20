<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Auth\UserAuth;

$userAuth = new UserAuth();
$userAuth->logout();

header('Location: /index.php');
exit;
