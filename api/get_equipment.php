<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user has permission to access this module
if (!hasPermission($_SESSION['user_role'], 'equipment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $equipment_id = sanitize($_GET['id']);
    $is_view = isset($_GET['view']) && $_GET['view'] == 'true';
    
    // Get equipment details
    $sql = "SELECT e.*, d.name as department_name 
            FROM equipments e 
            LEFT JOIN departments d ON e.department_id = d.id 
            WHERE e.id = ? AND e.hospital_id = ? AND e.deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $equipment_id, $hospital_id);
    $stmt->execute();
    $equipment = $stmt->get_result()->fetch_assoc();
    
    if (!$equipment) {
        echo json_encode(['success' => false, 'message' => 'Equipment not found']);
        exit();
    }
    
    if ($is_view) {
        // Return view HTML
        $html = '
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Equipment Information</h6>
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>' . htmlspecialchars($equipment['name']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Model:</strong></td>
                        <td>' . htmlspecialchars($equipment['model']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Serial Number:</strong></td>
                        <td>' . htmlspecialchars($equipment['serial_number']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Department:</strong></td>
                        <td>' . htmlspecialchars($equipment['department_name'] ?? 'N/A') . '</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td><span class="badge bg-' . ($equipment['status'] == 'active' ? 'success' : ($equipment['status'] == 'maintenance' ? 'warning' : 'danger')) . '">' . ucfirst($equipment['status']) . '</span></td>
                    </tr>
                    <tr>
                        <td><strong>Location:</strong></td>
                        <td>' . htmlspecialchars($equipment['location']) . '</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">Purchase & Maintenance</h6>
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Purchase Date:</strong></td>
                        <td>' . date('M d, Y', strtotime($equipment['purchase_date'])) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Cost:</strong></td>
                        <td>$' . number_format($equipment['cost'], 2) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Supplier:</strong></td>
                        <td>' . htmlspecialchars($equipment['supplier']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Maintenance Schedule:</strong></td>
                        <td>' . htmlspecialchars($equipment['maintenance_schedule']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Warranty Expiry:</strong></td>
                        <td>' . ($equipment['warranty_expiry'] ? date('M d, Y', strtotime($equipment['warranty_expiry'])) : 'N/A') . '</td>
                    </tr>
                </table>
            </div>
        </div>';
        
        if ($equipment['description']) {
            $html .= '
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="text-primary">Description</h6>
                    <p class="text-muted">' . htmlspecialchars($equipment['description']) . '</p>
                </div>
            </div>';
        }
        
        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        // Return edit form HTML
        // Get departments for dropdown
        $sql = "SELECT id, name FROM departments WHERE hospital_id = ? AND deleted_at IS NULL ORDER BY name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $hospital_id);
        $stmt->execute();
        $departments = $stmt->get_result();
        
        $departments_html = '<option value="">Select Department</option>';
        while ($dept = $departments->fetch_assoc()) {
            $selected = ($dept['id'] == $equipment['department_id']) ? 'selected' : '';
            $departments_html .= '<option value="' . $dept['id'] . '" ' . $selected . '>' . htmlspecialchars($dept['name']) . '</option>';
        }
        
        $html = '
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_name" class="form-label">Equipment Name *</label>
                    <input type="text" class="form-control" id="edit_name" name="name" value="' . htmlspecialchars($equipment['name']) . '" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_model" class="form-label">Model</label>
                    <input type="text" class="form-control" id="edit_model" name="model" value="' . htmlspecialchars($equipment['model']) . '">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_serial_number" class="form-label">Serial Number</label>
                    <input type="text" class="form-control" id="edit_serial_number" name="serial_number" value="' . htmlspecialchars($equipment['serial_number']) . '">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_department_id" class="form-label">Department</label>
                    <select class="form-control" id="edit_department_id" name="department_id">
                        ' . $departments_html . '
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_purchase_date" class="form-label">Purchase Date</label>
                    <input type="date" class="form-control" id="edit_purchase_date" name="purchase_date" value="' . $equipment['purchase_date'] . '">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_cost" class="form-label">Cost ($)</label>
                    <input type="number" class="form-control" id="edit_cost" name="cost" step="0.01" min="0" value="' . $equipment['cost'] . '">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_supplier" class="form-label">Supplier</label>
                    <input type="text" class="form-control" id="edit_supplier" name="supplier" value="' . htmlspecialchars($equipment['supplier']) . '">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="edit_location" name="location" value="' . htmlspecialchars($equipment['location']) . '">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_maintenance_schedule" class="form-label">Maintenance Schedule</label>
                    <select class="form-control" id="edit_maintenance_schedule" name="maintenance_schedule">
                        <option value="">Select Schedule</option>
                        <option value="monthly"' . ($equipment['maintenance_schedule'] == 'monthly' ? ' selected' : '') . '>Monthly</option>
                        <option value="quarterly"' . ($equipment['maintenance_schedule'] == 'quarterly' ? ' selected' : '') . '>Quarterly</option>
                        <option value="semi-annually"' . ($equipment['maintenance_schedule'] == 'semi-annually' ? ' selected' : '') . '>Semi-Annually</option>
                        <option value="annually"' . ($equipment['maintenance_schedule'] == 'annually' ? ' selected' : '') . '>Annually</option>
                        <option value="as-needed"' . ($equipment['maintenance_schedule'] == 'as-needed' ? ' selected' : '') . '>As Needed</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_warranty_expiry" class="form-label">Warranty Expiry</label>
                    <input type="date" class="form-control" id="edit_warranty_expiry" name="warranty_expiry" value="' . $equipment['warranty_expiry'] . '">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="edit_status" class="form-label">Status</label>
                    <select class="form-control" id="edit_status" name="status" required>
                        <option value="active"' . ($equipment['status'] == 'active' ? ' selected' : '') . '>Active</option>
                        <option value="maintenance"' . ($equipment['status'] == 'maintenance' ? ' selected' : '') . '>Under Maintenance</option>
                        <option value="inactive"' . ($equipment['status'] == 'inactive' ? ' selected' : '') . '>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="edit_description" class="form-label">Description</label>
            <textarea class="form-control" id="edit_description" name="description" rows="3">' . htmlspecialchars($equipment['description']) . '</textarea>
        </div>';
        
        echo json_encode(['success' => true, 'html' => $html]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>