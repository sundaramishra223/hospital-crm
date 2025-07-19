<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user has permission to access this module
if (!hasPermission($_SESSION['user_role'], 'reports')) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];

// Get date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'financial';

// Get financial statistics
$sql = "SELECT 
            COUNT(*) as total_bills,
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
            SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
            AVG(total_amount) as avg_bill_amount
        FROM billing 
        WHERE hospital_id = ? AND created_at BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $hospital_id, $start_date, $end_date);
$stmt->execute();
$financial_stats = $stmt->get_result()->fetch_assoc();

// Get patient statistics
$sql = "SELECT 
            COUNT(*) as total_patients,
            SUM(CASE WHEN patient_type = 'inpatient' THEN 1 ELSE 0 END) as inpatients,
            SUM(CASE WHEN patient_type = 'outpatient' THEN 1 ELSE 0 END) as outpatients,
            COUNT(DISTINCT DATE(created_at)) as active_days
        FROM patients 
        WHERE hospital_id = ? AND created_at BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $hospital_id, $start_date, $end_date);
$stmt->execute();
$patient_stats = $stmt->get_result()->fetch_assoc();

// Get appointment statistics
$sql = "SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
            SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_show_appointments
        FROM appointments 
        WHERE hospital_id = ? AND appointment_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $hospital_id, $start_date, $end_date);
$stmt->execute();
$appointment_stats = $stmt->get_result()->fetch_assoc();

// Get department statistics
$sql = "SELECT 
            d.name as department_name,
            COUNT(DISTINCT p.id) as patient_count,
            COUNT(DISTINCT a.id) as appointment_count,
            SUM(b.total_amount) as revenue
        FROM departments d
        LEFT JOIN patients p ON d.id = p.department_id AND p.hospital_id = ? AND p.created_at BETWEEN ? AND ?
        LEFT JOIN appointments a ON d.id = a.department_id AND a.hospital_id = ? AND a.appointment_date BETWEEN ? AND ?
        LEFT JOIN billing b ON d.id = b.department_id AND b.hospital_id = ? AND b.created_at BETWEEN ? AND ?
        WHERE d.hospital_id = ? AND d.deleted_at IS NULL
        GROUP BY d.id, d.name
        ORDER BY revenue DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ississis", $hospital_id, $start_date, $end_date, $hospital_id, $start_date, $end_date, $hospital_id, $start_date, $end_date, $hospital_id);
$stmt->execute();
$department_stats = $stmt->get_result();

// Get monthly revenue data for chart
$sql = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as revenue,
            COUNT(*) as bill_count
        FROM billing 
        WHERE hospital_id = ? AND created_at BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $hospital_id, $start_date, $end_date);
