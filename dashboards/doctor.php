<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$doctor = getUserDetails($user_id);
// Assigned patients
$patients = $pdo->query("SELECT p.*, a.appointment_date FROM patients p LEFT JOIN appointments a ON a.patient_id = p.id AND a.doctor_id = $user_id WHERE p.status = 'active' AND a.doctor_id = $user_id GROUP BY p.id ORDER BY a.appointment_date DESC LIMIT 10")->fetchAll();
// Today's appointments
$todays_appointments = $pdo->query("SELECT a.*, p.first_name, p.last_name FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = $user_id AND DATE(a.appointment_date) = CURDATE() ORDER BY a.appointment_date")->fetchAll();
// Recent vitals
$vitals = $pdo->query("SELECT v.*, p.first_name, p.last_name FROM patient_vitals v JOIN patients p ON v.patient_id = p.id WHERE v.recorded_by_user_id = $user_id ORDER BY v.recorded_at DESC LIMIT 5")->fetchAll();
// Feedback
$feedbacks = $pdo->query("SELECT * FROM feedbacks WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
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
        <h2>Welcome Dr. <?php echo htmlspecialchars($doctor['name']); ?></h2>
        <div class="quick-links">
            <a href="../modules/appointments.php" class="btn btn-primary">View Appointments</a>
            <a href="../modules/patients.php" class="btn btn-info">View Patients</a>
            <a href="../modules/profile.php" class="btn btn-secondary">My Profile</a>
        </div>
        <div class="card">
            <h3>Today's Appointments</h3>
            <table>
                <tr><th>Time</th><th>Patient</th><th>Type</th><th>Status</th></tr>
                <?php foreach ($todays_appointments as $a): ?>
                <tr>
                    <td><?php echo date('H:i', strtotime($a['appointment_date'])); ?></td>
                    <td><?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($a['type']); ?></td>
                    <td><?php echo htmlspecialchars($a['status']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($todays_appointments)): ?><tr><td colspan="4">No appointments today.</td></tr><?php endif; ?>
            </table>
        </div>
        <div class="card">
            <h3>Assigned Patients</h3>
            <table>
                <tr><th>Name</th><th>Contact</th><th>Last Appointment</th></tr>
                <?php foreach ($patients as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['contact']); ?></td>
                    <td><?php echo $p['appointment_date'] ? date('d M Y', strtotime($p['appointment_date'])) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($patients)): ?><tr><td colspan="3">No assigned patients.</td></tr><?php endif; ?>
            </table>
        </div>
        <div class="card">
            <h3>Recent Patient Vitals</h3>
            <table>
                <tr><th>Patient</th><th>BP</th><th>HR</th><th>Temp</th><th>Date</th></tr>
                <?php foreach ($vitals as $v): ?>
                <tr>
                    <td><?php echo htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($v['blood_pressure']); ?></td>
                    <td><?php echo htmlspecialchars($v['heart_rate']); ?></td>
                    <td><?php echo htmlspecialchars($v['temperature']); ?></td>
                    <td><?php echo date('d M Y H:i', strtotime($v['recorded_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($vitals)): ?><tr><td colspan="5">No recent vitals.</td></tr><?php endif; ?>
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