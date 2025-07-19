<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$hospital_id = $_SESSION['hospital_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_shift':
                if (in_array($role, ['admin', 'doctor', 'nurse'])) {
                    $employee_id = (int)$_POST['employee_id'];
                    $shift_type = sanitize($_POST['shift_type']);
                    $start_time = sanitize($_POST['start_time']);
                    $end_time = sanitize($_POST['end_time']);
                    $days = sanitize($_POST['days']);
                    $department_id = (int)$_POST['department_id'];
                    $effective_from = sanitize($_POST['effective_from']);
                    $effective_to = sanitize($_POST['effective_to']);
                    $notes = sanitize($_POST['notes']);
                    
                    $stmt = $pdo->prepare("INSERT INTO shifts (employee_id, shift_type, start_time, end_time, days, department_id, effective_from, effective_to, notes, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$employee_id, $shift_type, $start_time, $end_time, $days, $department_id, $effective_from, $effective_to, $notes, $hospital_id]);
                    
                    addActivityLog($user_id, 'shift_added', "Added shift for employee ID: $employee_id");
                    $success = "Shift assigned successfully!";
                }
                break;
                
            case 'update_shift':
                if (in_array($role, ['admin', 'doctor', 'nurse'])) {
                    $shift_id = (int)$_POST['shift_id'];
                    $shift_type = sanitize($_POST['shift_type']);
                    $start_time = sanitize($_POST['start_time']);
                    $end_time = sanitize($_POST['end_time']);
                    $days = sanitize($_POST['days']);
                    $effective_from = sanitize($_POST['effective_from']);
                    $effective_to = sanitize($_POST['effective_to']);
                    $notes = sanitize($_POST['notes']);
                    
                    $stmt = $pdo->prepare("UPDATE shifts SET shift_type = ?, start_time = ?, end_time = ?, days = ?, effective_from = ?, effective_to = ?, notes = ? WHERE id = ? AND hospital_id = ?");
                    $stmt->execute([$shift_type, $start_time, $end_time, $days, $effective_from, $effective_to, $notes, $shift_id, $hospital_id]);
                    
                    addActivityLog($user_id, 'shift_updated', "Updated shift ID: $shift_id");
                    $success = "Shift updated successfully!";
                }
                break;
                
            case 'delete_shift':
                if (in_array($role, ['admin', 'doctor', 'nurse'])) {
                    $shift_id = (int)$_POST['shift_id'];
                    
                    $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = ? AND hospital_id = ?");
                    $stmt->execute([$shift_id, $hospital_id]);
                    
                    addActivityLog($user_id, 'shift_deleted', "Deleted shift ID: $shift_id");
                    $success = "Shift deleted successfully!";
                }
                break;
        }
    }
}

// Get departments
$departments = [];
$stmt = $pdo->prepare("SELECT * FROM departments WHERE hospital_id = ? AND status = 'active' ORDER BY name");
$stmt->execute([$hospital_id]);
$departments = $stmt->fetchAll();

// Get employees (doctors, nurses, staff)
$employees = [];
$stmt = $pdo->prepare("SELECT u.id, u.name, u.role, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.hospital_id = ? AND u.status = 'active' AND u.role IN ('doctor', 'nurse', 'staff') ORDER BY u.name");
$stmt->execute([$hospital_id]);
$employees = $stmt->fetchAll();

// Get shifts with filters
$where_conditions = ['hospital_id = ?'];
$params = [$hospital_id];

if (isset($_GET['department_id']) && $_GET['department_id']) {
    $where_conditions[] = 'department_id = ?';
    $params[] = (int)$_GET['department_id'];
}

if (isset($_GET['employee_id']) && $_GET['employee_id']) {
    $where_conditions[] = 'employee_id = ?';
    $params[] = (int)$_GET['employee_id'];
}

