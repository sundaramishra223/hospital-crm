<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

$allowed_roles = ['admin', 'staff', 'receptionist'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Add new staff
if (isset($_POST['add_staff'])) {
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $address = $_POST['address'] ?? '';
    $department = $_POST['department'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO users (name, contact, address, department, role, status) VALUES (?, ?, ?, ?, 'staff', 'active')");
    if ($stmt->execute([$name, $contact, $address, $department])) {
        $success = 'Staff added successfully!';
    } else {
        $error = 'Failed to add staff.';
    }
}

// Edit staff
if (isset($_POST['edit_staff'])) {
    $staff_id = $_POST['staff_id'];
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $address = $_POST['address'] ?? '';
    $department = $_POST['department'] ?? '';
    $stmt = $pdo->prepare("UPDATE users SET name=?, contact=?, address=?, department=? WHERE id=? AND role='staff'");
    if ($stmt->execute([$name, $contact, $address, $department, $staff_id])) {
        $success = 'Staff updated successfully!';
    } else {
        $error = 'Failed to update staff.';
    }
}

// Soft delete staff
if (isset($_POST['delete_staff'])) {
    $staff_id = $_POST['staff_id'];
    $stmt = $pdo->prepare("UPDATE users SET status='deleted' WHERE id=? AND role='staff'");
    if ($stmt->execute([$staff_id])) {
        $success = 'Staff deleted (soft) successfully!';
    } else {
        $error = 'Failed to delete staff.';
    }
}

// Fetch staff
$staff = $pdo->query("SELECT * FROM users WHERE role='staff' AND status != 'deleted' ORDER BY id DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        .staff-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .staff-table th, .staff-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .staff-table th { background: #f5f5f5; }
        body.dark-mode .staff-table th { background: #23272b; color: #eee; }
        .action-btns button { margin: 0 2px; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .alert-error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        body.dark-mode .alert-success { color: #d4edda; background: #155724; border: 1px solid #155724; }
        body.dark-mode .alert-error { color: #f8d7da; background: #721c24; border: 1px solid #721c24; }
        h2 { margin-bottom: 20px; }
        form h4 { margin-bottom: 10px; }
        form input, form select, form button { margin-right: 5px; }
        body.dark-mode form input, body.dark-mode form select { background: #181a1b; color: #eee; border: 1px solid #444; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($_SESSION['theme_mode'] ?? getSetting('theme_mode', 'light')); ?>-mode">
    <div class="container">
        <h2>Staff Management</h2>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" style="margin-bottom:20px;">
            <h4>Add New Staff</h4>
            <input type="text" name="name" placeholder="Staff Name" required>
            <input type="text" name="contact" placeholder="Contact" required>
            <input type="text" name="address" placeholder="Address" required>
            <input type="text" name="department" placeholder="Department (optional)">
            <button type="submit" name="add_staff">Add Staff</button>
        </form>
        <table class="staff-table">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Department</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($staff as $s): ?>
            <tr>
                <td><?php echo $s['id']; ?></td>
                <td><?php echo htmlspecialchars($s['name']); ?></td>
                <td><?php echo htmlspecialchars($s['contact']); ?></td>
                <td><?php echo htmlspecialchars($s['address']); ?></td>
                <td><?php echo htmlspecialchars($s['department']); ?></td>
                <td class="action-btns">
                    <!-- Edit Form -->
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="staff_id" value="<?php echo $s['id']; ?>">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($s['name']); ?>" style="width:80px;">
                        <input type="text" name="contact" value="<?php echo htmlspecialchars($s['contact']); ?>" style="width:80px;">
                        <input type="text" name="address" value="<?php echo htmlspecialchars($s['address']); ?>" style="width:80px;">
                        <input type="text" name="department" value="<?php echo htmlspecialchars($s['department']); ?>" style="width:80px;">
                        <button type="submit" name="edit_staff">Update</button>
                    </form>
                    <!-- Delete (soft) -->
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure? This will only mark the staff as deleted.');">
                        <input type="hidden" name="staff_id" value="<?php echo $s['id']; ?>">
                        <button type="submit" name="delete_staff">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>