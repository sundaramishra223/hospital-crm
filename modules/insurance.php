<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$provider_id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if ($action == 'add') {
        if (addInsuranceProvider($_POST)) {
            $message = 'Insurance provider added successfully!';
            logActivity($_SESSION['user_id'], 'add', 'Added insurance provider: ' . $_POST['name']);
        } else {
            $error = 'Failed to add insurance provider. Please try again.';
        }
    } elseif ($action == 'edit' && $provider_id) {
        if (updateInsuranceProvider($provider_id, $_POST)) {
            $message = 'Insurance provider updated successfully!';
            logActivity($_SESSION['user_id'], 'edit', 'Updated insurance provider ID: ' . $provider_id);
        } else {
            $error = 'Failed to update insurance provider. Please try again.';
        }
    }
}

// Handle delete action
if ($action == 'delete' && $provider_id) {
    if (deleteInsuranceProvider($provider_id)) {
        $message = 'Insurance provider deleted successfully!';
        logActivity($_SESSION['user_id'], 'delete', 'Deleted insurance provider ID: ' . $provider_id);
    } else {
        $error = 'Failed to delete insurance provider. Please try again.';
    }
    $action = 'list';
}

// Get data based on action
switch ($action) {
    case 'list':
        $providers = getInsuranceProviders();
        $insurance_stats = getInsuranceStats();
        $expiring_insurance = checkInsuranceExpiry();
        break;
    case 'edit':
        if ($provider_id) {
            $provider = getInsuranceProviderById($provider_id);
            if (!$provider) {
                $error = 'Insurance provider not found.';
                $action = 'list';
                $providers = getInsuranceProviders();
            }
        }
        break;
    case 'view':
        if ($provider_id) {
            $provider = getInsuranceProviderById($provider_id);
            $provider_patients = getPatientsByInsurance($provider_id);
            if (!$provider) {
                $error = 'Insurance provider not found.';
                $action = 'list';
                $providers = getInsuranceProviders();
            }
        }
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Management - <?php echo getSetting('site_title', 'Hospital CRM'); ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #6f42c1, #563d7c);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .insurance-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .stat-card.insured i { color: #28a745; }
        .stat-card.uninsured i { color: #dc3545; }
        .stat-card.expired i { color: #ffc107; }
        .stat-card.coverage i { color: #6f42c1; }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .insurance-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-expiring {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Insurance Management</li>
            </ol>
        </nav>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Insurance Management</h1>
                    <p>Manage insurance providers and patient insurance information</p>
                </div>
                <div>
                    <?php if ($action == 'list'): ?>
                        <a href="?action=add" class="btn btn-light btn-lg">
                            <i class="fa fa-plus"></i> Add Insurance Provider
                        </a>
                    <?php else: ?>
                        <a href="?" class="btn btn-light btn-lg">
                            <i class="fa fa-list"></i> Back to List
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Insurance Statistics -->
        <?php if ($action == 'list'): ?>
            <div class="insurance-stats">
                <div class="stat-card insured">
                    <i class="fa fa-shield"></i>
                    <h3><?php echo $insurance_stats['total_insured']; ?></h3>
                    <p>Insured Patients</p>
                </div>
                <div class="stat-card uninsured">
                    <i class="fa fa-user-times"></i>
                    <h3><?php echo $insurance_stats['total_uninsured']; ?></h3>
                    <p>Uninsured Patients</p>
                </div>
                <div class="stat-card expired">
                    <i class="fa fa-exclamation-triangle"></i>
                    <h3><?php echo $insurance_stats['expired_insurance']; ?></h3>
                    <p>Expired Insurance</p>
                </div>
                <div class="stat-card coverage">
                    <i class="fa fa-money"></i>
                    <h3><?php echo formatCurrency($insurance_stats['total_coverage']); ?></h3>
                    <p>Total Coverage</p>
                </div>
            </div>
            
            <!-- Expiring Insurance Alert -->
            <?php if (!empty($expiring_insurance)): ?>
                <div class="alert alert-warning">
                    <h5><i class="fa fa-exclamation-triangle"></i> Insurance Expiring Soon</h5>
                    <p><?php echo count($expiring_insurance); ?> patients have insurance expiring within 30 days.</p>
                    <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#expiringModal">
                        View Details
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <?php if ($action == 'list'): ?>
            <!-- Insurance Providers List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Insurance Providers (<?php echo count($providers); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Provider Name</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($providers as $provider): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $provider['name']; ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo substr($provider['coverage_details'], 0, 50) . '...'; ?></small>
                                        </td>
                                        <td><?php echo $provider['contact_person']; ?></td>
                                        <td><?php echo $provider['phone']; ?></td>
                                        <td><?php echo $provider['email']; ?></td>
                                        <td>
                                            <span class="insurance-badge badge-<?php echo $provider['status']; ?>">
                                                <?php echo ucfirst($provider['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?action=view&id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        <?php elseif ($action == 'add' || $action == 'edit'): ?>
            <!-- Add/Edit Insurance Provider Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $action == 'add' ? 'Add Insurance Provider' : 'Edit Insurance Provider'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Provider Name *</label>
                                    <input type="text" name="name" class="form-control" required value="<?php echo $provider['name'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control" value="<?php echo $provider['contact_person'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo $provider['phone'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo $provider['email'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo $provider['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Coverage Details</label>
                            <textarea name="coverage_details" class="form-control" rows="3" placeholder="Describe the insurance coverage details..."><?php echo $provider['coverage_details'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-save"></i> <?php echo $action == 'add' ? 'Add Provider' : 'Update Provider'; ?>
                            </button>
                            <a href="?" class="btn btn-secondary btn-lg ml-2">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($action == 'view' && isset($provider)): ?>
            <!-- View Insurance Provider Details -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Provider Information</h5>
                        </div>
                        <div class="card-body">
                            <h4><?php echo $provider['name']; ?></h4>
                            <p><strong>Contact Person:</strong> <?php echo $provider['contact_person']; ?></p>
                            <p><strong>Phone:</strong> <?php echo $provider['phone']; ?></p>
                            <p><strong>Email:</strong> <?php echo $provider['email']; ?></p>
                            <p><strong>Address:</strong> <?php echo $provider['address']; ?></p>
                            <p><strong>Coverage:</strong> <?php echo $provider['coverage_details']; ?></p>
                            
                            <div class="mt-3">
                                <a href="?action=edit&id=<?php echo $provider['id']; ?>" class="btn btn-warning btn-block">
                                    <i class="fa fa-edit"></i> Edit Provider
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Insured Patients (<?php echo count($provider_patients); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($provider_patients)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Patient Name</th>
                                                <th>Policy Number</th>
                                                <th>Coverage Amount</th>
                                                <th>Status</th>
                                                <th>Expiry Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($provider_patients as $patient): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo $patient['contact']; ?></small>
                                                    </td>
                                                    <td><?php echo $patient['insurance_policy_number']; ?></td>
                                                    <td><?php echo formatCurrency($patient['insurance_coverage_amount']); ?></td>
                                                    <td>
                                                        <span class="insurance-badge badge-<?php echo $patient['insurance_status']; ?>">
                                                            <?php echo ucfirst($patient['insurance_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($patient['insurance_expiry_date'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center">No patients found with this insurance provider.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Expiring Insurance Modal -->
    <div class="modal fade" id="expiringModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Insurance Expiring Soon</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($expiring_insurance)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Insurance Provider</th>
                                        <th>Policy Number</th>
                                        <th>Expiry Date</th>
                                        <th>Days Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiring_insurance as $patient): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo $patient['contact']; ?></small>
                                            </td>
                                            <td><?php echo $patient['insurance_provider_name']; ?></td>
                                            <td><?php echo $patient['insurance_policy_number']; ?></td>
                                            <td>
                                                <?php 
                                                $expiry_date = new DateTime($patient['insurance_expiry_date']);
                                                echo $expiry_date->format('M d, Y');
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $today = new DateTime();
                                                $expiry = new DateTime($patient['insurance_expiry_date']);
                                                $days_left = $today->diff($expiry)->days;
                                                echo $days_left . ' days';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>