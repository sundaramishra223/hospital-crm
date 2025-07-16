<?php
require_once 'includes/functions.php';

// Check if user is logged in and has pharmacy role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'pharmacy') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get pharmacy staff information
$pharmacy_info = getUserById($user_id);

// Get statistics
$total_medicines = getTotalRecords('medicines', ['hospital_id' => $hospital_id, 'status' => 'active']);
$low_stock = getTotalRecords('medicines', ['hospital_id' => $hospital_id, 'quantity <=' => 'reorder_level', 'status' => 'active']);
$expired_soon = getTotalRecords('medicines', ['hospital_id' => $hospital_id, 'expiry_date <=' => date('Y-m-d', strtotime('+30 days')), 'status' => 'active']);
$today_dispensed = getTotalRecords('prescriptions', ['DATE(dispensed_at)' => date('Y-m-d'), 'status' => 'dispensed']);

// Get recent prescriptions and low stock items
$recent_prescriptions = getRecentPrescriptions($hospital_id, 10);
$low_stock_items = getLowStockMedicines($hospital_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
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
                    <h1 class="h2"><i class="fas fa-pills me-2"></i>Pharmacy Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.href='modules/pharmacy.php'">
                                <i class="fas fa-pills"></i> Manage Inventory
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="location.href='modules/prescriptions.php'">
                                <i class="fas fa-file-prescription"></i> Prescriptions
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-mortar-pestle me-2"></i>Welcome, <?php echo htmlspecialchars($pharmacy_info['name']); ?>!</h5>
                    <p class="mb-0">Manage medicine inventory, process prescriptions, and ensure quality pharmaceutical care.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Medicines</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_medicines; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-pills fa-2x text-gray-300"></i>
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
                                            Low Stock</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $low_stock; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                            Expiring Soon</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $expired_soon; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-times fa-2x text-gray-300"></i>
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
                                            Today Dispensed</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_dispensed; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-hand-holding-medical fa-2x text-gray-300"></i>
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
                                        <a href="modules/pharmacy.php?action=add" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add Medicine
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/prescriptions.php?filter=pending" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-file-prescription me-2"></i>Pending Prescriptions
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/pharmacy.php?filter=low_stock" class="btn btn-outline-danger w-100">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/pharmacy.php?filter=expired" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-calendar-times me-2"></i>Expiry Management
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts & Recent Activity -->
                <div class="row">
                    <!-- Stock Alerts -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-warning">Stock Alerts</h6>
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="card-body">
                                <?php if (empty($low_stock_items)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <p>All medicines are well stocked</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($low_stock_items as $item): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <small class="text-danger">Low Stock</small>
                                                </div>
                                                <p class="mb-1">Current: <?php echo $item['quantity']; ?> | Reorder Level: <?php echo $item['reorder_level']; ?></p>
                                                <small>Batch: <?php echo htmlspecialchars($item['batch_number']); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Prescriptions -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Prescriptions</h6>
                                <i class="fas fa-file-prescription"></i>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php if (empty($recent_prescriptions)): ?>
                                        <div class="text-center text-muted py-3">
                                            <p>No recent prescriptions</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_prescriptions as $prescription): ?>
                                            <a href="modules/prescriptions.php?id=<?php echo $prescription['id']; ?>" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($prescription['patient_name']); ?></h6>
                                                    <small><?php echo date('M j', strtotime($prescription['created_at'])); ?></small>
                                                </div>
                                                <p class="mb-1">Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></p>
                                                <small>
                                                    <span class="badge bg-<?php echo getStatusColor($prescription['status']); ?>">
                                                        <?php echo ucfirst($prescription['status']); ?>
                                                    </span>
                                                </small>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Overview -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Inventory Overview</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Medicine</th>
                                                <th>Category</th>
                                                <th>Stock</th>
                                                <th>Batch</th>
                                                <th>Expiry</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- This would be populated with inventory data -->
                                            <tr>
                                                <td>Paracetamol 500mg</td>
                                                <td>Analgesic</td>
                                                <td>150 tablets</td>
                                                <td>PCM001</td>
                                                <td>Dec 2024</td>
                                                <td><span class="badge bg-success">In Stock</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">Edit</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Amoxicillin 250mg</td>
                                                <td>Antibiotic</td>
                                                <td>25 capsules</td>
                                                <td>AMX002</td>
                                                <td>Nov 2024</td>
                                                <td><span class="badge bg-warning">Low Stock</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning">Reorder</button>
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