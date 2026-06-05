<?php
require_once __DIR__ . '/../config/helpers.php';
start_secure_session();
require_admin();

include __DIR__ . '/../config/db.php';

$message = "";
$messageType = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf();

    $student_id   = trim($_POST['student_id'] ?? '');
    $full_name    = trim($_POST['full_name'] ?? '');
    $class_name   = trim($_POST['class_name'] ?? '');
    $gender       = trim($_POST['gender'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $fees_balance = (float) ($_POST['fees_balance'] ?? 0);

    /* IMAGE UPLOAD — hardened */
    $photoName = "default.png";
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {

        $fileTmp  = $_FILES['profile_photo']['tmp_name'];
        $fileSize = $_FILES['profile_photo']['size'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($mimeType, $allowedTypes, true)) {
            $message = "Invalid file type. Only JPG, PNG, WebP, GIF allowed.";
            $messageType = "error";
        } elseif (!in_array($ext, $allowedExts, true)) {
            $message = "Invalid file extension.";
            $messageType = "error";
        } elseif ($fileSize > $maxSize) {
            $message = "File too large. Maximum 2 MB.";
            $messageType = "error";
        } else {
            // Generate random filename (ignore user filename)
            $newName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destination = __DIR__ . '/../uploads/students/' . $newName;

            if (move_uploaded_file($fileTmp, $destination)) {
                $photoName = $newName;
            } else {
                $message = "Failed to save uploaded file.";
                $messageType = "error";
            }
        }
    }

    // Only insert if no upload error
    if ($messageType !== "error") {
        $stmt = mysqli_prepare($conn, "INSERT INTO students
            (student_id, full_name, class_name, gender, parent_phone, fees_balance, profile_photo)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param($stmt, "sssssds",
            $student_id, $full_name, $class_name,
            $gender, $parent_phone, $fees_balance, $photoName);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Student added successfully.";
        } else {
            $message = "Failed to add student. Please try again.";
            $messageType = "error";
            error_log("add-student insert error: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
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
        <img src="../assets/images/logo.png" alt="Logo">

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

    <?php if ($message): ?>

        <div class="<?php echo $messageType === 'error' ? 'error-message' : 'success-message'; ?>">
            <?php echo h($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="student-form">
        <?php echo csrf_field(); ?>

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
            <input type="number" name="fees_balance" step="0.01">
        </div>

        <div class="form-group">
            <label>Student Profile Photo (max 2 MB, JPG/PNG/WebP/GIF)</label>
            <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp,image/gif">
        </div>

        <button type="submit">
            Save Student
        </button>

    </form>

</main>

</body>
</html>
