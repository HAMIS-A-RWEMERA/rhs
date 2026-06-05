<?php
require_once __DIR__ . '/../config/helpers.php';
start_secure_session();
require_admin();

include __DIR__ . '/../config/db.php';

/* GET STUDENT ID */
if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$id = (int) $_GET['id'];

/* FETCH STUDENT */
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: students.php");
    exit();
}

/* UPDATE PROCESS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $student_id   = trim($_POST['student_id'] ?? '');
    $full_name    = trim($_POST['full_name'] ?? '');
    $class_name   = trim($_POST['class_name'] ?? '');
    $gender       = trim($_POST['gender'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');

    $stmt = mysqli_prepare($conn, "UPDATE students SET
        student_id = ?,
        full_name = ?,
        class_name = ?,
        gender = ?,
        parent_phone = ?
        WHERE id = ?");

    mysqli_stmt_bind_param($stmt, "sssssi",
        $student_id, $full_name, $class_name,
        $gender, $parent_phone, $id);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: students.php");
    exit();
}

mysqli_close($conn);
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
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label>Student ID</label>

                <input
                    type="text"
                    name="student_id"
                    value="<?php echo h($student['student_id']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Full Name</label>

                <input
                    type="text"
                    name="full_name"
                    value="<?php echo h($student['full_name']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Class</label>

                <input
                    type="text"
                    name="class_name"
                    value="<?php echo h($student['class_name']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Gender</label>

                <select name="gender" required>

                    <option value="Male"
                        <?php if ($student['gender'] === 'Male') echo 'selected'; ?>
                    >
                        Male
                    </option>

                    <option value="Female"
                        <?php if ($student['gender'] === 'Female') echo 'selected'; ?>
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
                    value="<?php echo h($student['parent_phone']); ?>"
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
