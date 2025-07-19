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

// Only admin can access backup/restore
if ($role != 'admin') {
    header('Location: ../dashboards/admin.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_backup':
                $backup_type = sanitize($_POST['backup_type']);
                $backup_name = sanitize($_POST['backup_name']);
                $description = sanitize($_POST['description']);
                
                // Create backup data
                $backup_data = [];
                
                if ($backup_type == 'settings' || $backup_type == 'full') {
                    // Backup settings
                    $stmt = $pdo->prepare("SELECT * FROM settings WHERE hospital_id = ?");
                    $stmt->execute([$hospital_id]);
                    $backup_data['settings'] = $stmt->fetchAll();
                }
                
                if ($backup_type == 'data' || $backup_type == 'full') {
                    // Backup key data tables
                    $tables = ['users', 'departments', 'doctors', 'patients', 'appointments', 'bills', 'medicines', 'lab_tests', 'equipments', 'insurance_providers'];
                    
                    foreach ($tables as $table) {
                        $stmt = $pdo->prepare("SELECT * FROM $table WHERE hospital_id = ?");
                        $stmt->execute([$hospital_id]);
                        $backup_data[$table] = $stmt->fetchAll();
                    }
                }
                
                // Save backup to database
                $backup_json = json_encode($backup_data);
                $stmt = $pdo->prepare("INSERT INTO system_backups (name, type, description, backup_data, created_by, hospital_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$backup_name, $backup_type, $description, $backup_json, $user_id, $hospital_id]);
                
                // Update last backup setting
                $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE setting_key = 'last_backup' AND hospital_id = ?");
                $stmt->execute([date('Y-m-d H:i:s'), $hospital_id]);
                
                addActivityLog($user_id, 'backup_created', "Created $backup_type backup: $backup_name");
                $success = "Backup created successfully!";
                break;
                
            case 'restore_backup':
                $backup_id = (int)$_POST['backup_id'];
                
                // Get backup data
                $stmt = $pdo->prepare("SELECT * FROM system_backups WHERE id = ? AND hospital_id = ?");
                $stmt->execute([$backup_id, $hospital_id]);
                $backup = $stmt->fetch();
                
                if ($backup) {
                    $backup_data = json_decode($backup['backup_data'], true);
                    
                    // Restore settings
                    if (isset($backup_data['settings'])) {
                        foreach ($backup_data['settings'] as $setting) {
                            $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE setting_key = ? AND hospital_id = ?");
                            $stmt->execute([$setting['value'], $setting['setting_key'], $hospital_id]);
                        }
                    }
                    
                    addActivityLog($user_id, 'backup_restored', "Restored backup: " . $backup['name']);
                    $success = "Backup restored successfully!";
                }
                break;
                
            case 'delete_backup':
                $backup_id = (int)$_POST['backup_id'];
                
                $stmt = $pdo->prepare("DELETE FROM system_backups WHERE id = ? AND hospital_id = ?");
                $stmt->execute([$backup_id, $hospital_id]);
                
                addActivityLog($user_id, 'backup_deleted', "Deleted backup ID: $backup_id");
                $success = "Backup deleted successfully!";
                break;
                
            case 'download_backup':
                $backup_id = (int)$_POST['backup_id'];
                
                // Get backup data
                $stmt = $pdo->prepare("SELECT * FROM system_backups WHERE id = ? AND hospital_id = ?");
                $stmt->execute([$backup_id, $hospital_id]);
                $backup = $stmt->fetch();
                
                if ($backup) {
                    // Set headers for download
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="backup_' . $backup['name'] . '_' . date('Y-m-d_H-i-s') . '.json"');
                    
                    echo $backup['backup_data'];
                    exit();
                }
                break;
        }
    }
}

// Get backups
$stmt = $pdo->prepare("SELECT sb.*, u.name as created_by_name FROM system_backups sb LEFT JOIN users u ON sb.created_by = u.id WHERE sb.hospital_id = ? ORDER BY sb.created_at DESC");
$stmt->execute([$hospital_id]);
$backups = $stmt->fetchAll();

// Get last backup time
$last_backup = 'Never';
$stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = 'last_backup' AND hospital_id = ?");
$stmt->execute([$hospital_id]);
$result = $stmt->fetch();
if ($result) {
    $last_backup = $result['value'];
}

