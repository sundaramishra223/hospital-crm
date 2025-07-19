<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$hospital_id = $_SESSION['hospital_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_room':
                if (in_array($role, ['admin', 'nurse'])) {
                    $room_number = sanitize($_POST['room_number']);
                    $room_type = sanitize($_POST['room_type']);
                    $floor = (int)$_POST['floor'];
                    $capacity = (int)$_POST['capacity'];
                    $charges_per_day = (float)$_POST['charges_per_day'];
                    $description = sanitize($_POST['description']);
                    $status = sanitize($_POST['status']);
                    
                    $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, floor, capacity, charges_per_day, description, status, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$room_number, $room_type, $floor, $capacity, $charges_per_day, $description, $status, $hospital_id]);
                    
                    addActivityLog($user_id, 'room_added', "Added room: $room_number");
                    $success = "Room added successfully!";
                }
                break;
                
            case 'update_room':
                if (in_array($role, ['admin', 'nurse'])) {
                    $room_id = (int)$_POST['room_id'];
                    $room_number = sanitize($_POST['room_number']);
                    $room_type = sanitize($_POST['room_type']);
                    $floor = (int)$_POST['floor'];
                    $capacity = (int)$_POST['capacity'];
                    $charges_per_day = (float)$_POST['charges_per_day'];
                    $description = sanitize($_POST['description']);
                    $status = sanitize($_POST['status']);
                    
                    $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type = ?, floor = ?, capacity = ?, charges_per_day = ?, description = ?, status = ? WHERE id = ? AND hospital_id = ?");
                    $stmt->execute([$room_number, $room_type, $floor, $capacity, $charges_per_day, $description, $status, $room_id, $hospital_id]);
                    
                    addActivityLog($user_id, 'room_updated', "Updated room: $room_number");
                    $success = "Room updated successfully!";
                }
                break;
                
            case 'delete_room':
                if (in_array($role, ['admin', 'nurse'])) {
                    $room_id = (int)$_POST['room_id'];
                    
                    // Check if room has any beds assigned
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM beds WHERE room_id = ?");
                    $stmt->execute([$room_id]);
                    $bed_count = $stmt->fetchColumn();
                    
                    if ($bed_count > 0) {
                        $error = "Cannot delete room. It has $bed_count bed(s) assigned to it.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ? AND hospital_id = ?");
                        $stmt->execute([$room_id, $hospital_id]);
                        
                        addActivityLog($user_id, 'room_deleted', "Deleted room ID: $room_id");
                        $success = "Room deleted successfully!";
                    }
                }
                break;
                
            case 'assign_bed':
                if (in_array($role, ['admin', 'nurse'])) {
                    $room_id = (int)$_POST['room_id'];
                    $bed_number = sanitize($_POST['bed_number']);
                    $bed_type = sanitize($_POST['bed_type']);
                    $price_per_day = (float)$_POST['price_per_day'];
                    
                    $stmt = $pdo->prepare("INSERT INTO beds (bed_number, room_id, bed_type, price_per_day, status, hospital_id) VALUES (?, ?, ?, ?, 'available', ?)");
                    $stmt->execute([$bed_number, $room_id, $bed_type, $price_per_day, $hospital_id]);
                    
                    addActivityLog($user_id, 'bed_assigned', "Assigned bed $bed_number to room");
                    $success = "Bed assigned successfully!";
                }
                break;
        }
    }
}

// Get rooms with filters
$where_conditions = ['hospital_id = ?'];
$params = [$hospital_id];

if (isset($_GET['floor']) && $_GET['floor']) {
    $where_conditions[] = 'floor = ?';
    $params[] = (int)$_GET['floor'];
}

if (isset($_GET['room_type']) && $_GET['room_type']) {
    $where_conditions[] = 'room_type = ?';
    $params[] = sanitize($_GET['room_type']);
}

