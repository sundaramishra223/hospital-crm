<?php
// Admin dashboard - Only accessible by admin role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get dashboard statistics
$stats = getAdminStats();
$advanced_stats = getAdvancedStats();
$system_health = getSystemHealth();
$departments = getDepartments();
$recent_activities = getRecentActivities(10);
$hospitals = getHospitals();
$feedbacks = getFeedbacks(5);
$home_visits = getHomeVisitRequests();
$video_consultations = getVideoConsultations();
$theme_color = getSetting('theme_color', '#667eea');
?>

<style>
/* Modern Admin Dashboard Styles */
.admin-dashboard {
    padding: 30px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.dark-mode .admin-dashboard {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d3748 100%);
}

/* Dashboard Header */
.dashboard-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dark-mode .dashboard-header {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 30px;
}

.header-left {
    flex: 1;
}

.dashboard-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.dashboard-title i {
    font-size: 28px;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.welcome-text {
    font-size: 16px;
    color: #666;
    margin: 0 0 20px 0;
    line-height: 1.6;
}

.dark-mode .welcome-text {
    color: #ccc;
}

.quick-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.quick-stat {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 12px;
    font-size: 14px;
    color: var(--primary-color);
    font-weight: 500;
}

.quick-stat i {
    font-size: 12px;
}

.header-right {
    display: flex;
    align-items: center;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.action-btn:hover::before {
    left: 100%;
}

.action-btn.primary {
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.action-btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.action-btn.secondary {
    background: rgba(102, 126, 234, 0.1);
    color: var(--primary-color);
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.action-btn.secondary:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

.action-btn.info {
    background: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
    border: 1px solid rgba(23, 162, 184, 0.2);
}

.action-btn.info:hover {
    background: #17a2b8;
    color: white;
    transform: translateY(-2px);
}

.btn-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Statistics Section */
.stats-section {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.dark-mode .stat-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), #764ba2);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    position: relative;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
}

.icon-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    border-radius: 15px;
    opacity: 0.3;
    filter: blur(10px);
    z-index: -1;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.stat-trend.positive {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.stat-trend.negative {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.stat-trend.neutral {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin: 0 0 8px 0;
    line-height: 1;
}

.dark-mode .stat-number {
    color: #fff;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin: 0 0 15px 0;
    font-weight: 500;
}

.dark-mode .stat-label {
    color: #ccc;
}

.stat-progress {
    width: 100%;
    height: 6px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
    overflow: hidden;
}

.dark-mode .stat-progress {
    background: rgba(255, 255, 255, 0.1);
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), #764ba2);
    border-radius: 3px;
    transition: width 1s ease;
}

/* Charts Section */
.charts-section {
    margin-bottom: 30px;
}

.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.chart-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.dark-mode .chart-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.chart-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.chart-title h4 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 5px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dark-mode .chart-title h4 {
    color: #fff;
}

.chart-title p {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.dark-mode .chart-title p {
    color: #ccc;
}

.chart-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.chart-filter {
    padding: 8px 12px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    background: white;
    font-size: 14px;
    color: #333;
    outline: none;
    transition: all 0.3s ease;
}

.dark-mode .chart-filter {
    background: #333;
    border-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.chart-filter:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.refresh-btn {
    width: 35px;
    height: 35px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    background: white;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.dark-mode .refresh-btn {
    background: #333;
    border-color: rgba(255, 255, 255, 0.1);
    color: #ccc;
}

.refresh-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
    transform: scale(1.05);
}

.chart-body {
    height: 300px;
    position: relative;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .charts-grid .side-chart {
        order: -1;
    }
}

@media (max-width: 768px) {
    .admin-dashboard {
        padding: 20px;
    }
    
    .dashboard-header {
        padding: 20px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 20px;
    }
    
    .header-actions {
        width: 100%;
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-stats {
        justify-content: center;
    }
    
    .dashboard-title {
        font-size: 24px;
        justify-content: center;
    }
    
    .welcome-text {
        text-align: center;
    }
}

@media (max-width: 480px) {
    .admin-dashboard {
        padding: 15px;
    }
    
    .dashboard-header {
        padding: 15px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .chart-card {
        padding: 20px;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="admin-dashboard" style="--primary-color: <?php echo $theme_color; ?>;">
    <!-- Modern Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <div class="welcome-section">
                    <h1 class="dashboard-title">
                        <i class="fa fa-dashboard"></i>
                        Admin Dashboard
                    </h1>
                    <p class="welcome-text">Welcome back, <strong><?php echo $_SESSION['user_name']; ?></strong>! Here's what's happening today.</p>
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <i class="fa fa-clock-o"></i>
                            <span><?php echo date('l, F d, Y'); ?></span>
                        </div>
                        <div class="quick-stat">
                            <i class="fa fa-users"></i>
                            <span><?php echo $stats['total_patients']; ?> Patients</span>
                        </div>
                        <div class="quick-stat">
                            <i class="fa fa-calendar"></i>
                            <span><?php echo $stats['today_appointments']; ?> Appointments</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <button class="action-btn primary" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                        <div class="btn-icon">
                            <i class="fa fa-plus"></i>
                        </div>
                        <span>Quick Add</span>
                    </button>
                    <button class="action-btn secondary" onclick="window.location='modules/settings.php'">
                        <div class="btn-icon">
                            <i class="fa fa-cog"></i>
                        </div>
                        <span>Settings</span>
                    </button>
                    <button class="action-btn info" onclick="window.location='modules/reports.php'">
                        <div class="btn-icon">
                            <i class="fa fa-chart-bar"></i>
                        </div>
                        <span>Reports</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card patients">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fa fa-users"></i>
                        <div class="icon-glow"></div>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>+12%</span>
                    </div>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($stats['total_patients']); ?></h3>
                    <p class="stat-label">Total Patients</p>
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: 85%"></div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card appointments">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fa fa-calendar-check"></i>
                        <div class="icon-glow"></div>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>+8%</span>
                    </div>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($stats['today_appointments']); ?></h3>
                    <p class="stat-label">Today's Appointments</p>
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: 72%"></div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card revenue">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fa fa-rupee"></i>
                        <div class="icon-glow"></div>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>+15%</span>
                    </div>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo formatCurrency($stats['monthly_revenue']); ?></h3>
                    <p class="stat-label">Monthly Revenue</p>
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: 90%"></div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card beds">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fa fa-bed"></i>
                        <div class="icon-glow"></div>
                    </div>
                    <div class="stat-trend neutral">
                        <i class="fa fa-minus"></i>
                        <span>0%</span>
                    </div>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($stats['occupied_beds']); ?></h3>
                    <p class="stat-label">Occupied Beds</p>
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: 65%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Charts Section -->
    <div class="charts-section">
        <div class="charts-grid">
            <div class="chart-card main-chart">
                <div class="chart-header">
                    <div class="chart-title">
                        <h4><i class="fa fa-chart-line"></i> Revenue Analytics</h4>
                        <p>Track your hospital's financial performance</p>
                    </div>
                    <div class="chart-controls">
                        <select id="revenueFilter" class="chart-filter">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                        <button class="refresh-btn" onclick="refreshRevenueChart()">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card side-chart">
                <div class="chart-header">
                    <div class="chart-title">
                        <h4><i class="fa fa-chart-pie"></i> Staff Distribution</h4>
                        <p>Overview of your team structure</p>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="staffChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Cards -->
    <div class="management-section">
        <div class="row">
            <div class="col-md-4">
                <div class="management-card doctors">
                    <div class="card-header">
                        <h4><i class="fa fa-user-md"></i> Doctor Management</h4>
                        <span class="badge badge-primary"><?php echo $stats['users']['doctor'] ?? 0; ?></span>
                    </div>
                    <div class="card-body">
                        <p>Add, edit, and manage doctor profiles with complete details</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/doctors.php'">
                                Manage Doctors
                            </button>
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addDoctorModal">
                                Add Doctor
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="management-card patients">
                    <div class="card-header">
                        <h4><i class="fa fa-users"></i> Patient Management</h4>
                        <span class="badge badge-info"><?php echo $stats['total_patients']; ?></span>
                    </div>
                    <div class="card-body">
                        <p>View and manage patient records and medical history</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/patients.php'">
                                Manage Patients
                            </button>
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addPatientModal">
                                Add Patient
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="management-card departments">
                    <div class="card-header">
                        <h4><i class="fa fa-building"></i> Department Management</h4>
                        <span class="badge badge-warning"><?php echo count($departments); ?></span>
                    </div>
                    <div class="card-body">
                        <p>Organize staff into departments and manage assignments</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/departments.php'">
                                Manage Departments
                            </button>
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addDepartmentModal">
                                Add Department
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Management Modules -->
    <div class="modules-section">
        <h3>System Modules</h3>
        <div class="row">
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-bed"></i>
                    <h5>Bed Management</h5>
                    <p>Manage hospital beds and occupancy</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/beds.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-flask"></i>
                    <h5>Lab Management</h5>
                    <p>Manage lab tests and results</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/lab.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-pills"></i>
                    <h5>Pharmacy</h5>
                    <p>Manage medicines and inventory</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/pharmacy.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-money"></i>
                    <h5>Billing System</h5>
                    <p>Manage bills and payments</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/billing.php'">
                        Manage
                    </button>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-ambulance"></i>
                    <h5>Ambulance</h5>
                    <p>Manage ambulance services</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/ambulance.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-graduation-cap"></i>
                    <h5>Intern System</h5>
                    <p>Manage intern assignments</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/interns.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-clock-o"></i>
                    <h5>Shift Management</h5>
                    <p>Manage staff shifts and schedules</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/shifts.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-envelope"></i>
                    <h5>Communication</h5>
                    <p>SMS, Email, and notifications</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/communication.php'">
                        Manage
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="activities-section">
        <div class="row">
            <div class="col-md-12">
                <div class="activity-card">
                    <div class="card-header">
                        <h4><i class="fa fa-history"></i> Recent Activities</h4>
                        <button class="btn btn-sm btn-outline-primary" onclick="window.location='modules/logs.php'">
                            View All Logs
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php if (!empty($recent_activities)): ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fa fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p><strong><?php echo $activity['user_name']; ?></strong> <?php echo $activity['description']; ?></p>
                                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No recent activities found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include modals for quick actions -->
<?php include 'includes/admin_modals.php'; ?>

<script>
// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeRevenueChart();
    initializeStaffChart();
});

// Revenue Chart
function initializeRevenueChart() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(getRevenueChartLabels()); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode(getRevenueChartData()); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
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

// Staff Distribution Chart
function initializeStaffChart() {
    const ctx = document.getElementById('staffChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($stats['users'] ?? [])); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($stats['users'] ?? [])); ?>,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF6384',
                    '#C9CBCF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
</script>