$stmt->execute();
$monthly_revenue = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fa fa-chart-line me-2"></i>Reports & Analytics
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Date Range Filter -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="report_type" class="form-label">Report Type</label>
                                            <select class="form-control" id="report_type" name="report_type">
                                                <option value="financial" <?php echo $report_type == 'financial' ? 'selected' : ''; ?>>Financial Reports</option>
                                                <option value="patient" <?php echo $report_type == 'patient' ? 'selected' : ''; ?>>Patient Reports</option>
                                                <option value="appointment" <?php echo $report_type == 'appointment' ? 'selected' : ''; ?>>Appointment Reports</option>
                                                <option value="department" <?php echo $report_type == 'department' ? 'selected' : ''; ?>>Department Reports</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fa fa-filter me-1"></i>Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Type Tabs -->
                    <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $report_type == 'financial' ? 'active' : ''; ?>" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                                <i class="fa fa-dollar-sign me-1"></i>Financial Reports
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $report_type == 'patient' ? 'active' : ''; ?>" id="patient-tab" data-bs-toggle="tab" data-bs-target="#patient" type="button" role="tab">
                                <i class="fa fa-user-injured me-1"></i>Patient Reports
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $report_type == 'appointment' ? 'active' : ''; ?>" id="appointment-tab" data-bs-toggle="tab" data-bs-target="#appointment" type="button" role="tab">
                                <i class="fa fa-calendar me-1"></i>Appointment Reports
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $report_type == 'department' ? 'active' : ''; ?>" id="department-tab" data-bs-toggle="tab" data-bs-target="#department" type="button" role="tab">
                                <i class="fa fa-building me-1"></i>Department Reports
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="reportTabContent">
                        <!-- Financial Reports Tab -->
                        <div class="tab-pane fade <?php echo $report_type == 'financial' ? 'show active' : ''; ?>" id="financial" role="tabpanel">
                            <!-- Financial Statistics -->
                            <div class="row mb-4">
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        Total Revenue
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($financial_stats['total_revenue'], 2); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-dollar-sign fa-2x text-gray-300"></i>
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
                                                        Paid Amount
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($financial_stats['paid_amount'], 2); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-check-circle fa-2x text-gray-300"></i>
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
                                                        Pending Amount
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($financial_stats['pending_amount'], 2); ?></div>
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
                                                        Average Bill
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($financial_stats['avg_bill_amount'], 2); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-chart-bar fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Revenue Chart -->
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">Monthly Revenue Trend</h6>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="revenueChart" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <a href="modules/billing.php" class="btn btn-outline-primary">
                                                    <i class="fa fa-file-invoice me-2"></i>View All Bills
                                                </a>
                                                <a href="modules/payments.php" class="btn btn-outline-success">
                                                    <i class="fa fa-credit-card me-2"></i>Payment Records
                                                </a>
                                                <button class="btn btn-outline-info" onclick="exportReport('financial')">
                                                    <i class="fa fa-download me-2"></i>Export Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Reports Tab -->
                        <div class="tab-pane fade <?php echo $report_type == 'patient' ? 'show active' : ''; ?>" id="patient" role="tabpanel">
                            <!-- Patient Statistics -->
                            <div class="row mb-4">
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        Total Patients
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $patient_stats['total_patients']; ?></div>
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
                                                        Inpatients
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $patient_stats['inpatients']; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-bed fa-2x text-gray-300"></i>
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
                                                        Outpatients
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $patient_stats['outpatients']; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-user fa-2x text-gray-300"></i>
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
                                                        Active Days
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $patient_stats['active_days']; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-calendar-day fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Patient Type Chart -->
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">Patient Distribution</h6>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="patientChart" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <a href="modules/patients.php" class="btn btn-outline-primary">
                                                    <i class="fa fa-user-injured me-2"></i>View All Patients
                                                </a>
                                                <a href="modules/patients.php" class="btn btn-outline-success">
                                                    <i class="fa fa-plus me-2"></i>Add New Patient
                                                </a>
                                                <button class="btn btn-outline-info" onclick="exportReport('patient')">
                                                    <i class="fa fa-download me-2"></i>Export Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Reports Tab -->
                        <div class="tab-pane fade <?php echo $report_type == 'appointment' ? 'show active' : ''; ?>" id="appointment" role="tabpanel">
                            <!-- Appointment Statistics -->
                            <div class="row mb-4">
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        Total Appointments
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $appointment_stats['total_appointments']; ?></div>
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
                                                        Completed
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $appointment_stats['completed_appointments']; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-check fa-2x text-gray-300"></i>
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
                                                        Cancelled
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $appointment_stats['cancelled_appointments']; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-times fa-2x text-gray-300"></i>
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
                                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                        No Show
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $appointment_stats['no_show_appointments']; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fa fa-user-times fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Appointment Status Chart -->
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">Appointment Status Distribution</h6>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="appointmentChart" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <a href="modules/appointments.php" class="btn btn-outline-primary">
                                                    <i class="fa fa-calendar me-2"></i>View All Appointments
                                                </a>
                                                <a href="modules/appointments.php" class="btn btn-outline-success">
                                                    <i class="fa fa-plus me-2"></i>Schedule Appointment
                                                </a>
                                                <button class="btn btn-outline-info" onclick="exportReport('appointment')">
                                                    <i class="fa fa-download me-2"></i>Export Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Department Reports Tab -->
                        <div class="tab-pane fade <?php echo $report_type == 'department' ? 'show active' : ''; ?>" id="department" role="tabpanel">
                            <!-- Department Statistics Table -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Department Performance</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Department</th>
                                                    <th>Patients</th>
                                                    <th>Appointments</th>
                                                    <th>Revenue</th>
                                                    <th>Performance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($dept = $department_stats->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($dept['department_name']); ?></strong>
                                                        </td>
                                                        <td><?php echo $dept['patient_count']; ?></td>
                                                        <td><?php echo $dept['appointment_count']; ?></td>
                                                        <td>$<?php echo number_format($dept['revenue'], 2); ?></td>
                                                        <td>
                                                            <?php
                                                            $performance = '';
                                                            if ($dept['revenue'] > 10000) {
                                                                $performance = '<span class="badge bg-success">Excellent</span>';
                                                            } elseif ($dept['revenue'] > 5000) {
                                                                $performance = '<span class="badge bg-info">Good</span>';
                                                            } elseif ($dept['revenue'] > 1000) {
                                                                $performance = '<span class="badge bg-warning">Average</span>';
                                                            } else {
                                                                $performance = '<span class="badge bg-danger">Low</span>';
                                                            }
                                                            echo $performance;
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Department Revenue Chart -->
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">Department Revenue Comparison</h6>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="departmentChart" width="400" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <a href="modules/departments.php" class="btn btn-outline-primary">
                                                    <i class="fa fa-building me-2"></i>View Departments
                                                </a>
                                                <a href="modules/doctors.php" class="btn btn-outline-success">
                                                    <i class="fa fa-user-md me-2"></i>Department Doctors
                                                </a>
                                                <button class="btn btn-outline-info" onclick="exportReport('department')">
                                                    <i class="fa fa-download me-2"></i>Export Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: [<?php 
            $labels = [];
            $data = [];
            while ($row = $monthly_revenue->fetch_assoc()) {
                $labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                $data[] = $row['revenue'];
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Monthly Revenue',
            data: [<?php echo implode(',', $data); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Patient Chart
const patientCtx = document.getElementById('patientChart').getContext('2d');
const patientChart = new Chart(patientCtx, {
    type: 'doughnut',
    data: {
        labels: ['Inpatients', 'Outpatients'],
        datasets: [{
            data: [<?php echo $patient_stats['inpatients']; ?>, <?php echo $patient_stats['outpatients']; ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 99, 132, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true
    }
});

// Appointment Chart
const appointmentCtx = document.getElementById('appointmentChart').getContext('2d');
const appointmentChart = new Chart(appointmentCtx, {
    type: 'bar',
    data: {
        labels: ['Completed', 'Cancelled', 'No Show'],
        datasets: [{
            label: 'Appointments',
            data: [<?php echo $appointment_stats['completed_appointments']; ?>, <?php echo $appointment_stats['cancelled_appointments']; ?>, <?php echo $appointment_stats['no_show_appointments']; ?>],
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(255, 99, 132, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Department Chart
const departmentCtx = document.getElementById('departmentChart').getContext('2d');
const departmentChart = new Chart(departmentCtx, {
    type: 'bar',
    data: {
        labels: [<?php 
            $dept_labels = [];
            $dept_data = [];
            $department_stats->data_seek(0);
            while ($dept = $department_stats->fetch_assoc()) {
                $dept_labels[] = "'" . addslashes($dept['department_name']) . "'";
                $dept_data[] = $dept['revenue'];
            }
            echo implode(',', $dept_labels);
        ?>],
        datasets: [{
            label: 'Department Revenue',
            data: [<?php echo implode(',', $dept_data); ?>],
            backgroundColor: 'rgba(153, 102, 255, 0.8)'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Export report function
function exportReport(type) {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    // Create a form to submit the export request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/export_report.php';
    form.innerHTML = `
        <input type="hidden" name="report_type" value="${type}">
        <input type="hidden" name="start_date" value="${startDate}">
        <input type="hidden" name="end_date" value="${endDate}">
    `;
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const triggerTabList = [].slice.call(document.querySelectorAll('#reportTabs button'));
    triggerTabList.forEach(function (triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>