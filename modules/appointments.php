<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'doctor', 'receptionist', 'patient'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];
$action = $_GET['action'] ?? '';
$appointment_id = $_GET['id'] ?? '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'book_appointment') {
        $patient_id = intval($_POST['patient_id']);
        $doctor_id = intval($_POST['doctor_id']);
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $appointment_datetime = $appointment_date . ' ' . $appointment_time;
        $duration = intval($_POST['duration']);
        $type = sanitize($_POST['type']);
        $notes = sanitize($_POST['notes']);
        
        // Check for conflicts
        $conflict_check = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date BETWEEN ? AND DATE_ADD(?, INTERVAL ? MINUTE) AND status IN ('scheduled', 'confirmed')");
        $end_time = date('Y-m-d H:i:s', strtotime($appointment_datetime . ' +' . $duration . ' minutes'));
        $conflict_check->execute([$doctor_id, $appointment_datetime, $appointment_datetime, $duration]);
        $conflicts = $conflict_check->fetch()['count'];
        
        if ($conflicts > 0) {
            $error_message = "Time slot conflict! Doctor is not available at this time.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, duration, type, status, notes, created_by) VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?)");
                $stmt->execute([$patient_id, $doctor_id, $appointment_datetime, $duration, $type, $notes, $user_id]);
                
                logActivity($user_id, 'create', "Booked appointment for patient ID: $patient_id");
                $success_message = "Appointment booked successfully!";
            } catch (Exception $e) {
                $error_message = "Error booking appointment: " . $e->getMessage();
            }
        }
    }
    
    if ($action == 'update_status') {
        $id = intval($_POST['id']);
        $status = sanitize($_POST['status']);
        $notes = sanitize($_POST['notes']);
        
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $id]);
            
            logActivity($user_id, 'update', "Updated appointment status ID: $id to $status");
            $success_message = "Appointment status updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating appointment: " . $e->getMessage();
        }
    }
    
    if ($action == 'reschedule') {
        $id = intval($_POST['id']);
        $new_date = $_POST['appointment_date'];
        $new_time = $_POST['appointment_time'];
        $new_datetime = $new_date . ' ' . $new_time;
        
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, status = 'scheduled' WHERE id = ?");
            $stmt->execute([$new_datetime, $id]);
            
            logActivity($user_id, 'update', "Rescheduled appointment ID: $id");
            $success_message = "Appointment rescheduled successfully!";
        } catch (Exception $e) {
            $error_message = "Error rescheduling appointment: " . $e->getMessage();
        }
    }
}

