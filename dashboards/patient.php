<?php
// Patient Dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_details = getUserDetails($user_id);

// Get patient record
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$user_id]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        // Create patient record if doesn't exist
        $stmt = $pdo->prepare("INSERT INTO patients (patient_id, name, email, phone, status) VALUES (?, ?, ?, ?, 'active')");
        $patient_id = 'PID' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
        $stmt->execute([$patient_id, $user_details['name'], $user_details['email'], $user_details['phone']]);
        $patient = [
            'patient_id' => $patient_id,
            'name' => $user_details['name'],
            'email' => $user_details['email'],
            'phone' => $user_details['phone']
        ];
    }
} catch (Exception $e) {
    $patient = ['name' => $user_details['name']];
}

// Get patient statistics
try {
    // Upcoming appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND appointment_date >= DATE('now')");
    $stmt->execute([$user_id]);
    $upcoming_appointments = $stmt->fetch()['count'];
    
    // Total visits
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $total_visits = $stmt->fetch()['count'];
    
    // Pending bills
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bills WHERE patient_id = ? AND payment_status != 'paid'");
    $stmt->execute([$user_id]);
    $pending_bills = $stmt->fetch()['count'];
    
    // Active prescriptions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE patient_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_prescriptions = $stmt->fetch()['count'] ?? 0;
    
} catch (Exception $e) {
    $upcoming_appointments = 0;
    $total_visits = 0;
    $pending_bills = 0;
    $active_prescriptions = 0;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>👤 Patient Dashboard</h1>
                <p class="text-muted">Welcome, <?php echo htmlspecialchars($patient['name']); ?>!</p>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-primary">📅</div>
                <div class="stat-number"><?php echo $upcoming_appointments; ?></div>
                <div class="stat-label">Upcoming Appointments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-success">🩺</div>
                <div class="stat-number"><?php echo $total_visits; ?></div>
                <div class="stat-label">Total Visits</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-warning">💰</div>
                <div class="stat-number"><?php echo $pending_bills; ?></div>
                <div class="stat-label">Pending Bills</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon text-info">💊</div>
                <div class="stat-number"><?php echo $active_prescriptions; ?></div>
                <div class="stat-label">Active Prescriptions</div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Upcoming Appointments -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>📅 Upcoming Appointments</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT a.*, u.name as doctor_name 
                            FROM appointments a 
                            LEFT JOIN users u ON a.doctor_id = u.id 
                            WHERE a.patient_id = ? AND a.appointment_date >= DATE('now')
                            ORDER BY a.appointment_date ASC, a.appointment_time ASC
                            LIMIT 5
                        ");
                        $stmt->execute([$user_id]);
                        $appointments = $stmt->fetchAll();
                        
                        if ($appointments): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Doctor</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
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
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center">
                                <a href="modules/appointments.php" class="btn btn-outline-primary">View All Appointments</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-calendar fa-3x mb-3"></i>
                                    <h5>No upcoming appointments</h5>
                                    <p>Schedule your next appointment with your doctor.</p>
                                    <a href="modules/appointments.php?action=book" class="btn btn-primary">Book Appointment</a>
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
        
        <!-- Quick Actions & Health Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>⚡ Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="modules/appointments.php?action=book" class="btn btn-outline-primary">
                            📅 Book Appointment
                        </a>
                        <a href="modules/medical-history.php" class="btn btn-outline-success">
                            📋 Medical History
                        </a>
                        <a href="modules/prescriptions.php" class="btn btn-outline-info">
                            💊 View Prescriptions
                        </a>
                        <a href="modules/bills.php" class="btn btn-outline-warning">
                            💰 View Bills
                        </a>
                        <a href="modules/profile.php" class="btn btn-outline-secondary">
                            ⚙️ Update Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Health Summary -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>🏥 Health Summary</h5>
                </div>
                <div class="card-body">
                    <div class="health-info">
                        <div class="health-item">
                            <strong>Blood Group:</strong>
                            <span class="text-muted"><?php echo $patient['blood_group'] ?? 'Not specified'; ?></span>
                        </div>
                        <div class="health-item">
                            <strong>Age:</strong>
                            <span class="text-muted">
                                <?php 
                                if (isset($patient['date_of_birth']) && $patient['date_of_birth']) {
                                    $age = date_diff(date_create($patient['date_of_birth']), date_create('now'))->y;
                                    echo $age . ' years';
                                } else {
                                    echo 'Not specified';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="health-item">
                            <strong>Gender:</strong>
                            <span class="text-muted"><?php echo ucfirst($patient['gender'] ?? 'Not specified'); ?></span>
                        </div>
                        <div class="health-item">
                            <strong>Emergency Contact:</strong>
                            <span class="text-muted"><?php echo $patient['emergency_contact_phone'] ?? 'Not specified'; ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="modules/profile.php" class="btn btn-sm btn-outline-primary">Update Health Info</a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Lab Results -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>🧪 Recent Lab Results</h5>
                </div>
                <div class="card-body">
                    <div class="text-center text-muted">
                        <i class="fa fa-flask fa-2x mb-2"></i>
                        <p>No recent lab results</p>
                        <small>Your latest test results will appear here</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>📋 Recent Medical Activity</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6>Appointment Completed</h6>
                                <p class="text-muted">General Consultation with Dr. Sharma</p>
                                <small class="text-muted">3 days ago</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6>Prescription Issued</h6>
                                <p class="text-muted">Medication for blood pressure</p>
                                <small class="text-muted">3 days ago</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6>Lab Test Ordered</h6>
                                <p class="text-muted">Complete Blood Count (CBC)</p>
                                <small class="text-muted">1 week ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.health-info {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.health-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f1f1;
}

.health-item:last-child {
    border-bottom: none;
}

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