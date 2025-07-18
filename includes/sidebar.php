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
<!-- Modern Sidebar Start -->
<aside class="main-sidebar bg-white shadow-sm d-flex flex-column" id="mainSidebar">
    <div class="sidebar-wrapper flex-grow-1 d-flex flex-column">
        <!-- User Profile Section -->
        <div class="sidebar-user d-flex align-items-center p-3 border-bottom">
            <div class="user-avatar me-3">
                <img src="<?php echo $user_details['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile" class="rounded-circle border border-2" width="48" height="48">
            </div>
            <div class="user-info">
                <div class="fw-bold text-dark small mb-1"><?php echo $user_details['name']; ?></div>
                <div class="text-muted" style="font-size: 13px;"> <?php echo ucfirst($_SESSION['user_role']); ?> </div>
            </div>
        </div>
        <!-- Navigation Menu -->
        <nav class="sidebar-nav flex-grow-1">
            <ul class="nav flex-column nav-pills py-2">
                <?php foreach ($menu_items as $item): ?>
                    <li class="nav-item <?php echo isset($item['submenu']) ? 'has-submenu' : ''; ?>">
                        <?php if (isset($item['submenu'])): ?>
                            <a href="#" class="nav-link d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#submenu-<?php echo md5($item['title']); ?>" aria-expanded="false">
                                <span><i class="fa <?php echo $item['icon']; ?> me-2"></i><?php echo $item['title']; ?></span>
                                <i class="fa fa-chevron-down"></i>
                            </a>
                            <ul class="collapse list-unstyled ps-4" id="submenu-<?php echo md5($item['title']); ?>">
                                <?php foreach ($item['submenu'] as $subitem): ?>
                                    <li>
                                        <a href="<?php echo $subitem['url']; ?>" class="nav-link text-secondary py-1 d-flex align-items-center">
                                            <i class="fa <?php echo $subitem['icon']; ?> me-2"></i>
                                            <span><?php echo $subitem['title']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <a href="<?php echo $item['url']; ?>" class="nav-link d-flex align-items-center <?php echo ($current_page == basename($item['url'])) ? 'active bg-primary text-white' : 'text-secondary'; ?>">
                                <i class="fa <?php echo $item['icon']; ?> me-2"></i>
                                <span><?php echo $item['title']; ?></span>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <!-- System Status -->
        <div class="sidebar-footer p-3 border-top mt-auto">
            <div class="system-status">
                <small class="text-muted text-uppercase">System Status</small>
                <div class="d-flex gap-2 mt-2">
                    <span class="status-dot online" title="System Online"></span>
                    <span class="status-dot <?php echo ($system_health['database'] == 'healthy') ? 'online' : 'offline'; ?>" title="Database"></span>
                    <span class="status-dot <?php echo ($system_health['disk_usage'] < 90) ? 'online' : 'warning'; ?>" title="Disk Space"></span>
                </div>
            </div>
        </div>
    </div>
</aside>
<!-- Modern Sidebar End -->

<style>
/* Modern Sidebar Styles */
.main-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: #fff;
    z-index: 1020;
    transition: all 0.3s;
    box-shadow: 2px 0 8px rgba(0,0,0,0.04);
}
.sidebar-user .user-avatar img {
    width: 48px;
    height: 48px;
    object-fit: cover;
}
.sidebar-user .user-info .fw-bold {
    font-size: 1rem;
}
.sidebar-nav .nav-link {
    border-radius: 6px;
    margin-bottom: 2px;
    font-size: 15px;
    transition: background 0.2s, color 0.2s;
}
.sidebar-nav .nav-link.active, .sidebar-nav .nav-link:hover {
    background: #0d6efd;
    color: #fff !important;
}
.sidebar-nav .nav-link i {
    font-size: 1.1rem;
}
.has-submenu > .nav-link {
    cursor: pointer;
}
.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    background: #ccc;
}
.status-dot.online { background: #28a745; }
.status-dot.warning { background: #ffc107; }
.status-dot.offline { background: #dc3545; }
@media (max-width: 991.98px) {
    .main-sidebar { left: -250px; }
    body.sidebar-open .main-sidebar { left: 0; }
}
</style>
<script>
// Bootstrap 5 collapse for submenu
// Sidebar toggle for mobile
// Add your sidebar toggle button in header with id="sidebarToggle"
document.addEventListener('DOMContentLoaded', function() {
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-open');
        });
    }
});
</script>
