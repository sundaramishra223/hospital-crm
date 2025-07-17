<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

// Allowed roles
$allowed_roles = ['admin', 'staff', 'nurse', 'doctor', 'receptionist'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Add new equipment
if (isset($_POST['add_equipment'])) {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $assigned_to = $_POST['assigned_to'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO equipment (name, type, status, assigned_to) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $type, $status, $assigned_to])) {
        $success = 'Equipment added successfully!';
    } else {
        $error = 'Failed to add equipment.';
    }
}

// Edit equipment
if (isset($_POST['edit_equipment'])) {
    $equipment_id = $_POST['equipment_id'];
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $assigned_to = $_POST['assigned_to'] ?? null;
    $stmt = $pdo->prepare("UPDATE equipment SET name=?, type=?, status=?, assigned_to=? WHERE id=?");
    if ($stmt->execute([$name, $type, $status, $assigned_to, $equipment_id])) {
        $success = 'Equipment updated successfully!';
    } else {
        $error = 'Failed to update equipment.';
    }
}

// Soft delete equipment
if (isset($_POST['delete_equipment'])) {
    $equipment_id = $_POST['equipment_id'];
    $stmt = $pdo->prepare("UPDATE equipment SET status='deleted' WHERE id=?");
    if ($stmt->execute([$equipment_id])) {
        $success = 'Equipment deleted (soft) successfully!';
    } else {
        $error = 'Failed to delete equipment.';
    }
}

// Fetch equipment
$sql = "SELECT e.*, u.name as assigned_name FROM equipment e LEFT JOIN users u ON e.assigned_to = u.id WHERE e.status != 'deleted' ORDER BY e.id DESC";
$equipment = $pdo->query($sql)->fetchAll();

// Fetch users for assignment
$users = $pdo->query("SELECT id, name, role FROM users WHERE status = 'active'")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        .equipment-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .equipment-table th, .equipment-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .equipment-table th { background: #f5f5f5; }
        body.dark-mode .equipment-table th { background: #23272b; color: #eee; }
        .status-available { color: green; font-weight: bold; }
        .status-inuse { color: orange; font-weight: bold; }
        .status-maintenance { color: #888; font-weight: bold; }
        .status-deleted { color: red; font-weight: bold; }
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
        <h2>Equipment Management</h2>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" style="margin-bottom:20px;">
            <h4>Add New Equipment</h4>
            <input type="text" name="name" placeholder="Equipment Name" required>
            <input type="text" name="type" placeholder="Type (Monitor/ECG/etc.)" required>
            <select name="status">
                <option value="available">Available</option>
                <option value="inuse">In Use</option>
                <option value="maintenance">Maintenance</option>
            </select>
            <select name="assigned_to">
                <option value="">Assign to (optional)</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']) . ' (' . $u['role'] . ')'; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add_equipment">Add Equipment</button>
        </form>
        <table class="equipment-table">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($equipment as $eq): ?>
            <tr>
                <td><?php echo $eq['id']; ?></td>
                <td><?php echo htmlspecialchars($eq['name']); ?></td>
                <td><?php echo htmlspecialchars($eq['type']); ?></td>
                <td class="status-<?php echo $eq['status']; ?>"><?php echo ucfirst($eq['status']); ?></td>
                <td><?php echo $eq['assigned_name'] ? htmlspecialchars($eq['assigned_name']) : '-'; ?></td>
                <td class="action-btns">
                    <!-- Edit Form -->
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="equipment_id" value="<?php echo $eq['id']; ?>">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($eq['name']); ?>" style="width:80px;">
                        <input type="text" name="type" value="<?php echo htmlspecialchars($eq['type']); ?>" style="width:80px;">
                        <select name="status">
                            <option value="available" <?php if ($eq['status']==='available') echo 'selected'; ?>>Available</option>
                            <option value="inuse" <?php if ($eq['status']==='inuse') echo 'selected'; ?>>In Use</option>
                            <option value="maintenance" <?php if ($eq['status']==='maintenance') echo 'selected'; ?>>Maintenance</option>
                        </select>
                        <select name="assigned_to">
                            <option value="">Assign to (optional)</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php if ($eq['assigned_to']==$u['id']) echo 'selected'; ?>><?php echo htmlspecialchars($u['name']) . ' (' . $u['role'] . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="edit_equipment">Update</button>
                    </form>
                    <!-- Delete (soft) -->
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure? This will only mark the equipment as deleted.');">
                        <input type="hidden" name="equipment_id" value="<?php echo $eq['id']; ?>">
                        <button type="submit" name="delete_equipment">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>