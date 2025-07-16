<?php
require_once 'includes/functions.php';

// Check if user is logged in and has nurse role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nurse') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get nurse information
$nurse_info = getUserById($user_id);

// Get statistics
$total_patients = getTotalRecords('patients', ['hospital_id' => $hospital_id, 'status' => 'active']);
$inpatients = getTotalRecords('patients', ['hospital_id' => $hospital_id, 'patient_type' => 'inpatient', 'status' => 'active']);
$critical_patients = getTotalRecords('patients', ['hospital_id' => $hospital_id, 'status' => 'active', 'critical' => 'yes']);
$today_vitals = getTotalRecords('patient_vitals', ['DATE(created_at)' => date('Y-m-d')]);

// Get recent patients assigned to nurse
$recent_patients = getRecentPatients($hospital_id, 10);
$critical_alerts = getCriticalPatients($hospital_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
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
                    <h1 class="h2"><i class="fas fa-plus-square me-2"></i>Nurse Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.href='modules/patients.php'">
                                <i class="fas fa-users"></i> Manage Patients
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-hand-holding-heart me-2"></i>Welcome, <?php echo htmlspecialchars($nurse_info['name']); ?>!</h5>
                    <p class="mb-0">Here's your patient care overview for today. Monitor vitals, update patient status, and provide quality care.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Patients</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_patients; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                            Inpatients</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $inpatients; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bed fa-2x text-gray-300"></i>
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
                                            Critical Patients</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $critical_patients; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                            Today's Vitals</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_vitals; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-heartbeat fa-2x text-gray-300"></i>
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
                                        <a href="modules/patients.php?action=add_vitals" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-heartbeat me-2"></i>Record Vitals
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/patients.php?filter=inpatient" class="btn btn-outline-info w-100">
                                            <i class="fas fa-bed me-2"></i>Inpatient Care
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/patients.php?filter=critical" class="btn btn-outline-danger w-100">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Critical Patients
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/patients.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-users me-2"></i>All Patients
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Critical Alerts & Recent Patients -->
                <div class="row">
                    <!-- Critical Alerts -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-danger">Critical Alerts</h6>
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="card-body">
                                <?php if (empty($critical_alerts)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <p>No critical alerts at the moment</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($critical_alerts as $alert): ?>
                                            <div class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($alert['name']); ?></h6>
                                                    <small class="text-danger">Critical</small>
                                                </div>
                                                <p class="mb-1">Room: <?php echo htmlspecialchars($alert['room_number'] ?? 'N/A'); ?></p>
                                                <small>Last vitals: <?php echo date('M j, Y g:i A', strtotime($alert['last_vitals'])); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Patients -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Patients</h6>
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php foreach ($recent_patients as $patient): ?>
                                        <a href="modules/patients.php?id=<?php echo $patient['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($patient['name']); ?></h6>
                                                <small><?php echo date('M j', strtotime($patient['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <span class="badge bg-<?php echo $patient['patient_type'] == 'inpatient' ? 'info' : 'secondary'; ?>">
                                                    <?php echo ucfirst($patient['patient_type']); ?>
                                                </span>
                                            </p>
                                            <small>Room: <?php echo htmlspecialchars($patient['room_number'] ?? 'Outpatient'); ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Care Schedule -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Today's Care Schedule</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Time</th>
                                                <th>Patient</th>
                                                <th>Task</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- This would be populated with scheduled tasks -->
                                            <tr>
                                                <td>9:00 AM</td>
                                                <td>John Doe</td>
                                                <td>Vital Signs Check</td>
                                                <td><span class="badge bg-warning">Medium</span></td>
                                                <td><span class="badge bg-success">Completed</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>11:30 AM</td>
                                                <td>Jane Smith</td>
                                                <td>Medication Administration</td>
                                                <td><span class="badge bg-danger">High</span></td>
                                                <td><span class="badge bg-warning">Pending</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-success">Complete</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
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
