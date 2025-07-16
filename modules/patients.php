<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'receptionist', 'doctor', 'nurse'])) {
    header('Location: ../index.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$patient_id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'all'; // all, inpatient, outpatient
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if ($action == 'add') {
        if (addPatient($_POST)) {
            $message = 'Patient registered successfully!';
            logActivity($_SESSION['user_id'], 'add', 'Added new patient: ' . $_POST['first_name'] . ' ' . $_POST['last_name']);
        } else {
            $error = 'Failed to register patient. Please try again.';
        }
    } elseif ($action == 'edit' && $patient_id) {
        if (updatePatient($patient_id, $_POST)) {
            $message = 'Patient updated successfully!';
            logActivity($_SESSION['user_id'], 'edit', 'Updated patient ID: ' . $patient_id);
        } else {
            $error = 'Failed to update patient. Please try again.';
        }
    } elseif ($action == 'convert' && $patient_id) {
        if (convertPatientType($patient_id, $_POST['new_type'])) {
            $message = 'Patient type converted successfully!';
            logActivity($_SESSION['user_id'], 'edit', 'Converted patient type for ID: ' . $patient_id);
        } else {
            $error = 'Failed to convert patient type. Please try again.';
        }
    }
}

// Handle delete action
if ($action == 'delete' && $patient_id) {
    if ($_SESSION['user_role'] == 'admin') {
        if (deletePatient($patient_id)) {
            $message = 'Patient deleted successfully!';
            logActivity($_SESSION['user_id'], 'delete', 'Deleted patient ID: ' . $patient_id);
        } else {
            $error = 'Failed to delete patient. Please try again.';
        }
        $action = 'list';
    } else {
        $error = 'Only admin can delete patients.';
    }
}

// Get data based on action
switch ($action) {
    case 'list':
        $patients = getPatients($type);
        break;
    case 'edit':
        if ($patient_id) {
            $patient = getPatientById($patient_id);
            if (!$patient) {
                $error = 'Patient not found.';
                $action = 'list';
                $patients = getPatients($type);
            }
        }
        break;
    case 'view':
        if ($patient_id) {
            $patient = getPatientById($patient_id);
            $patient_vitals = getPatientVitals($patient_id);
            $patient_history = getPatientHistory($patient_id);
            if (!$patient) {
                $error = 'Patient not found.';
                $action = 'list';
                $patients = getPatients($type);
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
    <title>Patient Management - <?php echo getSetting('site_title', 'Hospital CRM'); ?></title>
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
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 600;
        }
        
        .page-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        .filter-tabs {
            margin-bottom: 20px;
        }
        
        .filter-tabs .nav-link {
            border-radius: 25px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .filter-tabs .nav-link.active {
            background: #28a745;
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 30px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #333;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f1f1f1;
        }
        
        .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background: #28a745;
            border-color: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.25);
        }
        
        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inpatient {
            background: #ffeeba;
            color: #856404;
        }
        
        .status-outpatient {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-discharged {
            background: #f8d7da;
            color: #721c24;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-item a {
            color: #28a745;
            text-decoration: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        .patient-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #666;
        }
        
        .vitals-chart {
            height: 300px;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .history-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .history-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #28a745;
        }
        
        .history-item {
            position: relative;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .history-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 20px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #28a745;
        }
        
        .emergency-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .age-badge {
            background: #6c757d;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Patient Management</li>
                <?php if ($action != 'list'): ?>
                    <li class="breadcrumb-item active"><?php echo ucfirst($action); ?></li>
                <?php endif; ?>
            </ol>
        </nav>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Patient Management</h1>
                    <p>Manage patient records, medical history, and care plans</p>
                </div>
                <div>
                    <?php if ($action == 'list'): ?>
                        <a href="?action=add" class="btn btn-light btn-lg">
                            <i class="fa fa-plus"></i> Register New Patient
                        </a>
                    <?php else: ?>
                        <a href="?" class="btn btn-light btn-lg">
                            <i class="fa fa-list"></i> Back to List
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
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
            <!-- Filter Tabs -->
            <ul class="nav nav-pills filter-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $type == 'all' ? 'active' : ''; ?>" href="?type=all">
                        All Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $type == 'inpatient' ? 'active' : ''; ?>" href="?type=inpatient">
                        Inpatients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $type == 'outpatient' ? 'active' : ''; ?>" href="?type=outpatient">
                        Outpatients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $type == 'emergency' ? 'active' : ''; ?>" href="?type=emergency">
                        Emergency Cases
                    </a>
                </li>
            </ul>
            
            <!-- Patients List -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php echo ucfirst($type); ?> Patients (<?php echo count($patients); ?>)
                        </h5>
                        <div class="search-box">
                            <input type="text" id="patientSearch" class="form-control" placeholder="Search patients...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="patientsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Age/Gender</th>
                                    <th>Contact</th>
                                    <th>Visit Reason</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="patient-avatar mr-3">
                                                    <?php echo strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo $patient['email'] ?: 'No email'; ?></small>
                                                    <?php if (strpos($patient['visit_reason'], 'emergency') !== false): ?>
                                                        <span class="emergency-badge ml-2">EMERGENCY</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $age = date_diff(date_create($patient['date_of_birth']), date_create('today'))->y;
                                            echo $age . ' years';
                                            ?>
                                            <br>
                                            <span class="age-badge"><?php echo ucfirst($patient['gender']); ?></span>
                                        </td>
                                        <td>
                                            <?php echo $patient['contact']; ?>
                                            <?php if ($patient['emergency_contact']): ?>
                                                <br><small class="text-muted">Emergency: <?php echo $patient['emergency_contact']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo substr($patient['visit_reason'], 0, 30) . (strlen($patient['visit_reason']) > 30 ? '...' : ''); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo ucfirst($patient['patient_type'] ?? 'outpatient'); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo 'status-' . ($patient['status'] ?? 'active'); ?>">
                                                <?php echo ucfirst($patient['status'] ?? 'active'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?action=view&id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <?php if (in_array($_SESSION['user_role'], ['admin', 'receptionist'])): ?>
                                                    <a href="?action=edit&id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                                    <a href="?action=delete&id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown">
                                                        More
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="appointments.php?patient_id=<?php echo $patient['id']; ?>">
                                                            <i class="fa fa-calendar"></i> Book Appointment
                                                        </a>
                                                        <a class="dropdown-item" href="billing.php?patient_id=<?php echo $patient['id']; ?>">
                                                            <i class="fa fa-money"></i> View Bills
                                                        </a>
                                                        <?php if (in_array($_SESSION['user_role'], ['admin', 'doctor'])): ?>
                                                            <a class="dropdown-item" href="#" onclick="convertPatient(<?php echo $patient['id']; ?>)">
                                                                <i class="fa fa-exchange"></i> Convert Type
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
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
            <!-- Add/Edit Patient Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $action == 'add' ? 'Register New Patient' : 'Edit Patient'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>First Name *</label>
                                    <input type="text" name="first_name" class="form-control" required value="<?php echo $patient['first_name'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" value="<?php echo $patient['middle_name'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" required value="<?php echo $patient['last_name'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date of Birth *</label>
                                    <input type="date" name="date_of_birth" class="form-control" required value="<?php echo $patient['date_of_birth'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Gender *</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo (isset($patient['gender']) && $patient['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo (isset($patient['gender']) && $patient['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo (isset($patient['gender']) && $patient['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Contact *</label>
                                    <input type="tel" name="contact" class="form-control" required value="<?php echo $patient['contact'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Emergency Contact</label>
                                    <input type="tel" name="emergency_contact" class="form-control" value="<?php echo $patient['emergency_contact'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo $patient['email'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Patient Type</label>
                                    <select name="patient_type" class="form-control">
                                        <option value="outpatient" <?php echo (isset($patient['patient_type']) && $patient['patient_type'] == 'outpatient') ? 'selected' : ''; ?>>Outpatient</option>
                                        <option value="inpatient" <?php echo (isset($patient['patient_type']) && $patient['patient_type'] == 'inpatient') ? 'selected' : ''; ?>>Inpatient</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo $patient['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Visit Reason *</label>
                                    <textarea name="visit_reason" class="form-control" rows="3" required><?php echo $patient['visit_reason'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Attendant Details</label>
                                    <textarea name="attendant_details" class="form-control" rows="3" placeholder="Name, relation, contact of attendant"><?php echo $patient['attendant_details'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-save"></i> <?php echo $action == 'add' ? 'Register Patient' : 'Update Patient'; ?>
                            </button>
                            <a href="?" class="btn btn-secondary btn-lg ml-2">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($action == 'view' && isset($patient)): ?>
            <!-- View Patient Details -->
            <div class="row">
                <div class="col-md-4">
                    <!-- Patient Basic Info -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Patient Information</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="patient-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 36px;">
                                <?php echo strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?>
                            </div>
                            <h4><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></h4>
                            <p class="text-muted">Patient ID: #<?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            
                            <div class="patient-details">
                                <div class="detail-item">
                                    <div class="detail-label">Age:</div>
                                    <div class="detail-value">
                                        <?php echo date_diff(date_create($patient['date_of_birth']), date_create('today'))->y; ?> years
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Gender:</div>
                                    <div class="detail-value"><?php echo ucfirst($patient['gender']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Contact:</div>
                                    <div class="detail-value"><?php echo $patient['contact']; ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Type:</div>
                                    <div class="detail-value">
                                        <span class="badge badge-info"><?php echo ucfirst($patient['patient_type'] ?? 'outpatient'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <?php if (in_array($_SESSION['user_role'], ['admin', 'receptionist'])): ?>
                                    <a href="?action=edit&id=<?php echo $patient['id']; ?>" class="btn btn-warning btn-block mb-2">
                                        <i class="fa fa-edit"></i> Edit Patient
                                    </a>
                                <?php endif; ?>
                                <a href="appointments.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-primary btn-block mb-2">
                                    <i class="fa fa-calendar"></i> Book Appointment
                                </a>
                                <a href="billing.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-info btn-block">
                                    <i class="fa fa-money"></i> View Bills
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <!-- Patient Details Tabs -->
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#details">Details</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#vitals">Vitals</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#history">History</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Details Tab -->
                                <div class="tab-pane fade show active" id="details">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="patient-details">
                                                <h6>Contact Information</h6>
                                                <div class="detail-item">
                                                    <div class="detail-label">Email:</div>
                                                    <div class="detail-value"><?php echo $patient['email'] ?: 'Not provided'; ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Emergency Contact:</div>
                                                    <div class="detail-value"><?php echo $patient['emergency_contact'] ?: 'Not provided'; ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Address:</div>
                                                    <div class="detail-value"><?php echo $patient['address'] ?: 'Not provided'; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="patient-details">
                                                <h6>Visit Information</h6>
                                                <div class="detail-item">
                                                    <div class="detail-label">Visit Reason:</div>
                                                    <div class="detail-value"><?php echo $patient['visit_reason']; ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Attendant Details:</div>
                                                    <div class="detail-value"><?php echo $patient['attendant_details'] ?: 'None'; ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Registration Date:</div>
                                                    <div class="detail-value"><?php echo date('M d, Y', strtotime($patient['created_at'])); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Vitals Tab -->
                                <div class="tab-pane fade" id="vitals">
                                    <div class="vitals-chart">
                                        <h6>Recent Vitals</h6>
                                        <canvas id="vitalsChart"></canvas>
                                    </div>
                                    
                                    <?php if (!empty($patient_vitals)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Blood Pressure</th>
                                                        <th>Heart Rate</th>
                                                        <th>Temperature</th>
                                                        <th>Weight</th>
                                                        <th>Recorded By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($patient_vitals as $vital): ?>
                                                        <tr>
                                                            <td><?php echo date('M d, Y H:i', strtotime($vital['recorded_at'])); ?></td>
                                                            <td><?php echo $vital['blood_pressure']; ?></td>
                                                            <td><?php echo $vital['heart_rate']; ?> bpm</td>
                                                            <td><?php echo $vital['temperature']; ?>°F</td>
                                                            <td><?php echo $vital['weight']; ?> kg</td>
                                                            <td><?php echo $vital['recorded_by']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No vitals recorded yet.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- History Tab -->
                                <div class="tab-pane fade" id="history">
                                    <div class="history-timeline">
                                        <?php if (!empty($patient_history)): ?>
                                            <?php foreach ($patient_history as $history): ?>
                                                <div class="history-item">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6><?php echo $history['title']; ?></h6>
                                                            <p><?php echo $history['description']; ?></p>
                                                            <small class="text-muted">
                                                                By: <?php echo $history['doctor_name']; ?> | 
                                                                <?php echo date('M d, Y H:i', strtotime($history['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <span class="badge badge-<?php echo $history['type'] == 'consultation' ? 'primary' : ($history['type'] == 'prescription' ? 'success' : 'info'); ?>">
                                                            <?php echo ucfirst($history['type']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No medical history available.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Convert Patient Type Modal -->
    <div class="modal fade" id="convertModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Convert Patient Type</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="convertForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="patient_id" id="convertPatientId">
                        <div class="form-group">
                            <label>New Patient Type</label>
                            <select name="new_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="inpatient">Inpatient</option>
                                <option value="outpatient">Outpatient</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reason for Conversion</label>
                            <textarea name="conversion_reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Convert</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/chart.min.js"></script>
    <script>
        // Search functionality
        $('#patientSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#patientsTable tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Convert patient function
        function convertPatient(patientId) {
            $('#convertPatientId').val(patientId);
            $('#convertModal').modal('show');
        }
        
        // Convert form submission
        $('#convertForm').on('submit', function(e) {
            e.preventDefault();
            const patientId = $('#convertPatientId').val();
            window.location.href = '?action=convert&id=' + patientId + '&new_type=' + $('[name="new_type"]').val();
        });
        
        // Vitals chart (if on view page)
        <?php if ($action == 'view' && isset($patient_vitals)): ?>
        const ctx = document.getElementById('vitalsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($patient_vitals, 'recorded_at')); ?>,
                    datasets: [{
                        label: 'Heart Rate',
                        data: <?php echo json_encode(array_column($patient_vitals, 'heart_rate')); ?>,
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1
                    }, {
                        label: 'Temperature',
                        data: <?php echo json_encode(array_column($patient_vitals, 'temperature')); ?>,
                        borderColor: 'rgb(54, 162, 235)',
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
        }
        <?php endif; ?>
    </script>
</body>
</html>