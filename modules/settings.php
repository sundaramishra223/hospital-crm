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
if (!hasPermission($_SESSION['user_role'], 'settings')) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_general':
                $hospital_name = sanitize($_POST['hospital_name']);
                $hospital_address = sanitize($_POST['hospital_address']);
                $hospital_phone = sanitize($_POST['hospital_phone']);
                $hospital_email = sanitize($_POST['hospital_email']);
                $hospital_website = sanitize($_POST['hospital_website']);
                $currency = sanitize($_POST['currency']);
                $tax_rate = sanitize($_POST['tax_rate']);
                $timezone = sanitize($_POST['timezone']);
                
                $sql = "UPDATE system_settings SET 
                        hospital_name = ?, hospital_address = ?, hospital_phone = ?, 
                        hospital_email = ?, hospital_website = ?, currency = ?, 
                        tax_rate = ?, timezone = ?, updated_at = NOW() 
                        WHERE hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssdsi", $hospital_name, $hospital_address, $hospital_phone, 
                                $hospital_email, $hospital_website, $currency, $tax_rate, $timezone, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'General settings updated');
                    $success_message = "General settings updated successfully!";
                } else {
                    $error_message = "Error updating general settings: " . $conn->error;
                }
                break;
                
            case 'update_appearance':
                $primary_color = sanitize($_POST['primary_color']);
                $secondary_color = sanitize($_POST['secondary_color']);
                $logo_url = sanitize($_POST['logo_url']);
                $favicon_url = sanitize($_POST['favicon_url']);
                $enable_dark_mode = isset($_POST['enable_dark_mode']) ? 1 : 0;
                
                $sql = "UPDATE system_settings SET 
                        primary_color = ?, secondary_color = ?, logo_url = ?, 
                        favicon_url = ?, enable_dark_mode = ?, updated_at = NOW() 
                        WHERE hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $primary_color, $secondary_color, $logo_url, 
                                $favicon_url, $enable_dark_mode, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Appearance settings updated');
                    $success_message = "Appearance settings updated successfully!";
                } else {
                    $error_message = "Error updating appearance settings: " . $conn->error;
                }
                break;
                
            case 'update_notifications':
                $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
                $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
                $appointment_reminders = isset($_POST['appointment_reminders']) ? 1 : 0;
                $bill_reminders = isset($_POST['bill_reminders']) ? 1 : 0;
                $reminder_hours = sanitize($_POST['reminder_hours']);
                
                $sql = "UPDATE system_settings SET 
                        email_notifications = ?, sms_notifications = ?, appointment_reminders = ?, 
                        bill_reminders = ?, reminder_hours = ?, updated_at = NOW() 
                        WHERE hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiiis", $email_notifications, $sms_notifications, $appointment_reminders, 
                                $bill_reminders, $reminder_hours, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Notification settings updated');
                    $success_message = "Notification settings updated successfully!";
                } else {
                    $error_message = "Error updating notification settings: " . $conn->error;
                }
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
    $sql = "INSERT INTO system_settings (hospital_id, hospital_name, currency, tax_rate, timezone, 
            primary_color, secondary_color, enable_dark_mode, email_notifications, 
            appointment_reminders, reminder_hours, created_at) 
            VALUES (?, 'Hospital Name', 'USD', 10.00, 'UTC', '#007bff', '#6c757d', 0, 1, 1, '24')";
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

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fa fa-cogs me-2"></i>System Settings
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

                    <!-- Settings Tabs -->
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fa fa-cog me-1"></i>General Settings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab">
                                <i class="fa fa-palette me-1"></i>Appearance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                                <i class="fa fa-bell me-1"></i>Notifications
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="settingsTabContent">
                        <!-- General Settings Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_general">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Hospital Information</h5>
                                        
                                        <div class="mb-3">
                                            <label for="hospital_name" class="form-label">Hospital Name *</label>
                                            <input type="text" class="form-control" id="hospital_name" name="hospital_name" 
                                                   value="<?php echo htmlspecialchars($settings['hospital_name'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="hospital_address" class="form-label">Hospital Address</label>
                                            <textarea class="form-control" id="hospital_address" name="hospital_address" rows="3"><?php echo htmlspecialchars($settings['hospital_address'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="hospital_phone" class="form-label">Hospital Phone</label>
                                            <input type="tel" class="form-control" id="hospital_phone" name="hospital_phone" 
                                                   value="<?php echo htmlspecialchars($settings['hospital_phone'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="hospital_email" class="form-label">Hospital Email</label>
                                            <input type="email" class="form-control" id="hospital_email" name="hospital_email" 
                                                   value="<?php echo htmlspecialchars($settings['hospital_email'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="hospital_website" class="form-label">Hospital Website</label>
                                            <input type="url" class="form-control" id="hospital_website" name="hospital_website" 
                                                   value="<?php echo htmlspecialchars($settings['hospital_website'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">System Configuration</h5>
                                        
                                        <div class="mb-3">
                                            <label for="currency" class="form-label">Default Currency</label>
                                            <select class="form-control" id="currency" name="currency">
                                                <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                                <option value="EUR" <?php echo ($settings['currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                                <option value="INR" <?php echo ($settings['currency'] ?? '') == 'INR' ? 'selected' : ''; ?>>INR - Indian Rupee</option>
                                                <option value="BTC" <?php echo ($settings['currency'] ?? '') == 'BTC' ? 'selected' : ''; ?>>BTC - Bitcoin</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tax_rate" class="form-label">Default Tax Rate (%)</label>
                                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                                   value="<?php echo $settings['tax_rate'] ?? 10; ?>" min="0" max="100" step="0.01">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="timezone" class="form-label">Timezone</label>
                                            <select class="form-control" id="timezone" name="timezone">
                                                <option value="UTC" <?php echo ($settings['timezone'] ?? '') == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                <option value="America/New_York" <?php echo ($settings['timezone'] ?? '') == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                                <option value="America/Chicago" <?php echo ($settings['timezone'] ?? '') == 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                                <option value="America/Denver" <?php echo ($settings['timezone'] ?? '') == 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                                <option value="America/Los_Angeles" <?php echo ($settings['timezone'] ?? '') == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                                <option value="Asia/Kolkata" <?php echo ($settings['timezone'] ?? '') == 'Asia/Kolkata' ? 'selected' : ''; ?>>India Standard Time</option>
                                                <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') == 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-1"></i>Save General Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Appearance Tab -->
                        <div class="tab-pane fade" id="appearance" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_appearance">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Color Scheme</h5>
                                        
                                        <div class="mb-3">
                                            <label for="primary_color" class="form-label">Primary Color</label>
                                            <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" 
                                                   value="<?php echo $settings['primary_color'] ?? '#007bff'; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="secondary_color" class="form-label">Secondary Color</label>
                                            <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" 
                                                   value="<?php echo $settings['secondary_color'] ?? '#6c757d'; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enable_dark_mode" name="enable_dark_mode" 
                                                       <?php echo ($settings['enable_dark_mode'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_dark_mode">
                                                    Enable Dark Mode
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Branding</h5>
                                        
                                        <div class="mb-3">
                                            <label for="logo_url" class="form-label">Logo URL</label>
                                            <input type="url" class="form-control" id="logo_url" name="logo_url" 
                                                   value="<?php echo htmlspecialchars($settings['logo_url'] ?? ''); ?>">
                                            <small class="text-muted">Enter the URL of your hospital logo</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="favicon_url" class="form-label">Favicon URL</label>
                                            <input type="url" class="form-control" id="favicon_url" name="favicon_url" 
                                                   value="<?php echo htmlspecialchars($settings['favicon_url'] ?? ''); ?>">
                                            <small class="text-muted">Enter the URL of your favicon</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>Preview</h6>
                                            <div class="border rounded p-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <?php if ($settings['logo_url']): ?>
                                                        <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="Logo" style="height: 40px; margin-right: 10px;">
                                                    <?php endif; ?>
                                                    <span class="h5 mb-0"><?php echo htmlspecialchars($settings['hospital_name'] ?? 'Hospital Name'); ?></span>
                                                </div>
                                                <small class="text-muted">This is how your hospital name and logo will appear</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-1"></i>Save Appearance Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Notifications Tab -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_notifications">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Notification Preferences</h5>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                                       <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="email_notifications">
                                                    Enable Email Notifications
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications" 
                                                       <?php echo ($settings['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="sms_notifications">
                                                    Enable SMS Notifications
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="appointment_reminders" name="appointment_reminders" 
                                                       <?php echo ($settings['appointment_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="appointment_reminders">
                                                    Send Appointment Reminders
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="bill_reminders" name="bill_reminders" 
                                                       <?php echo ($settings['bill_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="bill_reminders">
                                                    Send Bill Reminders
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Reminder Settings</h5>
                                        
                                        <div class="mb-3">
                                            <label for="reminder_hours" class="form-label">Reminder Hours Before Appointment</label>
                                            <select class="form-control" id="reminder_hours" name="reminder_hours">
                                                <option value="1" <?php echo ($settings['reminder_hours'] ?? '') == '1' ? 'selected' : ''; ?>>1 hour</option>
                                                <option value="2" <?php echo ($settings['reminder_hours'] ?? '') == '2' ? 'selected' : ''; ?>>2 hours</option>
                                                <option value="6" <?php echo ($settings['reminder_hours'] ?? '') == '6' ? 'selected' : ''; ?>>6 hours</option>
                                                <option value="12" <?php echo ($settings['reminder_hours'] ?? '') == '12' ? 'selected' : ''; ?>>12 hours</option>
                                                <option value="24" <?php echo ($settings['reminder_hours'] ?? '') == '24' ? 'selected' : ''; ?>>24 hours</option>
                                                <option value="48" <?php echo ($settings['reminder_hours'] ?? '') == '48' ? 'selected' : ''; ?>>48 hours</option>
                                            </select>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <h6><i class="fa fa-info-circle me-2"></i>Notification Types</h6>
                                            <ul class="mb-0">
                                                <li><strong>Appointment Reminders:</strong> Notify patients about upcoming appointments</li>
                                                <li><strong>Bill Reminders:</strong> Notify patients about pending bills</li>
                                                <li><strong>System Notifications:</strong> Notify staff about important system events</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-1"></i>Save Notification Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Color preview functionality
document.getElementById('primary_color').addEventListener('change', function() {
    document.documentElement.style.setProperty('--bs-primary', this.value);
});

document.getElementById('secondary_color').addEventListener('change', function() {
    document.documentElement.style.setProperty('--bs-secondary', this.value);
});

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const triggerTabList = [].slice.call(document.querySelectorAll('#settingsTabs button'));
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