<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];
$action = $_GET['action'] ?? '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'mark_attendance') {
        $employee_id = intval($_POST['user_id']);
        $attendance_date = $_POST['attendance_date'];
        $check_in_time = $_POST['check_in_time'];
        $check_out_time = $_POST['check_out_time'] ?? null;
        $status = sanitize($_POST['status']);
        $notes = sanitize($_POST['notes']);
        
        // Check if attendance already exists for this date
        $check_existing = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE user_id = ? AND DATE(attendance_date) = ?");
        $check_existing->execute([$employee_id, $attendance_date]);
        $exists = $check_existing->fetch()['count'];
        
        if ($exists > 0) {
            $error_message = "Attendance for this date already exists!";
        } else {
            try {
                $check_in_datetime = $attendance_date . ' ' . $check_in_time;
                $check_out_datetime = $check_out_time ? $attendance_date . ' ' . $check_out_time : null;
                
                $stmt = $pdo->prepare("INSERT INTO attendance (user_id, attendance_date, check_in_time, check_out_time, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$employee_id, $check_in_datetime, $check_in_datetime, $check_out_datetime, $status, $notes]);
                
                logActivity($user_id, 'create', "Marked attendance for user ID: $employee_id for $attendance_date");
                $success_message = "Attendance marked successfully!";
            } catch (Exception $e) {
                $error_message = "Error marking attendance: " . $e->getMessage();
            }
        }
    }
    
    if ($action == 'check_out') {
        $attendance_id = intval($_POST['attendance_id']);
        $check_out_time = date('Y-m-d H:i:s');
        
        try {
            $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = ? WHERE id = ?");
            $stmt->execute([$check_out_time, $attendance_id]);
            
            logActivity($user_id, 'update', "Checked out for attendance ID: $attendance_id");
            $success_message = "Checked out successfully!";
        } catch (Exception $e) {
            $error_message = "Error checking out: " . $e->getMessage();
        }
    }
    
    if ($action == 'update_attendance') {
        $id = intval($_POST['id']);
        $status = sanitize($_POST['status']);
        $notes = sanitize($_POST['notes']);
        
        try {
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $id]);
            
            logActivity($user_id, 'update', "Updated attendance ID: $id");
            $success_message = "Attendance updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating attendance: " . $e->getMessage();
        }
    }
}

// Get filters
$date_filter = $_GET['date'] ?? date('Y-m-d');
$employee_filter = $_GET['employee'] ?? '';
$status_filter = $_GET['status'] ?? '';
$month_filter = $_GET['month'] ?? date('Y-m');

// Build query based on filters
$where_conditions = ['1=1'];
$params = [];

if (!empty($date_filter)) {
    $where_conditions[] = 'DATE(a.attendance_date) = ?';
    $params[] = $date_filter;
}

