<?php
require_once 'includes/functions.php';

// Check if user is logged in and has receptionist role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'receptionist') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get receptionist information
$receptionist_info = getUserById($user_id);

// Get statistics
$today_appointments = getTotalRecords('appointments', ['DATE(appointment_date)' => date('Y-m-d'), 'hospital_id' => $hospital_id]);
$today_registrations = getTotalRecords('patients', ['DATE(created_at)' => date('Y-m-d'), 'hospital_id' => $hospital_id]);
$pending_bills = getTotalRecords('bills', ['status' => 'pending', 'hospital_id' => $hospital_id]);
$total_patients = getTotalRecords('patients', ['hospital_id' => $hospital_id, 'status' => 'active']);

// Get today's appointments and recent registrations
$today_appointments_list = getTodayAppointments($hospital_id);
$recent_patients = getRecentPatients($hospital_id, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
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
                    <h1 class="h2"><i class="fas fa-user-tie me-2"></i>Receptionist Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.href='modules/patients.php?action=add'">
                                <i class="fas fa-user-plus"></i> New Patient
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="location.href='modules/appointments.php?action=add'">
                                <i class="fas fa-calendar-plus"></i> New Appointment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-handshake me-2"></i>Welcome, <?php echo htmlspecialchars($receptionist_info['name']); ?>!</h5>
                    <p class="mb-0">Manage patient registrations, appointments, and provide excellent front desk service.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
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
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            New Registrations</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_registrations; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-plus fa-2x text-gray-300"></i>
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
                                            Pending Bills</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_bills; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
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
                                        <a href="modules/patients.php?action=add" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-user-plus me-2"></i>Register Patient
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/appointments.php?action=add" class="btn btn-outline-success w-100">
                                            <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/billing.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-file-invoice me-2"></i>Billing & Payments
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="modules/patients.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-search me-2"></i>Find Patient
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Schedule & Recent Activity -->
                <div class="row">
                    <!-- Today's Appointments -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Today's Appointments</h6>
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="card-body">
                                <?php if (empty($today_appointments_list)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-calendar fa-3x mb-3"></i>
                                        <p>No appointments scheduled for today</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient</th>
                                                    <th>Doctor</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($today_appointments_list as $appointment): ?>
                                                    <tr>
                                                        <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                                        <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                                        <td><?php echo ucfirst($appointment['appointment_type']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo getStatusColor($appointment['status']); ?>">
                                                                <?php echo ucfirst($appointment['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Patient Search -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-success">Quick Patient Search</h6>
                            </div>
                            <div class="card-body">
                                <form id="quickSearchForm">
                                    <div class="mb-3">
                                        <label for="searchTerm" class="form-label">Search Patient</label>
                                        <input type="text" class="form-control" id="searchTerm" placeholder="Name, Phone, or ID">
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </form>
                                
                                <hr>
                                
                                <h6 class="text-muted mb-3">Recent Registrations</h6>
                                <div class="list-group">
                                    <?php foreach (array_slice($recent_patients, 0, 5) as $patient): ?>
                                        <a href="modules/patients.php?id=<?php echo $patient['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($patient['name']); ?></h6>
                                                <small><?php echo date('M j', strtotime($patient['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <small>
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($patient['phone']); ?>
                                                </small>
                                            </p>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Summary -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Today's Billing Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-success">₹12,450</h4>
                                        <p class="text-muted">Today's Collection</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-warning">₹3,200</h4>
                                        <p class="text-muted">Pending Payments</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-info">₹15,650</h4>
                                        <p class="text-muted">Total Bills Generated</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-primary">47</h4>
                                        <p class="text-muted">Transactions</p>
                                    </div>
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
        // Quick search functionality
        document.getElementById('quickSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = document.getElementById('searchTerm').value;
            if (searchTerm.trim()) {
                window.location.href = `modules/patients.php?search=${encodeURIComponent(searchTerm)}`;
            }
        });
    </script>
</body>
</html>