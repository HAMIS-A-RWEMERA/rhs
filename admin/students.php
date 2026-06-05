<?php
require_once __DIR__ . '/../config/helpers.php';
start_secure_session();
require_admin();

include __DIR__ . '/../config/db.php';

/* FETCH STUDENTS */
$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $like = '%' . $search . '%';
    $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE
        student_id LIKE ? OR full_name LIKE ? OR class_name LIKE ?
        ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $studentsResult = mysqli_stmt_get_result($stmt);
} else {
    $studentsResult = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC");
}
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

    <form class="search-form" method="GET">

        <input
            type="text"
            name="search"
            placeholder="Search student..."
            value="<?php echo h($search); ?>"
        >

        <button type="submit">
            Search
        </button>

    </form>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Class</th>
                    <th>Gender</th>
                    <th>Parent Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php while ($student = mysqli_fetch_assoc($studentsResult)): ?>

                <tr>
                    <td><?php echo (int) $student['id']; ?></td>
                    <td>
                        <img
                            src="../uploads/students/<?php echo h($student['profile_photo']); ?>"
                            class="student-photo"
                            alt="Photo"
                        >
                    </td>

                    <td><?php echo h($student['student_id']); ?></td>

                    <td><?php echo h($student['full_name']); ?></td>

                    <td><?php echo h($student['class_name']); ?></td>

                    <td><?php echo h($student['gender']); ?></td>

                    <td><?php echo h($student['parent_phone']); ?></td>

                    <td>

                        <a class="edit-btn" href="edit-student.php?id=<?php echo (int) $student['id']; ?>">
                            Edit
                        </a>

                        <form method="POST" action="delete-student.php" style="display:inline;"
                              onsubmit="return confirm('Delete this student permanently?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int) $student['id']; ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>

                    </td>
                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>
