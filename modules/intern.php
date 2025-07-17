<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

$allowed_roles = ['admin', 'intern', 'doctor', 'nurse', 'lab_tech', 'pharmacy'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Add new intern
if (isset($_POST['add_intern'])) {
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $address = $_POST['address'] ?? '';
    $department = $_POST['department'] ?? '';
    $supervisor = $_POST['supervisor'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO users (name, contact, address, department, supervisor, role, status) VALUES (?, ?, ?, ?, ?, 'intern', 'active')");
    if ($stmt->execute([$name, $contact, $address, $department, $supervisor])) {
        $success = 'Intern added successfully!';
    } else {
        $error = 'Failed to add intern.';
    }
}

// Edit intern
if (isset($_POST['edit_intern'])) {
    $intern_id = $_POST['intern_id'];
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $address = $_POST['address'] ?? '';
    $department = $_POST['department'] ?? '';
    $supervisor = $_POST['supervisor'] ?? '';
    $stmt = $pdo->prepare("UPDATE users SET name=?, contact=?, address=?, department=?, supervisor=? WHERE id=? AND role='intern'");
    if ($stmt->execute([$name, $contact, $address, $department, $supervisor, $intern_id])) {
        $success = 'Intern updated successfully!';
    } else {
        $error = 'Failed to update intern.';
    }
}

// Soft delete intern
if (isset($_POST['delete_intern'])) {
    $intern_id = $_POST['intern_id'];
    $stmt = $pdo->prepare("UPDATE users SET status='deleted' WHERE id=? AND role='intern'");
    if ($stmt->execute([$intern_id])) {
        $success = 'Intern deleted (soft) successfully!';
    } else {
        $error = 'Failed to delete intern.';
    }
}

// Fetch interns
$interns = $pdo->query("SELECT * FROM users WHERE role='intern' AND status != 'deleted' ORDER BY id DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Management</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        .intern-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .intern-table th, .intern-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .intern-table th { background: #f5f5f5; }
        body.dark-mode .intern-table th { background: #23272b; color: #eee; }
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
        <h2>Intern Management</h2>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" style="margin-bottom:20px;">
            <h4>Add New Intern</h4>
            <input type="text" name="name" placeholder="Intern Name" required>
            <input type="text" name="contact" placeholder="Contact" required>
            <input type="text" name="address" placeholder="Address" required>
            <input type="text" name="department" placeholder="Department (optional)">
            <input type="text" name="supervisor" placeholder="Supervisor (optional)">
            <button type="submit" name="add_intern">Add Intern</button>
        </form>
        <table class="intern-table">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Department</th>
                <th>Supervisor</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($interns as $i): ?>
            <tr>
                <td><?php echo $i['id']; ?></td>
                <td><?php echo htmlspecialchars($i['name']); ?></td>
                <td><?php echo htmlspecialchars($i['contact']); ?></td>
                <td><?php echo htmlspecialchars($i['address']); ?></td>
                <td><?php echo htmlspecialchars($i['department']); ?></td>
                <td><?php echo htmlspecialchars($i['supervisor']); ?></td>
                <td class="action-btns">
                    <!-- Edit Form -->
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="intern_id" value="<?php echo $i['id']; ?>">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($i['name']); ?>" style="width:80px;">
                        <input type="text" name="contact" value="<?php echo htmlspecialchars($i['contact']); ?>" style="width:80px;">
                        <input type="text" name="address" value="<?php echo htmlspecialchars($i['address']); ?>" style="width:80px;">
                        <input type="text" name="department" value="<?php echo htmlspecialchars($i['department']); ?>" style="width:80px;">
                        <input type="text" name="supervisor" value="<?php echo htmlspecialchars($i['supervisor']); ?>" style="width:80px;">
                        <button type="submit" name="edit_intern">Update</button>
                    </form>
                    <!-- Delete (soft) -->
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure? This will only mark the intern as deleted.');">
                        <input type="hidden" name="intern_id" value="<?php echo $i['id']; ?>">
                        <button type="submit" name="delete_intern">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>