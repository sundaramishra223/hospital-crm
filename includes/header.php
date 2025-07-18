<?php
$user_details = getUserDetails($_SESSION['user_id']);
$site_title = getSetting('site_title', 'Hospital CRM');
$logo = getSetting('logo', 'assets/images/logo.png');
$theme_color = getSetting('theme_color', '#667eea');
$theme_mode = getSetting('theme_mode', 'light');
$notification_count = getNotificationCount($_SESSION['user_id']);
?>

<header class="main-header" style="--primary-color: <?php echo $theme_color; ?>;">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <!-- Logo and Brand -->
            <div class="navbar-brand-wrapper">
                <a class="navbar-brand" href="index.php">
                    <div class="brand-logo-wrapper">
                        <img src="<?php echo $logo; ?>" alt="Logo" class="brand-logo">
                        <div class="logo-glow"></div>
                    </div>
                    <span class="brand-text"><?php echo $site_title; ?></span>
                </a>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <div class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="search-wrapper d-none d-md-block">
                <form class="search-form" action="modules/search.php" method="GET">
                    <div class="search-input-wrapper">
                        <i class="fa fa-search search-icon"></i>
                        <input type="text" class="form-control" name="q" placeholder="Search patients, doctors, appointments..." autocomplete="off">
                        <button class="search-btn" type="submit">
                            <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Right Side Navigation -->
            <div class="navbar-nav-right">
                <ul class="navbar-nav">
                    <!-- Quick Actions -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle quick-actions" href="#" data-bs-toggle="dropdown">
                            <div class="action-icon">
                                <i class="fa fa-plus"></i>
                            </div>
                            <span class="action-text d-none d-lg-inline">Quick Add</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end quick-actions-dropdown">
                            <div class="dropdown-header">
                                <h6>Quick Actions</h6>
                                <p>Add new records quickly</p>
                            </div>
                            <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'receptionist'): ?>
                                <a class="dropdown-item" href="modules/patients.php?action=add">
                                    <div class="action-item">
                                        <div class="action-icon-wrapper">
                                            <i class="fa fa-user-plus"></i>
                                        </div>
                                        <div class="action-content">
                                            <span class="action-title">Add Patient</span>
                                            <span class="action-desc">Register new patient</span>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <a class="dropdown-item" href="modules/doctors.php?action=add">
                                    <div class="action-item">
                                        <div class="action-icon-wrapper">
                                            <i class="fa fa-user-md"></i>
                                        </div>
                                        <div class="action-content">
                                            <span class="action-title">Add Doctor</span>
                                            <span class="action-desc">Register new doctor</span>
                                        </div>
                                    </div>
                                </a>
                                <a class="dropdown-item" href="modules/appointments.php?action=add">
                                    <div class="action-item">
                                        <div class="action-icon-wrapper">
                                            <i class="fa fa-calendar-plus"></i>
                                        </div>
                                        <div class="action-content">
                                            <span class="action-title">Schedule Appointment</span>
                                            <span class="action-desc">Book new appointment</span>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['user_role'] == 'doctor' || $_SESSION['user_role'] == 'nurse'): ?>
                                <a class="dropdown-item" href="modules/appointments.php?action=add">
                                    <div class="action-item">
                                        <div class="action-icon-wrapper">
                                            <i class="fa fa-calendar-plus"></i>
                                        </div>
                                        <div class="action-content">
                                            <span class="action-title">Schedule Appointment</span>
                                            <span class="action-desc">Book new appointment</span>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle notifications" href="#" data-bs-toggle="dropdown">
                            <div class="notification-icon">
                                <i class="fa fa-bell"></i>
                                <?php if ($notification_count > 0): ?>
                                    <span class="notification-badge" id="notificationCount"><?php echo $notification_count; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                            <div class="dropdown-header">
                                <div class="header-content">
                                    <h6>Notifications</h6>
                                    <span class="notification-count"><?php echo $notification_count; ?> new</span>
                                </div>
                                <a href="modules/notifications.php" class="view-all">View All</a>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <!-- Notifications will be loaded via AJAX -->
                                <div class="notification-item">
                                    <div class="notification-icon">
                                        <i class="fa fa-info-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p>Welcome to <?php echo $site_title; ?>!</p>
                                        <span class="notification-time">Just now</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    
                    <!-- Theme Toggle -->
                    <li class="nav-item">
                        <a class="nav-link theme-toggle" href="#" onclick="toggleTheme()">
                            <div class="theme-icon-wrapper">
                                <i class="fa fa-moon-o" id="themeIcon"></i>
                            </div>
                        </a>
                    </li>
                    
                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-profile" href="#" data-bs-toggle="dropdown">
                            <div class="profile-wrapper">
                                <img src="<?php echo $user_details['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile" class="profile-img">
                                <div class="profile-status online"></div>
                                <span class="user-name d-none d-lg-inline"><?php echo $user_details['name']; ?></span>
                                <i class="fa fa-chevron-down profile-arrow"></i>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end user-dropdown">
                            <div class="dropdown-header">
                                <div class="user-info">
                                    <div class="user-avatar-wrapper">
                                        <img src="<?php echo $user_details['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile">
                                        <div class="user-status online"></div>
                                    </div>
                                    <div class="user-details">
                                        <h6><?php echo $user_details['name']; ?></h6>
                                        <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                                        <span class="user-email"><?php echo $user_details['email']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="modules/profile.php">
                                <div class="menu-item">
                                    <i class="fa fa-user"></i>
                                    <span>My Profile</span>
                                </div>
                            </a>
                            <a class="dropdown-item" href="modules/settings.php">
                                <div class="menu-item">
                                    <i class="fa fa-cog"></i>
                                    <span>Settings</span>
                                </div>
                            </a>
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <a class="dropdown-item" href="modules/system-settings.php">
                                    <div class="menu-item">
                                        <i class="fa fa-wrench"></i>
                                        <span>System Settings</span>
                                    </div>
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item logout-item" href="logout.php">
                                <div class="menu-item">
                                    <i class="fa fa-sign-out"></i>
                                    <span>Logout</span>
                                </div>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<style>
