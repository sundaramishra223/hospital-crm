<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user has permission to access this module
if (!hasPermission($_SESSION['user_role'], 'equipment')) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_equipment':
                $name = sanitize($_POST['name']);
                $model = sanitize($_POST['model']);
                $serial_number = sanitize($_POST['serial_number']);
                $purchase_date = sanitize($_POST['purchase_date']);
                $cost = sanitize($_POST['cost']);
                $department_id = sanitize($_POST['department_id']);
                $maintenance_schedule = sanitize($_POST['maintenance_schedule']);
                $warranty_expiry = sanitize($_POST['warranty_expiry']);
                $supplier = sanitize($_POST['supplier']);
                $location = sanitize($_POST['location']);
                $status = sanitize($_POST['status']);
                $description = sanitize($_POST['description']);
                
                $sql = "INSERT INTO equipments (name, model, serial_number, purchase_date, cost, 
                        department_id, maintenance_schedule, warranty_expiry, supplier, location, 
                        status, description, hospital_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssissssssi", $name, $model, $serial_number, $purchase_date, $cost, 
                                $department_id, $maintenance_schedule, $warranty_expiry, $supplier, $location, 
                                $status, $description, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Equipment added: $name");
                    $success_message = "Equipment added successfully!";
                } else {
                    $error_message = "Error adding equipment: " . $conn->error;
                }
                break;
                
            case 'update_equipment':
                $equipment_id = sanitize($_POST['equipment_id']);
                $name = sanitize($_POST['name']);
                $model = sanitize($_POST['model']);
                $serial_number = sanitize($_POST['serial_number']);
                $purchase_date = sanitize($_POST['purchase_date']);
                $cost = sanitize($_POST['cost']);
                $department_id = sanitize($_POST['department_id']);
                $maintenance_schedule = sanitize($_POST['maintenance_schedule']);
                $warranty_expiry = sanitize($_POST['warranty_expiry']);
                $supplier = sanitize($_POST['supplier']);
                $location = sanitize($_POST['location']);
                $status = sanitize($_POST['status']);
                $description = sanitize($_POST['description']);
                
                $sql = "UPDATE equipments SET name = ?, model = ?, serial_number = ?, purchase_date = ?, 
                        cost = ?, department_id = ?, maintenance_schedule = ?, warranty_expiry = ?, 
                        supplier = ?, location = ?, status = ?, description = ?, updated_at = NOW() 
                        WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssissssssii", $name, $model, $serial_number, $purchase_date, $cost, 
                                $department_id, $maintenance_schedule, $warranty_expiry, $supplier, $location, 
                                $status, $description, $equipment_id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Equipment updated: $name");
                    $success_message = "Equipment updated successfully!";
                } else {
                    $error_message = "Error updating equipment: " . $conn->error;
                }
                break;
                
            case 'delete_equipment':
                $equipment_id = sanitize($_POST['equipment_id']);
                
                $sql = "UPDATE equipments SET deleted_at = NOW() WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $equipment_id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Equipment deleted: ID $equipment_id");
                    $success_message = "Equipment deleted successfully!";
                } else {
                    $error_message = "Error deleting equipment: " . $conn->error;
                }
                break;
                
            case 'restore_equipment':
                $equipment_id = sanitize($_POST['equipment_id']);
                
                $sql = "UPDATE equipments SET deleted_at = NULL WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $equipment_id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Equipment restored: ID $equipment_id");
                    $success_message = "Equipment restored successfully!";
                } else {
                    $error_message = "Error restoring equipment: " . $conn->error;
                }
                break;
        }
    }
}

// Get equipment list
$sql = "SELECT e.*, d.name as department_name 
        FROM equipments e 
        LEFT JOIN departments d ON e.department_id = d.id 
        WHERE e.hospital_id = ? AND e.deleted_at IS NULL 
        ORDER BY e.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$equipments = $stmt->get_result();

// Get departments for dropdown
$sql = "SELECT id, name FROM departments WHERE hospital_id = ? AND deleted_at IS NULL ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$departments = $stmt->get_result();

