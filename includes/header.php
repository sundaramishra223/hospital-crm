<?php
$user_details = getUserDetails($_SESSION['user_id']);
$site_title = getSetting('site_title', 'Hospital CRM');
$logo = getSetting('logo', 'assets/images/logo.png');
$theme_color = getSetting('theme_color', '#007bff');
$theme_mode = getSetting('theme_mode', 'light');
?>

<header class="main-header" style="--primary-color: <?php echo $theme_color; ?>;">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <!-- Logo and Brand -->
            <div class="navbar-brand-wrapper">
                <a class="navbar-brand" href="index.php">
                    <img src="<?php echo $logo; ?>" alt="Logo" class="brand-logo">
                    <span class="brand-text"><?php echo $site_title; ?></span>
                </a>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="search-wrapper d-none d-md-block">
                <form class="search-form" action="modules/search.php" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" placeholder="Search patients, doctors, appointments..." autocomplete="off">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Right Side Navigation -->
            <div class="navbar-nav-right">
                <ul class="navbar-nav">
                    <!-- Quick Actions -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle quick-actions" href="#" data-toggle="dropdown">
                            <i class="fa fa-plus-circle"></i>
                            <span class="d-none d-lg-inline">Quick Add</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'receptionist'): ?>
                                <a class="dropdown-item" href="modules/patients.php?action=add">
                                    <i class="fa fa-user-plus"></i> Add Patient
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <a class="dropdown-item" href="modules/doctors.php?action=add">
                                    <i class="fa fa-user-md"></i> Add Doctor
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="modules/appointments.php?action=add">
                                    <i class="fa fa-calendar-plus"></i> Schedule Appointment
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['user_role'] == 'doctor' || $_SESSION['user_role'] == 'nurse'): ?>
                                <a class="dropdown-item" href="modules/appointments.php?action=add">
                                    <i class="fa fa-calendar-plus"></i> Schedule Appointment
                                </a>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle notifications" href="#" data-toggle="dropdown">
                            <i class="fa fa-bell"></i>
                            <span class="notification-badge" id="notificationCount">3</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right notification-dropdown">
                            <div class="dropdown-header">
                                <h6>Notifications</h6>
                                <a href="modules/notifications.php" class="view-all">View All</a>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <!-- Notifications will be loaded via AJAX -->
                            </div>
                        </div>
                    </li>
                    
                    <!-- Theme Toggle -->
                    <li class="nav-item">
                        <a class="nav-link theme-toggle" href="#" onclick="toggleTheme()">
                            <i class="fa fa-moon-o" id="themeIcon"></i>
                        </a>
                    </li>
                    
                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-profile" href="#" data-toggle="dropdown">
                            <img src="<?php echo $user_details['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile" class="profile-img">
                            <span class="user-name d-none d-lg-inline"><?php echo $user_details['name']; ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right user-dropdown">
                            <div class="dropdown-header">
                                <div class="user-info">
                                    <img src="<?php echo $user_details['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile">
                                    <div class="user-details">
                                        <h6><?php echo $user_details['name']; ?></h6>
                                        <small><?php echo ucfirst($_SESSION['user_role']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="modules/profile.php">
                                <i class="fa fa-user"></i> My Profile
                            </a>
                            <a class="dropdown-item" href="modules/settings.php">
                                <i class="fa fa-cog"></i> Settings
                            </a>
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <a class="dropdown-item" href="modules/system-settings.php">
                                    <i class="fa fa-wrench"></i> System Settings
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fa fa-sign-out"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<style>
/* Header Styles */
.main-header {
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    height: 70px;
}

.dark-mode .main-header {
    background: #1a1a1a;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.navbar {
    padding: 0 20px;
    height: 70px;
}

.navbar-brand-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--primary-color);
}

.brand-logo {
    height: 40px;
    width: 40px;
    object-fit: contain;
}

.brand-text {
    font-size: 22px;
    font-weight: 600;
    color: var(--primary-color);
}

.sidebar-toggle {
    background: none;
    border: none;
    color: #666;
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    background: #f8f9fa;
    color: var(--primary-color);
}

.dark-mode .sidebar-toggle {
    color: #ccc;
}

.dark-mode .sidebar-toggle:hover {
    background: #333;
    color: var(--primary-color);
}

.search-wrapper {
    flex: 1;
    max-width: 400px;
    margin: 0 30px;
}

.search-form .input-group {
    border-radius: 25px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-form .form-control {
    border: none;
    padding: 12px 20px;
    border-radius: 25px 0 0 25px;
}

.search-form .btn {
    border-radius: 0 25px 25px 0;
    border: none;
    background: var(--primary-color);
    padding: 12px 20px;
}

.navbar-nav-right .nav-link {
    color: #666;
    padding: 15px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.navbar-nav-right .nav-link:hover {
    color: var(--primary-color);
    background: #f8f9fa;
}

.dark-mode .navbar-nav-right .nav-link {
    color: #ccc;
}

.dark-mode .navbar-nav-right .nav-link:hover {
    background: #333;
}

.notification-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-color);
}

.user-dropdown {
    min-width: 280px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.user-dropdown .dropdown-header {
    padding: 20px;
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: white;
    border-radius: 10px 10px 0 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-info img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
}

.user-details h6 {
    margin: 0;
    font-weight: 600;
}

.user-details small {
    opacity: 0.8;
}

.notification-dropdown {
    min-width: 350px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-dropdown .dropdown-header {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.notification-dropdown .view-all {
    color: var(--primary-color);
    font-size: 12px;
    text-decoration: none;
}

/* Responsive */
@media (max-width: 768px) {
    .search-wrapper {
        display: none !important;
    }
    
    .brand-text {
        display: none;
    }
    
    .user-name {
        display: none !important;
    }
    
    .navbar {
        padding: 0 15px;
    }
}
</style>

<script>
// Theme toggle function
function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById('themeIcon');
    
    if (body.classList.contains('dark-mode')) {
        body.classList.remove('dark-mode');
        body.classList.add('light-mode');
        icon.className = 'fa fa-moon-o';
        updateSetting('theme_mode', 'light');
    } else {
        body.classList.remove('light-mode');
        body.classList.add('dark-mode');
        icon.className = 'fa fa-sun-o';
        updateSetting('theme_mode', 'dark');
    }
}

// Load notifications
function loadNotifications() {
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('notificationCount').textContent = data.count;
            document.getElementById('notificationList').innerHTML = data.html;
        });
}

// Update setting via AJAX
function updateSetting(key, value) {
    fetch('api/update-setting.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({key: key, value: value})
    });
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setInterval(loadNotifications, 30000); // Refresh every 30 seconds
});
</script>