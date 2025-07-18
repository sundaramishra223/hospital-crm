<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success_message = 'You have been successfully logged out.';
}

// Get enabled roles from settings (default: all roles enabled)
$enabled_roles = getSetting('enabled_roles', 'admin,doctor,patient,nurse,staff,pharmacy,lab_tech,receptionist,intern');
$enabled_roles_array = explode(',', $enabled_roles);

// Get background image from settings
$login_bg_image = getSetting('login_bg_image', '');

// Define role information (simplified without features)
$roles = [
    'admin' => [
        'title' => 'Administrator',
        'icon' => 'fa-user-shield',
        'color' => '#667eea'
    ],
    'doctor' => [
        'title' => 'Doctor',
        'icon' => 'fa-user-md',
        'color' => '#38b2ac'
    ],
    'patient' => [
        'title' => 'Patient',
        'icon' => 'fa-user',
        'color' => '#ed8936'
    ],
    'nurse' => [
        'title' => 'Nurse',
        'icon' => 'fa-plus-square',
        'color' => '#9f7aea'
    ],
    'staff' => [
        'title' => 'Staff',
        'icon' => 'fa-users',
        'color' => '#f56565'
    ],
    'pharmacy' => [
        'title' => 'Pharmacy',
        'icon' => 'fa-pills',
        'color' => '#48bb78'
    ],
    'lab_tech' => [
        'title' => 'Lab Technician',
        'icon' => 'fa-flask',
        'color' => '#ed64a6'
    ],
    'receptionist' => [
        'title' => 'Receptionist',
        'icon' => 'fa-desk',
        'color' => '#4299e1'
    ],
    'intern' => [
        'title' => 'Intern',
        'icon' => 'fa-graduation-cap',
        'color' => '#805ad5'
    ]
];

