<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Get user details
$user_details = getUserDetails($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting('site_title', 'Hospital CRM'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo getSetting('favicon', 'assets/img/default-avatar.png'); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Modern CSS -->
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
</head>
<body class="<?php echo getSetting('theme_mode', 'light'); ?>-mode">
    
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <style>
        /* Main Content Layout */
        .main-content {
            margin-left: 280px;
            margin-top: 70px;
            margin-bottom: 60px;
            padding: 30px;
            min-height: calc(100vh - 130px);
            background: #f8f9fa;
            transition: margin-left 0.3s ease;
        }
        
        .dark-mode .main-content {
            background: #121212;
        }
        
        .sidebar-collapsed .main-content {
            margin-left: 70px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin-bottom: 50px;
                padding: 20px 15px;
            }
        }
        </style>
        <?php
        // Route to appropriate dashboard based on role
        switch($user_role) {
            case 'admin':
                include 'dashboards/admin.php';
                break;
            case 'doctor':
                include 'dashboards/doctor.php';
                break;
            case 'patient':
                include 'dashboards/patient.php';
                break;
            case 'nurse':
                include 'dashboards/nurse.php';
                break;
            case 'staff':
                include 'dashboards/staff.php';
                break;
            case 'pharmacy':
                include 'dashboards/pharmacy.php';
                break;
            case 'lab_tech':
                include 'dashboards/lab_tech.php';
                break;
            case 'receptionist':
                include 'dashboards/receptionist.php';
                break;
            case 'intern':
                include 'dashboards/intern.php';
                break;
            default:
                echo '<div class="alert alert-danger">Invalid user role</div>';
        }
        ?>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Modern Application JS -->
    <script src="assets/js/app.js"></script>
</body>
</html>
