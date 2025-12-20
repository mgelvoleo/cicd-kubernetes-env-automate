<?php
// Database configuration
define('DB_HOST', 'mysql');
define('DB_USER', 'phpuser');
define('DB_PASS', 'PhpPass123!');
define('DB_NAME', 'appdb');

// Create connection
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Helper function for clean output
function clean_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>