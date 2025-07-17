<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

// Only admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Filters
$user_id = $_GET['user_id'] ?? '';
$action = $_GET['action'] ?? '';
$date = $_GET['date'] ?? '';

$sql = "SELECT l.*, u.name as user_name, u.role as user_role FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
$params = [];
if ($user_id) {
    $sql .= " AND l.user_id = ?";
    $params[] = $user_id;
}
if ($action) {
    $sql .= " AND l.action = ?";
    $params[] = $action;
}
if ($date) {
    $sql .= " AND DATE(l.created_at) = ?";
    $params[] = $date;
}
$sql .= " ORDER BY l.created_at DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Fetch users and actions for filters
$users = $pdo->query("SELECT id, name, role FROM users WHERE status = 'active'")->fetchAll();
$actions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit & Logs</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 1100px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        .logs-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .logs-table th, .logs-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .logs-table th { background: #f5f5f5; }
        body.dark-mode .logs-table th { background: #23272b; color: #eee; }
        .filter-form { margin-bottom: 20px; }
        .filter-form select, .filter-form input { margin-right: 10px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; }
        body.dark-mode .filter-form select, body.dark-mode .filter-form input { background: #181a1b; color: #eee; border: 1px solid #444; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .alert-error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        body.dark-mode .alert-success { color: #d4edda; background: #155724; border: 1px solid #155724; }
        body.dark-mode .alert-error { color: #f8d7da; background: #721c24; border: 1px solid #721c24; }
        h2 { margin-bottom: 20px; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($_SESSION['theme_mode'] ?? getSetting('theme_mode', 'light')); ?>-mode">
    <div class="container">
        <h2>Audit & Logs</h2>
        <form method="get" class="filter-form">
            <select name="user_id">
                <option value="">All Users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php if ($user_id==$u['id']) echo 'selected'; ?>><?php echo htmlspecialchars($u['name']) . ' (' . $u['role'] . ')'; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="action">
                <option value="">All Actions</option>
                <?php foreach ($actions as $a): ?>
                    <option value="<?php echo htmlspecialchars($a['action']); ?>" <?php if ($action==$a['action']) echo 'selected'; ?>><?php echo htmlspecialchars($a['action']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
            <button type="submit">Filter</button>
        </form>
        <table class="logs-table">
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Role</th>
                <th>Action</th>
                <th>Description</th>
                <th>Date/Time</th>
            </tr>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?php echo $log['id']; ?></td>
                <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                <td><?php echo htmlspecialchars($log['user_role']); ?></td>
                <td><?php echo htmlspecialchars($log['action']); ?></td>
                <td><?php echo htmlspecialchars($log['description']); ?></td>
                <td><?php echo htmlspecialchars($log['created_at']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($logs)): ?>
            <div class="alert-error">No logs found for selected filters.</div>
        <?php endif; ?>
    </div>
</body>
</html>