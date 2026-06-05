<?php
require_once __DIR__ . '/../config/helpers.php';
start_secure_session();
require_admin();

include __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: students.php");
    exit();
}

verify_csrf();

$id = $_POST['id'] ?? '';

if (!empty($id)) {
    $stmt = mysqli_prepare($conn, "DELETE FROM students WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

header("Location: students.php");
exit();
