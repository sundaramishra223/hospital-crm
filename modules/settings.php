<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

// Only admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_title = $_POST['site_title'] ?? '';
    $theme_color = $_POST['theme_color'] ?? '#007bff';
    $theme_mode = $_POST['theme_mode'] ?? 'light';
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $logo_path = uploadFile($_FILES['logo'], '../assets/images/');
        if ($logo_path) {
            updateSetting('logo', $logo_path);
        }
    }
    // Handle favicon upload
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
        $favicon_path = uploadFile($_FILES['favicon'], '../assets/images/');
        if ($favicon_path) {
            updateSetting('favicon', $favicon_path);
        }
    }
    updateSetting('site_title', $site_title);
    updateSetting('theme_color', $theme_color);
    updateSetting('theme_mode', $theme_mode);
    $enable_tax = isset($_POST['enable_tax']) ? 1 : 0;
    $tax_percent = $_POST['tax_percent'] ?? 0;
    $enable_multi_currency = isset($_POST['enable_multi_currency']) ? 1 : 0;
    $default_currency = $_POST['default_currency'] ?? 'INR';
    $enable_crypto = isset($_POST['enable_crypto']) ? 1 : 0;
    updateSetting('enable_tax', $enable_tax);
    updateSetting('tax_percent', $tax_percent);
    updateSetting('enable_multi_currency', $enable_multi_currency);
    updateSetting('default_currency', $default_currency);
    updateSetting('enable_crypto', $enable_crypto);
    $template_reminder = $_POST['template_reminder'] ?? '';
    $template_bill = $_POST['template_bill'] ?? '';
    $template_emergency = $_POST['template_emergency'] ?? '';
    $template_custom = $_POST['template_custom'] ?? '';
    updateSetting('template_reminder', $template_reminder);
    updateSetting('template_bill', $template_bill);
    updateSetting('template_emergency', $template_emergency);
    updateSetting('template_custom', $template_custom);
    $success = 'Settings updated successfully!';
}

// Get current settings
$site_title = getSetting('site_title', 'Hospital CRM');
$logo = getSetting('logo', '../assets/images/logo.png');
$favicon = getSetting('favicon', '../assets/images/favicon.ico');
$theme_color = getSetting('theme_color', '#007bff');
$theme_mode = getSetting('theme_mode', 'light');
$enable_tax = getSetting('enable_tax', 0);
$tax_percent = getSetting('tax_percent', 0);
$enable_multi_currency = getSetting('enable_multi_currency', 0);
$default_currency = getSetting('default_currency', 'INR');
$enable_crypto = getSetting('enable_crypto', 0);
$template_reminder = getSetting('template_reminder', 'Dear {name}, your appointment is scheduled on {date}.');
$template_bill = getSetting('template_bill', 'Dear {name}, your bill amount is {amount}.');
$template_emergency = getSetting('template_emergency', 'Emergency alert: {message}');
$template_custom = getSetting('template_custom', 'Hello {name}, ...');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($site_title); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon); ?>">
    <style>
        body.light-mode { background: #f5f6fa; color: #222; }
        body.dark-mode { background: #181a1b; color: #eee; }
        .settings-form { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        body.dark-mode .settings-form { background: #23272b; color: #eee; }
        .settings-form label { font-weight: 500; margin-top: 15px; display: block; }
        .settings-form input[type="text"], .settings-form input[type="color"], .settings-form select { width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ccc; }
        body.dark-mode .settings-form input, body.dark-mode .settings-form select { background: #181a1b; color: #eee; border: 1px solid #444; }
        .settings-form input[type="file"] { margin-top: 5px; }
        .settings-form img { max-height: 40px; margin-top: 5px; }
        .settings-form button { margin-top: 20px; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        body.dark-mode .alert-success { color: #d4edda; background: #155724; border: 1px solid #155724; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($theme_mode); ?>-mode">
    <div class="settings-form">
        <h2>System Settings</h2>
        <?php if ($success): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label for="site_title">Site Title</label>
            <input type="text" name="site_title" id="site_title" value="<?php echo htmlspecialchars($site_title); ?>" required>

            <label for="logo">Logo</label>
            <input type="file" name="logo" id="logo" accept="image/*">
            <?php if ($logo): ?><img src="<?php echo $logo; ?>" alt="Logo"><?php endif; ?>

            <label for="favicon">Favicon</label>
            <input type="file" name="favicon" id="favicon" accept="image/x-icon,image/png">
            <?php if ($favicon): ?><img src="<?php echo $favicon; ?>" alt="Favicon"><?php endif; ?>

            <label for="theme_color">Theme Color</label>
            <input type="color" name="theme_color" id="theme_color" value="<?php echo htmlspecialchars($theme_color); ?>">

            <label for="theme_mode">Theme Mode</label>
            <select name="theme_mode" id="theme_mode">
                <option value="light" <?php if ($theme_mode === 'light') echo 'selected'; ?>>Light</option>
                <option value="dark" <?php if ($theme_mode === 'dark') echo 'selected'; ?>>Dark</option>
            </select>

            <label><input type="checkbox" name="enable_tax" value="1" <?php if ($enable_tax) echo 'checked'; ?>> Enable Tax</label>
            <input type="number" name="tax_percent" value="<?php echo htmlspecialchars($tax_percent); ?>" min="0" max="100" step="0.01" placeholder="Tax %">
            <label><input type="checkbox" name="enable_multi_currency" value="1" <?php if ($enable_multi_currency) echo 'checked'; ?>> Enable Multi-Currency</label>
            <input type="text" name="default_currency" value="<?php echo htmlspecialchars($default_currency); ?>" placeholder="Default Currency (e.g. INR, USD)">
            <label><input type="checkbox" name="enable_crypto" value="1" <?php if ($enable_crypto) echo 'checked'; ?>> Enable Crypto</label>

            <h3>Notification Templates</h3>
            <label>Appointment Reminder Template</label>
            <textarea name="template_reminder" rows="2"><?php echo htmlspecialchars($template_reminder); ?></textarea>
            <label>Bill Template</label>
            <textarea name="template_bill" rows="2"><?php echo htmlspecialchars($template_bill); ?></textarea>
            <label>Emergency Template</label>
            <textarea name="template_emergency" rows="2"><?php echo htmlspecialchars($template_emergency); ?></textarea>
            <label>Custom Template</label>
            <textarea name="template_custom" rows="2"><?php echo htmlspecialchars($template_custom); ?></textarea>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</body>
</html>