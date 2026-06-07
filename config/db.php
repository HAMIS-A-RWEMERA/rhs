<?php
/**
 * Database configuration.
 * For production: copy config/db.sample.php → config/db.php with real credentials.
 */

$host     = "localhost";
$user     = "root";
$password = "";
$database = "rhs";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    error_log("RHS DB connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please contact the administrator.");
}

// Ensure UTF-8
mysqli_set_charset($conn, "utf8mb4");