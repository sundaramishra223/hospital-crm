<?php
// Admin dashboard - Only accessible by admin role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Mock data for demonstration
$stats = [
    'total_patients' => 1247,
    'today_appointments' => 28,
    'monthly_revenue' => 95000,
    'occupied_beds' => 45,
    'users' => [
        'doctor' => 25,
        'nurse' => 45,
        'staff' => 30,
        'pharmacy' => 12,
        'lab_tech' => 8
    ]
];

$recent_activities = [
    [
        'user_name' => 'Dr. Smith',
        'action' => 'appointment',
        'description' => 'completed consultation with Patient #1234',
        'created_at' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
    ],
    [
        'user_name' => 'Nurse Johnson',
        'action' => 'patient',
        'description' => 'updated vital signs for Patient #5678',
        'created_at' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
    ],
    [
        'user_name' => 'Admin',
        'action' => 'system',
        'description' => 'updated system settings',
        'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
    ]
];

// Mock function for activity icons
function getActivityIcon($action) {
    switch($action) {
        case 'appointment': return 'calendar';
        case 'patient': return 'user';
        case 'system': return 'cog';
        default: return 'info';
    }
}

function formatCurrency($amount) {
    return '₹' . number_format($amount);
}
?>

<div class="admin-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row">
            <div class="col-md-8">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo $_SESSION['user_name'] ?? 'Administrator'; ?>! Here's what's happening at your hospital today.</p>
            </div>
            <div class="col-md-4 text-right">
                <div class="header-actions">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                        <i class="fa fa-plus"></i> Quick Add
                    </button>
                    <button class="btn btn-info" onclick="window.location='modules/settings.php'">
                        <i class="fa fa-cog"></i> Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-section">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card patients">
                    <div class="stat-icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_patients']); ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card appointments">
                    <div class="stat-icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['today_appointments']); ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card revenue">
                    <div class="stat-icon">
                        <i class="fa fa-rupee-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo formatCurrency($stats['monthly_revenue']); ?></h3>
                        <p>Monthly Revenue</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card beds">
                    <div class="stat-icon">
                        <i class="fa fa-bed"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['occupied_beds']); ?>/60</h3>
                        <p>Occupied Beds</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="row">
            <div class="col-md-8">
                <div class="chart-card">
                    <div class="chart-header">
                        <h4>Hospital Survey Analytics</h4>
                        <div class="chart-filters">
                            <select id="revenueFilter" class="form-control form-control-sm">
                                <option value="7">Last 7 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h4>Staff Distribution</h4>
                    </div>
                    <div class="chart-body">
                        <canvas id="staffChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Cards -->
    <div class="management-section">
        <div class="row">
            <div class="col-md-4">
                <div class="management-card doctors">
                    <div class="card-header">
                        <h4><i class="fas fa-user-md"></i> Doctor Management</h4>
                        <span class="badge"><?php echo $stats['users']['doctor'] ?? 0; ?></span>
                    </div>
                    <div class="card-body">
                        <p>Add, edit, and manage doctor profiles with complete details and specializations</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/doctors.php'">
                                Manage Doctors
                            </button>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                                Add Doctor
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="management-card patients">
                    <div class="card-header">
                        <h4><i class="fas fa-users"></i> Patient Management</h4>
                        <span class="badge"><?php echo $stats['total_patients']; ?></span>
                    </div>
                    <div class="card-body">
                        <p>View and manage patient records, medical history, and appointment schedules</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/patients.php'">
                                Manage Patients
                            </button>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                                Add Patient
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="management-card departments">
                    <div class="card-header">
                        <h4><i class="fas fa-building"></i> Department Management</h4>
                        <span class="badge">12</span>
                    </div>
                    <div class="card-body">
                        <p>Organize staff into departments and manage departmental assignments efficiently</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/departments.php'">
                                Manage Departments
                            </button>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                                Add Department
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Management Modules -->
    <div class="modules-section">
        <h3>System Modules</h3>
        <div class="row">
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-bed"></i>
                    <h5>Bed Management</h5>
                    <p>Manage hospital beds, track occupancy, and optimize bed allocation</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/beds.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-flask"></i>
                    <h5>Lab Management</h5>
                    <p>Manage lab tests, results, and equipment with comprehensive tracking</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/lab.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-pills"></i>
                    <h5>Pharmacy</h5>
                    <p>Manage medicines, inventory, prescriptions, and stock levels</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/pharmacy.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-money-bill"></i>
                    <h5>Billing System</h5>
                    <p>Manage bills, payments, insurance claims, and financial records</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/billing.php'">
                        Manage
                    </button>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-ambulance"></i>
                    <h5>Ambulance</h5>
                    <p>Manage ambulance services, emergency responses, and vehicle tracking</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/ambulance.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-graduation-cap"></i>
                    <h5>Intern System</h5>
                    <p>Manage intern assignments, training programs, and evaluations</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/interns.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-clock"></i>
                    <h5>Shift Management</h5>
                    <p>Manage staff shifts, schedules, and attendance tracking</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/shifts.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-envelope"></i>
                    <h5>Communication</h5>
                    <p>SMS, Email notifications, and internal communication system</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/communication.php'">
                        Manage
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="activities-section">
        <div class="row">
            <div class="col-md-12">
                <div class="activity-card">
                    <div class="card-header">
                        <h4><i class="fa fa-history"></i> Recent Activities</h4>
                        <button class="btn btn-sm btn-outline-primary" onclick="window.location='modules/logs.php'">
                            View All Logs
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php if (!empty($recent_activities)): ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fa fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <p><strong><?php echo $activity['user_name']; ?></strong> <?php echo $activity['description']; ?></p>
                                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No recent activities found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
