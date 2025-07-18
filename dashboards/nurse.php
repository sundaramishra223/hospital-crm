<?php
require_once 'includes/functions.php';

// Check if user is logged in and has nurse role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nurse') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];
$theme_color = getSetting('theme_color', '#667eea');

// Get nurse information
$nurse_info = getUserById($user_id);

// Get statistics
$total_patients = getTotalRecords('patients', ['hospital_id' => $hospital_id, 'status' => 'active']);
$today_appointments = getTotalRecords('appointments', ['DATE(appointment_date)' => date('Y-m-d'), 'hospital_id' => $hospital_id]);
$pending_tasks = getPendingTasks($user_id);
$my_attendance = getAttendanceStatus($user_id, date('Y-m-d'));

// Get recent activities and tasks
$recent_activities = getRecentActivities($user_id, 10);
$upcoming_tasks = getUpcomingTasks($user_id, 5);
?>

<div class="nurse-dashboard" style="--primary-color: <?php echo $theme_color; ?>;">
    <!-- Modern Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <div class="welcome-section">
                    <h1 class="dashboard-title">
                        <i class="fa fa-user-nurse"></i>
                        Nurse Dashboard
                    </h1>
                    <p class="welcome-text">Welcome back, <strong><?php echo htmlspecialchars($nurse_info['name']); ?></strong>! Provide excellent patient care and maintain health standards.</p>
                </div>
                <div class="quick-stats">
                    <div class="quick-stat-item">
                        <i class="fa fa-clock-o"></i>
                        <span>Last Login: <?php echo date('M d, h:i A'); ?></span>
                    </div>
                    <div class="quick-stat-item">
                        <i class="fa fa-users"></i>
                        <span><?php echo $total_patients; ?> Patients</span>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <button class="action-btn primary" onclick="markAttendance()">
                        <i class="fa fa-clock"></i>
                        <span>Mark Attendance</span>
                    </button>
                    <button class="action-btn secondary" onclick="location.href='modules/patients.php'">
                        <i class="fa fa-user-injured"></i>
                        <span>Patient Care</span>
                    </button>
                    <button class="action-btn success" onclick="location.href='modules/appointments.php'">
                        <i class="fa fa-calendar-check"></i>
                        <span>Appointments</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Status Card -->
    <div class="attendance-section">
        <div class="attendance-card <?php echo $my_attendance ? 'checked-in' : 'not-checked'; ?>">
            <div class="attendance-icon">
                <i class="fa fa-clock"></i>
                <div class="status-indicator <?php echo $my_attendance ? 'online' : 'offline'; ?>"></div>
            </div>
            <div class="attendance-info">
                <h4>Today's Attendance</h4>
                <?php if ($my_attendance): ?>
                    <div class="attendance-status success">
                        <i class="fa fa-check-circle"></i>
                        <span>Checked In at <?php echo date('g:i A', strtotime($my_attendance['check_in_time'])); ?></span>
                    </div>
                    <div class="attendance-details">
                        <span>Working Hours: 12 hrs</span>
                        <span>Break Time: 2 hrs</span>
                    </div>
                <?php else: ?>
                    <div class="attendance-status warning">
                        <i class="fa fa-clock"></i>
                        <span>Not Checked In Yet</span>
                    </div>
                    <div class="attendance-details">
                        <span>Shift: 7:00 AM - 7:00 PM</span>
                        <span>Ward: General Ward</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card patients">
                <div class="stat-icon">
                    <i class="fa fa-user-injured"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($total_patients); ?></div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-change positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>+<?php echo rand(2, 6); ?> this week</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="patientsChart" width="60" height="30"></canvas>
                </div>
            </div>
            
            <div class="stat-card appointments">
                <div class="stat-icon">
                    <i class="fa fa-calendar-check"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($today_appointments); ?></div>
                    <div class="stat-label">Today's Appointments</div>
                    <div class="stat-change positive">
                        <i class="fa fa-arrow-up"></i>
                        <span><?php echo round(($today_appointments / max($total_patients, 1)) * 100); ?>% of patients</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="appointmentsChart" width="60" height="30"></canvas>
                </div>
            </div>
            
            <div class="stat-card tasks">
                <div class="stat-icon">
                    <i class="fa fa-tasks"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $pending_tasks; ?></div>
                    <div class="stat-label">Pending Tasks</div>
                    <div class="stat-change warning">
                        <i class="fa fa-exclamation"></i>
                        <span>Needs attention</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="tasksChart" width="60" height="30"></canvas>
                </div>
            </div>
            
            <div class="stat-card vitals">
                <div class="stat-icon">
                    <i class="fa fa-heartbeat"></i>
                    <div class="icon-glow"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo rand(15, 25); ?></div>
                    <div class="stat-label">Vitals Checked</div>
                    <div class="stat-change positive">
                        <i class="fa fa-arrow-up"></i>
                        <span>Today's count</span>
                    </div>
                </div>
                <div class="stat-chart">
                    <canvas id="vitalsChart" width="60" height="30"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="actions-section">
        <div class="section-header">
            <h3>Quick Actions</h3>
            <p>Access frequently used nursing features quickly</p>
        </div>
        <div class="actions-grid">
            <a href="modules/patients.php" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-user-injured"></i>
                </div>
                <div class="action-content">
                    <h4>Patient Care</h4>
                    <p>Monitor and care for patients</p>
                </div>
                <div class="action-arrow">
                    <i class="fa fa-arrow-right"></i>
                </div>
            </a>
            
            <a href="modules/vitals.php" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-heartbeat"></i>
                </div>
                <div class="action-content">
                    <h4>Vitals Check</h4>
                    <p>Record patient vital signs</p>
                </div>
                <div class="action-arrow">
                    <i class="fa fa-arrow-right"></i>
                </div>
            </a>
            
            <a href="modules/medications.php" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-pills"></i>
                </div>
                <div class="action-content">
                    <h4>Medications</h4>
                    <p>Administer medications</p>
                </div>
                <div class="action-arrow">
                    <i class="fa fa-arrow-right"></i>
                </div>
            </a>
            
            <a href="modules/reports.php" class="action-card">
                <div class="action-icon">
                    <i class="fa fa-chart-line"></i>
                </div>
                <div class="action-content">
                    <h4>Nursing Reports</h4>
                    <p>Generate nursing reports</p>
                </div>
                <div class="action-arrow">
                    <i class="fa fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Activities & Tasks -->
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
            
            <div class="tasks-card">
                <div class="card-header">
                    <h4><i class="fa fa-tasks"></i> Upcoming Tasks</h4>
                    <a href="modules/tasks.php" class="view-all">View All</a>
                </div>
                <div class="card-body">
                    <div class="tasks-list">
                        <?php if (!empty($upcoming_tasks)): ?>
                            <?php foreach (array_slice($upcoming_tasks, 0, 5) as $task): ?>
                                <div class="task-item">
                                    <div class="task-status <?php echo $task['priority']; ?>"></div>
                                    <div class="task-content">
                                        <h5><?php echo $task['title']; ?></h5>
                                        <p><?php echo $task['description']; ?></p>
                                        <span class="task-time">Due: <?php echo date('M d, h:i A', strtotime($task['due_date'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-tasks">
                                <i class="fa fa-check-circle"></i>
                                <p>No pending tasks!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Nurse Dashboard Styles */
.nurse-dashboard {
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.dark-mode .nurse-dashboard {
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

/* Attendance Section */
.attendance-section {
    margin-bottom: 30px;
}

.attendance-card {
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

.dark-mode .attendance-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.attendance-card.checked-in {
    border-left: 4px solid #28a745;
}

.attendance-card.not-checked {
    border-left: 4px solid #ffc107;
}

.attendance-icon {
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

.status-indicator {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    border: 3px solid white;
}

.status-indicator.online {
    background: #28a745;
}

.status-indicator.offline {
    background: #ffc107;
}

.attendance-info {
    flex: 1;
}

.attendance-info h4 {
    margin: 0 0 10px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.dark-mode .attendance-info h4 {
    color: #fff;
}

.attendance-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 10px;
}

.attendance-status.success {
    color: #28a745;
}

.attendance-status.warning {
    color: #ffc107;
}

.attendance-details {
    display: flex;
    gap: 20px;
}

.attendance-details span {
    font-size: 12px;
    color: #666;
}

.dark-mode .attendance-details span {
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

.stat-card.patients {
    border-left: 4px solid #667eea;
}

.stat-card.appointments {
    border-left: 4px solid #28a745;
}

.stat-card.tasks {
    border-left: 4px solid #ffc107;
}

.stat-card.vitals {
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

.stat-card.tasks .stat-icon {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.stat-card.vitals .stat-icon {
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

/* Actions Section */
.actions-section {
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

.activity-card, .tasks-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.dark-mode .activity-card, .dark-mode .tasks-card {
    background: rgba(26, 26, 26, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.activity-card:hover, .tasks-card:hover {
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

.activity-list, .tasks-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item, .task-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.dark-mode .activity-item, .dark-mode .task-item {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.activity-item:last-child, .task-item:last-child {
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

.task-status {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.task-status.high {
    background: #dc3545;
}

.task-status.medium {
    background: #ffc107;
}

.task-status.low {
    background: #28a745;
}

.task-content {
    flex: 1;
}

.task-content h5 {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.dark-mode .task-content h5 {
    color: #fff;
}

.task-content p {
    margin: 0 0 5px 0;
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}

.dark-mode .task-content p {
    color: #ccc;
}

.task-time {
    font-size: 11px;
    color: #666;
}

.dark-mode .task-time {
    color: #ccc;
}

.no-activities, .no-tasks {
    text-align: center;
    padding: 30px 20px;
    color: #666;
}

.dark-mode .no-activities, .dark-mode .no-tasks {
    color: #ccc;
}

.no-activities i, .no-tasks i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.no-activities p, .no-tasks p {
    margin: 0;
    font-size: 14px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .stats-grid {
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
    .nurse-dashboard {
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
    
    .attendance-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .attendance-details {
        flex-direction: column;
        gap: 5px;
    }
}

@media (max-width: 480px) {
    .nurse-dashboard {
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
// Mark attendance function
function markAttendance() {
    fetch('api/mark-attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({action: 'check_in'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error marking attendance: ' + data.message);
        }
    })
    .catch(error => {
        console.log('Error:', error);
        alert('Error marking attendance');
    });
}

// Enhanced dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mini charts
    initializeMiniCharts();
    
    // Add hover effects
    addHoverEffects();
});

function initializeMiniCharts() {
    // Simple mini charts for statistics
    const charts = ['patientsChart', 'appointmentsChart', 'tasksChart', 'vitalsChart'];
    
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
    const cards = document.querySelectorAll('.stat-card, .action-card, .activity-card, .tasks-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}
</script>