if (isset($_GET['shift_type']) && $_GET['shift_type']) {
    $where_conditions[] = 'shift_type = ?';
    $params[] = sanitize($_GET['shift_type']);
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT s.*, u.name as employee_name, u.role as employee_role, d.name as department_name FROM shifts s LEFT JOIN users u ON s.employee_id = u.id LEFT JOIN departments d ON s.department_id = d.id WHERE $where_clause ORDER BY s.effective_from DESC");
$stmt->execute($params);
$shifts = $stmt->fetchAll();

// Get today's shifts
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT s.*, u.name as employee_name, u.role as employee_role, d.name as department_name FROM shifts s LEFT JOIN users u ON s.employee_id = u.id LEFT JOIN departments d ON s.department_id = d.id WHERE s.hospital_id = ? AND s.effective_from <= ? AND (s.effective_to IS NULL OR s.effective_to >= ?) ORDER BY s.start_time");
$stmt->execute([$hospital_id, $today, $today]);
$today_shifts = $stmt->fetchAll();

$page_title = "Shift Management";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="../dashboards/admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Shift Management</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="fa fa-calendar-check-o"></i> Shift Management
                </h4>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Total Shifts">Total Shifts</h5>
                            <h3 class="mt-3 mb-3"><?php echo count($shifts); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-primary rounded">
                                <i class="fa fa-calendar font-20 text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Today's Shifts">Today's Shifts</h5>
                            <h3 class="mt-3 mb-3"><?php echo count($today_shifts); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-success rounded">
                                <i class="fa fa-clock-o font-20 text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Active Employees">Active Employees</h5>
                            <h3 class="mt-3 mb-3"><?php echo count($employees); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-info rounded">
                                <i class="fa fa-users font-20 text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Departments">Departments</h5>
                            <h3 class="mt-3 mb-3"><?php echo count($departments); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-warning rounded">
                                <i class="fa fa-building font-20 text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Add/Edit Shift -->
        <?php if (in_array($role, ['admin', 'doctor', 'nurse'])): ?>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-plus"></i> Assign Shift
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_shift">
                        
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?> (<?php echo ucfirst($employee['role']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['id']; ?>">
                                    <?php echo htmlspecialchars($department['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Shift Type</label>
                            <select name="shift_type" class="form-select" required>
                                <option value="">Select Shift Type</option>
                                <option value="morning">Morning (6 AM - 2 PM)</option>
                                <option value="afternoon">Afternoon (2 PM - 10 PM)</option>
                                <option value="night">Night (10 PM - 6 AM)</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Working Days</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="monday" id="monday">
                                <label class="form-check-label" for="monday">Monday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="tuesday" id="tuesday">
                                <label class="form-check-label" for="tuesday">Tuesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="wednesday" id="wednesday">
                                <label class="form-check-label" for="wednesday">Wednesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="thursday" id="thursday">
                                <label class="form-check-label" for="thursday">Thursday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="friday" id="friday">
                                <label class="form-check-label" for="friday">Friday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="saturday" id="saturday">
                                <label class="form-check-label" for="saturday">Saturday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="sunday" id="sunday">
                                <label class="form-check-label" for="sunday">Sunday</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Effective From</label>
                                    <input type="date" name="effective_from" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Effective To (Optional)</label>
                                    <input type="date" name="effective_to" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Assign Shift
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Today's Shifts -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-clock-o"></i> Today's Shifts
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Shift Type</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_shifts as $shift): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-title bg-soft-primary rounded">
                                                    <?php echo strtoupper(substr($shift['employee_name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($shift['employee_name']); ?></h6>
                                                <small class="text-muted"><?php echo ucfirst($shift['employee_role']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($shift['department_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $shift['shift_type'] == 'morning' ? 'success' : ($shift['shift_type'] == 'afternoon' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($shift['shift_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('H:i', strtotime($shift['start_time'])) . ' - ' . date('H:i', strtotime($shift['end_time'])); ?></td>
                                    <td>
                                        <?php 
                                        $current_time = date('H:i');
                                        $start_time = date('H:i', strtotime($shift['start_time']));
                                        $end_time = date('H:i', strtotime($shift['end_time']));
                                        
                                        if ($current_time >= $start_time && $current_time <= $end_time) {
                                            echo '<span class="badge bg-success">Active</span>';
                                        } elseif ($current_time < $start_time) {
                                            echo '<span class="badge bg-warning">Upcoming</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Completed</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All Shifts -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">
                        <i class="fa fa-calendar"></i> All Shifts
                    </h4>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" style="width: auto;" onchange="filterShifts()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="filterShifts()">
                            <option value="">All Shift Types</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="night">Night</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="shiftsTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Shift Type</th>
                                    <th>Time</th>
                                    <th>Days</th>
                                    <th>Effective Period</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shifts as $shift): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-title bg-soft-primary rounded">
                                                    <?php echo strtoupper(substr($shift['employee_name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($shift['employee_name']); ?></h6>
                                                <small class="text-muted"><?php echo ucfirst($shift['employee_role']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($shift['department_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $shift['shift_type'] == 'morning' ? 'success' : ($shift['shift_type'] == 'afternoon' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($shift['shift_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('H:i', strtotime($shift['start_time'])) . ' - ' . date('H:i', strtotime($shift['end_time'])); ?></td>
                                    <td>
                                        <?php 
                                        $days = json_decode($shift['days'], true);
                                        if ($days) {
                                            echo implode(', ', array_map('ucfirst', $days));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($shift['effective_from'])); ?>
                                        <?php if ($shift['effective_to']): ?>
                                            - <?php echo date('M d, Y', strtotime($shift['effective_to'])); ?>
                                        <?php else: ?>
                                            - Ongoing
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (in_array($role, ['admin', 'doctor', 'nurse'])): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editShift(<?php echo $shift['id']; ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteShift(<?php echo $shift['id']; ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterShifts() {
    // Implementation for filtering shifts
    console.log('Filter functionality will be implemented');
}

function editShift(shiftId) {
    // Implementation for editing shift
    alert('Edit shift functionality will be implemented');
}

function deleteShift(shiftId) {
    if (confirm('Are you sure you want to delete this shift?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_shift">
            <input type="hidden" name="shift_id" value="${shiftId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-fill shift times based on shift type
document.querySelector('select[name="shift_type"]').addEventListener('change', function() {
    const shiftType = this.value;
    const startTime = document.querySelector('input[name="start_time"]');
    const endTime = document.querySelector('input[name="end_time"]');
    
    switch(shiftType) {
        case 'morning':
            startTime.value = '06:00';
            endTime.value = '14:00';
            break;
        case 'afternoon':
            startTime.value = '14:00';
            endTime.value = '22:00';
            break;
        case 'night':
            startTime.value = '22:00';
            endTime.value = '06:00';
            break;
        default:
            startTime.value = '';
            endTime.value = '';
    }
});
</script>

<?php include '../includes/footer.php'; ?>