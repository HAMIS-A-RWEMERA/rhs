<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RHS Admin Dashboard</title>

    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>

    <div class="logo-area">
        <img src="../assets/images/logo.png" alt="RHS Logo">

        <div>
            <h1>RHS Admin</h1>
            <p>School Management Dashboard</p>
        </div>
    </div>

    <nav>
        <ul>
            <li><a href="../index.php">Main Website</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

</header>

<main class="dashboard">

    <div class="section-title">
        <h2>Administrator Dashboard</h2>
        <p>
            Welcome back,
            <?php echo $_SESSION['admin_username']; ?>
        </p>
    </div>

    <div class="dashboard-cards">

        <div class="dashboard-card">
            <h3>Total Students</h3>
            <p>620</p>
        </div>

        <div class="dashboard-card">
            <h3>Teachers</h3>
            <p>35</p>
        </div>

        <div class="dashboard-card">
            <h3>News Posts</h3>
            <p>12</p>
        </div>

        <div class="dashboard-card">
            <h3>Pending Fees</h3>
            <p>24 Students</p>
        </div>

    </div>

</main>

<footer>
    <p>Rusumo High School CMS Dashboard</p>
</footer>

</body>
</html>