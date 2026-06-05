<?php
/**
 * Database configuration — copy this file to db.php and fill in your real credentials.
 * NEVER commit db.php with real credentials to version control.
 */

$host = "localhost";
$user = "your_db_user";
$password = "your_db_password";
$database = "rhs";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    error_log("RHS DB connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please contact the administrator.");
}
