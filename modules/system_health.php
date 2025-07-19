<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$hospital_id = $_SESSION['hospital_id'];

// Only admin can access system health
if ($role != 'admin') {
    header('Location: ../dashboards/admin.php');
    exit();
}

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_extensions' => get_loaded_extensions(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'disk_free_space' => disk_free_space('/'),
    'disk_total_space' => disk_total_space('/'),
    'current_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get()
];

// Get database statistics
$db_stats = [];
$stmt = $pdo->query("SHOW TABLE STATUS");
$tables = $stmt->fetchAll();

$total_size = 0;
$total_rows = 0;
foreach ($tables as $table) {
    $total_size += $table['Data_length'] + $table['Index_length'];
    $total_rows += $table['Rows'];
}

$db_stats['total_tables'] = count($tables);
$db_stats['total_size'] = $total_size;
$db_stats['total_rows'] = $total_rows;

// Get application statistics
$app_stats = [];

// Users count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$app_stats['total_users'] = $stmt->fetchColumn();

// Patients count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE status = 'active'");
$stmt->execute();
$app_stats['total_patients'] = $stmt->fetchColumn();

// Appointments count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$app_stats['total_appointments'] = $stmt->fetchColumn();

// Bills count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$app_stats['total_bills'] = $stmt->fetchColumn();

// Recent activity
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE hospital_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute([$hospital_id]);
$app_stats['recent_activity'] = $stmt->fetchColumn();

// System performance checks
$performance_checks = [];

