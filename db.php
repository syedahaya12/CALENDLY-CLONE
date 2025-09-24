<?php
date_default_timezone_set('Europe/Istanbul'); // +03 timezone
$host = 'localhost';
$dbname = 'dbh9abdhp4vpml';
$user = 'uannmukxu07nw';
$password = 'nhh1divf0d2c';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_PERSISTENT, false);
    $pdo->exec("SET time_zone = '+03:00';"); // Ensure MySQL uses +03 timezone
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed. Please check your configuration or try again later.");
}
?>
