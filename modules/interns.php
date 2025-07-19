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
if (!hasPermission($_SESSION['user_role'], 'interns')) {
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
                $university = sanitize($_POST['university']);
                $course = sanitize($_POST['course']);
                $start_date = sanitize($_POST['start_date']);
                $end_date = sanitize($_POST['end_date']);
                $supervisor_id = sanitize($_POST['supervisor_id']);
                $status = sanitize($_POST['status']);
                
                $sql = "INSERT INTO interns (first_name, last_name, email, phone, department_id, university, course, start_date, end_date, supervisor_id, status, hospital_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssssi", $first_name, $last_name, $email, $phone, $department_id, $university, $course, $start_date, $end_date, $supervisor_id, $status, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Intern added: ' . $first_name . ' ' . $last_name);
                    $success_message = "Intern added successfully!";
                } else {
                    $error_message = "Error adding intern: " . $conn->error;
                }
                break;
                
            case 'edit':
                $id = sanitize($_POST['id']);
                $first_name = sanitize($_POST['first_name']);
                $last_name = sanitize($_POST['last_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $department_id = sanitize($_POST['department_id']);
                $university = sanitize($_POST['university']);
                $course = sanitize($_POST['course']);
                $start_date = sanitize($_POST['start_date']);
                $end_date = sanitize($_POST['end_date']);
                $supervisor_id = sanitize($_POST['supervisor_id']);
                $status = sanitize($_POST['status']);
                
                $sql = "UPDATE interns SET first_name = ?, last_name = ?, email = ?, phone = ?, department_id = ?, 
                        university = ?, course = ?, start_date = ?, end_date = ?, supervisor_id = ?, status = ?, updated_at = NOW() 
                        WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssssii", $first_name, $last_name, $email, $phone, $department_id, $university, $course, $start_date, $end_date, $supervisor_id, $status, $id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], 'Intern updated: ' . $first_name . ' ' . $last_name);
                    $success_message = "Intern updated successfully!";
                } else {
                    $error_message = "Error updating intern: " . $conn->error;
                }
                break;
                
            case 'delete':
                $id = sanitize($_POST['id']);
                
                if (softDeleteRecord('interns', $id, $hospital_id)) {
                    logActivity($_SESSION['user_id'], 'Intern deleted: ID ' . $id);
                    $success_message = "Intern deleted successfully!";
                } else {
                    $error_message = "Error deleting intern";
                }
                break;
        }
    }
}

// Get interns with department and supervisor names
$sql = "SELECT i.*, d.name as department_name, CONCAT(dr.first_name, ' ', dr.last_name) as supervisor_name 
        FROM interns i 
        LEFT JOIN departments d ON i.department_id = d.id 
        LEFT JOIN doctors dr ON i.supervisor_id = dr.id 
        WHERE i.hospital_id = ? 
        ORDER BY i.first_name, i.last_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$interns = $stmt->get_result();

// Get departments for dropdown
$sql = "SELECT id, name FROM departments WHERE hospital_id = ? AND status = 'active' ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$departments = $stmt->get_result();

