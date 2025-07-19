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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_template':
                if ($role == 'admin') {
                    $template_name = sanitize($_POST['template_name']);
                    $template_type = sanitize($_POST['template_type']);
                    $subject = sanitize($_POST['subject']);
                    $content = sanitize($_POST['content']);
                    $variables = sanitize($_POST['variables']);
                    
                    $stmt = $pdo->prepare("INSERT INTO notification_templates (name, type, subject, content, variables, hospital_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$template_name, $template_type, $subject, $content, $variables, $hospital_id]);
                    
                    addActivityLog($user_id, 'notification_template_added', "Added notification template: $template_name");
                    $success = "Notification template added successfully!";
                }
                break;
                
            case 'update_template':
                if ($role == 'admin') {
                    $template_id = (int)$_POST['template_id'];
                    $template_name = sanitize($_POST['template_name']);
                    $template_type = sanitize($_POST['template_type']);
                    $subject = sanitize($_POST['subject']);
                    $content = sanitize($_POST['content']);
                    $variables = sanitize($_POST['variables']);
                    
                    $stmt = $pdo->prepare("UPDATE notification_templates SET name = ?, type = ?, subject = ?, content = ?, variables = ? WHERE id = ? AND hospital_id = ?");
                    $stmt->execute([$template_name, $template_type, $subject, $content, $variables, $template_id, $hospital_id]);
                    
                    addActivityLog($user_id, 'notification_template_updated', "Updated notification template: $template_name");
                    $success = "Notification template updated successfully!";
                }
                break;
                
            case 'send_notification':
                $recipient_type = sanitize($_POST['recipient_type']);
                $recipient_id = (int)$_POST['recipient_id'];
                $template_id = (int)$_POST['template_id'];
                $custom_message = sanitize($_POST['custom_message']);
                
                // Get template
                $stmt = $pdo->prepare("SELECT * FROM notification_templates WHERE id = ? AND hospital_id = ?");
                $stmt->execute([$template_id, $hospital_id]);
                $template = $stmt->fetch();
                
                if ($template) {
                    $message = $custom_message ?: $template['content'];
                    
                    // Send notification
                    $stmt = $pdo->prepare("INSERT INTO notifications (recipient_type, recipient_id, type, subject, message, sent_by, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$recipient_type, $recipient_id, $template['type'], $template['subject'], $message, $user_id, $hospital_id]);
                    
                    addActivityLog($user_id, 'notification_sent', "Sent notification to $recipient_type ID: $recipient_id");
                    $success = "Notification sent successfully!";
                }
                break;
        }
    }
}

// Get notification templates
$templates = [];
if ($role == 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM notification_templates WHERE hospital_id = ? ORDER BY name");
    $stmt->execute([$hospital_id]);
    $templates = $stmt->fetchAll();
}

// Get notifications for current user
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE recipient_type = 'user' AND recipient_id = ? AND hospital_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id, $hospital_id]);
$notifications = $stmt->fetchAll();

// Get users for notification sending (admin only)
$users = [];
if ($role == 'admin') {
    $stmt = $pdo->prepare("SELECT id, username, name, role FROM users WHERE hospital_id = ? AND status = 'active' ORDER BY name");
    $stmt->execute([$hospital_id]);
    $users = $stmt->fetchAll();
}

// Get patients for notification sending (admin only)
$patients = [];
if ($role == 'admin') {
    $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM patients WHERE status = 'active' ORDER BY first_name");
    $stmt->execute();
    $patients = $stmt->fetchAll();
}

$page_title = "Notifications Management";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="../dashboards/admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Notifications</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="fa fa-bell"></i> Notifications Management
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

    <div class="row">
        <!-- Notification Templates (Admin Only) -->
        <?php if ($role == 'admin'): ?>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">
                        <i class="fa fa-file-text"></i> Notification Templates
                    </h4>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                        <i class="fa fa-plus"></i> Add Template
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($template['name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $template['type'] == 'email' ? 'primary' : 'success'; ?>">
                                            <?php echo ucfirst($template['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($template['subject']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editTemplate(<?php echo $template['id']; ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(<?php echo $template['id']; ?>)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Send Notifications (Admin Only) -->
        <?php if ($role == 'admin'): ?>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-paper-plane"></i> Send Notifications
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="send_notification">
                        
                        <div class="mb-3">
                            <label class="form-label">Recipient Type</label>
                            <select name="recipient_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="user">Staff/Doctor/Nurse</option>
                                <option value="patient">Patient</option>
                                <option value="all_users">All Staff</option>
                                <option value="all_patients">All Patients</option>
                            </select>
                        </div>

                        <div class="mb-3" id="recipient_select_div">
                            <label class="form-label">Recipient</label>
                            <select name="recipient_id" class="form-select">
                                <option value="">Select Recipient</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Template</label>
                            <select name="template_id" class="form-select" required>
                                <option value="">Select Template</option>
                                <?php foreach ($templates as $template): ?>
                                <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Custom Message (Optional)</label>
                            <textarea name="custom_message" class="form-control" rows="3" placeholder="Leave empty to use template content"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-send"></i> Send Notification
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- My Notifications -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-bell"></i> My Notifications
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php echo $notification['type'] == 'email' ? 'primary' : 'success'; ?>">
                                            <?php echo ucfirst($notification['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($notification['subject']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($notification['message'], 0, 100)) . (strlen($notification['message']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></td>
                                    <td>
                                        <?php if ($notification['read_at']): ?>
                                            <span class="badge bg-success">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Unread</span>
                                        <?php endif; ?>
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

<!-- Add Template Modal -->
<?php if ($role == 'admin'): ?>
<div class="modal fade" id="addTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Notification Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_template">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="template_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="template_type" class="form-select" required>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="system">System Notification</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control" rows="5" required placeholder="Use {name}, {email}, {phone} as variables"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Available Variables</label>
                        <input type="text" name="variables" class="form-control" value="{name}, {email}, {phone}, {appointment_date}, {doctor_name}" readonly>
                        <small class="text-muted">These variables can be used in the content</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Template</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Handle recipient type change
document.querySelector('select[name="recipient_type"]').addEventListener('change', function() {
    const recipientType = this.value;
    const recipientSelect = document.querySelector('select[name="recipient_id"]');
    const recipientDiv = document.getElementById('recipient_select_div');
    
    if (recipientType === 'all_users' || recipientType === 'all_patients') {
        recipientDiv.style.display = 'none';
        recipientSelect.value = '';
    } else {
        recipientDiv.style.display = 'block';
        recipientSelect.innerHTML = '<option value="">Select Recipient</option>';
        
        if (recipientType === 'user') {
            <?php if ($role == 'admin'): ?>
            const users = <?php echo json_encode($users); ?>;
            users.forEach(user => {
                recipientSelect.innerHTML += `<option value="${user.id}">${user.name} (${user.role})</option>`;
            });
            <?php endif; ?>
        } else if (recipientType === 'patient') {
            <?php if ($role == 'admin'): ?>
            const patients = <?php echo json_encode($patients); ?>;
            patients.forEach(patient => {
                recipientSelect.innerHTML += `<option value="${patient.id}">${patient.name}</option>`;
            });
            <?php endif; ?>
        }
    }
});

function editTemplate(templateId) {
    // Implementation for editing template
    alert('Edit template functionality will be implemented');
}

function deleteTemplate(templateId) {
    if (confirm('Are you sure you want to delete this template?')) {
        // Implementation for deleting template
        alert('Delete template functionality will be implemented');
    }
}
</script>

<?php include '../includes/footer.php'; ?>