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
    
    // Nurse specific menu
    if ($role == 'nurse') {
        $menu[] = [
            'title' => 'Patient Care',
            'icon' => 'fa-heartbeat',
            'submenu' => [
                ['title' => 'Assigned Patients', 'url' => 'modules/assigned-patients.php', 'icon' => 'fa-users'],
                ['title' => 'Vitals Monitoring', 'url' => 'modules/vitals.php', 'icon' => 'fa-heartbeat'],
                ['title' => 'Medicine Log', 'url' => 'modules/medicine-log.php', 'icon' => 'fa-pills']
            ]
        ];
        
        $menu[] = [
            'title' => 'Ward Management',
            'icon' => 'fa-bed',
            'url' => 'modules/ward-management.php'
        ];
    }
    
    // Patient specific menu
    if ($role == 'patient') {
        $menu[] = [
            'title' => 'My Health',
            'icon' => 'fa-heartbeat',
            'submenu' => [
                ['title' => 'My Appointments', 'url' => 'modules/my-appointments.php', 'icon' => 'fa-calendar'],
                ['title' => 'Medical History', 'url' => 'modules/my-history.php', 'icon' => 'fa-history'],
                ['title' => 'Lab Reports', 'url' => 'modules/my-reports.php', 'icon' => 'fa-flask'],
                ['title' => 'Prescriptions', 'url' => 'modules/my-prescriptions.php', 'icon' => 'fa-file-text']
            ]
        ];
        
        $menu[] = [
            'title' => 'Billing',
            'icon' => 'fa-money',
            'submenu' => [
                ['title' => 'My Bills', 'url' => 'modules/my-bills.php', 'icon' => 'fa-file-text'],
                ['title' => 'Payment Receipts', 'url' => 'modules/my-payments.php', 'icon' => 'fa-receipt']
            ]
        ];
    }
    
    // Common items for staff roles
    if (in_array($role, ['pharmacy', 'lab_tech', 'receptionist', 'staff'])) {
        if ($role == 'pharmacy') {
            $menu[] = [
                'title' => 'Pharmacy',
                'icon' => 'fa-pills',
                'submenu' => [
                    ['title' => 'Medicine Inventory', 'url' => 'modules/medicine-inventory.php', 'icon' => 'fa-list'],
                    ['title' => 'Prescriptions', 'url' => 'modules/pharmacy-prescriptions.php', 'icon' => 'fa-file-text'],
                    ['title' => 'Stock Alerts', 'url' => 'modules/stock-alerts.php', 'icon' => 'fa-exclamation-triangle']
                ]
            ];
        }
        
        if ($role == 'lab_tech') {
            $menu[] = [
                'title' => 'Laboratory',
                'icon' => 'fa-flask',
                'submenu' => [
                    ['title' => 'Test Requests', 'url' => 'modules/test-requests.php', 'icon' => 'fa-list'],
                    ['title' => 'Upload Results', 'url' => 'modules/upload-results.php', 'icon' => 'fa-upload'],
                    ['title' => 'Test History', 'url' => 'modules/test-history.php', 'icon' => 'fa-history']
                ]
            ];
        }
        
        if ($role == 'receptionist') {
            $menu[] = [
                'title' => 'Reception',
                'icon' => 'fa-desk',
                'submenu' => [
                    ['title' => 'Patient Registration', 'url' => 'modules/patient-registration.php', 'icon' => 'fa-user-plus'],
                    ['title' => 'Appointments', 'url' => 'modules/reception-appointments.php', 'icon' => 'fa-calendar'],
                    ['title' => 'Visitor Management', 'url' => 'modules/visitors.php', 'icon' => 'fa-users']
                ]
            ];
        }
    }
    
    // Intern menu (based on assigned role)
    if ($role == 'intern') {
        $menu[] = [
            'title' => 'Intern Dashboard',
            'icon' => 'fa-graduation-cap',
            'submenu' => [
                ['title' => 'My Assignments', 'url' => 'modules/intern-assignments.php', 'icon' => 'fa-tasks'],
                ['title' => 'Learning Resources', 'url' => 'modules/learning-resources.php', 'icon' => 'fa-book'],
                ['title' => 'Supervisor Contact', 'url' => 'modules/supervisor.php', 'icon' => 'fa-user']
            ]
        ];
    }
    
    return $menu;
}

