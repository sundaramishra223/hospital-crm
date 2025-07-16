<?php
require_once 'includes/functions.php';

// Check if user is logged in and has lab_tech role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'lab_tech') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get lab tech information
$lab_tech_info = getUserById($user_id);

// Get statistics
$total_tests = getTotalRecords('lab_tests', ['status' => 'active']);
$pending_samples = getTotalRecords('lab_samples', ['status' => 'pending', 'hospital_id' => $hospital_id]);
$completed_today = getTotalRecords('lab_samples', ['DATE(completed_at)' => date('Y-m-d'), 'status' => 'completed']);
$in_progress = getTotalRecords('lab_samples', ['status' => 'in_progress', 'hospital_id' => $hospital_id]);

// Get pending samples and recent tests
$pending_samples_list = getPendingSamples($hospital_id, 10);
$recent_reports = getRecentLabReports($hospital_id, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Technician Dashboard - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-microscope me-2"></i>Lab Technician Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.href='modules/lab_tests.php'">
                                <i class="fas fa-flask"></i> Manage Tests
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="location.href='modules/lab_samples.php'">
                                <i class="fas fa-vial"></i> Process Samples
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-user-md me-2"></i>Welcome, <?php echo htmlspecialchars($lab_tech_info['name']); ?>!</h5>
                    <p class="mb-0">Process lab samples, conduct tests, and generate accurate medical reports for patient diagnosis.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Available Tests</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_tests; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-flask fa-2x text-gray-300"></i>
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
                                            Pending Samples</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_samples; ?></div>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            In Progress</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $in_progress; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-spinner fa-2x text-gray-300"></i>
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
                                            Completed Today</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_today; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/lab_samples.php?action=collect" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-vial me-2"></i>Collect Sample
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/lab_samples.php?filter=pending" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-hourglass-half me-2"></i>Process Pending
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/lab_reports.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-file-medical me-2"></i>Generate Report
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/lab_equipment.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-tools me-2"></i>Equipment Status
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Samples & Recent Reports -->
                <div class="row">
                    <!-- Pending Samples -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-warning">Pending Samples</h6>
                                <i class="fas fa-vials"></i>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pending_samples_list)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <p>No pending samples at the moment</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Sample ID</th>
                                                    <th>Patient</th>
                                                    <th>Test Type</th>
                                                    <th>Priority</th>
                                                    <th>Collected</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Sample data - would be populated from database -->
                                                <tr>
                                                    <td><strong>SAM001</strong></td>
                                                    <td>John Doe</td>
                                                    <td>Complete Blood Count</td>
                                                    <td><span class="badge bg-danger">Urgent</span></td>
                                                    <td>2 hours ago</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary">Process</button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>SAM002</strong></td>
                                                    <td>Jane Smith</td>
                                                    <td>Blood Sugar</td>
                                                    <td><span class="badge bg-warning">Normal</span></td>
                                                    <td>1 hour ago</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary">Process</button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>SAM003</strong></td>
                                                    <td>Rajesh Patel</td>
                                                    <td>Lipid Profile</td>
                                                    <td><span class="badge bg-info">Routine</span></td>
                                                    <td>30 mins ago</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary">Process</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Equipment Status -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-success">Equipment Status</h6>
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Hematology Analyzer</h6>
                                            <small>Last calibrated: Today</small>
                                        </div>
                                        <span class="badge bg-success">Online</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Chemistry Analyzer</h6>
                                            <small>Last calibrated: Yesterday</small>
                                        </div>
                                        <span class="badge bg-success">Online</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Microscope Unit 1</h6>
                                            <small>Maintenance due: Next week</small>
                                        </div>
                                        <span class="badge bg-warning">Caution</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Centrifuge</h6>
                                            <small>Service required</small>
                                        </div>
                                        <span class="badge bg-danger">Offline</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Reports & Test Categories -->
                <div class="row">
                    <!-- Recent Reports -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Reports Generated</h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">CBC Report - John Doe</h6>
                                            <small>15 mins ago</small>
                                        </div>
                                        <p class="mb-1">Complete Blood Count analysis completed</p>
                                        <small>Status: Normal values</small>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Glucose Test - Jane Smith</h6>
                                            <small>1 hour ago</small>
                                        </div>
                                        <p class="mb-1">Fasting blood glucose test</p>
                                        <small>Status: Elevated - requires doctor review</small>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Lipid Panel - Rajesh Patel</h6>
                                            <small>2 hours ago</small>
                                        </div>
                                        <p class="mb-1">Comprehensive lipid analysis</p>
                                        <small>Status: Within normal limits</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Categories -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-info">Available Test Categories</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-primary">
                                            <div class="card-body">
                                                <h6 class="text-primary">Blood Tests</h6>
                                                <p class="small mb-0">CBC, Glucose, Lipids, Electrolytes</p>
                                                <span class="badge bg-primary">12 Tests</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-success">
                                            <div class="card-body">
                                                <h6 class="text-success">Urine Tests</h6>
                                                <p class="small mb-0">Routine, Microscopy, Culture</p>
                                                <span class="badge bg-success">8 Tests</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-warning">
                                            <div class="card-body">
                                                <h6 class="text-warning">Microbiology</h6>
                                                <p class="small mb-0">Culture, Sensitivity, Gram Stain</p>
                                                <span class="badge bg-warning">6 Tests</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="card border-left-info">
                                            <div class="card-body">
                                                <h6 class="text-info">Biochemistry</h6>
                                                <p class="small mb-0">Enzymes, Proteins, Hormones</p>
                                                <span class="badge bg-info">15 Tests</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Workload Summary -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Today's Workload Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-warning">8</h4>
                                        <p class="text-muted">Samples Pending</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-info">5</h4>
                                        <p class="text-muted">In Progress</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-success">12</h4>
                                        <p class="text-muted">Completed</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-primary">25</h4>
                                        <p class="text-muted">Total Tests</p>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 48%" aria-valuenow="48" aria-valuemin="0" aria-valuemax="100">48% Complete</div>
                                </div>
                                <small class="text-muted">Daily target completion rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>