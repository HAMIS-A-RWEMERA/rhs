<?php
/**
 * Seed the default admin user.
 *
 * Run once after importing schema.sql:
 *   php database/seed.php
 *
 * Default credentials:
 *   Username: admin
 *   Password: Admin@1234
 *
 * Change the password immediately after first login in production.
 */

require_once __DIR__ . '/../config/db.php';

$username  = 'admin';
$password  = 'Admin@1234';
$full_name = 'System Administrator';
$role      = 'admin';
$email     = 'admin@rusumohighschool.rw';

$hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($check, "s", $username);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($result) > 0) {
    echo "Admin user already exists. Skipping.\n";
} else {
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $hash, $full_name, $role);

    if (mysqli_stmt_execute($stmt)) {
        echo "Admin user created successfully.\n";
        echo "  Username: $username\n";
        echo "  Password: $password\n";
        echo "  Role: $role\n";
        echo "\n  ⚠️  Change this password in production!\n";
    } else {
        echo "Error creating admin user: " . mysqli_error($conn) . "\n";
        exit(1);
    }
    mysqli_stmt_close($stmt);
}

mysqli_stmt_close($check);
mysqli_close($conn);