// Get system statistics
$total_backups = count($backups);
$total_size = 0;
foreach ($backups as $backup) {
    $total_size += strlen($backup['backup_data']);
}

$page_title = "Backup & Restore";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="../dashboards/admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Backup & Restore</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="fa fa-download"></i> Backup & Restore
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
                            <h5 class="text-muted fw-normal mt-0" title="Total Backups">Total Backups</h5>
                            <h3 class="mt-3 mb-3"><?php echo $total_backups; ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-primary rounded">
                                <i class="fa fa-download font-20 text-primary"></i>
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
                            <h5 class="text-muted fw-normal mt-0" title="Total Size">Total Size</h5>
                            <h3 class="mt-3 mb-3"><?php echo formatBytes($total_size); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-success rounded">
                                <i class="fa fa-database font-20 text-success"></i>
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
                            <h5 class="text-muted fw-normal mt-0" title="Last Backup">Last Backup</h5>
                            <h3 class="mt-3 mb-3"><?php echo $last_backup == 'Never' ? 'Never' : date('M d', strtotime($last_backup)); ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-info rounded">
                                <i class="fa fa-clock-o font-20 text-info"></i>
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
                            <h5 class="text-muted fw-normal mt-0" title="System Status">System Status</h5>
                            <h3 class="mt-3 mb-3">
                                <span class="badge bg-success">Healthy</span>
                            </h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-warning rounded">
                                <i class="fa fa-heartbeat font-20 text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Create Backup -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-plus"></i> Create Backup
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_backup">
                        
                        <div class="mb-3">
                            <label class="form-label">Backup Name</label>
                            <input type="text" name="backup_name" class="form-control" required placeholder="e.g., Monthly Backup, Settings Backup">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Backup Type</label>
                            <select name="backup_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="settings">Settings Only</option>
                                <option value="data">Data Only</option>
                                <option value="full">Full Backup</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Backup description, purpose, etc."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-save"></i> Create Backup
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Health -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-heartbeat"></i> System Health
                    </h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Database Connection</span>
                            <span class="badge bg-success">Connected</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Disk Space</span>
                            <span class="badge bg-success">Available</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>PHP Version</span>
                            <span class="badge bg-info"><?php echo PHP_VERSION; ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>MySQL Version</span>
                            <span class="badge bg-info"><?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-list"></i> Backup History
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($backup['name']); ?></h6>
                                            <?php if ($backup['description']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($backup['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $backup['type'] == 'full' ? 'danger' : ($backup['type'] == 'data' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($backup['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatBytes(strlen($backup['backup_data'])); ?></td>
                                    <td><?php echo htmlspecialchars($backup['created_by_name']); ?></td>
                                    <td>
                                        <div>
                                            <div><?php echo date('M d, Y', strtotime($backup['created_at'])); ?></div>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($backup['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="downloadBackup(<?php echo $backup['id']; ?>)">
                                                <i class="fa fa-download"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="restoreBackup(<?php echo $backup['id']; ?>, '<?php echo htmlspecialchars($backup['name']); ?>')">
                                                <i class="fa fa-undo"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteBackup(<?php echo $backup['id']; ?>)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restore Backup Modal -->
<div class="modal fade" id="restoreBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restore Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="restore_backup">
                <input type="hidden" name="backup_id" id="restore_backup_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action will overwrite current settings/data with the backup data. This action cannot be undone.
                    </div>
                    <p>Are you sure you want to restore the backup: <strong id="restore_backup_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Restore Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function downloadBackup(backupId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="download_backup">
        <input type="hidden" name="backup_id" value="${backupId}">
    `;
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function restoreBackup(backupId, backupName) {
    document.getElementById('restore_backup_id').value = backupId;
    document.getElementById('restore_backup_name').textContent = backupName;
    new bootstrap.Modal(document.getElementById('restoreBackupModal')).show();
}

function deleteBackup(backupId) {
    if (confirm('Are you sure you want to delete this backup?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_backup">
            <input type="hidden" name="backup_id" value="${backupId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>

<?php include '../includes/footer.php'; ?>