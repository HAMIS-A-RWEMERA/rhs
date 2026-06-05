<?php
require_once __DIR__ . '/../config/helpers.php';
start_secure_session();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../admin/dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    include __DIR__ . '/../config/db.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password_hash, full_name, role, is_active FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                $error = "Your account has been deactivated. Contact the administrator.";
            } else {
                regenerate_session();

                $_SESSION['user_id']       = (int) $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_fullname'] = $user['full_name'];
                $_SESSION['user_role']     = $user['role'];

                // Legacy session keys for backward compatibility
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username']  = $user['username'];

                header("Location: ../admin/dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid username or password.";
        }

        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RHS Staff Login</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="login-page">

    <div class="login-box">

        <h2>Staff Login</h2>
        <p style="color:#888; margin-bottom:15px; font-size:0.9em;">
            Rusumo High School Management System
        </p>

        <?php if ($error): ?>
            <p style="color:red; margin-bottom:15px;">
                <?php echo h($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit">
                Login
            </button>

        </form>

    </div>

</div>

</body>
</html>
