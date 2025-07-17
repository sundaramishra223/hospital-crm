<?php
$user_role = $_SESSION['user_role'];
$current_page = basename($_SERVER['PHP_SELF']);

// Get menu items based on user role
function getMenuItems($role) {
    $menu = [];
    
    // Common items for all roles
    $menu[] = [
        'title' => 'Dashboard',
        'icon' => 'fa-dashboard',
        'url' => 'index.php',
        'roles' => ['admin', 'doctor', 'nurse', 'staff', 'pharmacy', 'lab_tech', 'receptionist', 'patient', 'intern']
    ];
    
    $menu[] = [
        'title' => 'My Profile',
        'icon' => 'fa-user-circle',
        'url' => 'modules/profile.php',
        'roles' => ['admin', 'doctor', 'nurse', 'staff', 'pharmacy', 'lab_tech', 'receptionist', 'patient', 'intern']
    ];
    
    // Admin specific menu
    if ($role == 'admin') {
        $menu[] = [
            'title' => 'User Management',
            'icon' => 'fa-users',
            'submenu' => [
                ['title' => 'Doctors', 'url' => 'modules/doctors.php', 'icon' => 'fa-user-md'],
                ['title' => 'Nurses', 'url' => 'modules/nurses.php', 'icon' => 'fa-plus-square'],
                ['title' => 'Staff', 'url' => 'modules/staff.php', 'icon' => 'fa-users'],
                ['title' => 'Interns', 'url' => 'modules/interns.php', 'icon' => 'fa-graduation-cap'],
                ['title' => 'Role Permissions', 'url' => 'modules/permissions.php', 'icon' => 'fa-key']
            ]
        ];
        
        $menu[] = [
            'title' => 'Patient Management',
            'icon' => 'fa-user-plus',
            'submenu' => [
                ['title' => 'All Patients', 'url' => 'modules/patients.php', 'icon' => 'fa-users'],
                ['title' => 'Add Patient', 'url' => 'modules/patients.php?action=add', 'icon' => 'fa-user-plus'],
                ['title' => 'Inpatients', 'url' => 'modules/patients.php?type=inpatient', 'icon' => 'fa-bed'],
                ['title' => 'Outpatients', 'url' => 'modules/patients.php?type=outpatient', 'icon' => 'fa-walking']
            ]
        ];
        
        $menu[] = [
            'title' => 'Appointments',
            'icon' => 'fa-calendar',
            'submenu' => [
                ['title' => 'All Appointments', 'url' => 'modules/appointments.php', 'icon' => 'fa-calendar'],
                ['title' => 'Today\'s Schedule', 'url' => 'modules/appointments.php?date=today', 'icon' => 'fa-clock-o'],
                ['title' => 'Appointment Types', 'url' => 'modules/appointment-types.php', 'icon' => 'fa-list']
            ]
        ];
        
        $menu[] = [
            'title' => 'Hospital Management',
            'icon' => 'fa-hospital-o',
            'submenu' => [
                ['title' => 'Departments', 'url' => 'modules/departments.php', 'icon' => 'fa-building'],
                ['title' => 'Bed Management', 'url' => 'modules/beds.php', 'icon' => 'fa-bed'],
                ['title' => 'Equipment', 'url' => 'modules/equipment.php', 'icon' => 'fa-cogs'],
                ['title' => 'Multi-Hospital', 'url' => 'modules/hospitals.php', 'icon' => 'fa-hospital-o']
            ]
        ];
        
        $menu[] = [
            'title' => 'Medical Services',
            'icon' => 'fa-stethoscope',
            'submenu' => [
                ['title' => 'Lab Management', 'url' => 'modules/lab.php', 'icon' => 'fa-flask'],
                ['title' => 'Pharmacy', 'url' => 'modules/pharmacy.php', 'icon' => 'fa-pills'],
                ['title' => 'Surgery Schedule', 'url' => 'modules/surgeries.php', 'icon' => 'fa-cut'],
                ['title' => 'Home Visits', 'url' => 'modules/home-visits.php', 'icon' => 'fa-home'],
                ['title' => 'Video Consultations', 'url' => 'modules/video-consults.php', 'icon' => 'fa-video-camera']
            ]
        ];
        
        $menu[] = [
            'title' => 'Financial Management',
            'icon' => 'fa-money',
            'submenu' => [
                ['title' => 'Billing & Invoices', 'url' => 'modules/billing.php', 'icon' => 'fa-file-text'],
                ['title' => 'Insurance Management', 'url' => 'modules/insurance.php', 'icon' => 'fa-shield'],
                ['title' => 'Payment Methods', 'url' => 'modules/payment-methods.php', 'icon' => 'fa-credit-card'],
                ['title' => 'Insurance Claims', 'url' => 'modules/insurance.php', 'icon' => 'fa-shield'],
                ['title' => 'Salary Management', 'url' => 'modules/salary.php', 'icon' => 'fa-money-check-alt'],
                ['title' => 'Financial Reports', 'url' => 'modules/financial-reports.php', 'icon' => 'fa-chart-line']
            ]
        ];
        
        $menu[] = [
            'title' => 'System Management',
            'icon' => 'fa-cogs',
            'submenu' => [
                ['title' => 'System Settings', 'url' => 'modules/system-settings.php', 'icon' => 'fa-wrench'],
                ['title' => 'Attendance Management', 'url' => 'modules/attendance.php', 'icon' => 'fa-clock'],
                ['title' => 'Shift Management', 'url' => 'modules/shifts.php', 'icon' => 'fa-calendar-check-o'],
                ['title' => 'Communication', 'url' => 'modules/communication.php', 'icon' => 'fa-envelope'],
                ['title' => 'Feedback Management', 'url' => 'modules/feedback.php', 'icon' => 'fa-comment'],
                ['title' => 'Activity Logs', 'url' => 'modules/logs.php', 'icon' => 'fa-history']
            ]
        ];
        
        $menu[] = [
            'title' => 'Emergency Services',
            'icon' => 'fa-ambulance',
            'submenu' => [
                ['title' => 'Ambulance Management', 'url' => 'modules/ambulance.php', 'icon' => 'fa-ambulance'],
                ['title' => 'Emergency Cases', 'url' => 'modules/emergency.php', 'icon' => 'fa-exclamation-triangle']
            ]
        ];
    }
    
    // Doctor specific menu
    if ($role == 'doctor') {
        $menu[] = [
            'title' => 'My Patients',
            'icon' => 'fa-users',
            'submenu' => [
                ['title' => 'Assigned Patients', 'url' => 'modules/my-patients.php', 'icon' => 'fa-users'],
                ['title' => 'Patient History', 'url' => 'modules/patient-history.php', 'icon' => 'fa-history'],
                ['title' => 'Prescriptions', 'url' => 'modules/prescriptions.php', 'icon' => 'fa-file-text']
            ]
        ];
        
        $menu[] = [
            'title' => 'Appointments',
            'icon' => 'fa-calendar',
            'submenu' => [
                ['title' => 'My Schedule', 'url' => 'modules/my-schedule.php', 'icon' => 'fa-calendar'],
                ['title' => 'Today\'s Appointments', 'url' => 'modules/today-appointments.php', 'icon' => 'fa-clock-o']
            ]
        ];
        
        $menu[] = [
            'title' => 'Medical Records',
            'icon' => 'fa-file-medical',
            'submenu' => [
                ['title' => 'Consultations', 'url' => 'modules/consultations.php', 'icon' => 'fa-stethoscope'],
                ['title' => 'Lab Reports', 'url' => 'modules/lab-reports.php', 'icon' => 'fa-flask'],
                ['title' => 'Surgery Notes', 'url' => 'modules/surgery-notes.php', 'icon' => 'fa-cut']
            ]
        ];
    }
    
    // Additional menu items for other roles...
    
    return $menu;
}

