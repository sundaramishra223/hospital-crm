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
$user_role = $_SESSION['user_role'];
$action = $_GET['action'] ?? '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'update_profile') {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $date_of_birth = $_POST['date_of_birth'];
        $emergency_contact = sanitize($_POST['emergency_contact']);
        $emergency_phone = sanitize($_POST['emergency_phone']);
        $bio = sanitize($_POST['bio']);
        $qualifications = sanitize($_POST['qualifications']);
        $experience = sanitize($_POST['experience']);
        $specialization = sanitize($_POST['specialization']);
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, date_of_birth = ?, emergency_contact = ?, emergency_phone = ?, bio = ?, qualifications = ?, experience = ?, specialization = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $date_of_birth, $emergency_contact, $emergency_phone, $bio, $qualifications, $experience, $specialization, $user_id]);
            
            logActivity($user_id, 'update', "Updated profile information");
            $success_message = "Profile updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
    
    if ($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (!password_verify($current_password, $user_data['password'])) {
            $error_message = "Current password is incorrect!";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match!";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long!";
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                logActivity($user_id, 'update', "Changed password");
                $success_message = "Password changed successfully!";
            } catch (Exception $e) {
                $error_message = "Error changing password: " . $e->getMessage();
            }
        }
    }
    
    if ($action == 'upload_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $upload_dir = '../uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    try {
                        $avatar_url = 'uploads/avatars/' . $new_filename;
                        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                        $stmt->execute([$avatar_url, $user_id]);
                        
                        logActivity($user_id, 'update', "Updated profile picture");
                        $success_message = "Profile picture updated successfully!";
                    } catch (Exception $e) {
                        $error_message = "Error saving avatar: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Error uploading file!";
                }
            } else {
                $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed!";
            }
        } else {
            $error_message = "Please select a valid image file!";
        }
    }
}

// Get user profile data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user activity logs (recent 10)
$stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$recent_activities = $stmt->fetchAll();

