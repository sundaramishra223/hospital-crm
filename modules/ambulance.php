<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff', 'receptionist'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];
$action = $_GET['action'] ?? '';

// Create ambulances table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ambulances (
        id INT(11) NOT NULL AUTO_INCREMENT,
        vehicle_number VARCHAR(20) NOT NULL UNIQUE,
        driver_name VARCHAR(100) NOT NULL,
        driver_phone VARCHAR(20) NOT NULL,
        driver_license VARCHAR(50),
        vehicle_type ENUM('basic', 'advanced', 'emergency') DEFAULT 'basic',
        status ENUM('available', 'busy', 'maintenance', 'inactive') DEFAULT 'available',
        equipment TEXT,
        insurance_expiry DATE,
        last_service_date DATE,
        next_service_date DATE,
        hospital_id INT(11) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY vehicle_number (vehicle_number),
        KEY status (status)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ambulance_bookings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        ambulance_id INT(11) NOT NULL,
        patient_name VARCHAR(100) NOT NULL,
        patient_phone VARCHAR(20) NOT NULL,
        pickup_address TEXT NOT NULL,
        destination_address TEXT NOT NULL,
        booking_date DATETIME NOT NULL,
        emergency_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        status ENUM('pending', 'assigned', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
        distance_km DECIMAL(8,2),
        estimated_cost DECIMAL(10,2),
        actual_cost DECIMAL(10,2),
        notes TEXT,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ambulance_id (ambulance_id),
        KEY status (status),
        KEY booking_date (booking_date)
    )");
} catch (Exception $e) {
    // Tables might already exist
}

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'add_ambulance') {
        $vehicle_number = sanitize($_POST['vehicle_number']);
        $driver_name = sanitize($_POST['driver_name']);
        $driver_phone = sanitize($_POST['driver_phone']);
        $driver_license = sanitize($_POST['driver_license']);
        $vehicle_type = sanitize($_POST['vehicle_type']);
        $equipment = sanitize($_POST['equipment']);
        $insurance_expiry = $_POST['insurance_expiry'];
        $last_service_date = $_POST['last_service_date'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO ambulances (vehicle_number, driver_name, driver_phone, driver_license, vehicle_type, equipment, insurance_expiry, last_service_date, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vehicle_number, $driver_name, $driver_phone, $driver_license, $vehicle_type, $equipment, $insurance_expiry, $last_service_date, $hospital_id]);
            
            logActivity($user_id, 'create', "Added ambulance: $vehicle_number");
            $success_message = "Ambulance added successfully!";
        } catch (Exception $e) {
            $error_message = "Error adding ambulance: " . $e->getMessage();
        }
    }
    
    if ($action == 'book_ambulance') {
        $ambulance_id = intval($_POST['ambulance_id']);
        $patient_name = sanitize($_POST['patient_name']);
        $patient_phone = sanitize($_POST['patient_phone']);
        $pickup_address = sanitize($_POST['pickup_address']);
        $destination_address = sanitize($_POST['destination_address']);
        $booking_date = $_POST['booking_date'];
        $emergency_level = sanitize($_POST['emergency_level']);
        $distance_km = floatval($_POST['distance_km']);
        $estimated_cost = floatval($_POST['estimated_cost']);
        $notes = sanitize($_POST['notes']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO ambulance_bookings (ambulance_id, patient_name, patient_phone, pickup_address, destination_address, booking_date, emergency_level, distance_km, estimated_cost, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ambulance_id, $patient_name, $patient_phone, $pickup_address, $destination_address, $booking_date, $emergency_level, $distance_km, $estimated_cost, $notes, $user_id]);
            
            // Update ambulance status to busy
            $pdo->prepare("UPDATE ambulances SET status = 'busy' WHERE id = ?")->execute([$ambulance_id]);
            
            logActivity($user_id, 'create', "Booked ambulance for: $patient_name");
            $success_message = "Ambulance booked successfully!";
        } catch (Exception $e) {
            $error_message = "Error booking ambulance: " . $e->getMessage();
        }
    }
    
    if ($action == 'update_booking_status') {
        $booking_id = intval($_POST['booking_id']);
        $status = sanitize($_POST['status']);
        $actual_cost = floatval($_POST['actual_cost']);
        
        try {
            $stmt = $pdo->prepare("UPDATE ambulance_bookings SET status = ?, actual_cost = ? WHERE id = ?");
            $stmt->execute([$status, $actual_cost, $booking_id]);
            
            // If completed or cancelled, make ambulance available
            if (in_array($status, ['completed', 'cancelled'])) {
                $booking = $pdo->prepare("SELECT ambulance_id FROM ambulance_bookings WHERE id = ?")->execute([$booking_id]);
                $booking_data = $pdo->prepare("SELECT ambulance_id FROM ambulance_bookings WHERE id = ?")->fetch();
                if ($booking_data) {
                    $pdo->prepare("UPDATE ambulances SET status = 'available' WHERE id = ?")->execute([$booking_data['ambulance_id']]);
                }
            }
            
            logActivity($user_id, 'update', "Updated booking status ID: $booking_id to $status");
            $success_message = "Booking status updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating booking: " . $e->getMessage();
        }
    }
    
    if ($action == 'update_ambulance_status') {
        $ambulance_id = intval($_POST['ambulance_id']);
        $status = sanitize($_POST['ambulance_status']);
        
        try {
            $stmt = $pdo->prepare("UPDATE ambulances SET status = ? WHERE id = ?");
            $stmt->execute([$status, $ambulance_id]);
            
            logActivity($user_id, 'update', "Updated ambulance status ID: $ambulance_id to $status");
            $success_message = "Ambulance status updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating ambulance status: " . $e->getMessage();
        }
    }
}

