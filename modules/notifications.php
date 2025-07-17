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

// Send notification (simulate, no real API)
if (isset($_POST['send_notification'])) {
    $type = $_POST['type'] ?? 'custom';
    $message = $_POST['message'] ?? '';
    $role = $_POST['role'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $to_users = [];
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id=? AND status='active'");
        $stmt->execute([$user_id]);
        $to_users = $stmt->fetchAll();
    } elseif ($role) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE role=? AND status='active'");
        $stmt->execute([$role]);
        $to_users = $stmt->fetchAll();
    }
    foreach ($to_users as $u) {
        // Simulate sending (API slot)
        $pdo->prepare("INSERT INTO notifications (user_id, type, message, sent_at) VALUES (?, ?, ?, NOW())")
            ->execute([$u['id'], $type, $message]);
    }
    $success = count($to_users) . ' notification(s) sent! (Simulated, API slot here)';
}

// Fetch users and roles
$users = $pdo->query("SELECT id, name, role FROM users WHERE status='active' ORDER BY name")->fetchAll();
$roles = $pdo->query("SELECT DISTINCT role FROM users WHERE status='active'")->fetchAll();

// Fetch sent notifications
$sent = $pdo->query("SELECT n.*, u.name as user_name, u.role as user_role FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.sent_at DESC LIMIT 100")->fetchAll();

$template_reminder = getSetting('template_reminder', 'Dear {name}, your appointment is scheduled on {date}.');
$template_bill = getSetting('template_bill', 'Dear {name}, your bill amount is {amount}.');
$template_emergency = getSetting('template_emergency', 'Emergency alert: {message}');
$template_custom = getSetting('template_custom', 'Hello {name}, ...');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications (Email/SMS)</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        .notification-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .notification-table th, .notification-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .notification-table th { background: #f5f5f5; }
        body.dark-mode .notification-table th { background: #23272b; color: #eee; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .alert-error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        body.dark-mode .alert-success { color: #d4edda; background: #155724; border: 1px solid #155724; }
        body.dark-mode .alert-error { color: #f8d7da; background: #721c24; border: 1px solid #721c24; }
        h2 { margin-bottom: 20px; }
        form h4 { margin-bottom: 10px; }
        form input, form select, form textarea, form button { margin-right: 5px; margin-bottom: 5px; }
        form textarea { width: 100%; min-height: 60px; }
        body.dark-mode form input, body.dark-mode form select, body.dark-mode form textarea { background: #181a1b; color: #eee; border: 1px solid #444; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($_SESSION['theme_mode'] ?? getSetting('theme_mode', 'light')); ?>-mode">
    <div class="container">
        <h2>Notifications (Email/SMS)</h2>
        <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" style="margin-bottom:20px;" id="notificationForm">
            <h4>Send Notification</h4>
            <select name="type" id="typeSelect" required onchange="setTemplate()">
                <option value="reminder">Appointment Reminder</option>
                <option value="bill">Bill</option>
                <option value="emergency">Emergency</option>
                <option value="custom">Custom</option>
            </select>
            <select name="role">
                <option value="">Select Role (optional)</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?php echo $r['role']; ?>"><?php echo ucfirst($r['role']); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="user_id">
                <option value="">Select User (optional)</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']) . ' (' . $u['role'] . ')'; ?></option>
                <?php endforeach; ?>
            </select>
            <textarea name="message" id="messageBox" placeholder="Message template..." required></textarea>
            <button type="submit" name="send_notification">Send</button>
        </form>
        <h4>Sent Notifications (Log)</h4>
        <table class="notification-table">
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Role</th>
                <th>Type</th>
                <th>Message</th>
                <th>Sent At</th>
            </tr>
            <?php foreach ($sent as $n): ?>
            <tr>
                <td><?php echo $n['id']; ?></td>
                <td><?php echo htmlspecialchars($n['user_name']); ?></td>
                <td><?php echo htmlspecialchars($n['user_role']); ?></td>
                <td><?php echo htmlspecialchars($n['type']); ?></td>
                <td><?php echo htmlspecialchars($n['message']); ?></td>
                <td><?php echo htmlspecialchars($n['sent_at']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($sent)): ?>
            <div class="alert-error">No notifications sent yet.</div>
        <?php endif; ?>
        <div style="margin-top:30px; font-size:13px; color:#888;">
            <b>Note:</b> This is a simulation. Integrate with Twilio/Firebase or any Email/SMS API here.<br>
            Templates can be customized in the message box above.
        </div>
    </div>
    <script>
function setTemplate() {
    var type = document.getElementById('typeSelect').value;
    var templates = {
        reminder: <?php echo json_encode($template_reminder); ?>,
        bill: <?php echo json_encode($template_bill); ?>,
        emergency: <?php echo json_encode($template_emergency); ?>,
        custom: <?php echo json_encode($template_custom); ?>
    };
    document.getElementById('messageBox').value = templates[type] || '';
}
document.addEventListener('DOMContentLoaded', function() {
    setTemplate();
});
</script>
</body>
</html>