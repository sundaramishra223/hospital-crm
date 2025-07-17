<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$patient = $pdo->query("SELECT * FROM patients WHERE id = $user_id")->fetch();
// Bills
$bills = $pdo->query("SELECT * FROM bills WHERE patient_id = $user_id ORDER BY created_at DESC LIMIT 5")->fetchAll();
// Visits
$visits = $pdo->query("SELECT * FROM appointments WHERE patient_id = $user_id ORDER BY appointment_date DESC LIMIT 5")->fetchAll();
// Assigned doctor
$doctor = $pdo->query("SELECT d.*, u.name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = {$patient['doctor_id']} LIMIT 1")->fetch();
// Assigned nurse
$nurse = $pdo->query("SELECT u.* FROM users u WHERE u.role = 'nurse' AND u.department_id = {$patient['department_id']} LIMIT 1")->fetch();
// Assigned bed
$bed = $pdo->query("SELECT * FROM beds WHERE patient_id = $user_id AND status = 'occupied' LIMIT 1")->fetch();
// Lab reports
$lab_reports = $pdo->query("SELECT * FROM lab_tests WHERE patient_id = $user_id ORDER BY created_at DESC LIMIT 5")->fetchAll();
// Insurance info
$insurance = [
    'provider' => $patient['insurance_provider_id'] ? $pdo->query("SELECT name FROM insurance_providers WHERE id = {$patient['insurance_provider_id']}")->fetchColumn() : '-',
    'policy' => $patient['insurance_policy_number'] ?? '-',
    'coverage' => $patient['insurance_coverage_amount'] ?? '-',
    'status' => $patient['insurance_status'] ?? '-',
    'expiry' => $patient['insurance_expiry_date'] ?? '-',
    'claim' => $patient['insurance_status'] === 'active' ? 'Eligible' : 'Not eligible',
];
// Feedback
$feedbacks = $pdo->query("SELECT * FROM feedbacks WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .container { max-width: 1100px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .container { background: #23272b; color: #eee; }
        h2 { margin-bottom: 20px; }
        .card { background: #f9f9f9; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 4px #0001; }
        body.dark-mode .card { background: #23272b; color: #eee; }
        .quick-links a { margin-right: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        body.dark-mode th { background: #23272b; color: #eee; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($_SESSION['theme_mode'] ?? getSetting('theme_mode', 'light')); ?>-mode">
    <div class="container">
        <h2>Welcome <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
        <div class="quick-links">
            <a href="../modules/appointments.php" class="btn btn-primary">Book Appointment</a>
            <a href="../modules/billing.php" class="btn btn-info">View Bills</a>
            <a href="../modules/profile.php" class="btn btn-secondary">My Profile</a>
        </div>
        <div class="card">
            <h3>Insurance Information</h3>
            <table>
                <tr><th>Provider</th><td><?php echo htmlspecialchars($insurance['provider']); ?></td></tr>
                <tr><th>Policy Number</th><td><?php echo htmlspecialchars($insurance['policy']); ?></td></tr>
                <tr><th>Coverage Amount</th><td><?php echo htmlspecialchars($insurance['coverage']); ?></td></tr>
                <tr><th>Status</th><td><?php echo htmlspecialchars($insurance['status']); ?></td></tr>
                <tr><th>Expiry Date</th><td><?php echo htmlspecialchars($insurance['expiry']); ?></td></tr>
                <tr><th>Claim Status</th><td><?php echo htmlspecialchars($insurance['claim']); ?></td></tr>
            </table>
        </div>
        <div class="card">
            <h3>Recent Bills</h3>
            <table>
                <tr><th>Bill #</th><th>Date</th><th>Amount</th><th>Status</th></tr>
                <?php foreach ($bills as $b): ?>
                <tr>
                    <td><?php echo $b['id']; ?></td>
                    <td><?php echo date('d M Y', strtotime($b['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($b['total_amount']); ?></td>
                    <td><?php echo htmlspecialchars($b['status']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($bills)): ?><tr><td colspan="4">No bills found.</td></tr><?php endif; ?>
            </table>
        </div>
        <div class="card">
            <h3>Recent Visits/Appointments</h3>
            <table>
                <tr><th>Date</th><th>Type</th><th>Status</th></tr>
                <?php foreach ($visits as $v): ?>
                <tr>
                    <td><?php echo date('d M Y H:i', strtotime($v['appointment_date'])); ?></td>
                    <td><?php echo htmlspecialchars($v['type']); ?></td>
                    <td><?php echo htmlspecialchars($v['status']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($visits)): ?><tr><td colspan="3">No visits found.</td></tr><?php endif; ?>
            </table>
        </div>
        <div class="card">
            <h3>Assigned Doctor/Nurse/Bed</h3>
            <table>
                <tr><th>Doctor</th><td><?php echo $doctor ? htmlspecialchars($doctor['name']) : '-'; ?></td></tr>
                <tr><th>Nurse</th><td><?php echo $nurse ? htmlspecialchars($nurse['name']) : '-'; ?></td></tr>
                <tr><th>Bed</th><td><?php echo $bed ? htmlspecialchars($bed['bed_number']) : '-'; ?></td></tr>
            </table>
        </div>
        <div class="card">
            <h3>Lab Reports</h3>
            <table>
                <tr><th>Test</th><th>Date</th><th>Result</th></tr>
                <?php foreach ($lab_reports as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['name']); ?></td>
                    <td><?php echo date('d M Y', strtotime($l['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($l['result'] ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($lab_reports)): ?><tr><td colspan="3">No lab reports found.</td></tr><?php endif; ?>
            </table>
        </div>
        <div class="card">
            <h3>Recent Feedback</h3>
            <ul>
                <?php foreach ($feedbacks as $f): ?>
                <li><?php echo htmlspecialchars($f['message']); ?> <small>(<?php echo date('d M Y', strtotime($f['created_at'])); ?>)</small></li>
                <?php endforeach; ?>
                <?php if (empty($feedbacks)): ?><li>No feedback yet.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>