<?php
session_start();

$error = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Demo login credentials
    if($username === "admin" && $password === "1234"){

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;

        header("Location: ../admin/dashboard.php");
        exit();

    } else {

        $error = "Invalid username or password.";

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RHS Admin Login</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="login-page">

    <div class="login-box">

        <h2>Administrator Login</h2>

        <?php if($error): ?>
            <p style="color:red; margin-bottom:15px;">
                <?php echo $error; ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit">
                Login
            </button>

        </form>

    </div>

</div>

</body>
</html>