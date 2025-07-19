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
if (!hasPermission($_SESSION['user_role'], 'departments')) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $head_doctor_id = sanitize($_POST['head_doctor_id']);
                $status = sanitize($_POST['status']);
                
                $sql = "INSERT INTO departments (name, description, head_doctor_id, status, hospital_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $name, $description, $head_doctor_id, $status, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Department added: ' . $name);
                    $success_message = "Department added successfully!";
                } else {
                    $error_message = "Error adding department: " . $conn->error;
                }
                break;
                
            case 'edit':
                $id = sanitize($_POST['id']);
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $head_doctor_id = sanitize($_POST['head_doctor_id']);
                $status = sanitize($_POST['status']);
                
                $sql = "UPDATE departments SET name = ?, description = ?, head_doctor_id = ?, status = ?, updated_at = NOW() 
                        WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssii", $name, $description, $head_doctor_id, $status, $id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Department updated: ' . $name);
                    $success_message = "Department updated successfully!";
                } else {
                    $error_message = "Error updating department: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = sanitize($_POST['id']);
                
                if (softDeleteRecord('departments', $id, $hospital_id)) {
                    logActivity($_SESSION['user_id'], 'Department deleted: ID ' . $id);
                    $success_message = "Department deleted successfully!";
                } else {
                    $error_message = "Error deleting department";
                }
                break;
                
            case 'restore':
                $id = sanitize($_POST['id']);
                
                if (softRestoreRecord('departments', $id, $hospital_id)) {
                    logActivity($_SESSION['user_id'], 'Department restored: ID ' . $id);
                    $success_message = "Department restored successfully!";
                } else {
                    $error_message = "Error restoring department";
                }
                break;
        }
    }
}

// Get departments with doctor names
$sql = "SELECT d.*, CONCAT(dr.first_name, ' ', dr.last_name) as head_doctor_name 
        FROM departments d 
        LEFT JOIN doctors dr ON d.head_doctor_id = dr.id 
        WHERE d.hospital_id = ? 
        ORDER BY d.name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$departments = $stmt->get_result();

// Get doctors for dropdown
$sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM doctors WHERE hospital_id = ? AND status = 'active' ORDER BY first_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$doctors = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-building me-2"></i>Department Management
                    </h4>
                    <?php if (hasPermission($user_role, 'departments', 'create')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                        <i class="fa fa-plus me-1"></i>Add Department
                    </button>
                    <?php endif; ?>
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

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Head Doctor</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($dept = $departments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $dept['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($dept['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($dept['description']); ?></td>
                                    <td>
                                        <?php if ($dept['head_doctor_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($dept['head_doctor_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($dept['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($dept['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if (hasPermission($user_role, 'departments', 'read')): ?>
                                            <button class="btn btn-sm btn-info" onclick="viewDepartment(<?php echo $dept['id']; ?>)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission($user_role, 'departments', 'update')): ?>
                                            <button class="btn btn-sm btn-warning" onclick="editDepartment(<?php echo $dept['id']; ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission($user_role, 'departments', 'delete')): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteDepartment(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['name']); ?>')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
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

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="head_doctor_id" class="form-label">Head Doctor</label>
                        <select class="form-control" id="head_doctor_id" name="head_doctor_id">
                            <option value="">Select Head Doctor</option>
                            <?php while ($doctor = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_head_doctor_id" class="form-label">Head Doctor</label>
                        <select class="form-control" id="edit_head_doctor_id" name="head_doctor_id">
                            <option value="">Select Head Doctor</option>
                            <?php 
                            $doctors->data_seek(0);
                            while ($doctor = $doctors->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Department Modal -->
<div class="modal fade" id="viewDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Department Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="departmentDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editDepartment(id) {
    // Fetch department data and populate modal
    fetch(`../api/get_department.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.department.id;
                document.getElementById('edit_name').value = data.department.name;
                document.getElementById('edit_description').value = data.department.description;
                document.getElementById('edit_head_doctor_id').value = data.department.head_doctor_id;
                document.getElementById('edit_status').value = data.department.status;
                
                new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
            } else {
                alert('Error loading department data');
            }
        });
}

function viewDepartment(id) {
    // Fetch department data and show in modal
    fetch(`../api/get_department.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const dept = data.department;
                document.getElementById('departmentDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> ${dept.id}</p>
                            <p><strong>Name:</strong> ${dept.name}</p>
                            <p><strong>Description:</strong> ${dept.description || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Head Doctor:</strong> ${dept.head_doctor_name || 'Not assigned'}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${dept.status === 'active' ? 'success' : 'secondary'}">${dept.status}</span></p>
                            <p><strong>Created:</strong> ${new Date(dept.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                `;
                
                new bootstrap.Modal(document.getElementById('viewDepartmentModal')).show();
            } else {
                alert('Error loading department data');
            }
        });
}

function deleteDepartment(id, name) {
    if (confirm(`Are you sure you want to delete department "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>