$menu_items = getMenuItems($user_role);
$system_health = ['database' => 'healthy', 'disk_usage' => 75]; // Mock data
?>

<aside class="main-sidebar" id="mainSidebar">
    <div class="sidebar-wrapper">
        <!-- User Profile Section -->
        <div class="sidebar-user">
            <div class="user-avatar">
                <img src="<?php echo $user_details['profile_image'] ?? 'assets/img/default-avatar.png'; ?>" alt="Profile">
            </div>
            <div class="user-info">
                <h6><?php echo $user_details['name']; ?></h6>
                <small><?php echo ucfirst($_SESSION['user_role']); ?></small>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <?php foreach ($menu_items as $item): ?>
                    <li class="nav-item <?php echo isset($item['submenu']) ? 'has-submenu' : ''; ?>">
                        <?php if (isset($item['submenu'])): ?>
                            <a href="#" class="nav-link" data-toggle="submenu" data-title="<?php echo $item['title']; ?>">
                                <i class="fa <?php echo $item['icon']; ?>"></i>
                                <span class="nav-text"><?php echo $item['title']; ?></span>
                                <i class="fa fa-chevron-down submenu-arrow"></i>
                            </a>
                            <ul class="submenu">
                                <?php foreach ($item['submenu'] as $subitem): ?>
                                    <li>
                                        <a href="<?php echo $subitem['url']; ?>" class="submenu-link">
                                            <i class="fa <?php echo $subitem['icon']; ?>"></i>
                                            <span><?php echo $subitem['title']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo ($current_page == basename($item['url'])) ? 'active' : ''; ?>" data-title="<?php echo $item['title']; ?>">
                                <i class="fa <?php echo $item['icon']; ?>"></i>
                                <span class="nav-text"><?php echo $item['title']; ?></span>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <!-- System Status -->
        <div class="sidebar-footer">
            <div class="system-status">
                <small>System Status</small>
                <div class="status-indicators">
                    <span class="status-dot online" title="System Online"></span>
                    <span class="status-dot <?php echo ($system_health['database'] == 'healthy') ? 'online' : 'offline'; ?>" title="Database"></span>
                    <span class="status-dot <?php echo ($system_health['disk_usage'] < 90) ? 'online' : 'warning'; ?>" title="Disk Space"></span>
                </div>
            </div>
        </div>
    </div>
</aside>
