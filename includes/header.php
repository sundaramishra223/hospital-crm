<?php
$user_details = getUserDetails($_SESSION['user_id']);
$site_title = getSetting('site_title', 'Hospital CRM');
$logo = getSetting('logo', 'assets/img/default-avatar.png');
$theme_color = getSetting('theme_color', '#2563eb');
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
                            <img src="<?php echo $user_details['profile_image'] ?? 'assets/img/default-avatar.png'; ?>" alt="Profile" class="profile-img">
                            <span class="user-name d-none d-lg-inline"><?php echo $user_details['name']; ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right user-dropdown">
                            <div class="dropdown-header">
                                <div class="user-info">
                                    <img src="<?php echo $user_details['profile_image'] ?? 'assets/img/default-avatar.png'; ?>" alt="Profile">
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
