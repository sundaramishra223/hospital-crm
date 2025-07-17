<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

$allowed_roles = ['admin', 'staff', 'nurse', 'doctor', 'receptionist'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Add new shift
if (isset($_POST['add_shift'])) {
    $name = $_POST['name'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO shifts (name, start_time, end_time, assigned_to, status) VALUES (?, ?, ?, ?, 'active')");
    if ($stmt->execute([$name, $start_time, $end_time, $assigned_to])) {
        $success = 'Shift added successfully!';
    } else {
        $error = 'Failed to add shift.';
    }
}

// Edit shift
if (isset($_POST['edit_shift'])) {
    $shift_id = $_POST['shift_id'];
    $name = $_POST['name'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? null;
    $stmt = $pdo->prepare("UPDATE shifts SET name=?, start_time=?, end_time=?, assigned_to=? WHERE id=?");
    if ($stmt->execute([$name, $start_time, $end_time, $assigned_to, $shift_id])) {
        $success = 'Shift updated successfully!';
    } else {
        $error = 'Failed to update shift.';
    }
}

// Soft delete shift
if (isset($_POST['delete_shift'])) {
    $shift_id = $_POST['shift_id'];
    $stmt = $pdo->prepare("UPDATE shifts SET status='deleted' WHERE id=?");
    if ($stmt->execute([$shift_id])) {
        $success = 'Shift deleted (soft) successfully!';
    } else {
        $error = 'Failed to delete shift.';
    }
}

// Fetch shifts
$sql = "SELECT s.*, u.name as assigned_name, u.role as assigned_role FROM shifts s LEFT JOIN users u ON s.assigned_to = u.id WHERE s.status != 'deleted' ORDER BY s.id DESC";
$shifts = $pdo->query($sql)->fetchAll();

// Fetch users for assignment
$users = $pdo->query("SELECT id, name, role FROM users WHERE status = 'active'")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Management</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        .shift-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .shift-table th, .shift-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .shift-table th { background: #f5f5f5; }
        body.dark-mode .shift-table th { background: #23272b; color: #eee; }
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
        <h2>Shift Management</h2>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" style="margin-bottom:20px;">
            <h4>Add New Shift</h4>
            <input type="text" name="name" placeholder="Shift Name" required>
            <input type="time" name="start_time" required>
            <input type="time" name="end_time" required>
            <select name="assigned_to">
                <option value="">Assign to (optional)</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']) . ' (' . $u['role'] . ')'; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add_shift">Add Shift</button>
        </form>
        <table class="shift-table">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Assigned To</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($shifts as $s): ?>
            <tr>
                <td><?php echo $s['id']; ?></td>
                <td><?php echo htmlspecialchars($s['name']); ?></td>
                <td><?php echo htmlspecialchars($s['start_time']); ?></td>
                <td><?php echo htmlspecialchars($s['end_time']); ?></td>
                <td><?php echo $s['assigned_name'] ? htmlspecialchars($s['assigned_name']) . ' (' . $s['assigned_role'] . ')' : '-'; ?></td>
                <td class="action-btns">
                    <!-- Edit Form -->
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="shift_id" value="<?php echo $s['id']; ?>">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($s['name']); ?>" style="width:80px;">
                        <input type="time" name="start_time" value="<?php echo htmlspecialchars($s['start_time']); ?>">
                        <input type="time" name="end_time" value="<?php echo htmlspecialchars($s['end_time']); ?>">
                        <select name="assigned_to">
                            <option value="">Assign to (optional)</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php if ($s['assigned_to']==$u['id']) echo 'selected'; ?>><?php echo htmlspecialchars($u['name']) . ' (' . $u['role'] . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="edit_shift">Update</button>
                    </form>
                    <!-- Delete (soft) -->
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure? This will only mark the shift as deleted.');">
                        <input type="hidden" name="shift_id" value="<?php echo $s['id']; ?>">
                        <button type="submit" name="delete_shift">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>