<?php
require_once 'includes/functions.php';

// Check if user is logged in and has staff role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'staff') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get staff information
$staff_info = getUserById($user_id);

// Get statistics
$total_patients = getTotalRecords('patients', ['hospital_id' => $hospital_id, 'status' => 'active']);
$today_appointments = getTotalRecords('appointments', ['DATE(appointment_date)' => date('Y-m-d'), 'hospital_id' => $hospital_id]);
$pending_tasks = 5; // This would come from a tasks table
$my_attendance = getAttendanceStatus($user_id, date('Y-m-d'));

// Get recent activities and tasks
$recent_activities = getRecentActivities($user_id, 10);
$upcoming_tasks = getUpcomingTasks($user_id, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-users-cog me-2"></i>Staff Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAttendance()">
                                <i class="fas fa-clock"></i> Mark Attendance
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="location.href='modules/patients.php'">
                                <i class="fas fa-users"></i> Patient Support
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-handshake me-2"></i>Welcome, <?php echo htmlspecialchars($staff_info['name']); ?>!</h5>
                    <p class="mb-0">Support hospital operations, assist patients, and maintain quality service standards.</p>
                </div>

                <!-- Attendance Status -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-left-<?php echo $my_attendance ? 'success' : 'warning'; ?> shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            Today's Attendance
                                        </div>
                                        <div class="h6 mb-0">
                                            <?php if ($my_attendance): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i> 
                                                    Checked In at <?php echo date('g:i A', strtotime($my_attendance['check_in_time'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-warning">
                                                    <i class="fas fa-clock"></i> 
                                                    Not Checked In Yet
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Patients</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_patients; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Today's Appointments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_appointments; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Tasks</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_tasks; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Working Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">8.5 hrs</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-business-time fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/patients.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-user-friends me-2"></i>Patient Support
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/equipment.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-tools me-2"></i>Equipment Check
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/maintenance.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-wrench me-2"></i>Maintenance Log
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/reports.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-chart-line me-2"></i>Daily Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks & Activities -->
                <div class="row">
                    <!-- Today's Tasks -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Today's Tasks</h6>
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Equipment Sanitization</h6>
                                            <small class="text-warning">Pending</small>
                                        </div>
                                        <p class="mb-1">Clean and sanitize all medical equipment in Ward A</p>
                                        <small>Due: 2:00 PM</small>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Patient Transport</h6>
                                            <small class="text-success">Completed</small>
                                        </div>
                                        <p class="mb-1">Assist patient in Room 205 to Radiology</p>
                                        <small>Completed: 11:30 AM</small>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Inventory Check</h6>
                                            <small class="text-info">In Progress</small>
                                        </div>
                                        <p class="mb-1">Check medical supplies in storage room</p>
                                        <small>Started: 1:00 PM</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-success">Recent Activities</h6>
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Patient Assistance</h6>
                                            <small>30 mins ago</small>
                                        </div>
                                        <p class="mb-1">Helped patient with wheelchair to OPD</p>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Equipment Maintenance</h6>
                                            <small>1 hour ago</small>
                                        </div>
                                        <p class="mb-1">Reported malfunctioning BP monitor in Ward B</p>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Attendance Marked</h6>
                                            <small>8:30 AM</small>
                                        </div>
                                        <p class="mb-1">Checked in for today's shift</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Work Schedule -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">This Week's Schedule</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Day</th>
                                                <th>Shift</th>
                                                <th>Department</th>
                                                <th>Tasks</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="table-success">
                                                <td><strong>Today</strong></td>
                                                <td>8:00 AM - 4:30 PM</td>
                                                <td>General Ward</td>
                                                <td>Patient support, Equipment maintenance</td>
                                                <td><span class="badge bg-success">Active</span></td>
                                            </tr>
                                            <tr>
                                                <td>Tomorrow</td>
                                                <td>8:00 AM - 4:30 PM</td>
                                                <td>Emergency Ward</td>
                                                <td>Emergency support, Supplies management</td>
                                                <td><span class="badge bg-secondary">Scheduled</span></td>
                                            </tr>
                                            <tr>
                                                <td>Wednesday</td>
                                                <td>2:00 PM - 10:30 PM</td>
                                                <td>ICU Support</td>
                                                <td>Critical care support, Equipment monitoring</td>
                                                <td><span class="badge bg-secondary">Scheduled</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    
    <script>
        function markAttendance() {
            // AJAX call to mark attendance
            fetch('api/mark_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'check_in',
                    user_id: <?php echo $user_id; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance marked successfully!');
                    location.reload();
                } else {
                    alert('Error marking attendance: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }
    </script>
</body>
</html>
