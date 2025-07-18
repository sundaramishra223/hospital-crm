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
    padding: 20px;
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
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
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

.quick-stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 12px;
    color: var(--primary-color);
    font-size: 14px;
    font-weight: 500;
}

.quick-stat-item i {
    font-size: 16px;
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
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: white;
}

.action-btn.primary {
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
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

.action-btn.success {
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.action-btn.success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
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

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.stat-card.patients {
    border-left: 4px solid #667eea;
}

.stat-card.appointments {
    border-left: 4px solid #28a745;
}

.stat-card.revenue {
    border-left: 4px solid #ffc107;
}

.stat-card.beds {
    border-left: 4px solid #dc3545;
}

.stat-icon {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    margin-bottom: 20px;
}

.stat-card.patients .stat-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.stat-card.appointments .stat-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.stat-card.revenue .stat-icon {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.stat-card.beds .stat-icon {
    background: linear-gradient(135deg, #dc3545, #fd7e14);
}

.icon-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: inherit;
    border-radius: 15px;
    opacity: 0.3;
    filter: blur(10px);
    z-index: -1;
}

.stat-number {
    font-size: 36px;
    font-weight: 700;
    color: #333;
    margin-bottom: 5px;
    line-height: 1;
}

.dark-mode .stat-number {
    color: #fff;
}

.stat-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dark-mode .stat-label {
    color: #ccc;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
}

.stat-change.positive {
    color: #28a745;
}

.stat-change.negative {
    color: #dc3545;
}

.stat-change i {
    font-size: 10px;
}

.stat-chart {
    position: absolute;
    top: 20px;
    right: 20px;
    opacity: 0.1;
}

/* System Health Section */
.system-health-section {
    margin-bottom: 30px;
}

.health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.health-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
}

.dark-mode .health-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.health-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.health-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
}

.health-card.database .health-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.health-card.server .health-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.health-card.backup .health-icon {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.health-card.security .health-icon {
    background: linear-gradient(135deg, #dc3545, #fd7e14);
}

.health-info {
    flex: 1;
}

.health-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.dark-mode .health-info h4 {
    color: #fff;
}

.health-status {
    font-size: 12px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 8px;
    display: inline-block;
    margin-bottom: 8px;
}

.health-status.online {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.health-status.warning {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.health-status.offline {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.health-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.health-details span {
    font-size: 11px;
    color: #666;
}

.dark-mode .health-details span {
    color: #ccc;
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

.chart-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.chart-title p {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.chart-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.chart-filter {
    padding: 8px 12px;
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 8px;
    background: rgba(102, 126, 234, 0.05);
    color: #333;
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
}

.chart-filter:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.refresh-btn {
    width: 35px;
    height: 35px;
    border: none;
    border-radius: 8px;
    background: rgba(102, 126, 234, 0.1);
    color: var(--primary-color);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.refresh-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: rotate(180deg);
}

.chart-body {
    height: 300px;
    position: relative;
}

/* Management Cards */
.management-section {
    margin-bottom: 30px;
}

.management-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.dark-mode .management-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.management-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.management-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.management-card .card-header h4 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dark-mode .management-card .card-header h4 {
    color: #fff;
}

.management-card .card-header .badge {
    background-color: var(--primary-color);
    color: white;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.dark-mode .management-card .card-header .badge {
    background-color: #764ba2;
}

.management-card .card-body {
    flex: 1;
}

.management-card .card-body p {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.dark-mode .management-card .card-body p {
    color: #ccc;
}

.management-card .action-buttons {
    display: flex;
    gap: 10px;
}

.management-card .action-buttons .btn {
    flex: 1;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
}

.dark-mode .management-card .action-buttons .btn {
    background-color: #764ba2;
    border-color: #764ba2;
}

/* Additional Management Modules */
.modules-section {
    margin-bottom: 30px;
}

.modules-section h3 {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}

.dark-mode .modules-section h3 {
    color: #fff;
}

.modules-section .row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.module-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
}

.dark-mode .module-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.module-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.module-card i {
    font-size: 40px;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.dark-mode .module-card i {
    color: #764ba2;
}

.module-card h5 {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.dark-mode .module-card h5 {
    color: #fff;
}

.module-card p {
    font-size: 13px;
    color: #666;
    margin-bottom: 15px;
}

.dark-mode .module-card p {
    color: #ccc;
}

.module-card .btn {
    width: 100%;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    color: white;
    transition: all 0.3s ease;
}

.dark-mode .module-card .btn {
    background: linear-gradient(135deg, #764ba2, #667eea);
}

.module-card .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.dark-mode .module-card .btn:hover {
    background: linear-gradient(135deg, #764ba2, #667eea);
}

/* Recent Activities */
.activities-section {
    margin-bottom: 30px;
}

.activity-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.dark-mode .activity-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.activity-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.activity-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.activity-card .card-header h4 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dark-mode .activity-card .card-header h4 {
    color: #fff;
}

.activity-card .card-body {
    overflow-y: auto;
    max-height: 300px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.dark-mode .activity-item {
    border-bottom-color: rgba(255, 255, 255, 0.05);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: white;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
}

.dark-mode .activity-icon {
    background: linear-gradient(135deg, #764ba2, #667eea);
}

.activity-content {
    flex: 1;
}

.activity-content p {
    font-size: 14px;
    color: #333;
    margin: 0 0 5px 0;
}

.dark-mode .activity-content p {
    color: #ccc;
}

.activity-content strong {
    color: #333;
}

.dark-mode .activity-content strong {
    color: #fff;
}

.activity-content small {
    font-size: 11px;
    color: #666;
}

.dark-mode .activity-content small {
    color: #ccc;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .health-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 991.98px) {
    .admin-dashboard {
        padding: 15px;
    }
    
    .dashboard-header {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 20px;
    }
    
    .dashboard-title {
        font-size: 28px;
    }
    
    .header-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-number {
        font-size: 28px;
    }
}

@media (max-width: 768px) {
    .dashboard-title {
        font-size: 24px;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .quick-stats {
        gap: 10px;
    }
    
    .quick-stat-item {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .action-btn {
        padding: 10px 16px;
        font-size: 13px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .health-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .health-card {
        padding: 15px;
    }
    
    .health-icon {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .admin-dashboard {
        padding: 10px;
    }
    
    .dashboard-header {
        padding: 15px;
    }
    
    .dashboard-title {
        font-size: 20px;
    }
    
    .welcome-text {
        font-size: 14px;
    }
    
    .header-actions {
        gap: 8px;
    }
    
    .action-btn {
        padding: 8px 12px;
        font-size: 12px;
    }
    
    .action-btn span {
        display: none;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-number {
        font-size: 24px;
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
                </div>
                <div class="quick-stats">
                    <div class="quick-stat-item">
                        <i class="fa fa-clock-o"></i>
                        <span>Last Login: <?php echo date('M d, h:i A', strtotime($_SESSION['last_login'] ?? 'now')); ?></span>
                    </div>
                    <div class="quick-stat-item">
                        <i class="fa fa-users"></i>
                        <span><?php echo $stats['total_patients']; ?> Total Patients</span>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <button class="action-btn primary" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                        <i class="fa fa-plus"></i>
                        <span>Quick Add</span>
                    </button>
                    <button class="action-btn secondary" onclick="window.location='modules/settings.php'">
                        <i class="fa fa-cog"></i>
                        <span>Settings</span>
                    </button>
                    <button class="action-btn success" onclick="window.location='modules/reports.php'">
                        <i class="fa fa-chart-bar"></i>
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
                <div class="stat-icon">
                    <i class="fa fa-users"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['total_patients']); ?></div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-change positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>+<?php echo $stats['new_patients_today'] ?? 0; ?> today</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="patientsChart" width="60" height="40"></canvas>
                </div>
            </div>
            
            <div class="stat-card appointments">
                <div class="stat-icon">
                    <i class="fa fa-calendar-check"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['today_appointments']); ?></div>
                    <div class="stat-label">Today's Appointments</div>
                    <div class="stat-change <?php echo $stats['appointments_change'] >= 0 ? 'positive' : 'negative'; ?>">
                        <i class="fa fa-arrow-<?php echo $stats['appointments_change'] >= 0 ? 'up' : 'down'; ?>"></i>
                        <span><?php echo abs($stats['appointments_change'] ?? 0); ?>% from yesterday</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="appointmentsChart" width="60" height="40"></canvas>
                </div>
            </div>
            
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fa fa-rupee"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                    <div class="stat-change positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>+<?php echo $stats['revenue_growth'] ?? 0; ?>% this month</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="revenueChart" width="60" height="40"></canvas>
                </div>
            </div>
            
            <div class="stat-card beds">
                <div class="stat-icon">
                    <i class="fa fa-bed"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['occupied_beds']); ?></div>
                    <div class="stat-label">Occupied Beds</div>
                    <div class="stat-change">
                        <span><?php echo $stats['bed_occupancy'] ?? 0; ?>% occupancy rate</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="bedsChart" width="60" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- System Health Overview -->
    <div class="system-health-section">
        <div class="health-grid">
            <div class="health-card database">
                <div class="health-icon">
                    <i class="fa fa-database"></i>
                </div>
                <div class="health-info">
                    <h4>Database</h4>
                    <div class="health-status online">Online</div>
                    <div class="health-details">
                        <span>Uptime: 99.9%</span>
                        <span>Size: <?php echo formatBytes($system_health['db_size'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="health-card server">
                <div class="health-icon">
                    <i class="fa fa-server"></i>
                </div>
                <div class="health-info">
                    <h4>Server</h4>
                    <div class="health-status online">Healthy</div>
                    <div class="health-details">
                        <span>CPU: <?php echo $system_health['cpu_usage'] ?? 0; ?>%</span>
                        <span>Memory: <?php echo $system_health['memory_usage'] ?? 0; ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="health-card backup">
                <div class="health-icon">
                    <i class="fa fa-cloud"></i>
                </div>
                <div class="health-info">
                    <h4>Backup</h4>
                    <div class="health-status <?php echo $system_health['backup_status'] ? 'online' : 'warning'; ?>">
                        <?php echo $system_health['backup_status'] ? 'Updated' : 'Pending'; ?>
                    </div>
                    <div class="health-details">
                        <span>Last: <?php echo $system_health['last_backup'] ?? 'Never'; ?></span>
                        <span>Size: <?php echo formatBytes($system_health['backup_size'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="health-card security">
                <div class="health-icon">
                    <i class="fa fa-shield"></i>
                </div>
                <div class="health-info">
                    <h4>Security</h4>
                    <div class="health-status online">Secure</div>
                    <div class="health-details">
                        <span>SSL: Active</span>
                        <span>Firewall: Enabled</span>
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
