<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success_message = 'You have been successfully logged out.';
}

if ($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    
    if (empty($username) || empty($password) || empty($role)) {
        $error_message = 'All fields are required';
    } else {
        $user = authenticateUser($username, $password, $role);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['hospital_id'] = $user['hospital_id'];
            
            // Log login activity
            logActivity($user['id'], 'login', 'User logged in');
            
            header('Location: index.php');
            exit();
        } else {
            $error_message = 'Invalid credentials or role not enabled';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo getSetting('site_title', 'Hospital CRM'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo getSetting('favicon', 'assets/images/favicon.ico'); ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="assets/css/login.css" rel="stylesheet">
</head>
<body class="login-page">
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="<?php echo getSetting('logo', 'assets/images/logo.png'); ?>" alt="Logo" class="logo">
                <h2><?php echo getSetting('site_title', 'Hospital CRM'); ?></h2>
                <p>Multi-Role Healthcare Management System</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="role">Select Role</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="">Choose Role</option>
                        <option value="admin">Admin</option>
                        <option value="doctor">Doctor</option>
                        <option value="patient">Patient</option>
                        <option value="nurse">Nurse</option>
                        <option value="staff">Staff</option>
                        <option value="pharmacy">Pharmacy</option>
                        <option value="lab_tech">Lab Technician</option>
                        <option value="receptionist">Receptionist</option>
                        <option value="intern">Intern</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Hospital CRM. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
