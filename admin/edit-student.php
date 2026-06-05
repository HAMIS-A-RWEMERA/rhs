<?php
session_start();

if(!isset($_SESSION['admin_logged_in'])){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/db.php");

/* GET STUDENT ID */
if(!isset($_GET['id'])){
    header("Location: students.php");
    exit();
}

$id = $_GET['id'];

/* FETCH STUDENT */
$query = mysqli_query(
    $conn,
    "SELECT * FROM students WHERE id = '$id'"
);

$student = mysqli_fetch_assoc($query);

/* UPDATE PROCESS */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $student_id = $_POST['student_id'];
    $full_name = $_POST['full_name'];
    $class_name = $_POST['class_name'];
    $gender = $_POST['gender'];
    $parent_phone = $_POST['parent_phone'];

    mysqli_query(
        $conn,
        "UPDATE students SET

        student_id='$student_id',
        full_name='$full_name',
        class_name='$class_name',
        gender='$gender',
        parent_phone='$parent_phone'

        WHERE id='$id'"
    );

    header("Location: students.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">

    <div class="logo-section">

        <img src="../assets/images/logo.png" alt="Logo">

        <div>
            <h1>Edit Student</h1>
            <p>Update student information</p>
        </div>

    </div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>

</header>

<main class="dashboard-container">

    <div class="dashboard-card">

        <h2>Edit Student</h2>

        <form method="POST" class="student-form">

            <div class="form-group">
                <label>Student ID</label>

                <input
                    type="text"
                    name="student_id"
                    value="<?php echo $student['student_id']; ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Full Name</label>

                <input
                    type="text"
                    name="full_name"
                    value="<?php echo $student['full_name']; ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Class</label>

                <input
                    type="text"
                    name="class_name"
                    value="<?php echo $student['class_name']; ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Gender</label>

                <select name="gender" required>

                    <option value="Male"
                        <?php if($student['gender'] === 'Male') echo 'selected'; ?>
                    >
                        Male
                    </option>

                    <option value="Female"
                        <?php if($student['gender'] === 'Female') echo 'selected'; ?>
                    >
                        Female
                    </option>

                </select>
            </div>

            <div class="form-group">
                <label>Parent Phone</label>

                <input
                    type="text"
                    name="parent_phone"
                    value="<?php echo $student['parent_phone']; ?>"
                >
            </div>

            <button type="submit" class="add-btn">
                Update Student
            </button>

        </form>

    </div>

</main>

</body>
</html>