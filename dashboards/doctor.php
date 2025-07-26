<?php
// Doctor Dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_details = getUserDetails($user_id);

// Get doctor-specific statistics
try {
    // Today's appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = DATE('now')");
    $stmt->execute([$user_id]);
    $today_appointments = $stmt->fetch()['count'];
    
    // Total patients assigned
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM patients WHERE assigned_doctor_id = ?");
    $stmt->execute([$user_id]);
    $total_patients = $stmt->fetch()['count'];
    
    // Pending consultations
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND status = 'scheduled'");
    $stmt->execute([$user_id]);
    $pending_consultations = $stmt->fetch()['count'];
    
    // This week's appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date BETWEEN DATE('now', 'weekday 0', '-6 days') AND DATE('now', 'weekday 0')");
    $stmt->execute([$user_id]);
    $week_appointments = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $today_appointments = 0;
    $total_patients = 0;
    $pending_consultations = 0;
    $week_appointments = 0;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>👨‍⚕️ Doctor Dashboard</h1>
                <p class="text-muted">Welcome back, Dr. <?php echo htmlspecialchars($user_details['name']); ?>!</p>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-primary">📅</div>
                <div class="stat-number"><?php echo $today_appointments; ?></div>
                <div class="stat-label">Today's Appointments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-success">👥</div>
                <div class="stat-number"><?php echo $total_patients; ?></div>
                <div class="stat-label">Assigned Patients</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-warning">⏰</div>
                <div class="stat-number"><?php echo $pending_consultations; ?></div>
                <div class="stat-label">Pending Consultations</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-info">📊</div>
                <div class="stat-number"><?php echo $week_appointments; ?></div>
                <div class="stat-label">This Week</div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Today's Schedule -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>📅 Today's Schedule</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT a.*, p.name as patient_name, p.phone as patient_phone 
                            FROM appointments a 
                            LEFT JOIN patients p ON a.patient_id = p.id 
                            WHERE a.doctor_id = ? AND a.appointment_date = DATE('now')
                            ORDER BY a.appointment_time ASC
                        ");
                        $stmt->execute([$user_id]);
                        $appointments = $stmt->fetchAll();
                        
                        if ($appointments): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Patient</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($appointment['patient_phone']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo ucfirst($appointment['appointment_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge status">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="modules/consultations.php?appointment_id=<?php echo $appointment['id']; ?>" 
                                                   class="btn btn-sm btn-primary">Start Consultation</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-calendar-check fa-3x mb-3"></i>
                                    <h5>No appointments scheduled for today</h5>
                                    <p>You have a free day! Enjoy your time.</p>
                                </div>
                            </div>
                        <?php endif;
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error loading appointments: ' . $e->getMessage() . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>⚡ Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="modules/patients.php" class="btn btn-outline-primary">
                            👥 View My Patients
                        </a>
                        <a href="modules/appointments.php?action=add" class="btn btn-outline-success">
                            📅 Schedule Appointment
                        </a>
                        <a href="modules/prescriptions.php" class="btn btn-outline-info">
                            💊 Write Prescription
                        </a>
                        <a href="modules/lab-reports.php" class="btn btn-outline-warning">
                            🧪 View Lab Reports
                        </a>
                        <a href="modules/profile.php" class="btn btn-outline-secondary">
                            ⚙️ Update Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>📋 Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6>Consultation Completed</h6>
                                <p class="text-muted">Patient: John Doe</p>
                                <small class="text-muted">2 hours ago</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6>Prescription Issued</h6>
                                <p class="text-muted">Patient: Jane Smith</p>
                                <small class="text-muted">4 hours ago</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6>Lab Report Reviewed</h6>
                                <p class="text-muted">Patient: Mike Johnson</p>
                                <small class="text-muted">Yesterday</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content h6 {
    margin-bottom: 5px;
    font-size: 14px;
}

.timeline-content p {
    margin-bottom: 5px;
    font-size: 13px;
}

.page-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}
</style>