<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }
require_once 'includes/functions.php';
$error_message = '';
if ($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $role = 'admin';
    if (empty($username) || empty($password)) {
        $error_message = 'All fields are required';
    } else {
        $_POST['role'] = $role;
        include 'login.php';
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Hospital CRM</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
</head>
<body class="login-page">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card p-4 shadow-lg" style="min-width:350px;">
        <h3 class="mb-3 text-center">Admin Login</h3>
        <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="role" value="admin">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>
</body>
</html>