$menu_items = getMenuItems($user_role);
?>
<!-- Professional Attractive Sidebar Start -->
<aside class="main-sidebar" id="mainSidebar">
    <div class="sidebar-wrapper">
        <!-- Enhanced User Profile Section -->
        <div class="sidebar-user">
            <div class="user-avatar-wrapper">
                <div class="user-avatar">
                    <img src="<?php echo $user_details['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile">
                    <div class="status-indicator online"></div>
                </div>
            </div>
            <div class="user-info">
                <h6 class="user-name"><?php echo $user_details['name']; ?></h6>
                <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                <div class="user-actions">
                    <button class="btn-action" title="Settings"><i class="fa fa-cog"></i></button>
                    <button class="btn-action" title="Notifications"><i class="fa fa-bell"></i></button>
                </div>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="sidebar-search">
            <div class="search-wrapper">
                <i class="fa fa-search search-icon"></i>
                <input type="text" placeholder="Search patients, doctors..." class="search-input">
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h6 class="nav-section-title">MAIN</h6>
                <ul class="nav-list">
                    <?php foreach ($menu_items as $item): ?>
                        <li class="nav-item <?php echo isset($item['submenu']) ? 'has-submenu' : ''; ?>">
                            <?php if (isset($item['submenu'])): ?>
                                <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#submenu-<?php echo md5($item['title']); ?>" aria-expanded="false">
                                    <div class="nav-icon">
                                        <i class="fa <?php echo $item['icon']; ?>"></i>
                                    </div>
                                    <span class="nav-text"><?php echo $item['title']; ?></span>
                                    <div class="nav-arrow">
                                        <i class="fa fa-chevron-down"></i>
                                    </div>
                                    <?php if (isset($item['badge'])): ?>
                                        <span class="nav-badge"><?php echo $item['badge']; ?></span>
                                    <?php endif; ?>
                                </a>
                                <ul class="collapse submenu" id="submenu-<?php echo md5($item['title']); ?>">
                                    <?php foreach ($item['submenu'] as $subitem): ?>
                                        <li>
                                            <a href="<?php echo $subitem['url']; ?>" class="submenu-link">
                                                <i class="fa <?php echo $subitem['icon']; ?>"></i>
                                                <span><?php echo $subitem['title']; ?></span>
                                                <?php if (isset($subitem['badge'])): ?>
                                                    <span class="submenu-badge"><?php echo $subitem['badge']; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo ($current_page == basename($item['url'])) ? 'active' : ''; ?>">
                                    <div class="nav-icon">
                                        <i class="fa <?php echo $item['icon']; ?>"></i>
                                    </div>
                                    <span class="nav-text"><?php echo $item['title']; ?></span>
                                    <?php if (isset($item['badge'])): ?>
                                        <span class="nav-badge"><?php echo $item['badge']; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
        
        <!-- Enhanced System Status -->
        <div class="sidebar-footer">
            <div class="system-status">
                <div class="status-header">
                    <h6>System Status</h6>
                    <span class="status-time"><?php echo date('H:i'); ?></span>
                </div>
                <div class="status-indicators">
                    <div class="status-item">
                        <span class="status-dot online"></span>
                        <span class="status-label">System</span>
                    </div>
                    <div class="status-item">
                        <span class="status-dot <?php echo ($system_health['database'] == 'healthy') ? 'online' : 'offline'; ?>"></span>
                        <span class="status-label">Database</span>
                    </div>
                    <div class="status-item">
                        <span class="status-dot <?php echo ($system_health['disk_usage'] < 90) ? 'online' : 'warning'; ?>"></span>
                        <span class="status-label">Storage</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
<!-- Professional Attractive Sidebar End -->

<style>
/* Professional Attractive Sidebar Styles */
.main-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    z-index: 1020;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 4px 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.sidebar-wrapper {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
}

/* Enhanced User Profile */
.sidebar-user {
    padding: 25px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: relative;
    overflow: hidden;
}

.sidebar-user::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.user-avatar-wrapper {
    position: relative;
    margin-bottom: 15px;
}

.user-avatar {
    position: relative;
    width: 60px;
    height: 60px;
    margin: 0 auto 10px;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
}

.user-avatar:hover img {
    border-color: rgba(255,255,255,0.8);
    transform: scale(1.05);
}

.status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid white;
    background: #28a745;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}

