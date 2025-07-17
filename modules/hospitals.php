<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
$enable_multi_hospital = getSetting('enable_multi_hospital', 0);
if (!$enable_multi_hospital) {
    echo '<div style="padding:40px;text-align:center;">Multi-hospital/clinic system is disabled in settings.</div>';
    exit();
}
$success = '';
$error = '';
// Add hospital
if (isset($_POST['add_hospital'])) {
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $type = $_POST['type'] ?? 'hospital';
    $stmt = $pdo->prepare("INSERT INTO hospitals (name, address, phone, email, type, status) VALUES (?, ?, ?, ?, ?, 'active')");
    if ($stmt->execute([$name, $address, $phone, $email, $type])) {
        $success = 'Hospital/Clinic added!';
    } else {
        $error = 'Failed to add.';
    }
}
// Edit hospital
if (isset($_POST['edit_hospital'])) {
    $id = $_POST['id'];
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $type = $_POST['type'] ?? 'hospital';
    $stmt = $pdo->prepare("UPDATE hospitals SET name=?, address=?, phone=?, email=?, type=? WHERE id=?");
    if ($stmt->execute([$name, $address, $phone, $email, $type, $id])) {
        $success = 'Updated!';
    } else {
        $error = 'Failed to update.';
    }
}
// Soft delete
if (isset($_POST['delete_hospital'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE hospitals SET status='inactive' WHERE id=?");
    if ($stmt->execute([$id])) {
        $success = 'Deleted (soft)!';
    } else {
        $error = 'Failed to delete.';
    }
}
$hospitals = $pdo->query("SELECT * FROM hospitals WHERE status != 'inactive' ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospitals/Clinics Management</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        h2 { margin-bottom: 20px; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .alert-error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        body.dark-mode th { background: #23272b; color: #eee; }
        .action-btns button { margin: 0 2px; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($_SESSION['theme_mode'] ?? getSetting('theme_mode', 'light')); ?>-mode">
    <div class="container">
        <h2>Hospitals/Clinics Management</h2>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" style="margin-bottom:20px;">
            <h4>Add New Hospital/Clinic</h4>
            <input type="text" name="name" placeholder="Name" required>
            <input type="text" name="address" placeholder="Address" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <input type="email" name="email" placeholder="Email" required>
            <select name="type">
                <option value="hospital">Hospital</option>
                <option value="clinic">Clinic</option>
                <option value="specialty_center">Specialty Center</option>
            </select>
            <button type="submit" name="add_hospital">Add</button>
        </form>
        <table>
            <tr><th>Name</th><th>Type</th><th>Address</th><th>Phone</th><th>Email</th><th>Actions</th></tr>
            <?php foreach ($hospitals as $h): ?>
            <tr>
                <td><?php echo htmlspecialchars($h['name']); ?></td>
                <td><?php echo htmlspecialchars($h['type']); ?></td>
                <td><?php echo htmlspecialchars($h['address']); ?></td>
                <td><?php echo htmlspecialchars($h['phone']); ?></td>
                <td><?php echo htmlspecialchars($h['email']); ?></td>
                <td class="action-btns">
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($h['name']); ?>" style="width:80px;">
                        <input type="text" name="address" value="<?php echo htmlspecialchars($h['address']); ?>" style="width:80px;">
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($h['phone']); ?>" style="width:80px;">
                        <input type="email" name="email" value="<?php echo htmlspecialchars($h['email']); ?>" style="width:80px;">
                        <select name="type">
                            <option value="hospital" <?php if ($h['type']==='hospital') echo 'selected'; ?>>Hospital</option>
                            <option value="clinic" <?php if ($h['type']==='clinic') echo 'selected'; ?>>Clinic</option>
                            <option value="specialty_center" <?php if ($h['type']==='specialty_center') echo 'selected'; ?>>Specialty Center</option>
                        </select>
                        <button type="submit" name="edit_hospital">Update</button>
                    </form>
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure? This will only mark as inactive.');">
                        <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                        <button type="submit" name="delete_hospital">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($hospitals)): ?><tr><td colspan="6">No hospitals/clinics found.</td></tr><?php endif; ?>
        </table>
    </div>
</body>
</html>