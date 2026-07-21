<?php
// ============================================================
// DATABASE CONNECTION - Simple PDO Setup
// ============================================================

// Database credentials - change these to match your setup
$host = 'localhost';
$dbname = 'payroll_system';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set error mode to exceptions (shows errors clearly)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch results as associative arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // If connection fails, show error
    die("Database connection failed: " . $e->getMessage());
}
?>