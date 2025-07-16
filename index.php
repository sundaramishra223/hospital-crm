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
    <link rel="icon" type="image/x-icon" href="<?php echo getSetting('favicon', 'assets/images/favicon.ico'); ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
</head>
<body class="<?php echo getSetting('theme_mode', 'light'); ?>-mode">
    
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
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
            default:
                echo '<div class="alert alert-danger">Invalid user role</div>';
        }
        ?>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/chart.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>