.user-info {
    text-align: center;
    position: relative;
    z-index: 1;
}

.user-name {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 5px 0;
    color: white;
}

.user-role {
    font-size: 14px;
    opacity: 0.9;
    color: rgba(255,255,255,0.9);
    display: block;
    margin-bottom: 15px;
}

.user-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.btn-action {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-action:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

/* Search Bar */
.sidebar-search {
    padding: 20px;
    background: white;
    border-bottom: 1px solid #f0f0f0;
}

.search-wrapper {
    position: relative;
    background: #f8f9fa;
    border-radius: 25px;
    padding: 10px 15px;
    transition: all 0.3s ease;
}

.search-wrapper:focus-within {
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 14px;
}

.search-input {
    width: 100%;
    border: none;
    background: transparent;
    padding-left: 30px;
    font-size: 14px;
    outline: none;
}

.search-input::placeholder {
    color: #6c757d;
}

/* Navigation */
.sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.nav-section {
    margin-bottom: 30px;
}

.nav-section-title {
    font-size: 12px;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 20px 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f0;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin: 2px 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    border-radius: 0 25px 25px 0;
    margin-right: 15px;
}

.nav-link:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateX(5px);
    text-decoration: none;
}

.nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.nav-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(102, 126, 234, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    transition: all 0.3s ease;
}

.nav-link:hover .nav-icon,
.nav-link.active .nav-icon {
    background: rgba(255,255,255,0.2);
    transform: scale(1.1);
}

.nav-icon i {
    font-size: 16px;
    color: #667eea;
    transition: all 0.3s ease;
}

.nav-link:hover .nav-icon i,
.nav-link.active .nav-icon i {
    color: white;
}

.nav-text {
    flex: 1;
    font-weight: 500;
    font-size: 15px;
}

.nav-arrow {
    transition: transform 0.3s ease;
}

.nav-link[aria-expanded="true"] .nav-arrow {
    transform: rotate(180deg);
}

.nav-badge {
    background: #dc3545;
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 10px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-3px); }
    60% { transform: translateY(-2px); }
}

/* Submenu */
.submenu {
    background: #f8f9fa;
    margin: 5px 15px 5px 55px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
}

.submenu-link {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: #6c757d;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
    position: relative;
}

.submenu-link:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    text-decoration: none;
    transform: translateX(5px);
}

.submenu-link i {
    width: 20px;
    margin-right: 10px;
    font-size: 14px;
}

.submenu-badge {
    background: #28a745;
    color: white;
    font-size: 10px;
    padding: 1px 5px;
    border-radius: 8px;
    margin-left: auto;
}

/* Enhanced System Status */
.sidebar-footer {
    padding: 20px;
    background: white;
    border-top: 1px solid #f0f0f0;
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.status-header h6 {
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.status-time {
    font-size: 12px;
    color: #6c757d;
    background: #f8f9fa;
    padding: 2px 8px;
    border-radius: 10px;
}

.status-indicators {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ccc;
    position: relative;
}

.status-dot.online {
    background: #28a745;
    animation: pulse 2s infinite;
}

.status-dot.warning {
    background: #ffc107;
}

.status-dot.offline {
    background: #dc3545;
}

.status-label {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 991.98px) {
    .main-sidebar {
        left: -280px;
    }
    
    body.sidebar-open .main-sidebar {
        left: 0;
    }
}

/* Scrollbar Styling */
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(102, 126, 234, 0.3);
    border-radius: 2px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(102, 126, 234, 0.5);
}
</style>

<script>
// Enhanced Sidebar Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-open');
        });
    }
    
    // Search functionality
    var searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var searchTerm = this.value.toLowerCase();
            var navItems = document.querySelectorAll('.nav-item');
            
            navItems.forEach(function(item) {
                var text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Enhanced hover effects
    var navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        
        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(0)';
            }
        });
    });
    
    // Auto-open submenu for current page
    var currentUrl = window.location.pathname;
    var submenuLinks = document.querySelectorAll('.submenu-link');
    submenuLinks.forEach(function(link) {
        if (link.getAttribute('href') && currentUrl.includes(link.getAttribute('href'))) {
            var submenu = link.closest('.collapse');
            if (submenu) {
                submenu.classList.add('show');
                var parentLink = document.querySelector('[data-bs-target="#' + submenu.id + '"]');
                if (parentLink) {
                    parentLink.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
});
</script>
