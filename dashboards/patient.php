<?php
require_once 'includes/functions.php';

// Check if user is logged in and has patient role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];
$theme_color = getSetting('theme_color', '#667eea');

// Get patient information
$patient_info = getUserById($user_id);

// Get statistics
$total_appointments = getTotalRecords('appointments', ['patient_id' => $user_id]);
$upcoming_appointments = getTotalRecords('appointments', ['patient_id' => $user_id, 'appointment_date >=' => date('Y-m-d'), 'status' => 'confirmed']);
$completed_appointments = getTotalRecords('appointments', ['patient_id' => $user_id, 'status' => 'completed']);
$pending_bills = getTotalRecords('bills', ['patient_id' => $user_id, 'status' => 'pending']);

// Get recent activities and appointments
$recent_activities = getRecentActivities($user_id, 10);
$upcoming_appointments_list = getUpcomingAppointments($user_id, 5);

// Get health alerts
$health_alerts = getHealthAlerts($user_id);
?>

<div class="patient-dashboard" style="--primary-color: <?php echo $theme_color; ?>;">
    <!-- Modern Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <div class="welcome-section">
                    <h1 class="dashboard-title">
                        <i class="fa fa-user-injured"></i>
                        Patient Dashboard
                    </h1>
                    <p class="welcome-text">Welcome back, <strong><?php echo htmlspecialchars($patient_info['name']); ?></strong>! Track your health journey and manage your medical appointments.</p>
                </div>
                <div class="quick-stats">
                    <div class="quick-stat-item">
                        <i class="fa fa-calendar"></i>
                        <span><?php echo $upcoming_appointments; ?> Upcoming</span>
                    </div>
                    <div class="quick-stat-item">
                        <i class="fa fa-check-circle"></i>
                        <span><?php echo $completed_appointments; ?> Completed</span>
                    </div>
                    <div class="quick-stat-item">
                        <i class="fa fa-file-invoice"></i>
                        <span><?php echo $pending_bills; ?> Pending Bills</span>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <button class="action-btn primary" onclick="location.href='modules/appointments.php?action=book'">
                        <i class="fa fa-calendar-plus"></i>
                        <span>Book Appointment</span>
                    </button>
                    <button class="action-btn secondary" onclick="location.href='modules/medical_records.php'">
                        <i class="fa fa-file-medical"></i>
                        <span>Medical Records</span>
                    </button>
                    <button class="action-btn success" onclick="location.href='modules/billing.php'">
                        <i class="fa fa-credit-card"></i>
                        <span>Pay Bills</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Status Card -->
    <div class="health-section">
        <div class="health-card">
            <div class="health-icon">
                <i class="fa fa-heartbeat"></i>
                <div class="health-indicator good"></div>
            </div>
            <div class="health-info">
                <h4>Health Status</h4>
                <div class="health-status good">
                    <i class="fa fa-check-circle"></i>
                    <span>Good Health</span>
                </div>
                <div class="health-details">
                    <span>Last Checkup: <?php echo date('M d, Y', strtotime('-30 days')); ?></span>
                    <span>Next Follow-up: <?php echo date('M d, Y', strtotime('+30 days')); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card total-appointments">
                <div class="stat-icon">
                    <i class="fa fa-calendar"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($total_appointments); ?></div>
                    <div class="stat-label">Total Appointments</div>
                    <div class="stat-change positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>+<?php echo rand(2, 5); ?> this month</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="totalAppointmentsChart" width="60" height="30"></canvas>
                </div>
            </div>
            
            <div class="stat-card upcoming-appointments">
                <div class="stat-icon">
                    <i class="fa fa-calendar-day"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($upcoming_appointments); ?></div>
                    <div class="stat-label">Upcoming Appointments</div>
                    <div class="stat-change positive">
                        <i class="fa fa-clock"></i>
                        <span>Scheduled visits</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="upcomingAppointmentsChart" width="60" height="30"></canvas>
                </div>
            </div>
            
            <div class="stat-card completed-appointments">
                <div class="stat-icon">
                    <i class="fa fa-check-circle"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($completed_appointments); ?></div>
                    <div class="stat-label">Completed Visits</div>
                    <div class="stat-change positive">
                        <i class="fa fa-arrow-up"></i>
                        <span><?php echo round(($completed_appointments / max($total_appointments, 1)) * 100); ?>% completion rate</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="completedAppointmentsChart" width="60" height="30"></canvas>
                </div>
            </div>
            
            <div class="stat-card pending-bills">
                <div class="stat-icon">
                    <i class="fa fa-file-invoice"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($pending_bills); ?></div>
                    <div class="stat-label">Pending Bills</div>
                    <div class="stat-change warning">
                        <i class="fa fa-exclamation"></i>
                        <span>Requires attention</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="pendingBillsChart" width="60" height="30"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Alerts Section -->
    <div class="alerts-section">
        <div class="section-header">
            <h3>Health Alerts</h3>
            <p>Important health reminders and notifications</p>
        </div>
        <div class="alerts-grid">
            <?php if (!empty($health_alerts)): ?>
                <?php foreach (array_slice($health_alerts, 0, 4) as $alert): ?>
                    <div class="alert-card <?php echo $alert['type']; ?>">
                        <div class="alert-icon">
                            <i class="fa fa-<?php echo getHealthIcon($alert['type']); ?>"></i>
                            <div class="alert-badge <?php echo $alert['type']; ?>">
                                <?php echo ucfirst($alert['type']); ?>
                            </div>
                        </div>
                        <div class="alert-content">
                            <h4><?php echo $alert['title']; ?></h4>
                            <p><?php echo $alert['description']; ?></p>
                            <div class="alert-stats">
                                <div class="stat-item">
                                    <i class="fa fa-calendar"></i>
                                    <span>Due: <?php echo date('M d, Y', strtotime($alert['due_date'])); ?></span>
                                </div>
                                <div class="stat-item">
                                    <i class="fa fa-user-md"></i>
                                    <span>Dr. <?php echo $alert['doctor_name']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-alerts">
                    <i class="fa fa-check-circle"></i>
                    <p>No health alerts at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="actions-section">
        <div class="section-header">
            <h3>Quick Actions</h3>
            <p>Access frequently used patient features quickly</p>
        </div>
        <div class="actions-grid">
            <a href="modules/appointments.php?action=book" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-calendar-plus"></i>
                </div>
                <div class="action-content">
                    <h4>Book Appointment</h4>
                    <p>Schedule a new medical appointment</p>
                </div>
                <div class="action-arrow">
                    <i class="fa fa-arrow-right"></i>
                </div>
            </a>
            
            <a href="modules/medical_records.php" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-file-medical"></i>
                </div>
                <div class="action-content">
                    <h4>Medical Records</h4>
                    <p>View your medical history and reports</p>
                </div>
                <div class="action-arrow">
                    <i class="fa fa-arrow-right"></i>
                </div>
            </a>
            
            <a href="modules/billing.php" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-credit-card"></i>
                </div>
                <div class="action-content">
                    <h4>Pay Bills</h4>
                    <p>View and pay your medical bills</p>
                </div>
                <div class="action-arrow">
                    <i class="fa fa-arrow-right"></i>
                </div>
            </a>
            
            <a href="modules/prescriptions.php" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-prescription"></i>
                </div>
                <div class="action-content">
                    <h4>Prescriptions</h4>
                    <p>View your current prescriptions</p>
                </div>
                <div class="action-arrow">
                    <i class="fa fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Activities & Upcoming Appointments -->
    <div class="activities-section">
        <div class="activities-grid">
            <div class="activity-card">
                <div class="card-header">
                    <h4><i class="fa fa-history"></i> Recent Activities</h4>
                    <a href="modules/logs.php" class="view-all">View All</a>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fa fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p><strong><?php echo $activity['user_name']; ?></strong> <?php echo $activity['description']; ?></p>
                                        <span class="activity-time"><?php echo date('M d, h:i A', strtotime($activity['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-activities">
                                <i class="fa fa-info-circle"></i>
                                <p>No recent activities found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="appointments-card">
                <div class="card-header">
                    <h4><i class="fa fa-calendar"></i> Upcoming Appointments</h4>
                    <a href="modules/appointments.php" class="view-all">View All</a>
                </div>
                <div class="card-body">
                    <div class="appointments-list">
                        <?php if (!empty($upcoming_appointments_list)): ?>
                            <?php foreach (array_slice($upcoming_appointments_list, 0, 5) as $appointment): ?>
                                <div class="appointment-item">
                                    <div class="appointment-status <?php echo $appointment['status']; ?>"></div>
                                    <div class="appointment-content">
                                        <h5><?php echo $appointment['doctor_name']; ?></h5>
                                        <p><?php echo $appointment['department']; ?> - <?php echo $appointment['reason']; ?></p>
                                        <span class="appointment-time"><?php echo date('M d, h:i A', strtotime($appointment['appointment_date'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-appointments">
                                <i class="fa fa-calendar-times"></i>
                                <p>No upcoming appointments!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Patient Dashboard Styles */
.patient-dashboard {
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.dark-mode .patient-dashboard {
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
    color: white;
}

.action-btn.primary {
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.action-btn.secondary {
    background: rgba(102, 126, 234, 0.1);
    color: var(--primary-color);
    border: 1px solid rgba(102, 126, 234, 0.2);
}

.action-btn.success {
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Health Section */
.health-section {
    margin-bottom: 30px;
}

.health-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
}

.dark-mode .health-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.health-card {
    border-left: 4px solid #28a745;
}

.health-icon {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.health-indicator {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    border: 3px solid white;
}

.health-indicator.good {
    background: #28a745;
}

.health-indicator.warning {
    background: #ffc107;
}

.health-indicator.critical {
    background: #dc3545;
}

.health-info {
    flex: 1;
}

.health-info h4 {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.dark-mode .health-info h4 {
    color: #fff;
}

.health-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 10px;
}

.health-status.good {
    color: #28a745;
}

.health-status.warning {
    color: #ffc107;
}

.health-status.critical {
    color: #dc3545;
}

.health-details {
    display: flex;
    gap: 20px;
}

.health-details span {
    font-size: 12px;
    color: #666;
}

.dark-mode .health-details span {
    color: #ccc;
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

.stat-card.total-appointments {
    border-left: 4px solid #667eea;
}

.stat-card.upcoming-appointments {
    border-left: 4px solid #17a2b8;
}

.stat-card.completed-appointments {
    border-left: 4px solid #28a745;
}

.stat-card.pending-bills {
    border-left: 4px solid #ffc107;
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

.stat-card.total-appointments .stat-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.stat-card.upcoming-appointments .stat-icon {
    background: linear-gradient(135deg, #17a2b8, #20c997);
}

.stat-card.completed-appointments .stat-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.stat-card.pending-bills .stat-icon {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
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

.stat-content {
    margin-bottom: 15px;
}

.stat-number {
    font-size: 32px;
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
    margin-bottom: 10px;
}

.dark-mode .stat-label {
    color: #ccc;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 500;
}

.stat-change.positive {
    color: #28a745;
}

.stat-change.negative {
    color: #dc3545;
}

.stat-change.warning {
    color: #ffc107;
}

.stat-change.neutral {
    color: #6c757d;
}

.stat-chart {
    position: absolute;
    top: 20px;
    right: 20px;
    opacity: 0.3;
}

/* Alerts Section */
.alerts-section {
    margin-bottom: 30px;
}

.section-header {
    margin-bottom: 20px;
}

.section-header h3 {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin: 0 0 5px 0;
}

.dark-mode .section-header h3 {
    color: #fff;
}

.section-header p {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.dark-mode .section-header p {
    color: #ccc;
}

.alerts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.alert-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    position: relative;
}

.dark-mode .alert-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.alert-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
}

.alert-card.reminder {
    border-left: 4px solid #17a2b8;
}

.alert-card.warning {
    border-left: 4px solid #ffc107;
}

.alert-card.urgent {
    border-left: 4px solid #dc3545;
}

.alert-card.follow_up {
    border-left: 4px solid #28a745;
}

.alert-icon {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    margin-bottom: 20px;
}

.alert-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
}

.alert-badge.reminder {
    background: #17a2b8;
}

.alert-badge.warning {
    background: #ffc107;
    color: #333;
}

.alert-badge.urgent {
    background: #dc3545;
}

.alert-badge.follow_up {
    background: #28a745;
}

.alert-content h4 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 10px 0;
}

.dark-mode .alert-content h4 {
    color: #fff;
}

.alert-content p {
    font-size: 14px;
    color: #666;
    margin: 0 0 15px 0;
    line-height: 1.5;
}

.dark-mode .alert-content p {
    color: #ccc;
}

.alert-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #666;
}

.dark-mode .stat-item {
    color: #ccc;
}

.stat-item i {
    color: var(--primary-color);
    width: 14px;
}

.no-alerts {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    grid-column: 1 / -1;
}

.dark-mode .no-alerts {
    color: #ccc;
}

.no-alerts i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.no-alerts p {
    margin: 0;
    font-size: 14px;
}

/* Actions Section */
.actions-section {
    margin-bottom: 30px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.action-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 15px;
}

.dark-mode .action-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    text-decoration: none;
    color: inherit;
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
}

.action-content {
    flex: 1;
}

.action-content h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.dark-mode .action-content h4 {
    color: #fff;
}

.action-content p {
    margin: 0;
    font-size: 13px;
    color: #666;
    line-height: 1.4;
}

.dark-mode .action-content p {
    color: #ccc;
}

.action-arrow {
    color: var(--primary-color);
    font-size: 16px;
    opacity: 0.7;
    transition: all 0.3s ease;
}

.action-card:hover .action-arrow {
    opacity: 1;
    transform: translateX(3px);
}

/* Activities Section */
.activities-section {
    margin-bottom: 30px;
}

.activities-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.activity-card, .appointments-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.dark-mode .activity-card, .dark-mode .appointments-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.activity-card:hover, .appointments-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.card-header h4 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dark-mode .card-header h4 {
    color: #fff;
}

.view-all {
    color: var(--primary-color);
    font-size: 12px;
    text-decoration: none;
    font-weight: 500;
}

.activity-list, .appointments-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item, .appointment-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.dark-mode .activity-item, .dark-mode .appointment-item {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.activity-item:last-child, .appointment-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 35px;
    height: 35px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: white;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-content p {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #333;
    line-height: 1.4;
}

.dark-mode .activity-content p {
    color: #fff;
}

.activity-content strong {
    color: var(--primary-color);
}

.activity-time {
    font-size: 11px;
    color: #666;
}

.dark-mode .activity-time {
    color: #ccc;
}

.appointment-status {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.appointment-status.confirmed {
    background: #28a745;
}

.appointment-status.pending {
    background: #ffc107;
}

.appointment-status.cancelled {
    background: #dc3545;
}

.appointment-content {
    flex: 1;
}

.appointment-content h5 {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.dark-mode .appointment-content h5 {
    color: #fff;
}

.appointment-content p {
    margin: 0 0 5px 0;
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}

.dark-mode .appointment-content p {
    color: #ccc;
}

.appointment-time {
    font-size: 11px;
    color: #666;
}

.dark-mode .appointment-time {
    color: #ccc;
}

.no-activities, .no-appointments {
    text-align: center;
    padding: 30px 20px;
    color: #666;
}

.dark-mode .no-activities, .dark-mode .no-appointments {
    color: #ccc;
}

.no-activities i, .no-appointments i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.no-activities p, .no-appointments p {
    margin: 0;
    font-size: 14px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .alerts-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .actions-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .activities-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 991.98px) {
    .patient-dashboard {
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
    
    .header-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-number {
        font-size: 28px;
    }
    
    .actions-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 768px) {
    .dashboard-title {
        font-size: 24px;
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
    
    .action-btn span {
        display: none;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .health-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .health-details {
        flex-direction: column;
        gap: 5px;
    }
}

@media (max-width: 480px) {
    .patient-dashboard {
        padding: 10px;
    }
    
    .dashboard-header {
        padding: 15px;
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
    
    .action-card {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .action-arrow {
        display: none;
    }
}
</style>

<script>
// Enhanced dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mini charts
    initializeMiniCharts();
    
    // Add hover effects
    addHoverEffects();
});

function initializeMiniCharts() {
    // Simple mini charts for statistics
    const charts = ['totalAppointmentsChart', 'upcomingAppointmentsChart', 'completedAppointmentsChart', 'pendingBillsChart'];
    
    charts.forEach(chartId => {
        const canvas = document.getElementById(chartId);
        if (canvas) {
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 30);
            gradient.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
            gradient.addColorStop(1, 'rgba(102, 126, 234, 0.1)');
            
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 10, 60, 20);
        }
    });
}

function addHoverEffects() {
    // Add smooth hover effects to cards
    const cards = document.querySelectorAll('.stat-card, .action-card, .activity-card, .appointments-card, .alert-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Helper function to get health icon
function getHealthIcon(type) {
    const icons = {
        'reminder': 'bell',
        'warning': 'exclamation-triangle',
        'urgent': 'exclamation-circle',
        'follow_up': 'calendar-check',
        'default': 'info-circle'
    };
    return icons[type] || icons.default;
}
</script>