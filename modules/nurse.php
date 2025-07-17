<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

$allowed_roles = ['admin', 'nurse', 'doctor', 'receptionist'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Add new nurse
if (isset($_POST['add_nurse'])) {
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $address = $_POST['address'] ?? '';
    $vitals = $_POST['vitals'] ?? '';
    $stmt = $pdo->prepare("INSERT INTO users (name, contact, address, vitals, role, status) VALUES (?, ?, ?, ?, 'nurse', 'active')");
    if ($stmt->execute([$name, $contact, $address, $vitals])) {
        $success = 'Nurse added successfully!';
    } else {
        $error = 'Failed to add nurse.';
    }
}

// Edit nurse
if (isset($_POST['edit_nurse'])) {
    $nurse_id = $_POST['nurse_id'];
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $address = $_POST['address'] ?? '';
    $vitals = $_POST['vitals'] ?? '';
    $stmt = $pdo->prepare("UPDATE users SET name=?, contact=?, address=?, vitals=? WHERE id=? AND role='nurse'");
    if ($stmt->execute([$name, $contact, $address, $vitals, $nurse_id])) {
        $success = 'Nurse updated successfully!';
    } else {
        $error = 'Failed to update nurse.';
    }
}

// Soft delete nurse
if (isset($_POST['delete_nurse'])) {
    $nurse_id = $_POST['nurse_id'];
    $stmt = $pdo->prepare("UPDATE users SET status='deleted' WHERE id=? AND role='nurse'");
    if ($stmt->execute([$nurse_id])) {
        $success = 'Nurse deleted (soft) successfully!';
    } else {
        $error = 'Failed to delete nurse.';
    }
}

// Fetch nurses
$nurses = $pdo->query("SELECT * FROM users WHERE role='nurse' AND status != 'deleted' ORDER BY id DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Management</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        .nurse-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .nurse-table th, .nurse-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .nurse-table th { background: #f5f5f5; }
        body.dark-mode .nurse-table th { background: #23272b; color: #eee; }
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
        <h2>Nurse Management</h2>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" style="margin-bottom:20px;">
            <h4>Add New Nurse</h4>
            <input type="text" name="name" placeholder="Nurse Name" required>
            <input type="text" name="contact" placeholder="Contact" required>
            <input type="text" name="address" placeholder="Address" required>
            <input type="text" name="vitals" placeholder="Vitals (optional)">
            <button type="submit" name="add_nurse">Add Nurse</button>
        </form>
        <table class="nurse-table">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Vitals</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($nurses as $n): ?>
            <tr>
                <td><?php echo $n['id']; ?></td>
                <td><?php echo htmlspecialchars($n['name']); ?></td>
                <td><?php echo htmlspecialchars($n['contact']); ?></td>
                <td><?php echo htmlspecialchars($n['address']); ?></td>
                <td><?php echo htmlspecialchars($n['vitals']); ?></td>
                <td class="action-btns">
                    <!-- Edit Form -->
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="nurse_id" value="<?php echo $n['id']; ?>">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($n['name']); ?>" style="width:80px;">
                        <input type="text" name="contact" value="<?php echo htmlspecialchars($n['contact']); ?>" style="width:80px;">
                        <input type="text" name="address" value="<?php echo htmlspecialchars($n['address']); ?>" style="width:80px;">
                        <input type="text" name="vitals" value="<?php echo htmlspecialchars($n['vitals']); ?>" style="width:80px;">
                        <button type="submit" name="edit_nurse">Update</button>
                    </form>
                    <!-- Delete (soft) -->
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure? This will only mark the nurse as deleted.');">
                        <input type="hidden" name="nurse_id" value="<?php echo $n['id']; ?>">
                        <button type="submit" name="delete_nurse">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>