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
$salary_id = $_GET['id'] ?? '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'generate_salary') {
        $employee_id = intval($_POST['user_id']);
        $month = intval($_POST['month']);
        $year = intval($_POST['year']);
        $basic_salary = floatval($_POST['basic_salary']);
        $allowances = floatval($_POST['allowances']);
        $deductions = floatval($_POST['deductions']);
        
        $gross_salary = $basic_salary + $allowances;
        $net_salary = $gross_salary - $deductions;
        
        // Check if salary slip already exists
        $check_existing = $pdo->prepare("SELECT COUNT(*) as count FROM salary_slips WHERE user_id = ? AND month = ? AND year = ?");
        $check_existing->execute([$employee_id, $month, $year]);
        $exists = $check_existing->fetch()['count'];
        
        if ($exists > 0) {
            $error_message = "Salary slip for this month and year already exists!";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO salary_slips (user_id, month, year, basic_salary, allowances, deductions, gross_salary, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'generated')");
                $stmt->execute([$employee_id, $month, $year, $basic_salary, $allowances, $deductions, $gross_salary, $net_salary]);
                
                logActivity($user_id, 'create', "Generated salary slip for user ID: $employee_id for $month/$year");
                $success_message = "Salary slip generated successfully!";
            } catch (Exception $e) {
                $error_message = "Error generating salary slip: " . $e->getMessage();
            }
        }
    }
    
    if ($action == 'update_status') {
        $id = intval($_POST['id']);
        $status = sanitize($_POST['status']);
        
        try {
            $stmt = $pdo->prepare("UPDATE salary_slips SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            logActivity($user_id, 'update', "Updated salary slip status ID: $id to $status");
            $success_message = "Salary slip status updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating salary slip: " . $e->getMessage();
        }
    }
    
    if ($action == 'update_salary') {
        $id = intval($_POST['id']);
        $basic_salary = floatval($_POST['basic_salary']);
        $allowances = floatval($_POST['allowances']);
        $deductions = floatval($_POST['deductions']);
        
        $gross_salary = $basic_salary + $allowances;
        $net_salary = $gross_salary - $deductions;
        
        try {
            $stmt = $pdo->prepare("UPDATE salary_slips SET basic_salary = ?, allowances = ?, deductions = ?, gross_salary = ?, net_salary = ? WHERE id = ?");
            $stmt->execute([$basic_salary, $allowances, $deductions, $gross_salary, $net_salary, $id]);
            
            logActivity($user_id, 'update', "Updated salary slip ID: $id");
            $success_message = "Salary slip updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating salary slip: " . $e->getMessage();
        }
    }
}

// Get filters
$month_filter = $_GET['month'] ?? date('n');
$year_filter = $_GET['year'] ?? date('Y');
$employee_filter = $_GET['employee'] ?? '';
$status_filter = $_GET['status'] ?? '';

$user = getUserDetails($user_id);
$user_department_id = $user['department_id'] ?? null;

// Build query based on filters
$where_conditions = ['1=1'];
$params = [];

if (!empty($month_filter)) {
    $where_conditions[] = 'month = ?';
    $params[] = $month_filter;
}

if (!empty($year_filter)) {
    $where_conditions[] = 'year = ?';
    $params[] = $year_filter;
}

if (!empty($employee_filter)) {
    $where_conditions[] = 'ss.user_id = ?';
    $params[] = $employee_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = 'ss.status = ?';
    $params[] = $status_filter;
}

// Department-based view rights for non-admin
if ($user_role !== 'admin' && $user_department_id) {
    $where_conditions[] = 'u.department_id = ?';
    $params[] = $user_department_id;
}

$where_clause = implode(' AND ', $where_conditions);

