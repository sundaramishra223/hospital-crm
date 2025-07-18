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
    <!-- Additional Modern CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?php echo getSetting('theme_mode', 'light'); ?>-mode">
    
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <style>
        /* Modern Main Content Layout */
        .main-content {
            margin-left: 280px;
            margin-top: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
        }
        
        .dark-mode .main-content {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d3748 100%);
        }
        
        .sidebar-collapsed .main-content {
            margin-left: 70px;
        }
        
        /* Dashboard Container */
        .dashboard-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Page Header */
        .page-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark-mode .page-header {
            background: rgba(26, 26, 26, 0.9);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .dark-mode .page-title {
            color: #f7fafc;
        }
        
        .page-title i {
            color: #667eea;
            font-size: 24px;
        }
        
        .page-subtitle {
            color: #718096;
            font-size: 16px;
            margin: 8px 0 0 0;
            font-weight: 400;
        }
        
        .dark-mode .page-subtitle {
            color: #a0aec0;
        }
        
        /* Breadcrumb */
        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .breadcrumb-item {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .breadcrumb-item:hover {
            color: #5a67d8;
        }
        
        .breadcrumb-separator {
            color: #cbd5e0;
        }
        
        .breadcrumb-current {
            color: #718096;
            font-weight: 500;
        }
        
        /* Dashboard Content */
        .dashboard-content {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-height: calc(100vh - 200px);
        }
        
        .dark-mode .dashboard-content {
            background: rgba(26, 26, 26, 0.9);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Loading Animation */
        .dashboard-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Error Styling */
        .dashboard-error {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border: 1px solid #fc8181;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            color: #c53030;
            text-align: center;
        }
        
        .dark-mode .dashboard-error {
            background: linear-gradient(135deg, #742a2a 0%, #9b2c2c 100%);
            border-color: #e53e3e;
            color: #fed7d7;
        }
        
        /* Success Styling */
        .dashboard-success {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            border: 1px solid #68d391;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            color: #22543d;
            text-align: center;
        }
        
        .dark-mode .dashboard-success {
            background: linear-gradient(135deg, #22543d 0%, #38a169 100%);
            border-color: #48bb78;
            color: #c6f6d5;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-container {
                padding: 20px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .dashboard-content {
                padding: 20px;
            }
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
            
            .dashboard-container {
                padding: 15px;
            }
            
            .page-title {
                font-size: 24px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
            }
            
            .page-header {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .dashboard-content {
                padding: 15px;
            }
            
            .page-title {
                font-size: 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        
        /* Smooth Transitions */
        .dashboard-container * {
            transition: all 0.3s ease;
        }
        
        /* Custom Scrollbar */
        .dashboard-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .dashboard-content::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .dashboard-content::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.3);
            border-radius: 3px;
        }
        
        .dashboard-content::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.5);
        }
        </style>
        
        <div class="dashboard-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fa fa-dashboard"></i>
                    Dashboard
                </h1>
                <p class="page-subtitle">
                    Welcome back, <?php echo $user_details['name']; ?>! Here's what's happening today.
                </p>
                <nav class="breadcrumb-nav">
                    <a href="index.php" class="breadcrumb-item">Home</a>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-current">Dashboard</span>
                </nav>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
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
                        echo '<div class="dashboard-error">
                                <i class="fa fa-exclamation-triangle"></i>
                                <h4>Invalid User Role</h4>
                                <p>The system could not determine your role. Please contact the administrator.</p>
                              </div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/chart.min.js"></script>
    <script src="assets/js/app.js"></script>
    
    <script>
    // Enhanced Dashboard Functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth page transitions
        document.body.style.opacity = '0';
        setTimeout(function() {
            document.body.style.transition = 'opacity 0.5s ease';
            document.body.style.opacity = '1';
        }, 100);
        
        // Auto-refresh dashboard data (optional)
        setInterval(function() {
            // You can add AJAX calls here to refresh dashboard data
            console.log('Dashboard data refreshed');
        }, 300000); // Refresh every 5 minutes
        
        // Enhanced error handling
        window.addEventListener('error', function(e) {
            console.error('Dashboard error:', e.error);
        });
        
        // Responsive sidebar toggle
        var sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-open');
            });
        }
        
        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991.98) {
                if (!e.target.closest('.main-sidebar') && !e.target.closest('#sidebarToggle')) {
                    document.body.classList.remove('sidebar-open');
                }
            }
        });
    });
    </script>
</body>
</html>
