<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

// Allowed roles
$allowed_roles = ['admin', 'nurse', 'doctor', 'receptionist'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

// Handle add/edit/delete/assign actions
$success = '';
$error = '';

// Add new bed
if (isset($_POST['add_bed'])) {
    $number = $_POST['number'] ?? '';
    $type = $_POST['type'] ?? '';
    $ward = $_POST['ward'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $stmt = $pdo->prepare("INSERT INTO beds (number, type, ward, status) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$number, $type, $ward, $status])) {
        $success = 'Bed added successfully!';
    } else {
        $error = 'Failed to add bed.';
    }
}

// Edit bed
if (isset($_POST['edit_bed'])) {
    $bed_id = $_POST['bed_id'];
    $number = $_POST['number'] ?? '';
    $type = $_POST['type'] ?? '';
    $ward = $_POST['ward'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $stmt = $pdo->prepare("UPDATE beds SET number=?, type=?, ward=?, status=? WHERE id=?");
    if ($stmt->execute([$number, $type, $ward, $status, $bed_id])) {
        $success = 'Bed updated successfully!';
    } else {
        $error = 'Failed to update bed.';
    }
}

// Soft delete bed
if (isset($_POST['delete_bed'])) {
    $bed_id = $_POST['bed_id'];
    $stmt = $pdo->prepare("UPDATE beds SET status='deleted' WHERE id=?");
    if ($stmt->execute([$bed_id])) {
        $success = 'Bed deleted (soft) successfully!';
    } else {
        $error = 'Failed to delete bed.';
    }
}

// Assign bed to patient
if (isset($_POST['assign_bed'])) {
    $bed_id = $_POST['bed_id'];
    $patient_id = $_POST['patient_id'];
    $stmt = $pdo->prepare("UPDATE beds SET status='occupied', assigned_patient_id=? WHERE id=?");
    if ($stmt->execute([$patient_id, $bed_id])) {
        $success = 'Bed assigned to patient!';
    } else {
        $error = 'Failed to assign bed.';
    }
}

// Discharge bed (make available)
if (isset($_POST['discharge_bed'])) {
    $bed_id = $_POST['bed_id'];
    $stmt = $pdo->prepare("UPDATE beds SET status='available', assigned_patient_id=NULL WHERE id=?");
    if ($stmt->execute([$bed_id])) {
        $success = 'Bed marked as available!';
    } else {
        $error = 'Failed to discharge bed.';
    }
}

// Fetch beds
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT b.*, p.name as patient_name FROM beds b LEFT JOIN patients p ON b.assigned_patient_id = p.id WHERE b.status != 'deleted'";
if ($filter === 'occupied') {
    $sql .= " AND b.status = 'occupied'";
} elseif ($filter === 'available') {
    $sql .= " AND b.status = 'available'";
}
$sql .= " ORDER BY b.id DESC";
$beds = $pdo->query($sql)->fetchAll();

// Fetch patients for assignment
$patients = $pdo->query("SELECT id, name FROM patients WHERE status = 'active'")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed Management</title>
    <style>
        .bed-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .bed-table th, .bed-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .bed-table th { background: #f5f5f5; }
        .status-available { color: green; font-weight: bold; }
        .status-occupied { color: orange; font-weight: bold; }
        .status-maintenance { color: #888; font-weight: bold; }
        .status-deleted { color: red; font-weight: bold; }
        .action-btns button { margin: 0 2px; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .alert-error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        h2 { margin-bottom: 20px; }
        form h4 { margin-bottom: 10px; }
        form input, form select, form button { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Bed Management</h2>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" style="margin-bottom:20px;">
            <h4>Add New Bed</h4>
            <input type="text" name="number" placeholder="Bed Number" required>
            <input type="text" name="type" placeholder="Type (ICU/General/Private)" required>
            <input type="text" name="ward" placeholder="Ward" required>
            <select name="status">
                <option value="available">Available</option>
                <option value="maintenance">Maintenance</option>
            </select>
            <button type="submit" name="add_bed">Add Bed</button>
        </form>
        <table class="bed-table">
            <tr>
                <th>ID</th>
                <th>Number</th>
                <th>Type</th>
                <th>Ward</th>
                <th>Status</th>
                <th>Assigned Patient</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($beds as $bed): ?>
            <tr>
                <td><?php echo $bed['id']; ?></td>
                <td><?php echo htmlspecialchars($bed['number']); ?></td>
                <td><?php echo htmlspecialchars($bed['type']); ?></td>
                <td><?php echo htmlspecialchars($bed['ward']); ?></td>
                <td class="status-<?php echo $bed['status']; ?>"><?php echo ucfirst($bed['status']); ?></td>
                <td><?php echo $bed['patient_name'] ? htmlspecialchars($bed['patient_name']) : '-'; ?></td>
                <td class="action-btns">
                    <!-- Edit Form -->
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="bed_id" value="<?php echo $bed['id']; ?>">
                        <input type="text" name="number" value="<?php echo htmlspecialchars($bed['number']); ?>" style="width:60px;">
                        <input type="text" name="type" value="<?php echo htmlspecialchars($bed['type']); ?>" style="width:60px;">
                        <input type="text" name="ward" value="<?php echo htmlspecialchars($bed['ward']); ?>" style="width:60px;">
                        <select name="status">
                            <option value="available" <?php if ($bed['status']==='available') echo 'selected'; ?>>Available</option>
                            <option value="occupied" <?php if ($bed['status']==='occupied') echo 'selected'; ?>>Occupied</option>
                            <option value="maintenance" <?php if ($bed['status']==='maintenance') echo 'selected'; ?>>Maintenance</option>
                        </select>
                        <button type="submit" name="edit_bed">Update</button>
                    </form>
                    <!-- Assign/Discharge -->
                    <?php if ($bed['status'] === 'available'): ?>
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="bed_id" value="<?php echo $bed['id']; ?>">
                            <select name="patient_id">
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_bed">Assign</button>
                        </form>
                    <?php elseif ($bed['status'] === 'occupied'): ?>
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="bed_id" value="<?php echo $bed['id']; ?>">
                            <button type="submit" name="discharge_bed">Discharge</button>
                        </form>
                    <?php endif; ?>
                    <!-- Delete (soft) -->
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure? This will only mark the bed as deleted.');">
                        <input type="hidden" name="bed_id" value="<?php echo $bed['id']; ?>">
                        <button type="submit" name="delete_bed">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>