// Get user statistics based on role
$user_stats = [];
if ($user_role == 'doctor') {
    // Doctor stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_patients FROM appointments WHERE doctor_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $user_stats['total_patients'] = $stmt->fetch()['total_patients'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_appointments FROM appointments WHERE doctor_id = ?");
    $stmt->execute([$user_id]);
    $user_stats['total_appointments'] = $stmt->fetch()['total_appointments'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_appointments FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE()");
    $stmt->execute([$user_id]);
    $user_stats['today_appointments'] = $stmt->fetch()['today_appointments'];
} elseif ($user_role == 'patient') {
    // Patient stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_appointments FROM appointments WHERE patient_id = ?");
    $stmt->execute([$user_id]);
    $user_stats['total_appointments'] = $stmt->fetch()['total_appointments'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_bills FROM billing WHERE patient_id = ?");
    $stmt->execute([$user_id]);
    $user_stats['total_bills'] = $stmt->fetch()['total_bills'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .profile-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #007bff;
            border: 2px solid white;
            border-radius: 50%;
            padding: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .avatar-upload:hover {
            background: #0056b3;
            transform: scale(1.1);
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .stat-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .activity-item {
            border-left: 3px solid #e9ecef;
            padding-left: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 8px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #007bff;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-user-circle me-2"></i>My Profile</h1>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Header Card -->
                <div class="card profile-card shadow mb-4">
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="avatar-container">
                                    <img src="<?php echo $user['avatar'] ? '../' . $user['avatar'] : '../assets/img/default-avatar.png'; ?>" 
                                         alt="Profile Picture" class="profile-avatar">
                                    <label for="avatar-upload" class="avatar-upload">
                                        <i class="fas fa-camera text-white"></i>
                                        <input type="file" id="avatar-upload" name="avatar" accept="image/*" style="display: none;">
                                    </label>
                                </div>
                                <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                                <p class="mb-0">
                                    <span class="badge bg-light text-dark fs-6">
                                        <i class="fas fa-<?php echo getRoleIcon($user['role']); ?> me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-8 text-start">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><i class="fas fa-envelope me-2"></i><strong>Email:</strong><br><?php echo htmlspecialchars($user['email']); ?></p>
                                        <p><i class="fas fa-phone me-2"></i><strong>Phone:</strong><br><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><i class="fas fa-calendar me-2"></i><strong>Joined:</strong><br><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                                        <p><i class="fas fa-check-circle me-2"></i><strong>Status:</strong><br>
                                            <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <?php if (!empty($user_stats)): ?>
                <div class="row mb-4">
                    <?php if ($user_role == 'doctor'): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <p class="text-muted mb-1">Total Patients</p>
                                            <h3 class="mb-0"><?php echo $user_stats['total_patients']; ?></h3>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <p class="text-muted mb-1">Total Appointments</p>
                                            <h3 class="mb-0"><?php echo $user_stats['total_appointments']; ?></h3>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <p class="text-muted mb-1">Today's Appointments</p>
                                            <h3 class="mb-0"><?php echo $user_stats['today_appointments']; ?></h3>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($user_role == 'patient'): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <p class="text-muted mb-1">My Appointments</p>
                                            <h3 class="mb-0"><?php echo $user_stats['total_appointments']; ?></h3>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-check fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card stat-card h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <p class="text-muted mb-1">Total Bills</p>
                                            <h3 class="mb-0"><?php echo $user_stats['total_bills']; ?></h3>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-invoice fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Profile Information Tabs -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal-info">
                                            <i class="fas fa-user me-1"></i>Personal Information
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#security">
                                            <i class="fas fa-lock me-1"></i>Security
                                        </button>
                                    </li>
                                    <?php if (in_array($user_role, ['doctor', 'nurse', 'staff'])): ?>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#professional">
                                            <i class="fas fa-briefcase me-1"></i>Professional
                                        </button>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- Personal Information Tab -->
                                    <div class="tab-pane fade show active" id="personal-info">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_profile">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="name" class="form-label">Full Name *</label>
                                                        <input type="text" class="form-control" id="name" name="name" 
                                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Email Address *</label>
                                                        <input type="email" class="form-control" id="email" name="email" 
                                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="phone" class="form-label">Phone Number</label>
                                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                                               value="<?php echo $user['date_of_birth']; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label for="address" class="form-label">Address</label>
                                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="emergency_contact" class="form-label">Emergency Contact Name</label>
                                                        <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" 
                                                               value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="emergency_phone" class="form-label">Emergency Contact Phone</label>
                                                        <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone" 
                                                               value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label for="bio" class="form-label">Bio / About Me</label>
                                                        <textarea class="form-control" id="bio" name="bio" rows="4" 
                                                                  placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Update Profile
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Security Tab -->
                                    <div class="tab-pane fade" id="security">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="change_password">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label for="current_password" class="form-label">Current Password *</label>
                                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="new_password" class="form-label">New Password *</label>
                                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                                               minlength="6" required>
                                                        <div class="form-text">Password must be at least 6 characters long.</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                               minlength="6" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-key me-1"></i>Change Password
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Professional Tab -->
                                    <?php if (in_array($user_role, ['doctor', 'nurse', 'staff'])): ?>
                                    <div class="tab-pane fade" id="professional">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_profile">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="qualifications" class="form-label">Qualifications</label>
                                                        <textarea class="form-control" id="qualifications" name="qualifications" rows="3" 
                                                                  placeholder="MBBS, MD, etc."><?php echo htmlspecialchars($user['qualifications'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="specialization" class="form-label">Specialization</label>
                                                        <input type="text" class="form-control" id="specialization" name="specialization" 
                                                               value="<?php echo htmlspecialchars($user['specialization'] ?? ''); ?>" 
                                                               placeholder="Cardiology, Surgery, etc.">
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label for="experience" class="form-label">Experience</label>
                                                        <textarea class="form-control" id="experience" name="experience" rows="4" 
                                                                  placeholder="Years of experience, previous positions, etc."><?php echo htmlspecialchars($user['experience'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-briefcase me-1"></i>Update Professional Info
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Sidebar -->
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Recent Activity
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_activities)): ?>
                                    <p class="text-muted text-center">No recent activity</p>
                                <?php else: ?>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="activity-item">
                                            <strong><?php echo ucfirst($activity['action']); ?></strong>
                                            <p class="text-muted mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Avatar Upload Form (Hidden) -->
    <form id="avatar-form" method="POST" enctype="multipart/form-data" style="display: none;">
        <input type="hidden" name="action" value="upload_avatar">
    </form>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle avatar upload
        document.getElementById('avatar-upload').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('action', 'upload_avatar');
                formData.append('avatar', this.files[0]);
                
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error uploading avatar');
                });
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

<?php
function getRoleIcon($role) {
    $icons = [
        'admin' => 'user-shield',
        'doctor' => 'user-md',
        'nurse' => 'user-nurse',
        'patient' => 'user',
        'staff' => 'users',
        'pharmacy' => 'pills',
        'lab_tech' => 'flask',
        'receptionist' => 'desk',
        'intern' => 'graduation-cap'
    ];
    return $icons[$role] ?? 'user';
}
?>