// Get filters
$filter = $_GET['filter'] ?? '';
$date_filter = $_GET['date'] ?? '';
$doctor_filter = $_GET['doctor'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query based on role and filters
$where_conditions = ['1=1'];
$params = [];

// Role-based access control
if ($user_role == 'doctor') {
    $doctor_user = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $doctor_user->execute([$user_id]);
    $doctor_data = $doctor_user->fetch();
    if ($doctor_data) {
        $where_conditions[] = 'doctor_id = ?';
        $params[] = $doctor_data['id'];
    }
} elseif ($user_role == 'patient') {
    $where_conditions[] = 'patient_id = ?';
    $params[] = $user_id; // Assuming patient_id is same as user_id
}

// Apply filters
if ($date_filter == 'today') {
    $where_conditions[] = 'DATE(appointment_date) = CURDATE()';
} elseif ($date_filter == 'tomorrow') {
    $where_conditions[] = 'DATE(appointment_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
} elseif ($date_filter == 'week') {
    $where_conditions[] = 'appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
} elseif (!empty($date_filter)) {
    $where_conditions[] = 'DATE(appointment_date) = ?';
    $params[] = $date_filter;
}

if (!empty($doctor_filter)) {
    $where_conditions[] = 'doctor_id = ?';
    $params[] = $doctor_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = 'status = ?';
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get appointments with patient and doctor details
$stmt = $pdo->prepare("
    SELECT a.*, 
           CONCAT(p.first_name, ' ', p.last_name) as patient_name,
           p.phone as patient_phone,
           CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
           dept.name as department_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN departments dept ON d.department_id = dept.id
    WHERE $where_clause
    ORDER BY a.appointment_date ASC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get patients and doctors for dropdowns
$patients = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, phone FROM patients WHERE status = 'active' ORDER BY first_name")->fetchAll();
$doctors = $pdo->query("SELECT d.id, CONCAT(d.first_name, ' ', d.last_name) as name, dept.name as department FROM doctors d LEFT JOIN departments dept ON d.department_id = dept.id WHERE d.status = 'active' ORDER BY d.first_name")->fetchAll();

// Get statistics
$total_appointments = count($appointments);
$today_appointments = count(array_filter($appointments, function($apt) { return date('Y-m-d', strtotime($apt['appointment_date'])) == date('Y-m-d'); }));
$scheduled_count = count(array_filter($appointments, function($apt) { return $apt['status'] == 'scheduled'; }));
$completed_count = count(array_filter($appointments, function($apt) { return $apt['status'] == 'completed'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-calendar-alt me-2"></i>Appointment Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <?php if (in_array($user_role, ['admin', 'receptionist'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal">
                                    <i class="fas fa-plus"></i> Book Appointment
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportSchedule()">
                                <i class="fas fa-download"></i> Export Schedule
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Appointments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_appointments; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Today</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_appointments; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Scheduled</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $scheduled_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completed</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filters & Quick Views</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="date" class="form-label">Date Filter</label>
                                <select class="form-control" id="date" name="date">
                                    <option value="">All Dates</option>
                                    <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="tomorrow" <?php echo $date_filter == 'tomorrow' ? 'selected' : ''; ?>>Tomorrow</option>
                                    <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>This Week</option>
                                </select>
                            </div>
                            <?php if ($user_role != 'doctor'): ?>
                                <div class="col-md-3">
                                    <label for="doctor" class="form-label">Doctor</label>
                                    <select class="form-control" id="doctor" name="doctor">
                                        <option value="">All Doctors</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['id']; ?>" <?php echo $doctor_filter == $doctor['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($doctor['name']); ?> (<?php echo htmlspecialchars($doctor['department']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="scheduled" <?php echo $status_filter == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="no_show" <?php echo $status_filter == 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="appointments.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Appointments List -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Appointments Schedule</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <?php if ($user_role != 'doctor'): ?>
                                            <th>Doctor</th>
                                        <?php endif; ?>
                                        <th>Type</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($appointments)): ?>
                                        <tr>
                                            <td colspan="<?php echo $user_role != 'doctor' ? '7' : '6'; ?>" class="text-center text-muted py-4">
                                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                                <p>No appointments found</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($appointments as $appointment): ?>
                                            <tr class="<?php echo getAppointmentRowClass($appointment); ?>">
                                                <td>
                                                    <strong><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></strong><br>
                                                    <small class="text-muted"><?php echo date('g:i A', strtotime($appointment['appointment_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['patient_phone']); ?>
                                                    </small>
                                                </td>
                                                <?php if ($user_role != 'doctor'): ?>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($appointment['doctor_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($appointment['department_name']); ?></small>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <span class="badge bg-<?php echo getAppointmentTypeColor($appointment['type']); ?>">
                                                        <?php echo ucfirst($appointment['type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $appointment['duration']; ?> mins</td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusColor($appointment['status']); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $appointment['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if (in_array($user_role, ['admin', 'receptionist', 'doctor'])): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateStatus(<?php echo $appointment['id']; ?>, '<?php echo $appointment['status']; ?>')">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="reschedule(<?php echo $appointment['id']; ?>)">
                                                                <i class="fas fa-clock"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewDetails(<?php echo $appointment['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Book Appointment Modal -->
    <?php if (in_array($user_role, ['admin', 'receptionist'])): ?>
    <div class="modal fade" id="bookAppointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="book_appointment">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="patient_id" class="form-label">Patient *</label>
                                    <select class="form-control" id="patient_id" name="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['id']; ?>">
                                                <?php echo htmlspecialchars($patient['name']); ?> - <?php echo htmlspecialchars($patient['phone']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="doctor_id" class="form-label">Doctor *</label>
                                    <select class="form-control" id="doctor_id" name="doctor_id" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['id']; ?>">
                                                <?php echo htmlspecialchars($doctor['name']); ?> (<?php echo htmlspecialchars($doctor['department']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="appointment_date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="appointment_time" class="form-label">Time *</label>
                                    <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (minutes) *</label>
                                    <select class="form-control" id="duration" name="duration" required>
                                        <option value="15">15 minutes</option>
                                        <option value="30" selected>30 minutes</option>
                                        <option value="45">45 minutes</option>
                                        <option value="60">1 hour</option>
                                        <option value="90">1.5 hours</option>
                                        <option value="120">2 hours</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type *</label>
                                    <select class="form-control" id="type" name="type" required>
                                        <option value="consultation">Consultation</option>
                                        <option value="followup">Follow-up</option>
                                        <option value="emergency">Emergency</option>
                                        <option value="surgery">Surgery</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes or special instructions"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Book Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Appointment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" id="status_appointment_id">
                        
                        <div class="mb-3">
                            <label for="status_select" class="form-label">Status *</label>
                            <select class="form-control" id="status_select" name="status" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="no_show">No Show</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="status_notes" name="notes" rows="3" placeholder="Update notes or reason for status change"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div class="modal fade" id="rescheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reschedule Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="rescheduleForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reschedule">
                        <input type="hidden" name="id" id="reschedule_appointment_id">
                        
                        <div class="mb-3">
                            <label for="reschedule_date" class="form-label">New Date *</label>
                            <input type="date" class="form-control" id="reschedule_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reschedule_time" class="form-label">New Time *</label>
                            <input type="time" class="form-control" id="reschedule_time" name="appointment_time" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reschedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(id, currentStatus) {
            document.getElementById('status_appointment_id').value = id;
            document.getElementById('status_select').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
        
        function reschedule(id) {
            document.getElementById('reschedule_appointment_id').value = id;
            new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
        }
        
        function viewDetails(id) {
            // Would redirect to appointment details page
            window.location.href = 'appointment_details.php?id=' + id;
        }
        
        function exportSchedule() {
            // Implement export functionality
            alert('Export functionality will be implemented');
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>

<?php
// Helper functions
function getAppointmentRowClass($appointment) {
    $now = time();
    $apt_time = strtotime($appointment['appointment_date']);
    
    if ($appointment['status'] == 'cancelled') return 'table-danger';
    if ($appointment['status'] == 'completed') return 'table-success';
    if ($apt_time < $now && $appointment['status'] == 'scheduled') return 'table-warning';
    if (date('Y-m-d', $apt_time) == date('Y-m-d')) return 'table-info';
    
    return '';
}

function getAppointmentTypeColor($type) {
    switch ($type) {
        case 'emergency': return 'danger';
        case 'surgery': return 'warning';
        case 'followup': return 'info';
        default: return 'primary';
    }
}

function getStatusColor($status) {
    switch ($status) {
        case 'completed': return 'success';
        case 'confirmed': return 'info';
        case 'cancelled': return 'danger';
        case 'no_show': return 'secondary';
        default: return 'warning';
    }
}
?>
