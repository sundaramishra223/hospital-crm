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

// Only admin can access audit logs
if ($role != 'admin') {
    header('Location: ../dashboards/admin.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'clear_logs':
                $days = (int)$_POST['days'];
                $date = date('Y-m-d', strtotime("-$days days"));
                
                $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < ? AND hospital_id = ?");
                $stmt->execute([$date, $hospital_id]);
                
                $deleted_count = $stmt->rowCount();
                addActivityLog($user_id, 'logs_cleared', "Cleared $deleted_count audit logs older than $days days");
                $success = "Cleared $deleted_count audit logs successfully!";
                break;
                
            case 'export_logs':
                $start_date = sanitize($_POST['start_date']);
                $end_date = sanitize($_POST['end_date']);
                $action_type = sanitize($_POST['action_type']);
                
                // Export functionality will be implemented
                $success = "Export functionality will be implemented";
                break;
        }
    }
}

// Get audit logs with filters
$where_conditions = ['hospital_id = ?'];
$params = [$hospital_id];

if (isset($_GET['user_id']) && $_GET['user_id']) {
    $where_conditions[] = 'user_id = ?';
    $params[] = (int)$_GET['user_id'];
}

if (isset($_GET['action_type']) && $_GET['action_type']) {
    $where_conditions[] = 'action_type = ?';
    $params[] = sanitize($_GET['action_type']);
}

if (isset($_GET['start_date']) && $_GET['start_date']) {
    $where_conditions[] = 'DATE(created_at) >= ?';
    $params[] = sanitize($_GET['start_date']);
}

if (isset($_GET['end_date']) && $_GET['end_date']) {
    $where_conditions[] = 'DATE(created_at) <= ?';
    $params[] = sanitize($_GET['end_date']);
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE $where_clause");
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// Pagination
$records_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;
$total_pages = ceil($total_records / $records_per_page);

$stmt = $pdo->prepare("SELECT al.*, u.name as user_name, u.role as user_role FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id WHERE $where_clause ORDER BY al.created_at DESC LIMIT $records_per_page OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get users for filter
$users = [];
$stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE hospital_id = ? AND status = 'active' ORDER BY name");
$stmt->execute([$hospital_id]);
$users = $stmt->fetchAll();

// Get action types for filter
$action_types = [];
$stmt = $pdo->prepare("SELECT DISTINCT action_type FROM activity_logs WHERE hospital_id = ? ORDER BY action_type");
$stmt->execute([$hospital_id]);
$action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$today_logs = 0;
$week_logs = 0;
$month_logs = 0;
$total_logs = $total_records;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE hospital_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$hospital_id]);
$today_logs = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE hospital_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$hospital_id]);
$week_logs = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE hospital_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute([$hospital_id]);
$month_logs = $stmt->fetchColumn();

$page_title = "Audit Logs";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="../dashboards/admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Audit Logs</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="fa fa-history"></i> Audit Logs
                </h4>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Total Logs">Total Logs</h5>
                            <h3 class="mt-3 mb-3"><?php echo number_format($total_logs); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-primary rounded">
                                <i class="fa fa-history font-20 text-primary"></i>
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
                            <h5 class="text-muted fw-normal mt-0" title="Today's Logs">Today</h5>
                            <h3 class="mt-3 mb-3"><?php echo number_format($today_logs); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-success rounded">
                                <i class="fa fa-calendar-day font-20 text-success"></i>
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
                            <h5 class="text-muted fw-normal mt-0" title="This Week">This Week</h5>
                            <h3 class="mt-3 mb-3"><?php echo number_format($week_logs); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-info rounded">
                                <i class="fa fa-calendar-week font-20 text-info"></i>
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
                            <h5 class="text-muted fw-normal mt-0" title="This Month">This Month</h5>
                            <h3 class="mt-3 mb-3"><?php echo number_format($month_logs); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-warning rounded">
                                <i class="fa fa-calendar-alt font-20 text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Filters -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-filter"></i> Filters
                    </h4>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <select name="user_id" class="form-select">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name']); ?> (<?php echo ucfirst($user['role']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Action Type</label>
                            <select name="action_type" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($action_types as $action_type): ?>
                                <option value="<?php echo $action_type; ?>" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == $action_type) ? 'selected' : ''; ?>>
                                    <?php echo ucwords(str_replace('_', ' ', $action_type)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-search"></i> Filter
                        </button>
                        
                        <a href="audit_logs.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fa fa-refresh"></i> Clear Filters
                        </a>
                    </form>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-cogs"></i> Actions
                    </h4>
                </div>
                <div class="card-body">
                    <button class="btn btn-outline-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                        <i class="fa fa-trash"></i> Clear Old Logs
                    </button>
                    
                    <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#exportLogsModal">
                        <i class="fa fa-download"></i> Export Logs
                    </button>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">
                        <i class="fa fa-list"></i> Activity Logs
                    </h4>
                    <div class="text-muted">
                        Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo number_format($total_records); ?> records
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-title bg-soft-primary rounded">
                                                    <?php echo strtoupper(substr($log['user_name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($log['user_name']); ?></h6>
                                                <small class="text-muted"><?php echo ucfirst($log['user_role']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getActionColor($log['action_type']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $log['action_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-wrap" style="max-width: 300px;">
                                            <?php echo htmlspecialchars($log['description']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                    </td>
                                    <td>
                                        <div>
                                            <div><?php echo date('M d, Y', strtotime($log['created_at'])); ?></div>
                                            <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Old Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="clear_logs">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action will permanently delete audit logs older than the specified number of days.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delete logs older than (days)</label>
                        <select name="days" class="form-select" required>
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                            <option value="180">180 days</option>
                            <option value="365">1 year</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Clear Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Logs Modal -->
<div class="modal fade" id="exportLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Audit Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="export_logs">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action Type (Optional)</label>
                        <select name="action_type" class="form-select">
                            <option value="">All Actions</option>
                            <?php foreach ($action_types as $action_type): ?>
                            <option value="<?php echo $action_type; ?>"><?php echo ucwords(str_replace('_', ' ', $action_type)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Export</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
function getActionColor($action_type) {
    $colors = [
        'login' => 'success',
        'logout' => 'secondary',
        'create' => 'primary',
        'update' => 'warning',
        'delete' => 'danger',
        'view' => 'info',
        'export' => 'dark',
        'import' => 'dark',
        'backup' => 'success',
        'restore' => 'warning',
        'settings' => 'info',
        'notification' => 'primary',
        'payment' => 'success',
        'billing' => 'warning',
        'appointment' => 'info',
        'patient' => 'primary',
        'doctor' => 'success',
        'nurse' => 'info',
        'staff' => 'secondary',
        'pharmacy' => 'warning',
        'lab' => 'danger',
        'equipment' => 'dark',
        'room' => 'primary',
        'bed' => 'success',
        'shift' => 'info',
        'attendance' => 'warning',
        'salary' => 'success',
        'ambulance' => 'danger',
        'insurance' => 'primary',
        'feedback' => 'info'
    ];
    
    return $colors[$action_type] ?? 'secondary';
}
?>

<?php include '../includes/footer.php'; ?>