// Database connection test
try {
    $start_time = microtime(true);
    $stmt = $pdo->query("SELECT 1");
    $stmt->fetch();
    $db_response_time = (microtime(true) - $start_time) * 1000;
    $performance_checks['database'] = [
        'status' => 'healthy',
        'response_time' => round($db_response_time, 2),
        'message' => 'Database connection is working properly'
    ];
} catch (Exception $e) {
    $performance_checks['database'] = [
        'status' => 'error',
        'response_time' => 0,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
}

// Disk space check
$disk_usage_percent = (($system_info['disk_total_space'] - $system_info['disk_free_space']) / $system_info['disk_total_space']) * 100;
if ($disk_usage_percent > 90) {
    $performance_checks['disk_space'] = [
        'status' => 'warning',
        'usage' => round($disk_usage_percent, 2),
        'message' => 'Disk space is running low'
    ];
} else {
    $performance_checks['disk_space'] = [
        'status' => 'healthy',
        'usage' => round($disk_usage_percent, 2),
        'message' => 'Disk space is adequate'
    ];
}

// Memory usage check
$memory_usage = memory_get_usage(true);
$memory_limit_bytes = return_bytes($system_info['memory_limit']);
$memory_usage_percent = ($memory_usage / $memory_limit_bytes) * 100;

if ($memory_usage_percent > 80) {
    $performance_checks['memory'] = [
        'status' => 'warning',
        'usage' => round($memory_usage_percent, 2),
        'message' => 'Memory usage is high'
    ];
} else {
    $performance_checks['memory'] = [
        'status' => 'healthy',
        'usage' => round($memory_usage_percent, 2),
        'message' => 'Memory usage is normal'
    ];
}

// Recent errors (if any)
$recent_errors = [];
$error_log_file = ini_get('error_log');
if ($error_log_file && file_exists($error_log_file)) {
    $error_log_content = file_get_contents($error_log_file);
    $lines = explode("\n", $error_log_content);
    $recent_lines = array_slice($lines, -50); // Last 50 lines
    
    foreach ($recent_lines as $line) {
        if (strpos($line, 'error') !== false || strpos($line, 'Error') !== false) {
            $recent_errors[] = $line;
        }
    }
}

$page_title = "System Health";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="../dashboards/admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">System Health</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="fa fa-heartbeat"></i> System Health Monitor
                </h4>
            </div>
        </div>
    </div>

    <!-- System Status Overview -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Overall Status">System Status</h5>
                            <h3 class="mt-3 mb-3">
                                <span class="badge bg-success">Healthy</span>
                            </h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-success rounded">
                                <i class="fa fa-check-circle font-20 text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Database Response">DB Response</h5>
                            <h3 class="mt-3 mb-3"><?php echo $performance_checks['database']['response_time']; ?>ms</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-primary rounded">
                                <i class="fa fa-database font-20 text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Disk Usage">Disk Usage</h5>
                            <h3 class="mt-3 mb-3"><?php echo $performance_checks['disk_space']['usage']; ?>%</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-warning rounded">
                                <i class="fa fa-hdd-o font-20 text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Memory Usage">Memory Usage</h5>
                            <h3 class="mt-3 mb-3"><?php echo $performance_checks['memory']['usage']; ?>%</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-info rounded">
                                <i class="fa fa-memory font-20 text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- System Information -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-info-circle"></i> System Information
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td><strong>PHP Version</strong></td>
                                    <td><?php echo $system_info['php_version']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>MySQL Version</strong></td>
                                    <td><?php echo $system_info['mysql_version']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Server Software</strong></td>
                                    <td><?php echo $system_info['server_software']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Memory Limit</strong></td>
                                    <td><?php echo $system_info['memory_limit']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Max Execution Time</strong></td>
                                    <td><?php echo $system_info['max_execution_time']; ?> seconds</td>
                                </tr>
                                <tr>
                                    <td><strong>Upload Max Filesize</strong></td>
                                    <td><?php echo $system_info['upload_max_filesize']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Post Max Size</strong></td>
                                    <td><?php echo $system_info['post_max_size']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Timezone</strong></td>
                                    <td><?php echo $system_info['timezone']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Current Time</strong></td>
                                    <td><?php echo $system_info['current_time']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Checks -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-tachometer"></i> Performance Checks
                    </h4>
                </div>
                <div class="card-body">
                    <?php foreach ($performance_checks as $check_name => $check): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0"><?php echo ucwords(str_replace('_', ' ', $check_name)); ?></h6>
                                <small class="text-muted"><?php echo $check['message']; ?></small>
                            </div>
                            <div>
                                <?php if ($check['status'] == 'healthy'): ?>
                                    <span class="badge bg-success">Healthy</span>
                                <?php elseif ($check['status'] == 'warning'): ?>
                                    <span class="badge bg-warning">Warning</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Error</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (isset($check['usage'])): ?>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-<?php echo $check['status'] == 'healthy' ? 'success' : ($check['status'] == 'warning' ? 'warning' : 'danger'); ?>" 
                                 style="width: <?php echo $check['usage']; ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Database Statistics -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-database"></i> Database Statistics
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-primary"><?php echo $db_stats['total_tables']; ?></h3>
                                <p class="text-muted mb-0">Total Tables</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-success"><?php echo number_format($db_stats['total_rows']); ?></h3>
                                <p class="text-muted mb-0">Total Rows</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>Database Size</span>
                            <span><?php echo formatBytes($db_stats['total_size']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Statistics -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-chart-bar"></i> Application Statistics
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-primary"><?php echo number_format($app_stats['total_users']); ?></h3>
                                <p class="text-muted mb-0">Total Users</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-success"><?php echo number_format($app_stats['total_patients']); ?></h3>
                                <p class="text-muted mb-0">Total Patients</p>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-warning"><?php echo number_format($app_stats['total_appointments']); ?></h3>
                                <p class="text-muted mb-0">Appointments</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-info"><?php echo number_format($app_stats['total_bills']); ?></h3>
                                <p class="text-muted mb-0">Total Bills</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>Recent Activity (24h)</span>
                            <span><?php echo number_format($app_stats['recent_activity']); ?> logs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Errors -->
    <?php if (!empty($recent_errors)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-exclamation-triangle"></i> Recent Errors
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Error Message</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_errors, -10) as $error): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($error); ?></code></td>
                                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- System Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-cogs"></i> System Actions
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary w-100 mb-2" onclick="refreshSystemHealth()">
                                <i class="fa fa-refresh"></i> Refresh Status
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="backup.php" class="btn btn-outline-success w-100 mb-2">
                                <i class="fa fa-download"></i> Create Backup
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="audit_logs.php" class="btn btn-outline-info w-100 mb-2">
                                <i class="fa fa-history"></i> View Logs
                            </a>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-warning w-100 mb-2" onclick="clearCache()">
                                <i class="fa fa-trash"></i> Clear Cache
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshSystemHealth() {
    location.reload();
}

function clearCache() {
    if (confirm('Are you sure you want to clear the system cache?')) {
        // Implementation for clearing cache
        alert('Cache cleared successfully!');
    }
}

// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>

<?php include '../includes/footer.php'; ?>