<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Patient') {
    header('Location: ../login.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$patient_id = $_SESSION['user_id'];

// Get patient's statistics
$sql = "SELECT COUNT(*) as total_appointments FROM appointments WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total_appointments'];

$sql = "SELECT COUNT(*) as upcoming_appointments FROM appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status IN ('scheduled', 'confirmed')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result()->fetch_assoc()['upcoming_appointments'];

$sql = "SELECT COUNT(*) as total_bills FROM billing WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$total_bills = $stmt->get_result()->fetch_assoc()['total_bills'];

$sql = "SELECT SUM(total_amount) as total_paid FROM billing WHERE patient_id = ? AND status = 'paid'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$total_paid = $stmt->get_result()->fetch_assoc()['total_paid'] ?? 0;

// Get patient details
$sql = "SELECT p.*, CONCAT(d.first_name, ' ', d.last_name) as doctor_name, i.provider_name, i.policy_number, i.coverage_amount, i.expiry_date 
        FROM patients p 
        LEFT JOIN doctors d ON p.assigned_doctor_id = d.id 
        LEFT JOIN insurance i ON p.id = i.patient_id 
        WHERE p.id = ? AND p.hospital_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $patient_id, $hospital_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

// Get upcoming appointments
$sql = "SELECT a.*, CONCAT(d.first_name, ' ', d.last_name) as doctor_name, dept.name as department_name 
        FROM appointments a 
        LEFT JOIN doctors d ON a.doctor_id = d.id 
        LEFT JOIN departments dept ON a.department_id = dept.id 
        WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('scheduled', 'confirmed') 
        ORDER BY a.appointment_date ASC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$upcoming_appointments_list = $stmt->get_result();

// Get recent bills
$sql = "SELECT * FROM billing WHERE patient_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$recent_bills = $stmt->get_result();

// Get medical history (recent appointments)
$sql = "SELECT a.*, CONCAT(d.first_name, ' ', d.last_name) as doctor_name, dept.name as department_name 
        FROM appointments a 
        LEFT JOIN doctors d ON a.doctor_id = d.id 
        LEFT JOIN departments dept ON a.department_id = dept.id 
        WHERE a.patient_id = ? AND a.status = 'completed' 
        ORDER BY a.appointment_date DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$medical_history = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-info text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">
                                <i class="fa fa-user me-2"></i>Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                            </h2>
                            <p class="mb-0">Access your medical records, appointments, and billing information.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="display-4">
                                <i class="fa fa-heartbeat"></i>
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
                                Total Appointments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_appointments; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-calendar fa-2x text-gray-300"></i>
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
                                Upcoming Appointments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $upcoming_appointments; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-calendar-check fa-2x text-gray-300"></i>
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
                                Total Bills
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_bills; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-file-invoice fa-2x text-gray-300"></i>
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
                                Total Paid
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_paid, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Patient Information & Insurance -->
        <div class="col-lg-4">
            <!-- Patient Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-user-circle me-2"></i>Personal Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-4"><strong>Name:</strong></div>
                        <div class="col-8"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Age:</strong></div>
                        <div class="col-8"><?php echo $patient['age']; ?> years</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Gender:</strong></div>
                        <div class="col-8"><?php echo ucfirst($patient['gender']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Phone:</strong></div>
                        <div class="col-8"><?php echo htmlspecialchars($patient['phone']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Email:</strong></div>
                        <div class="col-8"><?php echo htmlspecialchars($patient['email']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Blood Group:</strong></div>
                        <div class="col-8">
                            <span class="badge bg-danger"><?php echo $patient['blood_group']; ?></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>Assigned Doctor:</strong></div>
                        <div class="col-8">
                            <?php if ($patient['doctor_name']): ?>
                                <span class="badge bg-info">Dr. <?php echo htmlspecialchars($patient['doctor_name']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Not assigned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insurance Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-shield-alt me-2"></i>Insurance Information
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($patient['provider_name']): ?>
                        <div class="row mb-3">
                            <div class="col-4"><strong>Provider:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($patient['provider_name']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4"><strong>Policy No:</strong></div>
                            <div class="col-8"><?php echo htmlspecialchars($patient['policy_number']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4"><strong>Coverage:</strong></div>
                            <div class="col-8">$<?php echo number_format($patient['coverage_amount'], 2); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4"><strong>Expiry:</strong></div>
                            <div class="col-8">
                                <?php 
                                $expiry_date = new DateTime($patient['expiry_date']);
                                $today = new DateTime();
                                $status_class = $expiry_date < $today ? 'danger' : ($expiry_date->diff($today)->days < 30 ? 'warning' : 'success');
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo date('M d, Y', strtotime($patient['expiry_date'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fa fa-shield-alt fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No insurance information available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="modules/appointments.php" class="btn btn-outline-primary">
                            <i class="fa fa-calendar-plus me-2"></i>Book Appointment
                        </a>
                        <a href="modules/billing.php" class="btn btn-outline-info">
                            <i class="fa fa-file-invoice me-2"></i>View Bills
                        </a>
                        <a href="modules/profile.php" class="btn btn-outline-warning">
                            <i class="fa fa-user-edit me-2"></i>Update Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments & Medical History -->
        <div class="col-lg-8">
            <!-- Upcoming Appointments -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-calendar-check me-2"></i>Upcoming Appointments
                    </h6>
                    <a href="modules/appointments.php" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus me-1"></i>Book Appointment
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($upcoming_appointments_list->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Doctor</th>
                                        <th>Department</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $upcoming_appointments_list->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></strong>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($appointment['appointment_date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($appointment['doctor_name']): ?>
                                                <span class="badge bg-info">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($appointment['department_name']): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($appointment['department_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo ucfirst($appointment['appointment_type']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($appointment['status'] == 'scheduled'): ?>
                                                <span class="badge bg-warning">Scheduled</span>
                                            <?php elseif ($appointment['status'] == 'confirmed'): ?>
                                                <span class="badge bg-success">Confirmed</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo ucfirst($appointment['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No upcoming appointments</h5>
                            <p class="text-muted">You can book a new appointment with your doctor.</p>
                            <a href="modules/appointments.php" class="btn btn-primary">
                                <i class="fa fa-plus me-1"></i>Book Appointment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Bills -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-file-invoice me-2"></i>Recent Bills
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($recent_bills->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Bill No</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($bill = $recent_bills->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $bill['bill_number']; ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($bill['created_at'])); ?></td>
                                        <td><strong>$<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <?php if ($bill['status'] == 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif ($bill['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo ucfirst($bill['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="modules/billing.php?view=<?php echo $bill['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fa fa-file-invoice fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No bills found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Medical History -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa fa-history me-2"></i>Medical History
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($medical_history->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Doctor</th>
                                        <th>Department</th>
                                        <th>Type</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($visit = $medical_history->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($visit['appointment_date'])); ?></td>
                                        <td>
                                            <?php if ($visit['doctor_name']): ?>
                                                <span class="badge bg-info">Dr. <?php echo htmlspecialchars($visit['doctor_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($visit['department_name']): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($visit['department_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo ucfirst($visit['appointment_type']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($visit['notes']): ?>
                                                <small><?php echo htmlspecialchars(substr($visit['notes'], 0, 50)) . (strlen($visit['notes']) > 50 ? '...' : ''); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">No notes</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fa fa-history fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No medical history available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add some interactive features
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
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