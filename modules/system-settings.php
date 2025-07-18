<?php
// System Settings - Modern Cliniva-Inspired UI
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/functions.php';
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Settings keys
$settings = [
    'site_title' => 'Site Title',
    'logo' => 'Logo URL',
    'favicon' => 'Favicon URL',
    'theme_color' => 'Theme Color',
    'theme_mode' => 'Theme Mode',
    'hospital_name' => 'Hospital Name',
    'hospital_address' => 'Hospital Address',
    'hospital_phone' => 'Hospital Phone',
    'hospital_email' => 'Hospital Email',
];

// Fetch current settings
$current = [];
foreach ($settings as $key => $label) {
    $current[$key] = getSetting($key, '');
}
?>
<style>
.settings-bg {
    background: linear-gradient(135deg, rgba(34,193,195,0.12) 0%, rgba(253,187,45,0.12) 100%);
    min-height: 100vh;
    padding: 30px 0;
}
.glass-card {
    background: rgba(255,255,255,0.25);
    box-shadow: 0 8px 32px 0 rgba(31,38,135,0.18);
    backdrop-filter: blur(8px);
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,0.18);
    transition: box-shadow 0.3s;
}
.glass-card:hover {
    box-shadow: 0 12px 40px 0 rgba(31,38,135,0.22);
}
.settings-label {
    font-weight: 500;
    color: #222;
}
.settings-input {
    border-radius: 10px;
    border: 1px solid #e3e3e3;
    padding: 10px 14px;
    margin-bottom: 16px;
}
.settings-btn {
    background: #22b8cf;
    color: #fff;
    border-radius: 10px;
    padding: 10px 28px;
    font-weight: 600;
    border: none;
    transition: background 0.2s;
}
.settings-btn:hover {
    background: #1098ad;
}
.settings-section-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 18px;
    color: #0b7285;
}
</style>
<div class="container settings-bg">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-2">System Settings <i class="fa fa-cogs text-primary"></i></h2>
            <p class="text-muted">Manage global system settings, appearance, and hospital information.</p>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form id="settingsForm" class="glass-card p-4">
                <div class="settings-section-title">General Settings</div>
                <label class="settings-label">Site Title</label>
                <input type="text" name="site_title" class="form-control settings-input" value="<?php echo htmlspecialchars($current['site_title']); ?>" required>
                <label class="settings-label">Logo URL</label>
                <input type="text" name="logo" class="form-control settings-input" value="<?php echo htmlspecialchars($current['logo']); ?>">
                <label class="settings-label">Favicon URL</label>
                <input type="text" name="favicon" class="form-control settings-input" value="<?php echo htmlspecialchars($current['favicon']); ?>">
                <label class="settings-label">Theme Color</label>
                <input type="color" name="theme_color" class="form-control settings-input" value="<?php echo htmlspecialchars($current['theme_color'] ?: '#667eea'); ?>">
                <label class="settings-label">Theme Mode</label>
                <select name="theme_mode" class="form-control settings-input">
                    <option value="light" <?php if($current['theme_mode']==='light') echo 'selected'; ?>>Light</option>
                    <option value="dark" <?php if($current['theme_mode']==='dark') echo 'selected'; ?>>Dark</option>
                </select>
                <div class="settings-section-title mt-4">Hospital Information</div>
                <label class="settings-label">Hospital Name</label>
                <input type="text" name="hospital_name" class="form-control settings-input" value="<?php echo htmlspecialchars($current['hospital_name']); ?>">
                <label class="settings-label">Hospital Address</label>
                <input type="text" name="hospital_address" class="form-control settings-input" value="<?php echo htmlspecialchars($current['hospital_address']); ?>">
                <label class="settings-label">Hospital Phone</label>
                <input type="text" name="hospital_phone" class="form-control settings-input" value="<?php echo htmlspecialchars($current['hospital_phone']); ?>">
                <label class="settings-label">Hospital Email</label>
                <input type="email" name="hospital_email" class="form-control settings-input" value="<?php echo htmlspecialchars($current['hospital_email']); ?>">
                <div class="text-end mt-4">
                    <button type="submit" class="settings-btn">Save Settings</button>
                </div>
                <div id="settingsMsg" class="mt-3"></div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('settingsForm').onsubmit = function(e) {
    e.preventDefault();
    var form = e.target;
    var data = new FormData(form);
    var msg = document.getElementById('settingsMsg');
    msg.innerHTML = '<span class="text-info">Saving...</span>';
    fetch('../api/update-setting.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(resp => {
        if(resp.success) {
            msg.innerHTML = '<span class="text-success">Settings updated successfully!</span>';
        } else {
            msg.innerHTML = '<span class="text-danger">Failed to update settings.</span>';
        }
    })
    .catch(() => {
        msg.innerHTML = '<span class="text-danger">Error saving settings.</span>';
    });
};
</script>
<?php require_once '../includes/footer.php'; ?>