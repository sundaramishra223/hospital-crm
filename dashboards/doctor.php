<?php
// Doctor Dashboard - Modern Cliniva-Inspired Design
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/functions.php';

// Simulated data for demonstration (replace with real queries)
$doctor_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'] ?? 'Dr. John Doe';
$today = date('Y-m-d');
$appointments = [
    ['time' => '09:00 AM', 'patient' => 'Amit Sharma', 'type' => 'Consultation', 'status' => 'upcoming'],
    ['time' => '10:30 AM', 'patient' => 'Priya Singh', 'type' => 'Follow-up', 'status' => 'upcoming'],
    ['time' => '12:00 PM', 'patient' => 'Rahul Verma', 'type' => 'Consultation', 'status' => 'completed'],
];
$patients_today = 8;
$patients_total = 320;
$pending_lab = 2;
$pending_prescriptions = 3;
$alerts = [
    ['type' => 'lab', 'message' => '2 lab results pending review'],
    ['type' => 'prescription', 'message' => '3 prescriptions need approval'],
];
$recent_activities = [
    ['time' => '08:45 AM', 'activity' => 'Reviewed lab report for Priya Singh'],
    ['time' => '08:30 AM', 'activity' => 'Consulted Amit Sharma'],
    ['time' => 'Yesterday', 'activity' => 'Updated prescription for Rahul Verma'],
];
$quick_actions = [
    ['icon' => 'fa-user-injured', 'label' => 'My Patients', 'link' => '../modules/patients.php'],
    ['icon' => 'fa-prescription', 'label' => 'Prescriptions', 'link' => '../modules/prescriptions.php'],
    ['icon' => 'fa-vials', 'label' => 'Lab Requests', 'link' => '../modules/lab.php'],
    ['icon' => 'fa-calendar-check', 'label' => 'Appointments', 'link' => '../modules/appointments.php'],
];
?>
<style>
.doctor-dashboard-bg {
    background: linear-gradient(135deg, rgba(34,193,195,0.2) 0%, rgba(253,187,45,0.2) 100%);
    min-height: 100vh;
    padding: 30px 0;
}
.glass-card {
    background: rgba(255,255,255,0.25);
    box-shadow: 0 8px 32px 0 rgba(31,38,135,0.18);
    backdrop-filter: blur(8px);
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,0.18);
    transition: box-shadow 0.3s;
}
.glass-card:hover {
    box-shadow: 0 12px 40px 0 rgba(31,38,135,0.22);
}
.stats-card {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 18px 24px;
    margin-bottom: 18px;
    color: #222;
}
.stats-icon {
    font-size: 2.2rem;
    color: #22b8cf;
    background: rgba(34,184,207,0.12);
    border-radius: 50%;
    padding: 12px;
}
.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 18px 0;
    color: #222;
    text-decoration: none;
    border-radius: 12px;
    transition: background 0.2s, box-shadow 0.2s;
}
.quick-action:hover {
    background: rgba(34,184,207,0.08);
    box-shadow: 0 4px 16px rgba(34,184,207,0.12);
    color: #0b7285;
}
.alert-glass {
    background: rgba(255, 243, 205, 0.7);
    border-left: 5px solid #ffc107;
    padding: 12px 18px;
    border-radius: 10px;
    margin-bottom: 12px;
    color: #856404;
    font-weight: 500;
}
@media (max-width: 991px) {
    .stats-card { flex-direction: column; align-items: flex-start; }
}
</style>
<div class="container doctor-dashboard-bg">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($doctor_name); ?> 👨‍⚕️</h2>
            <p class="text-muted">Here's your overview for <?php echo date('l, F j, Y'); ?>.</p>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="glass-card stats-card">
                <span class="stats-icon"><i class="fa fa-users"></i></span>
                <div>
                    <div class="fw-bold fs-4" data-bs-toggle="tooltip" title="Patients seen today"><?php echo $patients_today; ?></div>
                    <div class="text-muted">Today's Patients</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card stats-card">
                <span class="stats-icon"><i class="fa fa-user-md"></i></span>
                <div>
                    <div class="fw-bold fs-4" data-bs-toggle="tooltip" title="Total patients managed"><?php echo $patients_total; ?></div>
                    <div class="text-muted">Total Patients</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card stats-card">
                <span class="stats-icon"><i class="fa fa-vials"></i></span>
                <div>
                    <div class="fw-bold fs-4"><?php echo $pending_lab; ?></div>
                    <div class="text-muted">Lab Results Pending</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card stats-card">
                <span class="stats-icon"><i class="fa fa-prescription"></i></span>
                <div>
                    <div class="fw-bold fs-4"><?php echo $pending_prescriptions; ?></div>
                    <div class="text-muted">Prescriptions Pending</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fa fa-calendar-check text-primary me-2"></i>Today's Appointments</h5>
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td><?php echo $appt['time']; ?></td>
                                <td><?php echo htmlspecialchars($appt['patient']); ?></td>
                                <td><?php echo $appt['type']; ?></td>
                                <td>
                                    <?php if ($appt['status'] == 'upcoming'): ?>
                                        <span class="badge bg-info">Upcoming</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3"><i class="fa fa-history text-primary me-2"></i>Recent Activities</h5>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($recent_activities as $activity): ?>
                        <li class="mb-2"><span class="text-muted small"><?php echo $activity['time']; ?>:</span> <?php echo htmlspecialchars($activity['activity']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fa fa-bolt text-warning me-2"></i>Alerts & Notifications</h5>
                <?php foreach ($alerts as $alert): ?>
                    <div class="alert-glass mb-2">
                        <i class="fa fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($alert['message']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3"><i class="fa fa-bolt text-primary me-2"></i>Quick Actions</h5>
                <div class="row g-2">
                    <?php foreach ($quick_actions as $action): ?>
                        <div class="col-6">
                            <a href="<?php echo $action['link']; ?>" class="quick-action">
                                <i class="fa <?php echo $action['icon']; ?> fa-2x mb-2"></i>
                                <div><?php echo $action['label']; ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>