// Get salary slips with employee details
$stmt = $pdo->prepare("
    SELECT ss.*, u.name as employee_name, u.role as employee_role, u.email as employee_email, u.department_id
    FROM salary_slips ss
    JOIN users u ON ss.user_id = u.id
    WHERE $where_clause
    ORDER BY ss.year DESC, ss.month DESC, u.name ASC
");
$stmt->execute($params);
$salary_slips = $stmt->fetchAll();

// Get employees for dropdown
$employees = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('doctor', 'nurse', 'staff', 'pharmacy', 'lab_tech', 'receptionist') AND status = 'active' ORDER BY name")->fetchAll();

// Get statistics
$total_slips = count($salary_slips);
$total_payroll = array_sum(array_column($salary_slips, 'net_salary'));
$pending_payments = count(array_filter($salary_slips, function($slip) { return $slip['status'] == 'generated'; }));
$paid_count = count(array_filter($salary_slips, function($slip) { return $slip['status'] == 'paid'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Management - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
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
                    <h1 class="h2"><i class="fas fa-money-check-alt me-2"></i>Salary Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <?php if ($user_role == 'admin'): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#generateSalaryModal">
                                    <i class="fas fa-plus"></i> Generate Salary
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportPayroll()">
                                <i class="fas fa-download"></i> Export Payroll
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
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Salary Slips</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_slips; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Payroll</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($total_payroll, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Payments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_payments; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Paid Slips</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $paid_count; ?></div>
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
                        <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label for="month" class="form-label">Month</label>
                                <select class="form-control" id="month" name="month">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>" <?php echo $month_filter == $m ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-control" id="year" name="year">
                                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
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
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="generated" <?php echo $status_filter == 'generated' ? 'selected' : ''; ?>>Generated</option>
                                    <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="salary.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Salary Slips List -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Salary Slips</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Month/Year</th>
                                        <th>Basic Salary</th>
                                        <th>Allowances</th>
                                        <th>Deductions</th>
                                        <th>Gross Salary</th>
                                        <th>Net Salary</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($salary_slips)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fas fa-money-check-alt fa-3x mb-3"></i>
                                                <p>No salary slips found</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($salary_slips as $slip): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($slip['employee_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo ucfirst($slip['employee_role']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo date('F Y', mktime(0, 0, 0, $slip['month'], 1, $slip['year'])); ?></strong>
                                                </td>
                                                <td class="text-end">₹<?php echo number_format($slip['basic_salary'], 2); ?></td>
                                                <td class="text-end text-success">+₹<?php echo number_format($slip['allowances'], 2); ?></td>
                                                <td class="text-end text-danger">-₹<?php echo number_format($slip['deductions'], 2); ?></td>
                                                <td class="text-end">₹<?php echo number_format($slip['gross_salary'], 2); ?></td>
                                                <td class="text-end"><strong>₹<?php echo number_format($slip['net_salary'], 2); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getSalaryStatusColor($slip['status']); ?>">
                                                        <?php echo ucfirst($slip['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($user_role == 'admin'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editSalary(<?php echo $slip['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="updateStatus(<?php echo $slip['id']; ?>, '<?php echo $slip['status']; ?>')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="printSlip(<?php echo $slip['id']; ?>)">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($salary_slips)): ?>
                                    <tfoot>
                                        <tr class="table-info">
                                            <th colspan="6" class="text-end">Total:</th>
                                            <th class="text-end">₹<?php echo number_format($total_payroll, 2); ?></th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Generate Salary Modal -->
    <?php if ($user_role == 'admin'): ?>
    <div class="modal fade" id="generateSalaryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Salary Slip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="generate_salary">
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
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="salary_month" class="form-label">Month *</label>
                                    <select class="form-control" id="salary_month" name="month" required>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php echo date('n') == $m ? 'selected' : ''; ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="salary_year" class="form-label">Year *</label>
                                    <select class="form-control" id="salary_year" name="year" required>
                                        <?php for ($y = date('Y') - 1; $y <= date('Y'); $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo date('Y') == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="basic_salary" class="form-label">Basic Salary (₹) *</label>
                                    <input type="number" class="form-control" id="basic_salary" name="basic_salary" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="allowances" class="form-label">Allowances (₹)</label>
                                    <input type="number" class="form-control" id="allowances" name="allowances" step="0.01" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deductions" class="form-label">Deductions (₹)</label>
                                    <input type="number" class="form-control" id="deductions" name="deductions" step="0.01" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Net Salary</label>
                                    <div class="form-control-plaintext" id="net_salary_display">₹0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Generate Salary Slip</button>
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
                    <h5 class="modal-title">Update Payment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="updateStatusForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" id="status_slip_id">
                        
                        <div class="mb-3">
                            <label for="status_select" class="form-label">Status *</label>
                            <select class="form-control" id="status_select" name="status" required>
                                <option value="generated">Generated</option>
                                <option value="paid">Paid</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
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

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate net salary automatically
        function calculateNetSalary() {
            const basic = parseFloat(document.getElementById('basic_salary').value) || 0;
            const allowances = parseFloat(document.getElementById('allowances').value) || 0;
            const deductions = parseFloat(document.getElementById('deductions').value) || 0;
            const net = basic + allowances - deductions;
            document.getElementById('net_salary_display').textContent = '₹' + net.toLocaleString('en-IN', {minimumFractionDigits: 2});
        }
        
        // Add event listeners
        document.getElementById('basic_salary').addEventListener('input', calculateNetSalary);
        document.getElementById('allowances').addEventListener('input', calculateNetSalary);
        document.getElementById('deductions').addEventListener('input', calculateNetSalary);
        
        function updateStatus(id, currentStatus) {
            document.getElementById('status_slip_id').value = id;
            document.getElementById('status_select').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
        
        function editSalary(id) {
            window.location.href = 'salary.php?action=edit&id=' + id;
        }
        
        function printSlip(id) {
            window.open('salary_slip_print.php?id=' + id, '_blank');
        }
        
        function exportPayroll() {
            alert('Export functionality will be implemented');
        }
    </script>
</body>
</html>

<?php
function getSalaryStatusColor($status) {
    switch ($status) {
        case 'paid': return 'success';
        case 'cancelled': return 'danger';
        default: return 'warning';
    }
}
?>