// Get equipment statistics
$sql = "SELECT 
            COUNT(*) as total_equipment,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_equipment,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_equipment,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_equipment,
            SUM(cost) as total_value
        FROM equipments 
        WHERE hospital_id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-cogs me-2"></i>Equipment Management
                    </h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                        <i class="fa fa-plus me-1"></i>Add Equipment
                    </button>
                </div>
                <div class="card-body">
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

                    <!-- Equipment Statistics -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Equipment
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_equipment']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-cogs fa-2x text-gray-300"></i>
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
                                                Active Equipment
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active_equipment']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-check-circle fa-2x text-gray-300"></i>
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
                                                Under Maintenance
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['maintenance_equipment']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-tools fa-2x text-gray-300"></i>
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
                                                Total Value
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($stats['total_value'], 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Equipment List -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="equipmentTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Name</th>
                                    <th>Model</th>
                                    <th>Serial Number</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Purchase Date</th>
                                    <th>Cost</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($equipment = $equipments->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($equipment['name']); ?></strong>
                                            <?php if ($equipment['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($equipment['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($equipment['model']); ?></td>
                                        <td><?php echo htmlspecialchars($equipment['serial_number']); ?></td>
                                        <td><?php echo htmlspecialchars($equipment['department_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($equipment['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    break;
                                                case 'maintenance':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'inactive':
                                                    $status_class = 'danger';
                                                    break;
                                                default:
                                                    $status_class = 'secondary';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($equipment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($equipment['location']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($equipment['purchase_date'])); ?></td>
                                        <td>$<?php echo number_format($equipment['cost'], 2); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewEquipment(<?php echo $equipment['id']; ?>)">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        onclick="editEquipment(<?php echo $equipment['id']; ?>)">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteEquipment(<?php echo $equipment['id']; ?>, '<?php echo htmlspecialchars($equipment['name']); ?>')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Equipment Modal -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-plus me-2"></i>Add New Equipment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_equipment">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Equipment Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="serial_number" class="form-label">Serial Number</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-control" id="department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['id']; ?>">
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="purchase_date" class="form-label">Purchase Date</label>
                                <input type="date" class="form-control" id="purchase_date" name="purchase_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost" class="form-label">Cost ($)</label>
                                <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="supplier" name="supplier">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="maintenance_schedule" class="form-label">Maintenance Schedule</label>
                                <select class="form-control" id="maintenance_schedule" name="maintenance_schedule">
                                    <option value="">Select Schedule</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="semi-annually">Semi-Annually</option>
                                    <option value="annually">Annually</option>
                                    <option value="as-needed">As Needed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                                <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="maintenance">Under Maintenance</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i>Save Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Equipment Modal -->
<div class="modal fade" id="editEquipmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-edit me-2"></i>Edit Equipment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_equipment">
                <input type="hidden" name="equipment_id" id="edit_equipment_id">
                <div class="modal-body" id="editEquipmentBody">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i>Update Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Equipment Modal -->
<div class="modal fade" id="viewEquipmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-eye me-2"></i>Equipment Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewEquipmentBody">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// DataTable initialization
$(document).ready(function() {
    $('#equipmentTable').DataTable({
        "order": [[6, "desc"]], // Sort by purchase date
        "pageLength": 25,
        "responsive": true
    });
});

// Edit equipment function
function editEquipment(equipmentId) {
    $.ajax({
        url: 'api/get_equipment.php',
        type: 'GET',
        data: { id: equipmentId },
        success: function(response) {
            if (response.success) {
                $('#edit_equipment_id').val(equipmentId);
                $('#editEquipmentBody').html(response.html);
                $('#editEquipmentModal').modal('show');
            } else {
                alert('Error loading equipment details: ' + response.message);
            }
        },
        error: function() {
            alert('Error loading equipment details');
        }
    });
}

// View equipment function
function viewEquipment(equipmentId) {
    $.ajax({
        url: 'api/get_equipment.php',
        type: 'GET',
        data: { id: equipmentId, view: true },
        success: function(response) {
            if (response.success) {
                $('#viewEquipmentBody').html(response.html);
                $('#viewEquipmentModal').modal('show');
            } else {
                alert('Error loading equipment details: ' + response.message);
            }
        },
        error: function() {
            alert('Error loading equipment details');
        }
    });
}

// Delete equipment function
function deleteEquipment(equipmentId, equipmentName) {
    if (confirm('Are you sure you want to delete the equipment "' + equipmentName + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_equipment">
            <input type="hidden" name="equipment_id" value="${equipmentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>