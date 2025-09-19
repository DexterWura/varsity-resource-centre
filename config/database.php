<?php
$host = 'localhost'; // Database host
$dbname = 'timetable'; // Your database name
$username = 'root'; // Database username
$password = ''; // Database password (default is empty in XAMPP)

// Create a PDO connection
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}