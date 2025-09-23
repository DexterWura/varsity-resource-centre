<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use Auth\UserAuth;

$userAuth = new UserAuth();

echo "<h1>User Authentication Debug</h1>";
echo "<p><strong>Is logged in:</strong> " . ($userAuth->check() ? 'Yes' : 'No') . "</p>";

if ($userAuth->check()) {
    $user = $userAuth->user();
    echo "<p><strong>User ID:</strong> " . $user->getId() . "</p>";
    echo "<p><strong>Full Name:</strong> " . htmlspecialchars($user->getFullName()) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($user->getEmail()) . "</p>";
    echo "<p><strong>Is Active:</strong> " . ($user->isActive() ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p>User is not logged in.</p>";
    echo "<p><a href='/login.php'>Go to Login</a></p>";
}
?>
