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
if (!hasPermission($_SESSION['user_role'], 'nurses')) {
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
                $first_name = sanitize($_POST['first_name']);
                $last_name = sanitize($_POST['last_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $department_id = sanitize($_POST['department_id']);
                $qualification = sanitize($_POST['qualification']);
                $experience = sanitize($_POST['experience']);
                $shift = sanitize($_POST['shift']);
                $status = sanitize($_POST['status']);
                
                $sql = "INSERT INTO nurses (first_name, last_name, email, phone, department_id, qualification, experience, shift, status, hospital_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssi", $first_name, $last_name, $email, $phone, $department_id, $qualification, $experience, $shift, $status, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Nurse added: ' . $first_name . ' ' . $last_name);
                    $success_message = "Nurse added successfully!";
                } else {
                    $error_message = "Error adding nurse: " . $conn->error;
                }
                break;
                
            case 'edit':
                $id = sanitize($_POST['id']);
                $first_name = sanitize($_POST['first_name']);
                $last_name = sanitize($_POST['last_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $department_id = sanitize($_POST['department_id']);
                $qualification = sanitize($_POST['qualification']);
                $experience = sanitize($_POST['experience']);
                $shift = sanitize($_POST['shift']);
                $status = sanitize($_POST['status']);
                
                $sql = "UPDATE nurses SET first_name = ?, last_name = ?, email = ?, phone = ?, department_id = ?, 
                        qualification = ?, experience = ?, shift = ?, status = ?, updated_at = NOW() 
                        WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssii", $first_name, $last_name, $email, $phone, $department_id, $qualification, $experience, $shift, $status, $id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Nurse updated: ' . $first_name . ' ' . $last_name);
                    $success_message = "Nurse updated successfully!";
                } else {
                    $error_message = "Error updating nurse: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = sanitize($_POST['id']);
                
                if (softDeleteRecord('nurses', $id, $hospital_id)) {
                    logActivity($_SESSION['user_id'], 'Nurse deleted: ID ' . $id);
                    $success_message = "Nurse deleted successfully!";
                } else {
                    $error_message = "Error deleting nurse";
                }
                break;
                
            case 'restore':
                $id = sanitize($_POST['id']);
                
                if (softRestoreRecord('nurses', $id, $hospital_id)) {
                    logActivity($_SESSION['user_id'], 'Nurse restored: ID ' . $id);
                    $success_message = "Nurse restored successfully!";
                } else {
                    $error_message = "Error restoring nurse";
                }
                break;
        }
    }
}

// Get nurses with department names
$sql = "SELECT n.*, d.name as department_name 
        FROM nurses n 
        LEFT JOIN departments d ON n.department_id = d.id 
        WHERE n.hospital_id = ? 
        ORDER BY n.first_name, n.last_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$nurses = $stmt->get_result();

// Get departments for dropdown
$sql = "SELECT id, name FROM departments WHERE hospital_id = ? AND status = 'active' ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$departments = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-plus-square me-2"></i>Nurse Management
                    </h4>
                    <?php if (hasPermission($user_role, 'nurses', 'create')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNurseModal">
                        <i class="fa fa-plus me-1"></i>Add Nurse
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
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Qualification</th>
                                    <th>Experience</th>
                                    <th>Shift</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($nurse = $nurses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $nurse['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($nurse['email']); ?></td>
                                    <td><?php echo htmlspecialchars($nurse['phone']); ?></td>
                                    <td>
                                        <?php if ($nurse['department_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($nurse['department_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($nurse['qualification']); ?></td>
                                    <td><?php echo htmlspecialchars($nurse['experience']); ?> years</td>
                                    <td>
                                        <?php if ($nurse['shift'] == 'morning'): ?>
                                            <span class="badge bg-warning">Morning</span>
                                        <?php elseif ($nurse['shift'] == 'afternoon'): ?>
                                            <span class="badge bg-info">Afternoon</span>
                                        <?php elseif ($nurse['shift'] == 'night'): ?>
                                            <span class="badge bg-dark">Night</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($nurse['shift']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($nurse['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if (hasPermission($user_role, 'nurses', 'read')): ?>
                                            <button class="btn btn-sm btn-info" onclick="viewNurse(<?php echo $nurse['id']; ?>)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission($user_role, 'nurses', 'update')): ?>
                                            <button class="btn btn-sm btn-warning" onclick="editNurse(<?php echo $nurse['id']; ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission($user_role, 'nurses', 'delete')): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteNurse(<?php echo $nurse['id']; ?>, '<?php echo htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name']); ?>')">
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

<!-- Add Nurse Modal -->
<div class="modal fade" id="addNurseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Nurse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-control" id="department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="qualification" class="form-label">Qualification *</label>
                                <input type="text" class="form-control" id="qualification" name="qualification" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="experience" class="form-label">Experience (Years) *</label>
                                <input type="number" class="form-control" id="experience" name="experience" min="0" max="50" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="shift" class="form-label">Shift *</label>
                                <select class="form-control" id="shift" name="shift" required>
                                    <option value="">Select Shift</option>
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="night">Night</option>
                                    <option value="flexible">Flexible</option>
                                </select>
                            </div>
                        </div>
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
                    <button type="submit" class="btn btn-primary">Add Nurse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Nurse Modal -->
<div class="modal fade" id="editNurseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Nurse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_department_id" class="form-label">Department</label>
                                <select class="form-control" id="edit_department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_qualification" class="form-label">Qualification *</label>
                                <input type="text" class="form-control" id="edit_qualification" name="qualification" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_experience" class="form-label">Experience (Years) *</label>
                                <input type="number" class="form-control" id="edit_experience" name="experience" min="0" max="50" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_shift" class="form-label">Shift *</label>
                                <select class="form-control" id="edit_shift" name="shift" required>
                                    <option value="">Select Shift</option>
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="night">Night</option>
                                    <option value="flexible">Flexible</option>
                                </select>
                            </div>
                        </div>
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
                    <button type="submit" class="btn btn-warning">Update Nurse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Nurse Modal -->
<div class="modal fade" id="viewNurseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nurse Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="nurseDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editNurse(id) {
    // Fetch nurse data and populate modal
    fetch(`../api/get_nurse.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.nurse.id;
                document.getElementById('edit_first_name').value = data.nurse.first_name;
                document.getElementById('edit_last_name').value = data.nurse.last_name;
                document.getElementById('edit_email').value = data.nurse.email;
                document.getElementById('edit_phone').value = data.nurse.phone;
                document.getElementById('edit_department_id').value = data.nurse.department_id;
                document.getElementById('edit_qualification').value = data.nurse.qualification;
                document.getElementById('edit_experience').value = data.nurse.experience;
                document.getElementById('edit_shift').value = data.nurse.shift;
                document.getElementById('edit_status').value = data.nurse.status;
                
                new bootstrap.Modal(document.getElementById('editNurseModal')).show();
            } else {
                alert('Error loading nurse data');
            }
        });
}

function viewNurse(id) {
    // Fetch nurse data and show in modal
    fetch(`../api/get_nurse.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const nurse = data.nurse;
                document.getElementById('nurseDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> ${nurse.id}</p>
                            <p><strong>Name:</strong> ${nurse.first_name} ${nurse.last_name}</p>
                            <p><strong>Email:</strong> ${nurse.email}</p>
                            <p><strong>Phone:</strong> ${nurse.phone}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Department:</strong> ${nurse.department_name || 'Not assigned'}</p>
                            <p><strong>Qualification:</strong> ${nurse.qualification}</p>
                            <p><strong>Experience:</strong> ${nurse.experience} years</p>
                            <p><strong>Shift:</strong> <span class="badge bg-${getShiftBadgeColor(nurse.shift)}">${nurse.shift}</span></p>
                            <p><strong>Status:</strong> <span class="badge bg-${nurse.status === 'active' ? 'success' : 'secondary'}">${nurse.status}</span></p>
                        </div>
                    </div>
                `;
                
                new bootstrap.Modal(document.getElementById('viewNurseModal')).show();
            } else {
                alert('Error loading nurse data');
            }
        });
}

function getShiftBadgeColor(shift) {
    switch(shift) {
        case 'morning': return 'warning';
        case 'afternoon': return 'info';
        case 'night': return 'dark';
        default: return 'secondary';
    }
}

function deleteNurse(id, name) {
    if (confirm(`Are you sure you want to delete nurse "${name}"?`)) {
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