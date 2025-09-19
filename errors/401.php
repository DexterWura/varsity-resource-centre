<?php
http_response_code(401);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unauthorized</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="text-center">
        <h1 class="display-6">401</h1>
        <p class="text-muted">Please sign in to continue.</p>
        <a href="/index.php" class="btn btn-primary btn-sm">Go Home</a>
    </div>
    </div>
</body>
</html>