if ($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    
    if (empty($username) || empty($password) || empty($role)) {
        $error_message = 'All fields are required';
    } else {
        $user = authenticateUser($username, $password, $role);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['hospital_id'] = $user['hospital_id'];
            
            // Log login activity
            logActivity($user['id'], 'login', 'User logged in');
            
            header('Location: index.php');
            exit();
        } else {
            $error_message = 'Invalid credentials or role not enabled';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo getSetting('site_title', 'Hospital CRM'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo getSetting('favicon', 'assets/images/favicon.ico'); ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    
    <div class="login-container">
        <!-- Background Image/Animation -->
        <div class="background-animation">
            <?php if ($login_bg_image): ?>
                <div class="bg-image" style="background-image: url('<?php echo $login_bg_image; ?>');"></div>
            <?php else: ?>
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                    <div class="shape shape-4"></div>
                    <div class="shape shape-5"></div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="login-content">
            <!-- Header Section -->
            <div class="login-header">
                <div class="logo-section">
                    <img src="<?php echo getSetting('logo', 'assets/images/logo.png'); ?>" alt="Logo" class="logo">
                    <h1><?php echo getSetting('site_title', 'Hospital CRM'); ?></h1>
                    <p class="subtitle">Multi-Role Healthcare Management System</p>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-modern">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-modern">
                    <i class="fa fa-check-circle"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Role Selection Cards -->
            <div class="role-selection" id="roleSelection">
                <h3 class="section-title">Select Your Role</h3>
                <div class="role-cards">
                    <?php foreach ($roles as $role_key => $role_info): ?>
                        <?php if (in_array($role_key, $enabled_roles_array)): ?>
                            <a href="login_<?php echo $role_key; ?>.php" class="role-card-link">
                                <div class="role-card" data-role="<?php echo $role_key; ?>">
                                    <div class="role-card-header" style="background: linear-gradient(135deg, <?php echo $role_info['color']; ?> 0%, <?php echo adjustBrightness($role_info['color'], -20); ?> 100%);">
                                        <div class="role-icon">
                                            <i class="fa <?php echo $role_info['icon']; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="role-card-body">
                                        <h4 class="role-title"><?php echo $role_info['title']; ?></h4>
                                    </div>
                                    <div class="role-card-footer">
                                        <span class="btn-select-role">
                                            <i class="fa fa-sign-in"></i>
                                            Login as <?php echo $role_info['title']; ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Login Form Modal (Bootstrap 5) -->
            <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content login-modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="loginModalLabel">Login to <span id="selectedRoleTitle">System</span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" class="login-form" id="loginFormElement" autocomplete="off">
                                <input type="hidden" name="role" id="modal-role" value="">
                                <div class="form-group">
                                    <label for="modal-username">
                                        <i class="fa fa-user"></i>
                                        Username
                                    </label>
                                    <input type="text" name="username" id="modal-username" class="form-control" required autocomplete="off">
                                </div>
                                <div class="form-group">
                                    <label for="modal-password">
                                        <i class="fa fa-lock"></i>
                                        Password
                                    </label>
                                    <div class="password-input">
                                        <input type="password" name="password" id="modal-password" class="form-control" required autocomplete="off">
                                        <button type="button" class="btn-toggle-password" onclick="togglePassword()">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" class="btn-login">
                                    <i class="fa fa-sign-in"></i>
                                    Login
                                </button>
                            </form>
                            <div class="form-footer mt-3">
                                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                                <a href="register.php" class="register-link">Create Account</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_title', 'Hospital CRM'); ?>. All rights reserved.</p>
                <div class="footer-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Service</a>
                    <a href="support.php">Support</a>
                </div>
            </div>
        </div> <!-- .login-content -->
    </div> <!-- .login-container -->
    
    <style>
    /* Modern Login Page Styles */
    .login-page {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        font-family: 'Inter', sans-serif;
        overflow-x: hidden;
    }
    
    /* Background Animation/Image */
    .background-animation {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        overflow: hidden;
    }
    
    .bg-image {
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.3;
    }
    
    .floating-shapes {
        position: relative;
        width: 100%;
        height: 100%;
    }
    
    .shape {
        position: absolute;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
    }
    
    .shape-1 {
        width: 80px;
        height: 80px;
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }
    
    .shape-2 {
        width: 120px;
        height: 120px;
        top: 60%;
        right: 10%;
        animation-delay: 2s;
    }
    
    .shape-3 {
        width: 60px;
        height: 60px;
        top: 80%;
        left: 20%;
        animation-delay: 4s;
    }
    
    .shape-4 {
        width: 100px;
        height: 100px;
        top: 10%;
        right: 30%;
        animation-delay: 1s;
    }
    
    .shape-5 {
        width: 40px;
        height: 40px;
        top: 40%;
        left: 60%;
        animation-delay: 3s;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    /* Login Container */
    .login-container {
        position: relative;
        z-index: 2;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .login-content {
        width: 100%;
        max-width: 1200px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 30px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
    }
    
    /* Header */
    .login-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px;
        text-align: center;
        color: white;
    }
    
    .logo-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
    
    .logo {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.2);
        padding: 15px;
    }
    
    .login-header h1 {
        font-size: 36px;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .subtitle {
        font-size: 18px;
        opacity: 0.9;
        margin: 0;
        font-weight: 400;
    }
    
    /* Alerts */
    .alert-modern {
        margin: 20px;
        padding: 15px 20px;
        border-radius: 15px;
        border: none;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
        color: #c53030;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
        color: #22543d;
    }
    
    /* Role Selection */
    .role-selection {
        padding: 40px;
    }
    
    .section-title {
        text-align: center;
        font-size: 28px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 30px;
    }
    
    .role-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .role-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        cursor: pointer;
    }
    
    .role-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    .role-card-header {
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .role-card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }
    
    .role-icon {
        position: relative;
        z-index: 1;
    }
    
    .role-icon i {
        font-size: 48px;
        color: white;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .role-card-body {
        padding: 25px;
        text-align: center;
    }
    
    .role-title {
        font-size: 20px;
        font-weight: 600;
        color: #2d3748;
        margin: 0;
    }
    
    .role-card-footer {
        padding: 20px 25px 25px;
        text-align: center;
    }
    
    .btn-select-role {
        width: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 25px;
        border-radius: 15px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-select-role:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }
    
    /* Login Form */
    .login-form-container {
        padding: 40px;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .form-header {
        text-align: center;
        margin-bottom: 30px;
        position: relative;
    }
    
    .form-header h3 {
        font-size: 24px;
        font-weight: 600;
        color: #2d3748;
        margin: 0 0 20px 0;
    }
    
    .btn-back {
        background: none;
        border: none;
        color: #667eea;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 auto;
        padding: 8px 16px;
        border-radius: 20px;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: rgba(102, 126, 234, 0.1);
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        color: #4a5568;
        margin-bottom: 8px;
    }
    
    .form-group label i {
        color: #667eea;
        width: 16px;
    }
    
    .form-control {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 15px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: white;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .password-input {
        position: relative;
    }
    
    .btn-toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #718096;
        cursor: pointer;
        padding: 5px;
    }
    
    .btn-toggle-password:hover {
        color: #667eea;
    }
    
    .btn-login {
        width: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 18px 25px;
        border-radius: 15px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }
    
    .form-footer {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    
    .forgot-link, .register-link {
        color: #667eea;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    
    .forgot-link:hover, .register-link:hover {
        color: #5a67d8;
    }
    
    /* Footer */
    .login-footer {
        background: #f7fafc;
        padding: 25px 40px;
        text-align: center;
        border-top: 1px solid #e2e8f0;
    }
    
    .login-footer p {
        color: #718096;
        margin: 0 0 10px 0;
        font-size: 14px;
    }
    
    .footer-links {
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    
    .footer-links a {
        color: #667eea;
        text-decoration: none;
        font-size: 12px;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    
    .footer-links a:hover {
        color: #5a67d8;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .login-container {
            padding: 10px;
        }
        
        .login-header {
            padding: 30px 20px;
        }
        
        .login-header h1 {
            font-size: 28px;
        }
        
        .role-selection {
            padding: 20px;
        }
        
        .role-cards {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .login-form-container {
            padding: 20px;
        }
        
        .form-footer {
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }
    }
    /* Login Modal Custom Styles */
    .login-modal-content {
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.15);
        background: rgba(255,255,255,0.98);
        backdrop-filter: blur(10px);
    }
    .login-modal-content .modal-header {
        border-bottom: none;
        padding-bottom: 0;
    }
    .login-modal-content .modal-title {
        font-weight: 700;
        color: #667eea;
    }
    .login-modal-content .modal-body {
        padding-top: 0;
    }
    .modal-backdrop.show { z-index: 1050; }
    .modal { z-index: 1100; }
    .login-modal-content { z-index: 1200; }
    </style>
    
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle JS: Use CDN fallback if local file is missing -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Role selection functionality
    function selectRole(role) {
        console.log('Selecting role:', role); // Debug log
        
        // Set the hidden input value
        document.getElementById('modal-role').value = role;
        
        // Update the title
        document.getElementById('selectedRoleTitle').textContent = getRoleTitle(role);
        
        // Show the login modal
        var modalEl = document.getElementById('loginModal');
        var loginModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        loginModal.show();
        
        // Focus on username field after modal is shown
        setTimeout(() => {
            document.getElementById('modal-username').focus();
        }, 400);
    }
    
    function showRoleSelection() {
        document.getElementById('roleSelection').style.display = 'block';
        document.getElementById('loginForm').style.display = 'none';
    }
    
    function getRoleTitle(role) {
        const titles = {
            'admin': 'Administrator',
            'doctor': 'Doctor',
            'patient': 'Patient',
            'nurse': 'Nurse',
            'staff': 'Staff',
            'pharmacy': 'Pharmacy',
            'lab_tech': 'Lab Technician',
            'receptionist': 'Receptionist',
            'intern': 'Intern'
        };
        return titles[role] || 'System';
    }
    
    function togglePassword() {
        const passwordInput = document.getElementById('modal-password');
        const toggleBtn = document.querySelector('.btn-toggle-password i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.className = 'fa fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleBtn.className = 'fa fa-eye';
        }
    }
    
    // Enhanced form validation and functionality
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded'); // Debug log
        
        // Form submission
        const form = document.getElementById('loginFormElement');
        if (form) {
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('modal-username').value.trim();
                const password = document.getElementById('modal-password').value.trim();
                const role = document.getElementById('modal-role').value;
                
                console.log('Form submitted:', { username, password, role }); // Debug log
                
                if (!username || !password || !role) {
                    e.preventDefault();
                    alert('Please fill in all fields');
                    return false;
                }
                
                // Form is valid, let it submit
                console.log('Form is valid, submitting...'); // Debug log
            });
        }
        
        // Add click handlers to role cards
        const roleCards = document.querySelectorAll('.role-card');
        roleCards.forEach(card => {
            card.addEventListener('click', function() {
                const role = this.getAttribute('data-role');
                selectRole(role);
            });
            
            // Hover effects
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Enter key support for form fields
        document.getElementById('modal-username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('modal-password').focus();
            }
        });
        
        document.getElementById('modal-password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginFormElement').submit();
            }
        });
    });
    </script>
</body>
</html>

<?php
// Helper function to adjust color brightness
function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
?>
