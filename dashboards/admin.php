<?php
// Admin dashboard - Only accessible by admin role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get dashboard statistics
$stats = getAdminStats();
$advanced_stats = getAdvancedStats();
$system_health = getSystemHealth();
$departments = getDepartments();
$recent_activities = getRecentActivities(10);
$hospitals = getHospitals();
$feedbacks = getFeedbacks(5);
$home_visits = getHomeVisitRequests();
$video_consultations = getVideoConsultations();
?>

<div class="admin-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row">
            <div class="col-md-6">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo $_SESSION['user_name']; ?>!</p>
            </div>
            <div class="col-md-6 text-right">
                <div class="header-actions">
                    <!-- Hospital Switcher -->
                    <div class="hospital-switcher d-inline-block me-3">
                        <select class="form-select form-select-sm" id="hospitalSwitcher" onchange="switchHospital(this.value)">
                            <option value="">Select Hospital</option>
                            <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?php echo $hospital['id']; ?>" <?php echo ($_SESSION['hospital_id'] == $hospital['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hospital['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#quickAddModal">
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
                        <i class="fa fa-rupee"></i>
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
                        <h3><?php echo number_format($stats['occupied_beds']); ?></h3>
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
                        <h4>Revenue Analytics</h4>
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
                        <h4><i class="fa fa-user-md"></i> Doctor Management</h4>
                        <span class="badge badge-primary"><?php echo $stats['users']['doctor'] ?? 0; ?></span>
                    </div>
                    <div class="card-body">
                        <p>Add, edit, and manage doctor profiles with complete details</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/doctors.php'">
                                Manage Doctors
                            </button>
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addDoctorModal">
                                Add Doctor
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="management-card patients">
                    <div class="card-header">
                        <h4><i class="fa fa-users"></i> Patient Management</h4>
                        <span class="badge badge-info"><?php echo $stats['total_patients']; ?></span>
                    </div>
                    <div class="card-body">
                        <p>View and manage patient records and medical history</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/patients.php'">
                                Manage Patients
                            </button>
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addPatientModal">
                                Add Patient
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="management-card departments">
                    <div class="card-header">
                        <h4><i class="fa fa-building"></i> Department Management</h4>
                        <span class="badge badge-warning"><?php echo count($departments); ?></span>
                    </div>
                    <div class="card-body">
                        <p>Organize staff into departments and manage assignments</p>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="window.location='modules/departments.php'">
                                Manage Departments
                            </button>
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addDepartmentModal">
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
                    <p>Manage hospital beds and occupancy</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/beds.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-flask"></i>
                    <h5>Lab Management</h5>
                    <p>Manage lab tests and results</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/lab.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-pills"></i>
                    <h5>Pharmacy</h5>
                    <p>Manage medicines and inventory</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/pharmacy.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-money"></i>
                    <h5>Billing System</h5>
                    <p>Manage bills and payments</p>
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
                    <p>Manage ambulance services</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/ambulance.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-graduation-cap"></i>
                    <h5>Intern System</h5>
                    <p>Manage intern assignments</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/interns.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-clock-o"></i>
                    <h5>Shift Management</h5>
                    <p>Manage staff shifts and schedules</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location='modules/shifts.php'">
                        Manage
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="module-card">
                    <i class="fa fa-envelope"></i>
                    <h5>Communication</h5>
                    <p>SMS, Email, and notifications</p>
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

<!-- Include modals for quick actions -->
<?php include 'includes/admin_modals.php'; ?>

<script>
// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeRevenueChart();
    initializeStaffChart();
});

// Revenue Chart
function initializeRevenueChart() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(getRevenueChartLabels()); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode(getRevenueChartData()); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Staff Distribution Chart
function initializeStaffChart() {
    const ctx = document.getElementById('staffChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($stats['users'] ?? [])); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($stats['users'] ?? [])); ?>,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF6384',
                    '#C9CBCF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Hospital Switcher Function
function switchHospital(hospitalId) {
    if (hospitalId) {
        // Send AJAX request to switch hospital
        fetch('api/switch_hospital.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                hospital_id: hospitalId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to reflect new hospital context
                location.reload();
            } else {
                alert('Failed to switch hospital: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error switching hospital');
        });
    }
}
</script>
