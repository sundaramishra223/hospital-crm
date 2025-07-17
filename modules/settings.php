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
    $success = 'Settings updated successfully!';
}

// Get current settings
$site_title = getSetting('site_title', 'Hospital CRM');
$logo = getSetting('logo', '../assets/images/logo.png');
$favicon = getSetting('favicon', '../assets/images/favicon.ico');
$theme_color = getSetting('theme_color', '#007bff');
$theme_mode = getSetting('theme_mode', 'light');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($site_title); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon); ?>">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .settings-form { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
        .settings-form label { font-weight: 500; margin-top: 15px; display: block; }
        .settings-form input[type="text"], .settings-form input[type="color"], .settings-form select { width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ccc; }
        .settings-form input[type="file"] { margin-top: 5px; }
        .settings-form img { max-height: 40px; margin-top: 5px; }
        .settings-form button { margin-top: 20px; }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
    </style>
</head>
<body>
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

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</body>
</html>