if (isset($_GET['status']) && $_GET['status']) {
    $where_conditions[] = 'status = ?';
    $params[] = sanitize($_GET['status']);
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT r.*, COUNT(b.id) as bed_count, COUNT(CASE WHEN b.status = 'occupied' THEN 1 END) as occupied_beds FROM rooms r LEFT JOIN beds b ON r.id = b.room_id WHERE $where_clause GROUP BY r.id ORDER BY r.floor, r.room_number");
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Get available patients for room assignment
$patients = [];
$stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, patient_type FROM patients WHERE status = 'active' AND patient_type = 'inpatient' ORDER BY first_name");
$stmt->execute();
$patients = $stmt->fetchAll();

// Get statistics
$total_rooms = count($rooms);
$available_rooms = 0;
$occupied_rooms = 0;
$maintenance_rooms = 0;

foreach ($rooms as $room) {
    if ($room['status'] == 'available') {
        $available_rooms++;
    } elseif ($room['status'] == 'occupied') {
        $occupied_rooms++;
    } elseif ($room['status'] == 'maintenance') {
        $maintenance_rooms++;
    }
}

$page_title = "Room Management";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="../dashboards/admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Room Management</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="fa fa-building"></i> Room Management
                </h4>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Total Rooms">Total Rooms</h5>
                            <h3 class="mt-3 mb-3"><?php echo $total_rooms; ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-primary rounded">
                                <i class="fa fa-building font-20 text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Available Rooms">Available</h5>
                            <h3 class="mt-3 mb-3"><?php echo $available_rooms; ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-success rounded">
                                <i class="fa fa-check font-20 text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Occupied Rooms">Occupied</h5>
                            <h3 class="mt-3 mb-3"><?php echo $occupied_rooms; ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-warning rounded">
                                <i class="fa fa-user font-20 text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="text-muted fw-normal mt-0" title="Maintenance">Maintenance</h5>
                            <h3 class="mt-3 mb-3"><?php echo $maintenance_rooms; ?></h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-soft-danger rounded">
                                <i class="fa fa-wrench font-20 text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Add/Edit Room -->
        <?php if (in_array($role, ['admin', 'nurse'])): ?>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">
                        <i class="fa fa-plus"></i> Add Room
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_room">
                        
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <input type="text" name="room_number" class="form-control" required placeholder="e.g., 101, 2A, ICU-1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Room Type</label>
                            <select name="room_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="general">General Ward</option>
                                <option value="private">Private Room</option>
                                <option value="icu">ICU</option>
                                <option value="emergency">Emergency</option>
                                <option value="operation_theater">Operation Theater</option>
                                <option value="laboratory">Laboratory</option>
                                <option value="consultation">Consultation Room</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Floor</label>
                                    <input type="number" name="floor" class="form-control" required min="0" max="50">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" name="capacity" class="form-control" required min="1" max="10">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Charges per Day (₹)</label>
                            <input type="number" name="charges_per_day" class="form-control" required min="0" step="0.01">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="reserved">Reserved</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Room description, facilities, etc."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Add Room
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Room List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">
                        <i class="fa fa-list"></i> Room List
                    </h4>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" style="width: auto;" onchange="filterRooms()">
                            <option value="">All Floors</option>
                            <?php for ($i = 0; $i <= 20; $i++): ?>
                            <option value="<?php echo $i; ?>">Floor <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="filterRooms()">
                            <option value="">All Types</option>
                            <option value="general">General</option>
                            <option value="private">Private</option>
                            <option value="icu">ICU</option>
                            <option value="emergency">Emergency</option>
                        </select>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="filterRooms()">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="roomsTable">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Type</th>
                                    <th>Floor</th>
                                    <th>Beds</th>
                                    <th>Charges/Day</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-title bg-soft-primary rounded">
                                                    <?php echo strtoupper(substr($room['room_number'], 0, 2)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($room['room_number']); ?></h6>
                                                <small class="text-muted">Capacity: <?php echo $room['capacity']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $room['room_type'] == 'icu' ? 'danger' : ($room['room_type'] == 'private' ? 'primary' : 'secondary'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $room['room_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $room['floor']; ?></td>
                                    <td>
                                        <?php echo $room['bed_count']; ?> total
                                        <?php if ($room['occupied_beds'] > 0): ?>
                                            <br><small class="text-warning"><?php echo $room['occupied_beds']; ?> occupied</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>₹<?php echo number_format($room['charges_per_day'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $status_color = $room['status'] == 'available' ? 'success' : ($room['status'] == 'occupied' ? 'warning' : 'danger');
                                        $status_icon = $room['status'] == 'available' ? 'check' : ($room['status'] == 'occupied' ? 'user' : 'wrench');
                                        ?>
                                        <span class="badge bg-<?php echo $status_color; ?>">
                                            <i class="fa fa-<?php echo $status_icon; ?>"></i>
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewRoom(<?php echo $room['id']; ?>)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php if (in_array($role, ['admin', 'nurse'])): ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="editRoom(<?php echo $room['id']; ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(<?php echo $room['id']; ?>)">
                                                <i class="fa fa-trash"></i>
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
    </div>

    <!-- Room Details Modal -->
    <div class="modal fade" id="roomDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Room Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="roomDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Bed Modal -->
    <?php if (in_array($role, ['admin', 'nurse'])): ?>
    <div class="modal fade" id="assignBedModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Bed to Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_bed">
                    <input type="hidden" name="room_id" id="bed_room_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Bed Number</label>
                            <input type="text" name="bed_number" class="form-control" required placeholder="e.g., A1, B2, ICU-1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bed Type</label>
                            <select name="bed_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="general">General</option>
                                <option value="private">Private</option>
                                <option value="icu">ICU</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Day (₹)</label>
                            <input type="number" name="price_per_day" class="form-control" required min="0" step="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Bed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function filterRooms() {
    // Implementation for filtering rooms
    console.log('Filter functionality will be implemented');
}

function viewRoom(roomId) {
    // Implementation for viewing room details
    alert('View room details functionality will be implemented');
}

function editRoom(roomId) {
    // Implementation for editing room
    alert('Edit room functionality will be implemented');
}

function deleteRoom(roomId) {
    if (confirm('Are you sure you want to delete this room?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_room">
            <input type="hidden" name="room_id" value="${roomId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function assignBed(roomId) {
    document.getElementById('bed_room_id').value = roomId;
    new bootstrap.Modal(document.getElementById('assignBedModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>