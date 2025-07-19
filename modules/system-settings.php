<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user has permission to access this module
if (!hasPermission($_SESSION['user_role'], 'system_settings')) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_security':
                $session_timeout = sanitize($_POST['session_timeout']);
                $max_login_attempts = sanitize($_POST['max_login_attempts']);
                $password_expiry_days = sanitize($_POST['password_expiry_days']);
                $enable_two_factor = isset($_POST['enable_two_factor']) ? 1 : 0;
                $enable_audit_log = isset($_POST['enable_audit_log']) ? 1 : 0;
                
                $sql = "UPDATE system_settings SET 
                        session_timeout = ?, max_login_attempts = ?, password_expiry_days = ?, 
                        enable_two_factor = ?, enable_audit_log = ?, updated_at = NOW() 
                        WHERE hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiiii", $session_timeout, $max_login_attempts, $password_expiry_days, 
                                $enable_two_factor, $enable_audit_log, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Security settings updated');
                    $success_message = "Security settings updated successfully!";
                } else {
                    $error_message = "Error updating security settings: " . $conn->error;
                }
                break;
                
            case 'update_backup':
                $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
                $backup_frequency = sanitize($_POST['backup_frequency']);
                $backup_retention_days = sanitize($_POST['backup_retention_days']);
                $backup_email = sanitize($_POST['backup_email']);
                
                $sql = "UPDATE system_settings SET 
                        auto_backup = ?, backup_frequency = ?, backup_retention_days = ?, 
                        backup_email = ?, updated_at = NOW() 
                        WHERE hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isisi", $auto_backup, $backup_frequency, $backup_retention_days, 
                                $backup_email, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Backup settings updated');
                    $success_message = "Backup settings updated successfully!";
                } else {
                    $error_message = "Error updating backup settings: " . $conn->error;
                }
                break;
                
            case 'update_performance':
                $cache_enabled = isset($_POST['cache_enabled']) ? 1 : 0;
                $cache_duration = sanitize($_POST['cache_duration']);
                $max_file_size = sanitize($_POST['max_file_size']);
                $allowed_file_types = sanitize($_POST['allowed_file_types']);
                
                $sql = "UPDATE system_settings SET 
                        cache_enabled = ?, cache_duration = ?, max_file_size = ?, 
                        allowed_file_types = ?, updated_at = NOW() 
                        WHERE hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iissi", $cache_enabled, $cache_duration, $max_file_size, 
                                $allowed_file_types, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Performance settings updated');
                    $success_message = "Performance settings updated successfully!";
                } else {
                    $error_message = "Error updating performance settings: " . $conn->error;
                }
                break;
                
            case 'clear_cache':
                // Clear system cache
                $cache_dir = '../cache/';
                if (is_dir($cache_dir)) {
                    $files = glob($cache_dir . '*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                }
                logActivity($_SESSION['user_id'], 'System cache cleared');
                $success_message = "System cache cleared successfully!";
                break;
                
            case 'clear_logs':
                // Clear old logs (keep last 30 days)
                $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $hospital_id);
                $stmt->execute();
                
                logActivity($_SESSION['user_id'], 'Old logs cleared');
                $success_message = "Old logs cleared successfully!";
                break;
        }
    }
}

// Get current settings
$sql = "SELECT * FROM system_settings WHERE hospital_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

// If no settings exist, create default settings
if (!$settings) {
    $sql = "INSERT INTO system_settings (hospital_id, session_timeout, max_login_attempts, 
            password_expiry_days, enable_audit_log, auto_backup, backup_frequency, 
            backup_retention_days, cache_enabled, cache_duration, max_file_size, 
            allowed_file_types, created_at) 
            VALUES (?, 30, 5, 90, 1, 1, 'daily', 30, 1, 3600, 5242880, 'jpg,jpeg,png,pdf,doc,docx', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    
    // Fetch the newly created settings
    $sql = "SELECT * FROM system_settings WHERE hospital_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $settings = $stmt->get_result()->fetch_assoc();
}

// Get system statistics
$sql = "SELECT COUNT(*) as total_users FROM users WHERE hospital_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total_users'];

$sql = "SELECT COUNT(*) as total_patients FROM patients WHERE hospital_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total_patients'];

