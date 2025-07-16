<?php
require_once 'includes/functions.php';

// Check if user is logged in and has intern role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'intern') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get intern information
$intern_info = getUserById($user_id);

// Get limited statistics (appropriate for intern level)
$patients_observed = 15; // This would come from intern_activities table
$cases_studied = 8;
$training_hours = 45;
$supervisor_rating = 4.2;

// Get recent learning activities and assignments
$recent_activities = getInternActivities($user_id, 10);
$upcoming_assignments = getInternAssignments($user_id, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Dashboard - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
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
                    <h1 class="h2"><i class="fas fa-graduation-cap me-2"></i>Intern Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.href='modules/learning.php'">
                                <i class="fas fa-book"></i> Learning Modules
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="location.href='modules/supervision.php'">
                                <i class="fas fa-user-check"></i> Request Supervision
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-success mb-4">
                    <h5><i class="fas fa-seedling me-2"></i>Welcome to your medical journey, <?php echo htmlspecialchars($intern_info['name']); ?>!</h5>
                    <p class="mb-0">Learn, observe, and grow under expert supervision. Your training progress is being carefully monitored.</p>
                </div>

                <!-- Training Progress Card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-left-primary shadow">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Training Progress - Month 2 of 12
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: 17%" aria-valuenow="17" aria-valuemin="0" aria-valuemax="100">17%</div>
                                        </div>
                                        <div class="h6 mb-0 mt-2">
                                            <span class="text-success">On Track</span> - Next evaluation in 4 weeks
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Patients Observed</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $patients_observed; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-eye fa-2x text-gray-300"></i>
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
                                            Cases Studied</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cases_studied; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-medical fa-2x text-gray-300"></i>
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
                                            Training Hours</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $training_hours; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Supervisor Rating</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $supervisor_rating; ?>/5</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-star fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions for Interns -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Learning Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/patient_observation.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-eye me-2"></i>Patient Observation
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/case_studies.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-book-medical me-2"></i>Case Studies
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/supervision.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-user-check me-2"></i>Request Supervision
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/progress_report.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-chart-bar me-2"></i>Progress Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Learning Modules & Current Assignments -->
                <div class="row">
                    <!-- Current Learning Modules -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-success">Current Learning Modules</h6>
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Basic Patient Assessment</h6>
                                            <small class="text-success">75% Complete</small>
                                        </div>
                                        <p class="mb-1">Fundamentals of patient examination and history taking</p>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: 75%"></div>
                                        </div>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Medical Documentation</h6>
                                            <small class="text-warning">40% Complete</small>
                                        </div>
                                        <p class="mb-1">Proper medical record keeping and documentation standards</p>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" style="width: 40%"></div>
                                        </div>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Emergency Protocols</h6>
                                            <small class="text-info">Not Started</small>
                                        </div>
                                        <p class="mb-1">Hospital emergency procedures and protocols</p>
                                        <div class="progress">
                                            <div class="progress-bar bg-secondary" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supervision & Mentorship -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Supervision & Mentorship</h6>
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="text-primary">Assigned Supervisor</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3">
                                            <i class="fas fa-user-md fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <strong>Dr. Rajesh Sharma</strong><br>
                                            <small class="text-muted">Senior Physician - Cardiology</small><br>
                                            <small><i class="fas fa-phone"></i> +91-9876543210</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-success">Recent Feedback</h6>
                                    <div class="alert alert-light">
                                        <small>
                                            <i class="fas fa-quote-left"></i>
                                            "Good progress in patient interaction skills. Focus more on medical terminology and documentation accuracy."
                                            <br><strong>- Dr. Sharma (3 days ago)</strong>
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button class="btn btn-outline-primary" onclick="requestSupervision()">
                                        <i class="fas fa-calendar-plus"></i> Request Supervision Session
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities & Upcoming Schedule -->
                <div class="row">
                    <!-- Recent Learning Activities -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-info">Recent Learning Activities</h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Patient Case Study</h6>
                                            <small>2 hours ago</small>
                                        </div>
                                        <p class="mb-1">Reviewed diabetes management case with Dr. Sharma</p>
                                        <small>Department: Endocrinology</small>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Patient Observation</h6>
                                            <small>Yesterday</small>
                                        </div>
                                        <p class="mb-1">Observed routine check-up procedures in OPD</p>
                                        <small>Duration: 3 hours</small>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Module Completion</h6>
                                            <small>2 days ago</small>
                                        </div>
                                        <p class="mb-1">Completed "Vital Signs Assessment" module</p>
                                        <small>Score: 85/100</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Schedule -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-warning">This Week's Schedule</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Day</th>
                                                <th>Time</th>
                                                <th>Activity</th>
                                                <th>Department</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="table-primary">
                                                <td><strong>Today</strong></td>
                                                <td>2:00 PM</td>
                                                <td>Patient Observation</td>
                                                <td>Cardiology</td>
                                            </tr>
                                            <tr>
                                                <td>Tomorrow</td>
                                                <td>9:00 AM</td>
                                                <td>Learning Module</td>
                                                <td>Online</td>
                                            </tr>
                                            <tr>
                                                <td>Wednesday</td>
                                                <td>10:00 AM</td>
                                                <td>Case Study Review</td>
                                                <td>General Medicine</td>
                                            </tr>
                                            <tr>
                                                <td>Friday</td>
                                                <td>3:00 PM</td>
                                                <td>Supervisor Meeting</td>
                                                <td>Dr. Sharma's Office</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Tracking -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Performance Tracking</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-info">15</h4>
                                        <p class="text-muted">Patients Observed</p>
                                        <small class="text-success">Target: 20/month</small>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-success">8</h4>
                                        <p class="text-muted">Case Studies</p>
                                        <small class="text-success">Target: 10/month</small>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-warning">45</h4>
                                        <p class="text-muted">Training Hours</p>
                                        <small class="text-warning">Target: 60/month</small>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-primary">4.2/5</h4>
                                        <p class="text-muted">Average Rating</p>
                                        <small class="text-success">Excellent Performance</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="alert alert-info">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>Learning Tip:</strong> Focus on completing your Emergency Protocols module this week to stay on track with your training schedule.
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
        function requestSupervision() {
            // AJAX call to request supervision session
            alert('Supervision request sent to Dr. Sharma. You will receive a confirmation shortly.');
        }
    </script>
</body>
</html>
