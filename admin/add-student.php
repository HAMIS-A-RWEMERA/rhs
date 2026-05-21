<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: ../auth/login.php");
    exit();
}

// 1. This loads your $conn variable
include("../config/db.php");

$message = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){

    // 2. mysqli_real_escape_string prevents names with symbols from breaking your SQL query
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $parent_phone = mysqli_real_escape_string($conn, $_POST['parent_phone']);
    $fees_balance = mysqli_real_escape_string($conn, $_POST['fees_balance']);

    $sql = "INSERT INTO students (student_id, full_name, class_name, gender, parent_phone, fees_balance)
            VALUES ('$student_id', '$full_name', '$class_name', '$gender', '$parent_phone', '$fees_balance')";

    // 3. Changed $connection to $conn to match your db.php file
    if(mysqli_query($conn, $sql)){
        $message = "Student added successfully.";
    } else {
        // 4. This will tell you the exact database error if it fails
        $message = "Failed to add student. Error: " . mysqli_error($conn);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">

    <div class="admin-logo">
        <img src="../assets/logo.png" alt="Logo">

        <div>
            <h1>RHS Admin</h1>
            <p>Student Management System</p>
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
        <h2>Add New Student</h2>
        <p>Create a new student profile.</p>
    </div>

    <?php if($message): ?>

        <div class="success-message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>

    <form class="student-form" method="POST">

        <div class="form-group">
            <label>Student ID</label>
            <input type="text" name="student_id" required>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>

        <div class="form-group">
            <label>Class</label>
            <input type="text" name="class_name" required>
        </div>

        <div class="form-group">
            <label>Gender</label>

            <select name="gender" required>
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
            </select>
        </div>

        <div class="form-group">
            <label>Parent Phone</label>
            <input type="text" name="parent_phone">
        </div>

        <div class="form-group">
            <label>Fees Balance</label>
            <input type="number" name="fees_balance">
        </div>

        <button type="submit">
            Save Student
        </button>

    </form>

</main>

</body>
</html>