// Get filters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Get ambulances
$ambulance_where = ['hospital_id = ?'];
$ambulance_params = [$hospital_id];

if (!empty($status_filter)) {
    $ambulance_where[] = 'status = ?';
    $ambulance_params[] = $status_filter;
}

if (!empty($type_filter)) {
    $ambulance_where[] = 'vehicle_type = ?';
    $ambulance_params[] = $type_filter;
}

$ambulance_where_clause = implode(' AND ', $ambulance_where);

$stmt = $pdo->prepare("SELECT * FROM ambulances WHERE $ambulance_where_clause ORDER BY vehicle_number");
$stmt->execute($ambulance_params);
$ambulances = $stmt->fetchAll();

// Get bookings
$booking_where = ['1=1'];
$booking_params = [];

if (!empty($date_filter)) {
    $booking_where[] = 'DATE(booking_date) = ?';
    $booking_params[] = $date_filter;
}

$booking_where_clause = implode(' AND ', $booking_where);

$stmt = $pdo->prepare("
    SELECT ab.*, a.vehicle_number, a.driver_name, a.driver_phone, a.vehicle_type
    FROM ambulance_bookings ab
    JOIN ambulances a ON ab.ambulance_id = a.id
    WHERE $booking_where_clause
    ORDER BY ab.booking_date DESC
");
$stmt->execute($booking_params);
$bookings = $stmt->fetchAll();

// Get statistics
$total_ambulances = count($ambulances);
$available_ambulances = count(array_filter($ambulances, function($amb) { return $amb['status'] == 'available'; }));
$busy_ambulances = count(array_filter($ambulances, function($amb) { return $amb['status'] == 'busy'; }));
$maintenance_ambulances = count(array_filter($ambulances, function($amb) { return $amb['status'] == 'maintenance'; }));

$total_bookings = count($bookings);
$pending_bookings = count(array_filter($bookings, function($booking) { return $booking['status'] == 'pending'; }));
$active_bookings = count(array_filter($bookings, function($booking) { return in_array($booking['status'], ['assigned', 'in_transit']); }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambulance Management - <?php echo getHospitalSetting('hospital_name', 'Hospital CRM'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-ambulance me-2"></i>Ambulance Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#emergencyBookingModal">
                                <i class="fas fa-exclamation-triangle"></i> Emergency Booking
                            </button>
                            <?php if ($user_role == 'admin'): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAmbulanceModal">
                                    <i class="fas fa-plus"></i> Add Ambulance
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Emergency Hotline -->
                <div class="alert alert-danger mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5><i class="fas fa-phone-alt me-2"></i>Emergency Ambulance Hotline</h5>
                            <p class="mb-0">Call <strong>108</strong> for immediate emergency ambulance service</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-light btn-lg" onclick="window.open('tel:108')">
                                <i class="fas fa-phone"></i> Call 108
                            </button>
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
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Ambulances</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_ambulances; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ambulance fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $available_ambulances; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">On Service</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $busy_ambulances; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-route fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Pending Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ambulance Fleet Status -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Ambulance Fleet Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Vehicle Number</th>
                                                <th>Driver</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Last Service</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ambulances as $ambulance): ?>
                                                <tr class="<?php echo getAmbulanceRowClass($ambulance); ?>">
                                                    <td><strong><?php echo htmlspecialchars($ambulance['vehicle_number']); ?></strong></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($ambulance['driver_name']); ?><br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($ambulance['driver_phone']); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getVehicleTypeColor($ambulance['vehicle_type']); ?>">
                                                            <?php echo ucfirst($ambulance['vehicle_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getAmbulanceStatusColor($ambulance['status']); ?>">
                                                            <?php echo ucfirst($ambulance['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo $ambulance['last_service_date'] ? date('M j, Y', strtotime($ambulance['last_service_date'])) : 'N/A'; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="bookAmbulance(<?php echo $ambulance['id']; ?>)" <?php echo $ambulance['status'] != 'available' ? 'disabled' : ''; ?>>
                                                                <i class="fas fa-calendar-plus"></i>
                                                            </button>
                                                            <?php if ($user_role == 'admin'): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="updateAmbulanceStatus(<?php echo $ambulance['id']; ?>, '<?php echo $ambulance['status']; ?>')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Booking Panel -->
                    <div class="col-lg-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-success">Quick Booking</h6>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label class="form-label">Patient Name</label>
                                        <input type="text" class="form-control" id="quick_patient_name">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Patient Phone</label>
                                        <input type="tel" class="form-control" id="quick_patient_phone">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Pickup Address</label>
                                        <textarea class="form-control" id="quick_pickup_address" rows="2"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Emergency Level</label>
                                        <select class="form-control" id="quick_emergency_level">
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="critical">Critical</option>
                                            <option value="low">Low</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-success w-100" onclick="quickBooking()">
                                        <i class="fas fa-ambulance me-2"></i>Book Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Vehicle</th>
                                        <th>Pickup → Destination</th>
                                        <th>Date/Time</th>
                                        <th>Emergency</th>
                                        <th>Status</th>
                                        <th>Cost</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bookings)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                                <p>No bookings found</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr class="<?php echo getBookingRowClass($booking); ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['patient_name']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['patient_phone']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['vehicle_number']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['driver_name']); ?></small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <strong>From:</strong> <?php echo htmlspecialchars(substr($booking['pickup_address'], 0, 30)); ?>...<br>
                                                        <strong>To:</strong> <?php echo htmlspecialchars(substr($booking['destination_address'], 0, 30)); ?>...
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, g:i A', strtotime($booking['booking_date'])); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getEmergencyLevelColor($booking['emergency_level']); ?>">
                                                        <?php echo ucfirst($booking['emergency_level']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getBookingStatusColor($booking['status']); ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($booking['actual_cost']): ?>
                                                        ₹<?php echo number_format($booking['actual_cost'], 2); ?>
                                                    <?php elseif ($booking['estimated_cost']): ?>
                                                        ₹<?php echo number_format($booking['estimated_cost'], 2); ?> (Est.)
                                                    <?php else: ?>
                                                        <span class="text-muted">TBD</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Emergency Booking Modal -->
    <div class="modal fade" id="emergencyBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">🚨 Emergency Ambulance Booking</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="book_ambulance">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ambulance_id" class="form-label">Available Ambulance *</label>
                                    <select class="form-control" id="ambulance_id" name="ambulance_id" required>
                                        <option value="">Select Ambulance</option>
                                        <?php foreach ($ambulances as $ambulance): ?>
                                            <?php if ($ambulance['status'] == 'available'): ?>
                                                <option value="<?php echo $ambulance['id']; ?>">
                                                    <?php echo htmlspecialchars($ambulance['vehicle_number']); ?> - <?php echo htmlspecialchars($ambulance['driver_name']); ?> (<?php echo ucfirst($ambulance['vehicle_type']); ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="booking_date" class="form-label">Date/Time *</label>
                                    <input type="datetime-local" class="form-control" id="booking_date" name="booking_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="patient_name" class="form-label">Patient Name *</label>
                                    <input type="text" class="form-control" id="patient_name" name="patient_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="patient_phone" class="form-label">Patient Phone *</label>
                                    <input type="tel" class="form-control" id="patient_phone" name="patient_phone" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pickup_address" class="form-label">Pickup Address *</label>
                                    <textarea class="form-control" id="pickup_address" name="pickup_address" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="destination_address" class="form-label">Destination *</label>
                                    <textarea class="form-control" id="destination_address" name="destination_address" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="emergency_level" class="form-label">Emergency Level *</label>
                                    <select class="form-control" id="emergency_level" name="emergency_level" required>
                                        <option value="critical">Critical</option>
                                        <option value="high">High</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="low">Low</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="distance_km" class="form-label">Distance (KM)</label>
                                    <input type="number" class="form-control" id="distance_km" name="distance_km" step="0.1" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="estimated_cost" class="form-label">Estimated Cost (₹)</label>
                                    <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="booking_notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="booking_notes" name="notes" rows="2" placeholder="Special instructions, medical condition, etc."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">🚨 Book Emergency Ambulance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Ambulance Modal -->
    <?php if ($user_role == 'admin'): ?>
    <div class="modal fade" id="addAmbulanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Ambulance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_ambulance">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vehicle_number" class="form-label">Vehicle Number *</label>
                                    <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vehicle_type" class="form-label">Vehicle Type *</label>
                                    <select class="form-control" id="vehicle_type" name="vehicle_type" required>
                                        <option value="basic">Basic Life Support</option>
                                        <option value="advanced">Advanced Life Support</option>
                                        <option value="emergency">Emergency Response</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="driver_name" class="form-label">Driver Name *</label>
                                    <input type="text" class="form-control" id="driver_name" name="driver_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="driver_phone" class="form-label">Driver Phone *</label>
                                    <input type="tel" class="form-control" id="driver_phone" name="driver_phone" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="driver_license" class="form-label">Driver License</label>
                                    <input type="text" class="form-control" id="driver_license" name="driver_license">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="insurance_expiry" class="form-label">Insurance Expiry</label>
                                    <input type="date" class="form-control" id="insurance_expiry" name="insurance_expiry">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_service_date" class="form-label">Last Service Date</label>
                                    <input type="date" class="form-control" id="last_service_date" name="last_service_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="equipment" class="form-label">Equipment List</label>
                                    <textarea class="form-control" id="equipment" name="equipment" rows="3" placeholder="Defibrillator, Oxygen tank, First aid kit, etc."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Ambulance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function quickBooking() {
            alert('Quick booking functionality will be implemented with AJAX');
        }
        
        function bookAmbulance(ambulanceId) {
            document.getElementById('ambulance_id').value = ambulanceId;
            new bootstrap.Modal(document.getElementById('emergencyBookingModal')).show();
        }
        
        function updateBookingStatus(bookingId, currentStatus) {
            const newStatus = prompt('Enter new status (pending, assigned, in_transit, completed, cancelled):', currentStatus);
            if (newStatus && newStatus !== currentStatus) {
                const actualCost = prompt('Enter actual cost (if applicable):');
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_booking_status">
                    <input type="hidden" name="booking_id" value="${bookingId}">
                    <input type="hidden" name="status" value="${newStatus}">
                    <input type="hidden" name="actual_cost" value="${actualCost || 0}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function updateAmbulanceStatus(ambulanceId, currentStatus) {
            const newStatus = prompt('Enter new status (available, busy, maintenance, inactive):', currentStatus);
            if (newStatus && newStatus !== currentStatus) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_ambulance_status">
                    <input type="hidden" name="ambulance_id" value="${ambulanceId}">
                    <input type="hidden" name="ambulance_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Calculate estimated cost based on distance
        document.getElementById('distance_km').addEventListener('input', function() {
            const distance = parseFloat(this.value) || 0;
            const costPerKm = 25; // ₹25 per km
            const baseCost = 200; // ₹200 base cost
            const estimatedCost = baseCost + (distance * costPerKm);
            document.getElementById('estimated_cost').value = estimatedCost.toFixed(2);
        });
    </script>
</body>
</html>

<?php
function getAmbulanceRowClass($ambulance) {
    switch ($ambulance['status']) {
        case 'available': return 'table-success';
        case 'busy': return 'table-warning';
        case 'maintenance': return 'table-info';
        case 'inactive': return 'table-secondary';
        default: return '';
    }
}

function getAmbulanceStatusColor($status) {
    switch ($status) {
        case 'available': return 'success';
        case 'busy': return 'warning';
        case 'maintenance': return 'info';
        case 'inactive': return 'secondary';
        default: return 'secondary';
    }
}

function getVehicleTypeColor($type) {
    switch ($type) {
        case 'emergency': return 'danger';
        case 'advanced': return 'warning';
        case 'basic': return 'primary';
        default: return 'secondary';
    }
}

function getBookingRowClass($booking) {
    switch ($booking['emergency_level']) {
        case 'critical': return 'table-danger';
        case 'high': return 'table-warning';
        default: return '';
    }
}

function getBookingStatusColor($status) {
    switch ($status) {
        case 'completed': return 'success';
        case 'in_transit': return 'info';
        case 'assigned': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getEmergencyLevelColor($level) {
    switch ($level) {
        case 'critical': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'info';
        case 'low': return 'success';
        default: return 'secondary';
    }
}
?>
