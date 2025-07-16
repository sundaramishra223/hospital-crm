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
$doctor_id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if ($action == 'add') {
        if (addDoctor($_POST)) {
            $message = 'Doctor added successfully!';
            logActivity($_SESSION['user_id'], 'add', 'Added new doctor: ' . $_POST['first_name'] . ' ' . $_POST['last_name']);
        } else {
            $error = 'Failed to add doctor. Please try again.';
        }
    } elseif ($action == 'edit' && $doctor_id) {
        if (updateDoctor($doctor_id, $_POST)) {
            $message = 'Doctor updated successfully!';
            logActivity($_SESSION['user_id'], 'edit', 'Updated doctor ID: ' . $doctor_id);
        } else {
            $error = 'Failed to update doctor. Please try again.';
        }
    }
}

// Handle delete action
if ($action == 'delete' && $doctor_id) {
    if (deleteDoctor($doctor_id)) {
        $message = 'Doctor deleted successfully!';
        logActivity($_SESSION['user_id'], 'delete', 'Deleted doctor ID: ' . $doctor_id);
    } else {
        $error = 'Failed to delete doctor. Please try again.';
    }
    $action = 'list'; // Redirect to list after deletion
}

// Get data based on action
switch ($action) {
    case 'list':
        $doctors = getDoctors();
        break;
    case 'edit':
        if ($doctor_id) {
            $doctor = getDoctorById($doctor_id);
            if (!$doctor) {
                $error = 'Doctor not found.';
                $action = 'list';
                $doctors = getDoctors();
            }
        }
        break;
    case 'view':
        if ($doctor_id) {
            $doctor = getDoctorById($doctor_id);
            if (!$doctor) {
                $error = 'Doctor not found.';
                $action = 'list';
                $doctors = getDoctors();
            }
        }
        break;
}

$departments = getDepartments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - <?php echo getSetting('site_title', 'Hospital CRM'); ?></title>
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
            background: linear-gradient(135deg, #007bff, #0056b3);
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
        
        .action-buttons {
            margin-bottom: 20px;
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
        
        .table {
            border-radius: 10px;
            overflow: hidden;
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
        
        .btn-primary {
            background: #007bff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .doctor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
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
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-item a {
            color: #007bff;
            text-decoration: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        .doctor-details {
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
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Doctor Management</li>
                <?php if ($action != 'list'): ?>
                    <li class="breadcrumb-item active"><?php echo ucfirst($action); ?></li>
                <?php endif; ?>
            </ol>
        </nav>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Doctor Management</h1>
                    <p>Manage doctor profiles, departments, and access permissions</p>
                </div>
                <div>
                    <?php if ($action == 'list'): ?>
                        <a href="?action=add" class="btn btn-light btn-lg">
                            <i class="fa fa-plus"></i> Add New Doctor
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
            <!-- Doctors List -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Doctors (<?php echo count($doctors); ?>)</h5>
                        <div class="search-box">
                            <input type="text" id="doctorSearch" class="form-control" placeholder="Search doctors...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="doctorsTable">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Contact</th>
                                    <th>Experience</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo $doctor['image'] ?: '../assets/images/default-doctor.png'; ?>" alt="Photo" class="doctor-avatar">
                                        </td>
                                        <td>
                                            <strong><?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $doctor['email']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $doctor['department_name'] ?: 'Not Assigned'; ?></span>
                                        </td>
                                        <td><?php echo $doctor['phone']; ?></td>
                                        <td><?php echo substr($doctor['experience'], 0, 50) . (strlen($doctor['experience']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $doctor['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($doctor['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?action=view&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this doctor?')">
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
            <!-- Add/Edit Doctor Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Doctor' : 'Edit Doctor'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>First Name *</label>
                                    <input type="text" name="first_name" class="form-control" required value="<?php echo $doctor['first_name'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" value="<?php echo $doctor['middle_name'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" required value="<?php echo $doctor['last_name'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" class="form-control" required value="<?php echo $doctor['email'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone *</label>
                                    <input type="tel" name="phone" class="form-control" required value="<?php echo $doctor['phone'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($action == 'add'): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Username *</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Password *</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Department</label>
                                    <select name="department_id" class="form-control">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo (isset($doctor['department_id']) && $doctor['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo $dept['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Profile Image</label>
                                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                                    <?php if (isset($doctor['image']) && $doctor['image']): ?>
                                        <small class="text-muted">Current image: <?php echo basename($doctor['image']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Contact Details</label>
                            <textarea name="contact" class="form-control" rows="2"><?php echo $doctor['contact'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo $doctor['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Education</label>
                                    <textarea name="education" class="form-control" rows="3" placeholder="MBBS, MD, etc."><?php echo $doctor['education'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Experience</label>
                                    <textarea name="experience" class="form-control" rows="3" placeholder="Years of experience, previous positions"><?php echo $doctor['experience'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Certificates</label>
                                    <textarea name="certificates" class="form-control" rows="2" placeholder="Medical certificates, licenses"><?php echo $doctor['certificates'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Awards</label>
                                    <textarea name="awards" class="form-control" rows="2" placeholder="Awards and recognitions"><?php echo $doctor['awards'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Vitals/Specialization</label>
                            <textarea name="vitals" class="form-control" rows="2" placeholder="Medical specializations, vital information"><?php echo $doctor['vitals'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-save"></i> <?php echo $action == 'add' ? 'Add Doctor' : 'Update Doctor'; ?>
                            </button>
                            <a href="?" class="btn btn-secondary btn-lg ml-2">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($action == 'view' && isset($doctor)): ?>
            <!-- View Doctor Details -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Doctor Details</h5>
                        <div>
                            <a href="?action=edit&id=<?php echo $doctor['id']; ?>" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="?action=delete&id=<?php echo $doctor['id']; ?>" class="btn btn-danger ml-2" onclick="return confirm('Are you sure?')">
                                <i class="fa fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img src="<?php echo $doctor['image'] ?: '../assets/images/default-doctor.png'; ?>" alt="Photo" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <h4><?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></h4>
                            <p class="text-muted"><?php echo $doctor['department_name'] ?: 'No Department'; ?></p>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="doctor-details">
                                        <h6>Contact Information</h6>
                                        <div class="detail-item">
                                            <div class="detail-label">Email:</div>
                                            <div class="detail-value"><?php echo $doctor['email']; ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Phone:</div>
                                            <div class="detail-value"><?php echo $doctor['phone']; ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Address:</div>
                                            <div class="detail-value"><?php echo $doctor['address'] ?: 'Not provided'; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="doctor-details">
                                        <h6>Professional Information</h6>
                                        <div class="detail-item">
                                            <div class="detail-label">Education:</div>
                                            <div class="detail-value"><?php echo $doctor['education'] ?: 'Not provided'; ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Experience:</div>
                                            <div class="detail-value"><?php echo $doctor['experience'] ?: 'Not provided'; ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">Specialization:</div>
                                            <div class="detail-value"><?php echo $doctor['vitals'] ?: 'Not provided'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($doctor['certificates'] || $doctor['awards']): ?>
                                <div class="row mt-3">
                                    <?php if ($doctor['certificates']): ?>
                                        <div class="col-md-6">
                                            <div class="doctor-details">
                                                <h6>Certificates</h6>
                                                <p><?php echo nl2br($doctor['certificates']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($doctor['awards']): ?>
                                        <div class="col-md-6">
                                            <div class="doctor-details">
                                                <h6>Awards & Recognition</h6>
                                                <p><?php echo nl2br($doctor['awards']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        $('#doctorSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#doctorsTable tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    </script>
</body>
</html>