// Get doctors for supervisor dropdown
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
                        <i class="fa fa-graduation-cap me-2"></i>Intern Management
                    </h4>
                    <?php if (hasPermission($user_role, 'interns', 'create')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInternModal">
                        <i class="fa fa-plus me-1"></i>Add Intern
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
                                    <th>Department</th>
                                    <th>University</th>
                                    <th>Course</th>
                                    <th>Duration</th>
                                    <th>Supervisor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($intern = $interns->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $intern['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($intern['email']); ?></td>
                                    <td>
                                        <?php if ($intern['department_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($intern['department_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($intern['university']); ?></td>
                                    <td><?php echo htmlspecialchars($intern['course']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($intern['start_date'])) . ' - ' . date('M d, Y', strtotime($intern['end_date'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($intern['supervisor_name']): ?>
                                            <span class="badge bg-warning"><?php echo htmlspecialchars($intern['supervisor_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($intern['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($intern['status'] == 'completed'): ?>
                                            <span class="badge bg-info">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if (hasPermission($user_role, 'interns', 'read')): ?>
                                            <button class="btn btn-sm btn-info" onclick="viewIntern(<?php echo $intern['id']; ?>)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission($user_role, 'interns', 'update')): ?>
                                            <button class="btn btn-sm btn-warning" onclick="editIntern(<?php echo $intern['id']; ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission($user_role, 'interns', 'delete')): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteIntern(<?php echo $intern['id']; ?>, '<?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?>')">
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

<!-- Add Intern Modal -->
<div class="modal fade" id="addInternModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Intern</h5>
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
                                <label for="university" class="form-label">University *</label>
                                <input type="text" class="form-control" id="university" name="university" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course" class="form-label">Course *</label>
                                <input type="text" class="form-control" id="course" name="course" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supervisor_id" class="form-label">Supervisor</label>
                                <select class="form-control" id="supervisor_id" name="supervisor_id">
                                    <option value="">Select Supervisor</option>
                                    <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                    <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date *</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Intern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Intern Modal -->
<div class="modal fade" id="editInternModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Intern</h5>
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
                                <label for="edit_university" class="form-label">University *</label>
                                <input type="text" class="form-control" id="edit_university" name="university" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_course" class="form-label">Course *</label>
                                <input type="text" class="form-control" id="edit_course" name="course" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_supervisor_id" class="form-label">Supervisor</label>
                                <select class="form-control" id="edit_supervisor_id" name="supervisor_id">
                                    <option value="">Select Supervisor</option>
                                    <?php 
                                    $doctors->data_seek(0);
                                    while ($doctor = $doctors->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_end_date" class="form-label">End Date *</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Intern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Intern Modal -->
<div class="modal fade" id="viewInternModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Intern Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="internDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editIntern(id) {
    // Fetch intern data and populate modal
    fetch(`../api/get_intern.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.intern.id;
                document.getElementById('edit_first_name').value = data.intern.first_name;
                document.getElementById('edit_last_name').value = data.intern.last_name;
                document.getElementById('edit_email').value = data.intern.email;
                document.getElementById('edit_phone').value = data.intern.phone;
                document.getElementById('edit_department_id').value = data.intern.department_id;
                document.getElementById('edit_university').value = data.intern.university;
                document.getElementById('edit_course').value = data.intern.course;
                document.getElementById('edit_start_date').value = data.intern.start_date;
                document.getElementById('edit_end_date').value = data.intern.end_date;
                document.getElementById('edit_supervisor_id').value = data.intern.supervisor_id;
                document.getElementById('edit_status').value = data.intern.status;
                
                new bootstrap.Modal(document.getElementById('editInternModal')).show();
            } else {
                alert('Error loading intern data');
            }
        });
}

function viewIntern(id) {
    // Fetch intern data and show in modal
    fetch(`../api/get_intern.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const intern = data.intern;
                document.getElementById('internDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> ${intern.id}</p>
                            <p><strong>Name:</strong> ${intern.first_name} ${intern.last_name}</p>
                            <p><strong>Email:</strong> ${intern.email}</p>
                            <p><strong>Phone:</strong> ${intern.phone}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Department:</strong> ${intern.department_name || 'Not assigned'}</p>
                            <p><strong>University:</strong> ${intern.university}</p>
                            <p><strong>Course:</strong> ${intern.course}</p>
                            <p><strong>Supervisor:</strong> ${intern.supervisor_name || 'Not assigned'}</p>
                            <p><strong>Duration:</strong> ${new Date(intern.start_date).toLocaleDateString()} - ${new Date(intern.end_date).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${getStatusBadgeColor(intern.status)}">${intern.status}</span></p>
                        </div>
                    </div>
                `;
                
                new bootstrap.Modal(document.getElementById('viewInternModal')).show();
            } else {
                alert('Error loading intern data');
            }
        });
}

function getStatusBadgeColor(status) {
    switch(status) {
        case 'active': return 'success';
        case 'completed': return 'info';
        default: return 'secondary';
    }
}

function deleteIntern(id, name) {
    if (confirm(`Are you sure you want to delete intern "${name}"?`)) {
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