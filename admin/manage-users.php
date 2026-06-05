<?php
require_once __DIR__ . '/../config/helpers.php';
start_secure_session();
require_login('admin');

include __DIR__ . '/../config/db.php';

$message = "";
$messageType = "";

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    verify_csrf();

    if ($_POST['action'] === 'add_user') {
        $username  = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $role      = $_POST['role'] ?? 'admin';

        $validRoles = ['admin', 'registrar', 'discipline_master', 'bursar', 'director_of_studies', 'teacher', 'parent'];

        if (empty($username) || empty($full_name) || empty($password)) {
            $message = "Username, full name, and password are required.";
            $messageType = "error";
        } elseif (strlen($password) < 6) {
            $message = "Password must be at least 6 characters.";
            $messageType = "error";
        } elseif (!in_array($role, $validRoles, true)) {
            $message = "Invalid role selected.";
            $messageType = "error";
        } else {
            // Check if username exists
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($check, "s", $username);
            mysqli_stmt_execute($check);
            $exists = mysqli_stmt_get_result($check);

            if (mysqli_num_rows($exists) > 0) {
                $message = "Username already exists.";
                $messageType = "error";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $hash, $full_name, $role);

                if (mysqli_stmt_execute($stmt)) {
                    $message = "User '$username' created successfully with role '$role'.";
                    $messageType = "success";
                } else {
                    $message = "Failed to create user. Please try again.";
                    $messageType = "error";
                    error_log("manage-users insert error: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($check);
        }
    }

    if ($_POST['action'] === 'toggle_active') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $newStatus = (int) ($_POST['new_status'] ?? 0);

        if ($userId === current_user_id()) {
            $message = "You cannot deactivate your own account.";
            $messageType = "error";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $newStatus, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = $newStatus ? "User activated." : "User deactivated.";
            $messageType = "success";
        }
    }

    if ($_POST['action'] === 'reset_password') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        if (strlen($newPassword) < 6) {
            $message = "Password must be at least 6 characters.";
            $messageType = "error";
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hash, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = "Password reset successfully.";
            $messageType = "success";
        }
    }
}

// Fetch all users
$usersResult = mysqli_query($conn, "SELECT id, username, email, full_name, role, is_active, created_at FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | RHS Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-header">
    <div class="admin-logo">
        <img src="../assets/images/logo.png" alt="RHS Logo">
        <div>
            <h1>RHS Admin</h1>
            <p>User Management</p>
        </div>
    </div>
    <nav class="admin-nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage-users.php">Users</a></li>
            <li><a href="view-students.php">Students</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main class="dashboard">

    <div class="dashboard-title">
        <h2>Manage Staff Accounts</h2>
        <p>Create and manage user accounts for staff members.</p>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageType === 'error' ? 'error-message' : 'success-message'; ?>">
            <?php echo h($message); ?>
        </div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="dashboard-card" style="margin-bottom: 30px;">
        <h3>Add New User</h3>
        <form method="POST" class="student-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add_user">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="e.g. jdoe">
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="e.g. John Doe">
            </div>

            <div class="form-group">
                <label>Email (optional)</label>
                <input type="email" name="email" placeholder="e.g. jdoe@rusumohighschool.rw">
            </div>

            <div class="form-group">
                <label>Password (min 6 characters)</label>
                <input type="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="admin">Admin</option>
                    <option value="registrar">Registrar</option>
                    <option value="discipline_master">Discipline Master</option>
                    <option value="bursar">Bursar</option>
                    <option value="director_of_studies">Director of Studies</option>
                    <option value="teacher">Teacher</option>
                    <option value="parent">Parent</option>
                </select>
            </div>

            <button type="submit">Create User</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($user = mysqli_fetch_assoc($usersResult)): ?>
                <tr>
                    <td><?php echo (int) $user['id']; ?></td>
                    <td><?php echo h($user['username']); ?></td>
                    <td><?php echo h($user['full_name']); ?></td>
                    <td><?php echo h(str_replace('_', ' ', ucfirst($user['role']))); ?></td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span style="color:green;">Active</span>
                        <?php else: ?>
                            <span style="color:red;">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo h(date('d M Y', strtotime($user['created_at']))); ?></td>
                    <td>
                        <?php if ((int) $user['id'] !== current_user_id()): ?>
                            <!-- Toggle active -->
                            <form method="POST" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                <button type="submit" class="<?php echo $user['is_active'] ? 'delete-btn' : 'edit-btn'; ?>"
                                        onclick="return confirm('<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> this user?')">
                                    <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>

                            <!-- Reset password -->
                            <form method="POST" style="display:inline;"
                                  onsubmit="var p = prompt('Enter new password (min 6 chars):'); if(!p || p.length < 6){alert('Password must be at least 6 characters.');return false;} this.querySelector('[name=new_password]').value=p; return true;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                <input type="hidden" name="new_password" value="">
                                <button type="submit" class="edit-btn">Reset Pwd</button>
                            </form>
                        <?php else: ?>
                            <em style="color:#888;">You</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</main>

</body>
</html>
