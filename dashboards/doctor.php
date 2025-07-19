<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Doctor') {
    header('Location: ../login.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$doctor_id = $_SESSION['user_id'];

// Get doctor's statistics
$sql = "SELECT COUNT(*) as total_patients FROM patients WHERE assigned_doctor_id = ? AND hospital_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $doctor_id, $hospital_id);
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total_patients'];

$sql = "SELECT COUNT(*) as today_appointments FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE() AND status != 'cancelled'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_assoc()['today_appointments'];

$sql = "SELECT COUNT(*) as pending_appointments FROM appointments WHERE doctor_id = ? AND status = 'scheduled' AND appointment_date >= CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$pending_appointments = $stmt->get_result()->fetch_assoc()['pending_appointments'];

$sql = "SELECT COUNT(*) as completed_appointments FROM appointments WHERE doctor_id = ? AND status = 'completed' AND DATE(appointment_date) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$completed_appointments = $stmt->get_result()->fetch_assoc()['completed_appointments'];

// Get today's appointments
$sql = "SELECT a.*, p.first_name, p.last_name, p.phone, d.name as department_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        LEFT JOIN departments d ON a.department_id = d.id 
        WHERE a.doctor_id = ? AND DATE(a.appointment_date) = CURDATE() 
        ORDER BY a.appointment_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$today_appointments_list = $stmt->get_result();

// Get recent patients
$sql = "SELECT p.*, a.appointment_date 
        FROM patients p 
        LEFT JOIN appointments a ON p.id = a.patient_id 
        WHERE p.assigned_doctor_id = ? AND p.hospital_id = ? 
        ORDER BY a.appointment_date DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $doctor_id, $hospital_id);
$stmt->execute();
$recent_patients = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fa fa-user-md me-2"></i>Welcome, Dr. <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                            </h2>
                            <p class="mb-0">Manage your patients, appointments, and medical records efficiently.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="display-4">
                                <i class="fa fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Patients
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_patients; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today's Appointments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_appointments; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Appointments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_appointments; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Completed Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_appointments; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Today's Appointments -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-calendar-day me-2"></i>Today's Appointments
                    </h6>
                    <a href="modules/appointments.php" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus me-1"></i>New Appointment
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($today_appointments_list->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient</th>
                                        <th>Department</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $today_appointments_list->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('H:i', strtotime($appointment['appointment_date'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($appointment['phone']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($appointment['department_name']): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($appointment['department_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo ucfirst($appointment['appointment_type']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($appointment['status'] == 'scheduled'): ?>
                                                <span class="badge bg-warning">Scheduled</span>
                                            <?php elseif ($appointment['status'] == 'confirmed'): ?>
                                                <span class="badge bg-info">Confirmed</span>
                                            <?php elseif ($appointment['status'] == 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($appointment['status'] == 'cancelled'): ?>
                                                <span class="badge bg-danger">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo ucfirst($appointment['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="modules/patients.php?view=<?php echo $appointment['patient_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="modules/appointments.php?edit=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No appointments scheduled for today</h5>
                            <p class="text-muted">You can add new appointments or check upcoming schedules.</p>
                            <a href="modules/appointments.php" class="btn btn-primary">
                                <i class="fa fa-plus me-1"></i>Schedule Appointment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Patients -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <a href="modules/patients.php" class="btn btn-outline-primary w-100">
                                <i class="fa fa-user-plus mb-2"></i><br>Add Patient
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="modules/appointments.php" class="btn btn-outline-success w-100">
                                <i class="fa fa-calendar-plus mb-2"></i><br>New Appointment
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="modules/billing.php" class="btn btn-outline-info w-100">
                                <i class="fa fa-file-invoice mb-2"></i><br>Create Bill
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="modules/pharmacy.php" class="btn btn-outline-warning w-100">
                                <i class="fa fa-pills mb-2"></i><br>Prescriptions
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Patients -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-users me-2"></i>Recent Patients
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($recent_patients->num_rows > 0): ?>
                        <?php while ($patient = $recent_patients->fetch_assoc()): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fa fa-user"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h6>
                                <small class="text-muted">
                                    <?php if ($patient['appointment_date']): ?>
                                        Last visit: <?php echo date('M d, Y', strtotime($patient['appointment_date'])); ?>
                                    <?php else: ?>
                                        No recent visits
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="modules/patients.php?view=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fa fa-users fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No patients assigned yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Status -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-chart-line me-2"></i>Today's Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-success"><?php echo $completed_appointments; ?></h4>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning"><?php echo $today_appointments - $completed_appointments; ?></h4>
                            <small class="text-muted">Remaining</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh appointments every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);

// Add some interactive features
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to appointment rows
    const appointmentRows = document.querySelectorAll('tbody tr');
    appointmentRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>