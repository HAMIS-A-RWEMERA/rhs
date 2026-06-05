<?php
require_once __DIR__ . '/../config/helpers.php';
start_secure_session();
require_admin();

include __DIR__ . '/../config/db.php';

/* TOTAL STUDENTS QUERY */
$studentQuery = mysqli_query($conn, "SELECT COUNT(*) AS total_students FROM students");
$studentData = mysqli_fetch_assoc($studentQuery);
$totalStudents = $studentData['total_students'];

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RHS Admin Dashboard</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">

    <div class="admin-logo">
        <img src="../assets/images/logo.png" alt="RHS Logo">

        <div>
            <h1>RHS Admin</h1>
            <p>School Management Dashboard</p>
        </div>
    </div>

    <nav class="admin-nav">
        <ul>
            <li><a href="../index.php">Main Website</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="add-student.php">Add Student</a></li>
            <li><a href="view-students.php">Students</a></li>
            <?php if (has_role('admin')): ?>
            <li><a href="manage-users.php">Users</a></li>
            <?php endif; ?>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </nav>

</header>

<main class="dashboard">

    <div class="dashboard-title">
        <h2>Administrator Dashboard</h2>
        <p>
            Welcome back,
            <?php echo h(current_user_name()); ?>
            <span style="font-size:0.8em; color:#888;">
                (<?php echo h(current_role()); ?>)
            </span>
        </p>
    </div>

    <div class="dashboard-grid">

        <div class="dashboard-card">
            <h3>Total Students</h3>
            <p><?php echo (int) $totalStudents; ?></p>
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
