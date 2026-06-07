<?php
/**
 * RHS Database v2 Installer
 *
 * Runs the new normalized schema (database_v2.sql) and seeds
 * the default admin account.
 *
 * Usage:
 *   php database/install_v2.php
 *
 * Or include this in a web-based setup wizard later.
 */

echo "══════════════════════════════════════════════════════════\n";
echo "  RUSUMO HIGH SCHOOL — Database v2 Installer\n";
echo "══════════════════════════════════════════════════════════\n\n";

// ── 1. Load schema SQL ─────────────────────────────────────
$schemaPath = __DIR__ . '/database_v2.sql';

if (!file_exists($schemaPath)) {
    die("ERROR: database_v2.sql not found at $schemaPath\n");
}

$schema = file_get_contents($schemaPath);

if ($schema === false || strlen($schema) === 0) {
    die("ERROR: Failed to read database_v2.sql\n");
}

// ── 2. Load db config ─────────────────────────────────────
require_once __DIR__ . '/../config/db.php';

echo "[1] Running schema...\n";

// Split by DELIMITER to handle triggers/stored procedures
$statements = [];
$current = '';
$delimiter = ';';

foreach (preg_split("/((\r?\n)|(\r\n?))/", $schema) as $line) {
    // Check for delimiter change
    if (preg_match('/^DELIMITER\s+(\S+)/i', $line, $m)) {
        $delimiter = $m[1];
        continue;
    }

    // Check if we've reached end of statement with current delimiter
    $trimmed = trim($line);
    if (strpos($trimmed, $delimiter) !== false && $delimiter === ';') {
        $current .= $line;
        $statements[] = trim($current);
        $current = '';
        continue;
    } elseif (strpos($trimmed, $delimiter) !== false && $delimiter !== ';') {
        $current .= $line;
        $statements[] = trim($current);
        $current = '';
        $delimiter = ';'; // Reset to default after trigger block
        continue;
    }

    $current .= $line . "\n";
}

// Add any remaining
if (trim($current)) {
    $statements[] = trim($current);
}

$success = 0;
$errors = 0;

foreach ($statements as $i => $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;

    // Skip comment-only lines
    if (preg_match('/^(--|#)/', $stmt)) continue;

    if (mysqli_query($conn, $stmt)) {
        $success++;
    } else {
        // Ignore "already exists" errors for IF NOT EXISTS tables
        if (mysqli_errno($conn) === 1050) { // Table already exists
            $success++;
            continue;
        }
        echo "  ⚠️  Statement " . ($i + 1) . " failed: " . mysqli_error($conn) . "\n";
        $errors++;
    }
}

echo "  ✓ $success statements executed, $errors errors\n\n";

// ── 3. Check if admin exists, seed if not ────────────────
echo "[2] Checking admin account...\n";

$check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
$adminUser = 'admin';
mysqli_stmt_bind_param($check, "s", $adminUser);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($result) > 0) {
    echo "  ✓ Admin user already exists. Skipping seed.\n";
} else {
    mysqli_stmt_close($check);

    // Get admin role ID from the new roles table
    $roleQuery = mysqli_query($conn, "SELECT id FROM roles WHERE slug = 'admin' LIMIT 1");
    $roleRow = mysqli_fetch_assoc($roleQuery);
    $adminRoleId = $roleRow ? (int) $roleRow['id'] : 1;

    $username  = 'admin';
    $email     = 'admin@rusumohighschool.rw';
    $password  = 'Admin@1234';
    $hash      = password_hash($password, PASSWORD_DEFAULT);
    $fullName  = 'System Administrator';
    $firstName = 'System';
    $lastName  = 'Administrator';

    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (username, email, password_hash, first_name, last_name, role_id, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    mysqli_stmt_bind_param($stmt, "sssssi", $username, $email, $hash, $firstName, $lastName, $adminRoleId);

    if (mysqli_stmt_execute($stmt)) {
        echo "  ✓ Admin user created successfully.\n";
        echo "    Username: admin\n";
        echo "    Password: Admin@1234\n";
        echo "    Email:    admin@rusumohighschool.rw\n";
    } else {
        echo "  ⚠️  Failed to create admin: " . mysqli_error($conn) . "\n";
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

echo "\n══════════════════════════════════════════════════════════\n";
echo "  INSTALLATION COMPLETE\n";
echo "══════════════════════════════════════════════════════════\n";
echo "\n";
echo "  The database_v2.sql has been applied.\n";
echo "  Your RHS system is ready for the new normalized schema.\n";
echo "\n";
echo "  ⚠️  IMPORTANT: Change the default admin password after\n";
echo "     first login! Default: Admin@1234\n";
echo "\n";
echo "  NEXT STEP: Update config/db.php if needed, then\n";
echo "  navigate to ../auth/login.php to log in.\n";
echo "\n";