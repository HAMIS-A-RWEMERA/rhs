<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/db.php");

/* FETCH STUDENTS */
$studentsQuery = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | RHS Admin</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">

    <div class="logo-section">
        <img src="../assets/images/logo.png" alt="RHS Logo">

        <div>
            <h1>Student Management</h1>
            <p>Rusumo High School CMS</p>
        </div>
    </div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="../index.php">Main Website</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>

</header>

<main class="dashboard-container">

    <div class="dashboard-top">
        <h2>All Registered Students</h2>

        <a href="add-student.php" class="add-btn">
            + Add New Student
        </a>
    </div>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Class</th>
                    <th>Gender</th>
                    <th>Parent Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php while($student = mysqli_fetch_assoc($studentsQuery)): ?>

                <tr>
                    <td><?php echo $student['id']; ?></td>

                    <td><?php echo $student['student_id']; ?></td>

                    <td><?php echo $student['full_name']; ?></td>

                    <td><?php echo $student['class_name']; ?></td>

                    <td><?php echo $student['gender']; ?></td>

                    <td><?php echo $student['parent_phone']; ?></td>

                    <td>

                        <a class="edit-btn" href="edit-student.php?id=<?php echo $student['id']; ?>">
                            Edit
                        </a>

                        <a 
                           class="delete-btn"
                           href="delete-student.php?id=<?php echo $student['id']; ?>"
                           onclick="return confirm('Delete this student permanently?')"
                        >
                            Delete
                        </a>

                    </td>
                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>