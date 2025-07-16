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

<aside class="main-sidebar" id="mainSidebar">
    <div class="sidebar-wrapper">
        <!-- User Profile Section -->
        <div class="sidebar-user">
            <div class="user-avatar">
                <img src="<?php echo $user_details['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile">
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
                            <a href="#" class="nav-link" data-toggle="submenu">
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
                            <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo ($current_page == basename($item['url'])) ? 'active' : ''; ?>">
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

<style>
/* Sidebar Styles */
.main-sidebar {
    position: fixed;
    top: 70px;
    left: 0;
    width: 280px;
    height: calc(100vh - 70px);
    background: #fff;
    border-right: 1px solid #e9ecef;
    z-index: 1020;
    transition: all 0.3s ease;
    overflow: hidden;
}

.dark-mode .main-sidebar {
    background: #1a1a1a;
    border-right-color: #333;
}

.sidebar-collapsed .main-sidebar {
    width: 70px;
}

.sidebar-wrapper {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.sidebar-user {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 15px;
}

.dark-mode .sidebar-user {
    border-bottom-color: #333;
}

.user-avatar img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
}

.user-info h6 {
    margin: 0;
    font-weight: 600;
    color: #333;
}

.dark-mode .user-info h6 {
    color: #fff;
}

.user-info small {
    color: #666;
    font-size: 12px;
}

.dark-mode .user-info small {
    color: #ccc;
}

.sidebar-nav {
    flex: 1;
    padding: 10px 0;
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
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
    border-radius: 0 25px 25px 0;
    margin-right: 20px;
    position: relative;
}

.nav-link:hover {
    background: #f8f9fa;
    color: var(--primary-color);
    text-decoration: none;
}

.nav-link.active {
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: white;
}

.dark-mode .nav-link {
    color: #ccc;
}

.dark-mode .nav-link:hover {
    background: #333;
    color: var(--primary-color);
}

.nav-link i {
    width: 20px;
    text-align: center;
    margin-right: 12px;
    font-size: 16px;
}

.nav-text {
    flex: 1;
    font-weight: 500;
}

.submenu-arrow {
    transition: transform 0.3s ease;
    margin-left: auto;
    margin-right: 0 !important;
}

.has-submenu.open .submenu-arrow {
    transform: rotate(180deg);
}

.submenu {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background: #f8f9fa;
    margin-right: 20px;
    border-radius: 0 15px 15px 0;
}

.dark-mode .submenu {
    background: #2a2a2a;
}

.has-submenu.open .submenu {
    max-height: 500px;
}

.submenu-link {
    display: flex;
    align-items: center;
    padding: 10px 20px 10px 50px;
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.submenu-link:hover {
    background: #e9ecef;
    color: var(--primary-color);
    text-decoration: none;
}

.dark-mode .submenu-link {
    color: #ccc;
}

.dark-mode .submenu-link:hover {
    background: #333;
}

.submenu-link i {
    width: 16px;
    margin-right: 10px;
    font-size: 14px;
}

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid #e9ecef;
}

.dark-mode .sidebar-footer {
    border-top-color: #333;
}

.system-status small {
    display: block;
    color: #666;
    margin-bottom: 10px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dark-mode .system-status small {
    color: #ccc;
}

.status-indicators {
    display: flex;
    gap: 8px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ccc;
}

.status-dot.online {
    background: #28a745;
}

.status-dot.warning {
    background: #ffc107;
}

.status-dot.offline {
    background: #dc3545;
}

/* Collapsed sidebar styles */
.sidebar-collapsed .user-info,
.sidebar-collapsed .nav-text,
.sidebar-collapsed .submenu-arrow,
.sidebar-collapsed .system-status small {
    display: none;
}

.sidebar-collapsed .nav-link {
    justify-content: center;
    margin-right: 0;
    border-radius: 0;
}

.sidebar-collapsed .nav-link i {
    margin-right: 0;
}

.sidebar-collapsed .submenu {
    display: none;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .main-sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar-open .main-sidebar {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0 !important;
    }
}

/* Overlay for mobile */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1019;
    display: none;
}

@media (max-width: 768px) {
    .sidebar-open .sidebar-overlay {
        display: block;
    }
}
</style>

<script>
// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            document.body.classList.toggle('sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
        }
    });
    
    // Submenu toggle
    document.querySelectorAll('[data-toggle="submenu"]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.nav-item');
            parent.classList.toggle('open');
            
            // Close other submenus
            document.querySelectorAll('.nav-item.has-submenu').forEach(function(item) {
                if (item !== parent) {
                    item.classList.remove('open');
                }
            });
        });
    });
    
    // Close sidebar on overlay click (mobile)
    if (document.querySelector('.sidebar-overlay')) {
        document.querySelector('.sidebar-overlay').addEventListener('click', function() {
            document.body.classList.remove('sidebar-open');
        });
    }
    
    // Auto-open submenu if current page is in submenu
    const currentUrl = window.location.pathname;
    document.querySelectorAll('.submenu-link').forEach(function(link) {
        if (link.getAttribute('href') && currentUrl.includes(link.getAttribute('href'))) {
            const submenu = link.closest('.nav-item');
            if (submenu) {
                submenu.classList.add('open');
            }
        }
    });
});

// Create overlay for mobile
if (window.innerWidth <= 768) {
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
}
</script>