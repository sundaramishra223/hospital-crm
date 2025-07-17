<?php
// Demo file to showcase the modern UI without database requirements
session_start();

// Mock session data
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'Dr. John Smith';

// Include functions
require_once 'includes/functions_demo.php';

// Mock user details
$user_details = getUserDetails($_SESSION['user_id']);
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
<body class="light-mode">
    
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
            background: var(--bg-secondary);
            transition: margin-left 0.3s ease;
        }
        
        .dark-mode .main-content {
            background: var(--bg-dark-secondary);
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
        <?php include 'dashboards/admin.php'; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Modern Application JS -->
    <script src="assets/js/app.js"></script>
</body>
</html>