/* Modern Header Styles */
.main-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    height: 70px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark-mode .main-header {
    background: rgba(26, 26, 26, 0.95);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.navbar {
    padding: 0 25px;
    height: 70px;
}

/* Brand Section */
.navbar-brand-wrapper {
    display: flex;
    align-items: center;
    gap: 20px;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
    color: var(--primary-color);
    transition: all 0.3s ease;
}

.navbar-brand:hover {
    transform: translateY(-1px);
}

.brand-logo-wrapper {
    position: relative;
    width: 45px;
    height: 45px;
}

.brand-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    padding: 8px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.logo-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    border-radius: 12px;
    opacity: 0.1;
    filter: blur(10px);
    z-index: -1;
}

.brand-text {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary-color);
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Sidebar Toggle */
.sidebar-toggle {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 8px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.sidebar-toggle:hover {
    background: rgba(102, 126, 234, 0.1);
    color: var(--primary-color);
    transform: scale(1.05);
}

.hamburger {
    width: 20px;
    height: 16px;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.hamburger span {
    width: 100%;
    height: 2px;
    background: currentColor;
    border-radius: 2px;
    transition: all 0.3s ease;
}

.sidebar-toggle:hover .hamburger span:nth-child(1) {
    transform: translateY(6px) rotate(45deg);
}

.sidebar-toggle:hover .hamburger span:nth-child(2) {
    opacity: 0;
}

.sidebar-toggle:hover .hamburger span:nth-child(3) {
    transform: translateY(-6px) rotate(-45deg);
}

/* Search Bar */
.search-wrapper {
    flex: 1;
    max-width: 450px;
    margin: 0 30px;
}

.search-input-wrapper {
    position: relative;
    background: #f8f9fa;
    border-radius: 25px;
    padding: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.search-input-wrapper:focus-within {
    background: white;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
    transform: translateY(-1px);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 14px;
    z-index: 2;
}

.search-form .form-control {
    border: none;
    background: transparent;
    padding: 12px 45px 12px 40px;
    border-radius: 20px;
    font-size: 14px;
    outline: none;
}

.search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    border: none;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Navigation Items */
.navbar-nav-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.navbar-nav-right .nav-link {
    color: #666;
    padding: 12px;
    border-radius: 12px;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.navbar-nav-right .nav-link:hover {
    color: var(--primary-color);
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

/* Quick Actions */
.action-icon {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    transition: all 0.3s ease;
}

.quick-actions:hover .action-icon {
    transform: scale(1.1) rotate(90deg);
}

.action-text {
    font-weight: 500;
    font-size: 14px;
}

.quick-actions-dropdown {
    min-width: 320px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.05);
    padding: 0;
    overflow: hidden;
}

.quick-actions-dropdown .dropdown-header {
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    color: white;
    padding: 20px;
    margin: 0;
}

.quick-actions-dropdown .dropdown-header h6 {
    margin: 0 0 5px 0;
    font-weight: 600;
}

.quick-actions-dropdown .dropdown-header p {
    margin: 0;
    opacity: 0.8;
    font-size: 13px;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 5px 0;
}

.action-icon-wrapper {
    width: 40px;
    height: 40px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 16px;
}

.action-content {
    flex: 1;
}

.action-title {
    display: block;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.action-desc {
    display: block;
    color: #666;
    font-size: 12px;
    margin-top: 2px;
}

/* Notifications */
.notification-icon {
    position: relative;
    width: 35px;
    height: 35px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 16px;
    transition: all 0.3s ease;
}

.notifications:hover .notification-icon {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notification-dropdown {
    min-width: 380px;
    max-height: 450px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.05);
    padding: 0;
    overflow: hidden;
}

.notification-dropdown .dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}

.header-content h6 {
    margin: 0;
    font-weight: 600;
    color: #333;
}

.notification-count {
    font-size: 12px;
    color: var(--primary-color);
    font-weight: 500;
}

.view-all {
    color: var(--primary-color);
    font-size: 12px;
    text-decoration: none;
    font-weight: 500;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item .notification-icon {
    width: 35px;
    height: 35px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 14px;
}

.notification-content {
    flex: 1;
}

.notification-content p {
    margin: 0;
    font-size: 14px;
    color: #333;
    line-height: 1.4;
}

.notification-time {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
    display: block;
}

/* Theme Toggle */
.theme-icon-wrapper {
    width: 35px;
    height: 35px;
    background: rgba(102, 126, 234, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 16px;
    transition: all 0.3s ease;
}

.theme-toggle:hover .theme-icon-wrapper {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1) rotate(180deg);
}

/* User Profile */
.profile-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.user-profile:hover .profile-wrapper {
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.profile-img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-color);
    transition: all 0.3s ease;
}

.profile-status {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid white;
}

.profile-status.online {
    background: #28a745;
}

.user-name {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.profile-arrow {
    font-size: 12px;
    color: #666;
    transition: all 0.3s ease;
}

.user-profile:hover .profile-arrow {
    transform: rotate(180deg);
}

.user-dropdown {
    min-width: 300px;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.05);
    padding: 0;
    overflow: hidden;
}

.user-dropdown .dropdown-header {
    background: linear-gradient(135deg, var(--primary-color), #764ba2);
    color: white;
    padding: 25px;
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar-wrapper {
    position: relative;
    width: 50px;
    height: 50px;
}

.user-info img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.3);
    object-fit: cover;
}

.user-status {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
}

.user-status.online {
    background: #28a745;
}

.user-details h6 {
    margin: 0 0 5px 0;
    font-weight: 600;
    font-size: 16px;
}

.user-role {
    display: block;
    font-size: 13px;
    opacity: 0.9;
    margin-bottom: 3px;
}

.user-email {
    display: block;
    font-size: 12px;
    opacity: 0.7;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 5px 0;
}

.menu-item i {
    width: 16px;
    color: #666;
}

.menu-item span {
    font-size: 14px;
    color: #333;
}

.logout-item .menu-item {
    color: #dc3545;
}

.logout-item .menu-item i,
.logout-item .menu-item span {
    color: #dc3545;
}

/* Responsive */
@media (max-width: 991.98px) {
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
    
    .navbar-nav-right {
        gap: 5px;
    }
    
    .navbar-nav-right .nav-link {
        padding: 8px;
    }
}

@media (max-width: 768px) {
    .action-text {
        display: none !important;
    }
    
    .quick-actions-dropdown,
    .notification-dropdown,
    .user-dropdown {
        min-width: 280px;
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
            if (data.count > 0) {
                document.getElementById('notificationCount').textContent = data.count;
            }
            if (data.html) {
                document.getElementById('notificationList').innerHTML = data.html;
            }
        })
        .catch(error => {
            console.log('Notifications loading failed:', error);
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
    }).catch(error => {
        console.log('Setting update failed:', error);
    });
}

// Enhanced header functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load notifications
    loadNotifications();
    setInterval(loadNotifications, 30000); // Refresh every 30 seconds
    
    // Header scroll effect
    let lastScroll = 0;
    const header = document.querySelector('.main-header');
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > lastScroll && currentScroll > 100) {
            // Scrolling down
            header.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up
            header.style.transform = 'translateY(0)';
        }
        
        lastScroll = currentScroll;
    });
    
    // Enhanced dropdown animations
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('show.bs.dropdown', function() {
            this.style.opacity = '0';
            this.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                this.style.transition = 'all 0.3s ease';
                this.style.opacity = '1';
                this.style.transform = 'translateY(0)';
            }, 10);
        });
    });
    
    // Search functionality
    const searchInput = document.querySelector('.search-form input');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        searchInput.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    }
});

// Helper function to get notification count (if not available in PHP)
function getNotificationCount() {
    return <?php echo $notification_count; ?>;
}
</script>

<?php
// Helper function to get notification count
function getNotificationCount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}
?>
