<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/db.php");

$sql = "SELECT * FROM students ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">

    <div class="admin-logo">

        <img src="../assets/logo.png" alt="Logo">

        <div>
            <h1>RHS Admin</h1>
            <p>Student Records System</p>
        </div>

    </div>

    <nav class="admin-nav">

        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="add-student.php">Add Student</a></li>
            <li><a href="view-students.php">Students</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>

    </nav>

</header>

<main class="dashboard">

    <div class="dashboard-title">
        <h2>All Students</h2>
        <p>Manage registered student records.</p>
    </div>

    <div class="table-container">

        <table class="students-table">

            <thead>
                <tr>
                    <th>ID</th>
                     <th>Photo</th>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Class</th>
                    <th>Gender</th>
                    <th>Parent Phone</th>
                    <th>Fees Balance</th>
                </tr>
            </thead>

            <tbody>

            <?php while($student = mysqli_fetch_assoc($result)): ?>

                <tr>

                    <td><?php echo $student['id']; ?></td>

                     <td>

        <img
            src="../uploads/students/<?php echo $student['profile_photo']; ?>"
            alt="Student Photo"
            class="student-photo"
        >

    </td>
                    <td><?php echo $student['student_id']; ?></td>

                    <td><?php echo $student['full_name']; ?></td>

                    <td><?php echo $student['class_name']; ?></td>

                    <td><?php echo $student['gender']; ?></td>

                    <td><?php echo $student['parent_phone']; ?></td>

                    <td>
                        <?php echo number_format($student['fees_balance']); ?> RWF
                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>