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
if (!hasPermission($_SESSION['user_role'], 'staff')) {
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
                $position = sanitize($_POST['position']);
                $hire_date = sanitize($_POST['hire_date']);
                $salary = sanitize($_POST['salary']);
                $status = sanitize($_POST['status']);
                
                $sql = "INSERT INTO staff (first_name, last_name, email, phone, department_id, position, hire_date, salary, status, hospital_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssi", $first_name, $last_name, $email, $phone, $department_id, $position, $hire_date, $salary, $status, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Staff added: ' . $first_name . ' ' . $last_name);
                    $success_message = "Staff member added successfully!";
                } else {
                    $error_message = "Error adding staff member: " . $conn->error;
                }
                break;
                
            case 'edit':
                $id = sanitize($_POST['id']);
                $first_name = sanitize($_POST['first_name']);
                $last_name = sanitize($_POST['last_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $department_id = sanitize($_POST['department_id']);
                $position = sanitize($_POST['position']);
                $hire_date = sanitize($_POST['hire_date']);
                $salary = sanitize($_POST['salary']);
                $status = sanitize($_POST['status']);
                
                $sql = "UPDATE staff SET first_name = ?, last_name = ?, email = ?, phone = ?, department_id = ?, 
                        position = ?, hire_date = ?, salary = ?, status = ?, updated_at = NOW() 
                        WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssii", $first_name, $last_name, $email, $phone, $department_id, $position, $hire_date, $salary, $status, $id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Staff updated: ' . $first_name . ' ' . $last_name);
                    $success_message = "Staff member updated successfully!";
                } else {
                    $error_message = "Error updating staff member: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = sanitize($_POST['id']);
                
                if (softDeleteRecord('staff', $id, $hospital_id)) {
                    logActivity($_SESSION['user_id'], 'Staff deleted: ID ' . $id);
                    $success_message = "Staff member deleted successfully!";
                } else {
                    $error_message = "Error deleting staff member";
                }
                break;
                
            case 'restore':
                $id = sanitize($_POST['id']);
                
                if (softRestoreRecord('staff', $id, $hospital_id)) {
                    logActivity($_SESSION['user_id'], 'Staff restored: ID ' . $id);
                    $success_message = "Staff member restored successfully!";
                } else {
                    $error_message = "Error restoring staff member";
                }
                break;
        }
    }
}

// Get staff with department names
$sql = "SELECT s.*, d.name as department_name 
        FROM staff s 
        LEFT JOIN departments d ON s.department_id = d.id 
        WHERE s.hospital_id = ? 
        ORDER BY s.first_name, s.last_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$staff_members = $stmt->get_result();

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
                        <i class="fa fa-users me-2"></i>Staff Management
                    </h4>
                    <?php if (hasPermission($user_role, 'staff', 'create')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                        <i class="fa fa-plus me-1"></i>Add Staff
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
                                    <th>Position</th>
                                    <th>Hire Date</th>
                                    <th>Salary</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($staff = $staff_members->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $staff['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                    <td>
                                        <?php if ($staff['department_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($staff['department_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($staff['position']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($staff['hire_date'])); ?></td>
                                    <td><?php echo '$' . number_format($staff['salary'], 2); ?></td>
                                    <td>
                                        <?php if ($staff['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if (hasPermission($user_role, 'staff', 'read')): ?>
                                            <button class="btn btn-sm btn-info" onclick="viewStaff(<?php echo $staff['id']; ?>)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission($user_role, 'staff', 'update')): ?>
                                            <button class="btn btn-sm btn-warning" onclick="editStaff(<?php echo $staff['id']; ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission($user_role, 'staff', 'delete')): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>')">
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

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Staff Member</h5>
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
                                <label for="position" class="form-label">Position *</label>
                                <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hire_date" class="form-label">Hire Date *</label>
                                <input type="date" class="form-control" id="hire_date" name="hire_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salary" class="form-label">Salary *</label>
                                <input type="number" class="form-control" id="salary" name="salary" min="0" step="0.01" required>
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
                    <button type="submit" class="btn btn-primary">Add Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Staff Member</h5>
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
                                <label for="edit_position" class="form-label">Position *</label>
                                <input type="text" class="form-control" id="edit_position" name="position" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_hire_date" class="form-label">Hire Date *</label>
                                <input type="date" class="form-control" id="edit_hire_date" name="hire_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_salary" class="form-label">Salary *</label>
                                <input type="number" class="form-control" id="edit_salary" name="salary" min="0" step="0.01" required>
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
                    <button type="submit" class="btn btn-warning">Update Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Staff Modal -->
<div class="modal fade" id="viewStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Staff Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="staffDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editStaff(id) {
    // Fetch staff data and populate modal
    fetch(`../api/get_staff.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.staff.id;
                document.getElementById('edit_first_name').value = data.staff.first_name;
                document.getElementById('edit_last_name').value = data.staff.last_name;
                document.getElementById('edit_email').value = data.staff.email;
                document.getElementById('edit_phone').value = data.staff.phone;
                document.getElementById('edit_department_id').value = data.staff.department_id;
                document.getElementById('edit_position').value = data.staff.position;
                document.getElementById('edit_hire_date').value = data.staff.hire_date;
                document.getElementById('edit_salary').value = data.staff.salary;
                document.getElementById('edit_status').value = data.staff.status;
                
                new bootstrap.Modal(document.getElementById('editStaffModal')).show();
            } else {
                alert('Error loading staff data');
            }
        });
}

function viewStaff(id) {
    // Fetch staff data and show in modal
    fetch(`../api/get_staff.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const staff = data.staff;
                document.getElementById('staffDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> ${staff.id}</p>
                            <p><strong>Name:</strong> ${staff.first_name} ${staff.last_name}</p>
                            <p><strong>Email:</strong> ${staff.email}</p>
                            <p><strong>Phone:</strong> ${staff.phone}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Department:</strong> ${staff.department_name || 'Not assigned'}</p>
                            <p><strong>Position:</strong> ${staff.position}</p>
                            <p><strong>Hire Date:</strong> ${new Date(staff.hire_date).toLocaleDateString()}</p>
                            <p><strong>Salary:</strong> $${parseFloat(staff.salary).toLocaleString()}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${staff.status === 'active' ? 'success' : 'secondary'}">${staff.status}</span></p>
                        </div>
                    </div>
                `;
                
                new bootstrap.Modal(document.getElementById('viewStaffModal')).show();
            } else {
                alert('Error loading staff data');
            }
        });
}

function deleteStaff(id, name) {
    if (confirm(`Are you sure you want to delete staff member "${name}"?`)) {
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