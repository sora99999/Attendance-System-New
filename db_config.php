<?php
/**
 * Database Configuration
 * 
 * This file establishes connection to MySQL database
 * Default XAMPP credentials are used
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset('utf8mb4');

// Return connection object
return $conn;