if (!empty($employee_filter)) {
    $where_conditions[] = 'a.user_id = ?';
    $params[] = $employee_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = 'a.status = ?';
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get attendance records with employee details
$stmt = $pdo->prepare("
    SELECT a.*, u.name as employee_name, u.role as employee_role, u.email as employee_email
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE $where_clause
    ORDER BY a.attendance_date DESC, u.name ASC
");
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Get employees for dropdown
$employees = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('doctor', 'nurse', 'staff', 'pharmacy', 'lab_tech', 'receptionist') AND status = 'active' ORDER BY name")->fetchAll();

// Get statistics
$total_records = count($attendance_records);
$present_today = count(array_filter($attendance_records, function($record) { 
    return date('Y-m-d', strtotime($record['attendance_date'])) == date('Y-m-d') && $record['status'] == 'present'; 
}));
$absent_today = count(array_filter($attendance_records, function($record) { 
    return date('Y-m-d', strtotime($record['attendance_date'])) == date('Y-m-d') && $record['status'] == 'absent'; 
}));
$late_today = count(array_filter($attendance_records, function($record) { 
    return date('Y-m-d', strtotime($record['attendance_date'])) == date('Y-m-d') && $record['status'] == 'late'; 
}));

// Get monthly attendance summary
$monthly_stats = getMonthlyAttendanceStats($month_filter);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
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
                    <h1 class="h2"><i class="fas fa-clock me-2"></i>Attendance Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <?php if ($user_role == 'admin'): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
                                    <i class="fas fa-plus"></i> Mark Attendance
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportAttendance()">
                                <i class="fas fa-download"></i> Export Report
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

                <!-- Today's Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present Today</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $present_today; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent Today</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $absent_today; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-times fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late Today</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $late_today; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Records</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_records; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Check-in/Check-out -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Quick Check-in/Check-out</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success btn-lg w-100 mb-3" onclick="quickCheckIn()">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Check In Now
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-warning btn-lg w-100 mb-3" onclick="quickCheckOut()">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Check Out Now
                                </button>
                            </div>
                        </div>
                        <div class="text-center">
                            <small class="text-muted">Current time: <span id="current-time"><?php echo date('Y-m-d H:i:s'); ?></span></small>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="employee" class="form-label">Employee</label>
                                <select class="form-control" id="employee" name="employee">
                                    <option value="">All Employees</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['id']; ?>" <?php echo $employee_filter == $employee['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employee['name']); ?> (<?php echo ucfirst($employee['role']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="present" <?php echo $status_filter == 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo $status_filter == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo $status_filter == 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="half_day" <?php echo $status_filter == 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="attendance.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Attendance Records -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($attendance_records)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fas fa-clock fa-3x mb-3"></i>
                                                <p>No attendance records found</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr class="<?php echo getAttendanceRowClass($record); ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo ucfirst($record['employee_role']); ?></small>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($record['attendance_date'])); ?></td>
                                                <td>
                                                    <?php if ($record['check_in_time']): ?>
                                                        <?php echo date('g:i A', strtotime($record['check_in_time'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not checked in</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($record['check_out_time']): ?>
                                                        <?php echo date('g:i A', strtotime($record['check_out_time'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-warning">Not checked out</span>
                                                        <?php if (date('Y-m-d', strtotime($record['attendance_date'])) == date('Y-m-d')): ?>
                                                            <br><button class="btn btn-sm btn-warning mt-1" onclick="checkOut(<?php echo $record['id']; ?>)">Check Out</button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($record['check_in_time'] && $record['check_out_time']): ?>
                                                        <?php 
                                                        $duration = strtotime($record['check_out_time']) - strtotime($record['check_in_time']);
                                                        echo gmdate('H:i', $duration);
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getAttendanceStatusColor($record['status']); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($record['notes'] ?? '-'); ?>
                                                </td>
                                                <td>
                                                    <?php if ($user_role == 'admin'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editAttendance(<?php echo $record['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- Mark Attendance Modal -->
    <?php if ($user_role == 'admin'): ?>
    <div class="modal fade" id="markAttendanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="mark_attendance">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Employee *</label>
                                    <select class="form-control" id="user_id" name="user_id" required>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $employee): ?>
                                            <option value="<?php echo $employee['id']; ?>">
                                                <?php echo htmlspecialchars($employee['name']); ?> (<?php echo ucfirst($employee['role']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="attendance_date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="check_in_time" class="form-label">Check In Time *</label>
                                    <input type="time" class="form-control" id="check_in_time" name="check_in_time" value="<?php echo date('H:i'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="check_out_time" class="form-label">Check Out Time</label>
                                    <input type="time" class="form-control" id="check_out_time" name="check_out_time">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="attendance_status" class="form-label">Status *</label>
                                    <select class="form-control" id="attendance_status" name="status" required>
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                        <option value="late">Late</option>
                                        <option value="half_day">Half Day</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Mark Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current time every second
        setInterval(function() {
            document.getElementById('current-time').textContent = new Date().toLocaleString();
        }, 1000);
        
        function quickCheckIn() {
            // AJAX call for quick check-in
            if (confirm('Check in now?')) {
                fetch('../api/quick_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'check_in',
                        user_id: <?php echo $user_id; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Checked in successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function quickCheckOut() {
            if (confirm('Check out now?')) {
                fetch('../api/quick_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'check_out',
                        user_id: <?php echo $user_id; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Checked out successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        function checkOut(attendanceId) {
            if (confirm('Check out this employee?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="check_out">
                    <input type="hidden" name="attendance_id" value="${attendanceId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editAttendance(id) {
            window.location.href = 'attendance.php?action=edit&id=' + id;
        }
        
        function exportAttendance() {
            alert('Export functionality will be implemented');
        }
    </script>
</body>
</html>

<?php
function getAttendanceRowClass($record) {
    switch ($record['status']) {
        case 'absent': return 'table-danger';
        case 'late': return 'table-warning';
        case 'half_day': return 'table-info';
        case 'present': return 'table-success';
        default: return '';
    }
}

function getAttendanceStatusColor($status) {
    switch ($status) {
        case 'present': return 'success';
        case 'absent': return 'danger';
        case 'late': return 'warning';
        case 'half_day': return 'info';
        default: return 'secondary';
    }
}

function getMonthlyAttendanceStats($month) {
    // This would return monthly statistics
    return [
        'total_days' => 22,
        'present_days' => 20,
        'absent_days' => 2,
        'late_days' => 3
    ];
}
?>