$sql = "SELECT COUNT(*) as total_appointments FROM appointments WHERE hospital_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total_appointments'];

$sql = "SELECT COUNT(*) as total_bills FROM billing WHERE hospital_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$total_bills = $stmt->get_result()->fetch_assoc()['total_bills'];

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fa fa-wrench me-2"></i>System Settings & Configuration
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- System Statistics -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Users
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Patients
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_patients; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-user-injured fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Appointments
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_appointments; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Bills
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_bills; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-file-invoice fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tabs -->
                    <ul class="nav nav-tabs" id="systemSettingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                <i class="fa fa-shield-alt me-1"></i>Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                                <i class="fa fa-database me-1"></i>Backup & Recovery
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">
                                <i class="fa fa-tachometer-alt me-1"></i>Performance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                                <i class="fa fa-tools me-1"></i>Maintenance
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="systemSettingsTabContent">
                        <!-- Security Tab -->
                        <div class="tab-pane fade show active" id="security" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_security">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Authentication Settings</h5>
                                        
                                        <div class="mb-3">
                                            <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                            <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                                   value="<?php echo $settings['session_timeout'] ?? 30; ?>" min="5" max="480">
                                            <small class="text-muted">How long before a user session expires</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="max_login_attempts" class="form-label">Maximum Login Attempts</label>
                                            <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                                   value="<?php echo $settings['max_login_attempts'] ?? 5; ?>" min="3" max="10">
                                            <small class="text-muted">Number of failed attempts before account lockout</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="password_expiry_days" class="form-label">Password Expiry (days)</label>
                                            <input type="number" class="form-control" id="password_expiry_days" name="password_expiry_days" 
                                                   value="<?php echo $settings['password_expiry_days'] ?? 90; ?>" min="30" max="365">
                                            <small class="text-muted">Days before password expires</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Security Features</h5>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enable_two_factor" name="enable_two_factor" 
                                                       <?php echo ($settings['enable_two_factor'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_two_factor">
                                                    Enable Two-Factor Authentication
                                                </label>
                                            </div>
                                            <small class="text-muted">Require additional verification for login</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enable_audit_log" name="enable_audit_log" 
                                                       <?php echo ($settings['enable_audit_log'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_audit_log">
                                                    Enable Audit Logging
                                                </label>
                                            </div>
                                            <small class="text-muted">Log all user activities for security</small>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <h6><i class="fa fa-info-circle me-2"></i>Security Recommendations</h6>
                                            <ul class="mb-0">
                                                <li>Use strong passwords with mixed characters</li>
                                                <li>Enable two-factor authentication for admin accounts</li>
                                                <li>Regularly review audit logs</li>
                                                <li>Keep session timeouts reasonable</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-1"></i>Save Security Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Backup Tab -->
                        <div class="tab-pane fade" id="backup" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_backup">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Backup Configuration</h5>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup" 
                                                       <?php echo ($settings['auto_backup'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="auto_backup">
                                                    Enable Automatic Backups
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="backup_frequency" class="form-label">Backup Frequency</label>
                                            <select class="form-control" id="backup_frequency" name="backup_frequency">
                                                <option value="daily" <?php echo ($settings['backup_frequency'] ?? '') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                <option value="weekly" <?php echo ($settings['backup_frequency'] ?? '') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                <option value="monthly" <?php echo ($settings['backup_frequency'] ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="backup_retention_days" class="form-label">Backup Retention (days)</label>
                                            <input type="number" class="form-control" id="backup_retention_days" name="backup_retention_days" 
                                                   value="<?php echo $settings['backup_retention_days'] ?? 30; ?>" min="7" max="365">
                                            <small class="text-muted">How long to keep backup files</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="backup_email" class="form-label">Backup Notification Email</label>
                                            <input type="email" class="form-control" id="backup_email" name="backup_email" 
                                                   value="<?php echo htmlspecialchars($settings['backup_email'] ?? ''); ?>">
                                            <small class="text-muted">Email to notify about backup status</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Manual Backup</h5>
                                        
                                        <div class="alert alert-warning">
                                            <h6><i class="fa fa-exclamation-triangle me-2"></i>Backup Information</h6>
                                            <p class="mb-2">Manual backups include:</p>
                                            <ul class="mb-0">
                                                <li>Complete database backup</li>
                                                <li>System settings backup</li>
                                                <li>User data backup</li>
                                                <li>Configuration files</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="modules/backup.php" class="btn btn-outline-primary">
                                                <i class="fa fa-download me-2"></i>Create Manual Backup
                                            </a>
                                            <a href="modules/backup.php" class="btn btn-outline-info">
                                                <i class="fa fa-upload me-2"></i>Restore from Backup
                                            </a>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <h6>Recent Backups</h6>
                                            <div class="list-group">
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1">System Backup</h6>
                                                            <small class="text-muted">Last backup: <?php echo date('M d, Y H:i'); ?></small>
                                                        </div>
                                                        <span class="badge bg-success">Success</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-1"></i>Save Backup Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Performance Tab -->
                        <div class="tab-pane fade" id="performance" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_performance">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Cache Settings</h5>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="cache_enabled" name="cache_enabled" 
                                                       <?php echo ($settings['cache_enabled'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="cache_enabled">
                                                    Enable System Cache
                                                </label>
                                            </div>
                                            <small class="text-muted">Improves system performance</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cache_duration" class="form-label">Cache Duration (seconds)</label>
                                            <input type="number" class="form-control" id="cache_duration" name="cache_duration" 
                                                   value="<?php echo $settings['cache_duration'] ?? 3600; ?>" min="300" max="86400">
                                            <small class="text-muted">How long to keep cached data</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">File Upload Settings</h5>
                                        
                                        <div class="mb-3">
                                            <label for="max_file_size" class="form-label">Maximum File Size (bytes)</label>
                                            <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                                   value="<?php echo $settings['max_file_size'] ?? 5242880; ?>" min="1048576" max="52428800">
                                            <small class="text-muted">Maximum size for uploaded files (5MB default)</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="allowed_file_types" class="form-label">Allowed File Types</label>
                                            <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" 
                                                   value="<?php echo htmlspecialchars($settings['allowed_file_types'] ?? 'jpg,jpeg,png,pdf,doc,docx'); ?>">
                                            <small class="text-muted">Comma-separated list of allowed file extensions</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-1"></i>Save Performance Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Maintenance Tab -->
                        <div class="tab-pane fade" id="maintenance" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">System Maintenance</h5>
                                    
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6><i class="fa fa-broom me-2"></i>Clear System Cache</h6>
                                            <p class="text-muted">Remove temporary files and cached data to free up space.</p>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="clear_cache">
                                                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to clear the system cache?')">
                                                    <i class="fa fa-trash me-1"></i>Clear Cache
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6><i class="fa fa-file-alt me-2"></i>Clear Old Logs</h6>
                                            <p class="text-muted">Remove activity logs older than 30 days to free up database space.</p>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="clear_logs">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to clear old logs? This action cannot be undone.')">
                                                    <i class="fa fa-trash me-1"></i>Clear Old Logs
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="mb-3">System Health</h5>
                                    
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6><i class="fa fa-heartbeat me-2"></i>System Status</h6>
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <div class="border-end">
                                                        <h4 class="text-success"><?php echo $total_users; ?></h4>
                                                        <small class="text-muted">Active Users</small>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <h4 class="text-info"><?php echo $total_patients; ?></h4>
                                                    <small class="text-muted">Total Patients</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card">
                                        <div class="card-body">
                                            <h6><i class="fa fa-info-circle me-2"></i>Quick Actions</h6>
                                            <div class="d-grid gap-2">
                                                <a href="modules/system_health.php" class="btn btn-outline-info btn-sm">
                                                    <i class="fa fa-chart-line me-1"></i>View System Health
                                                </a>
                                                <a href="modules/audit_logs.php" class="btn btn-outline-warning btn-sm">
                                                    <i class="fa fa-list me-1"></i>View Audit Logs
                                                </a>
                                                <a href="modules/backup.php" class="btn btn-outline-success btn-sm">
                                                    <i class="fa fa-database me-1"></i>Backup System
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const triggerTabList = [].slice.call(document.querySelectorAll('#systemSettingsTabs button'));
    triggerTabList.forEach(function (triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>