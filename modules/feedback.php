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
if (!hasPermission($_SESSION['user_role'], 'feedback')) {
    header('Location: ../index.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$user_role = $_SESSION['user_role'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_feedback':
                $patient_id = sanitize($_POST['patient_id']);
                $doctor_id = sanitize($_POST['doctor_id']);
                $feedback_type = sanitize($_POST['feedback_type']);
                $rating = sanitize($_POST['rating']);
                $feedback_text = sanitize($_POST['feedback_text']);
                $category = sanitize($_POST['category']);
                $status = 'pending';
                
                $sql = "INSERT INTO feedbacks (patient_id, doctor_id, feedback_type, rating, 
                        feedback_text, category, status, hospital_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisisssi", $patient_id, $doctor_id, $feedback_type, $rating, 
                                $feedback_text, $category, $status, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Feedback submitted for patient ID: $patient_id");
                    $success_message = "Feedback submitted successfully!";
                } else {
                    $error_message = "Error submitting feedback: " . $conn->error;
                }
                break;
                
            case 'update_feedback_status':
                $feedback_id = sanitize($_POST['feedback_id']);
                $status = sanitize($_POST['status']);
                $admin_response = sanitize($_POST['admin_response']);
                
                $sql = "UPDATE feedbacks SET status = ?, admin_response = ?, updated_at = NOW() 
                        WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssii", $status, $admin_response, $feedback_id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Feedback status updated: ID $feedback_id to $status");
                    $success_message = "Feedback status updated successfully!";
                } else {
                    $error_message = "Error updating feedback status: " . $conn->error;
                }
                break;
                
            case 'delete_feedback':
                $feedback_id = sanitize($_POST['feedback_id']);
                
                $sql = "UPDATE feedbacks SET deleted_at = NOW() WHERE id = ? AND hospital_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $feedback_id, $hospital_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Feedback deleted: ID $feedback_id");
                    $success_message = "Feedback deleted successfully!";
                } else {
                    $error_message = "Error deleting feedback: " . $conn->error;
                }
                break;
        }
    }
}

// Get feedback list with filters
$where_conditions = ["f.hospital_id = ? AND f.deleted_at IS NULL"];
$params = [$hospital_id];
$param_types = "i";

if (isset($_GET['status']) && $_GET['status'] != '') {
    $where_conditions[] = "f.status = ?";
    $params[] = sanitize($_GET['status']);
    $param_types .= "s";
}

if (isset($_GET['category']) && $_GET['category'] != '') {
    $where_conditions[] = "f.category = ?";
    $params[] = sanitize($_GET['category']);
    $param_types .= "s";
}

if (isset($_GET['rating']) && $_GET['rating'] != '') {
    $where_conditions[] = "f.rating = ?";
    $params[] = sanitize($_GET['rating']);
    $param_types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

$sql = "SELECT f.*, p.first_name as patient_fname, p.last_name as patient_lname, 
        d.first_name as doctor_fname, d.last_name as doctor_lname 
        FROM feedbacks f 
        LEFT JOIN patients p ON f.patient_id = p.id 
        LEFT JOIN doctors d ON f.doctor_id = d.id 
        WHERE $where_clause 
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$feedbacks = $stmt->get_result();

// Get patients for dropdown
$sql = "SELECT id, first_name, last_name FROM patients WHERE hospital_id = ? AND deleted_at IS NULL ORDER BY first_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$patients = $stmt->get_result();

// Get doctors for dropdown
$sql = "SELECT id, first_name, last_name FROM doctors WHERE hospital_id = ? AND deleted_at IS NULL ORDER BY first_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$doctors = $stmt->get_result();

// Get feedback statistics
$sql = "SELECT 
            COUNT(*) as total_feedback,
            AVG(rating) as avg_rating,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_feedback,
            SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_feedback,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_feedback,
            SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_feedback,
            SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative_feedback
        FROM feedbacks 
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
                        <i class="fa fa-comment me-2"></i>Feedback Management
                    </h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeedbackModal">
                        <i class="fa fa-plus me-1"></i>Add Feedback
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

                    <!-- Feedback Statistics -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Feedback
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_feedback']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-comment fa-2x text-gray-300"></i>
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
                                                Average Rating
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_rating'], 1); ?>/5</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-star fa-2x text-gray-300"></i>
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
                                                Pending Review
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_feedback']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-clock fa-2x text-gray-300"></i>
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
                                                Positive Feedback
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['positive_feedback']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fa fa-thumbs-up fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="">All Status</option>
                                                <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="reviewed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                                                <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-control" id="category" name="category">
                                                <option value="">All Categories</option>
                                                <option value="service" <?php echo (isset($_GET['category']) && $_GET['category'] == 'service') ? 'selected' : ''; ?>>Service Quality</option>
                                                <option value="treatment" <?php echo (isset($_GET['category']) && $_GET['category'] == 'treatment') ? 'selected' : ''; ?>>Treatment</option>
                                                <option value="facility" <?php echo (isset($_GET['category']) && $_GET['category'] == 'facility') ? 'selected' : ''; ?>>Facility</option>
                                                <option value="staff" <?php echo (isset($_GET['category']) && $_GET['category'] == 'staff') ? 'selected' : ''; ?>>Staff Behavior</option>
                                                <option value="other" <?php echo (isset($_GET['category']) && $_GET['category'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="rating" class="form-label">Rating</label>
                                            <select class="form-control" id="rating" name="rating">
                                                <option value="">All Ratings</option>
                                                <option value="5" <?php echo (isset($_GET['rating']) && $_GET['rating'] == '5') ? 'selected' : ''; ?>>5 Stars</option>
                                                <option value="4" <?php echo (isset($_GET['rating']) && $_GET['rating'] == '4') ? 'selected' : ''; ?>>4 Stars</option>
                                                <option value="3" <?php echo (isset($_GET['rating']) && $_GET['rating'] == '3') ? 'selected' : ''; ?>>3 Stars</option>
                                                <option value="2" <?php echo (isset($_GET['rating']) && $_GET['rating'] == '2') ? 'selected' : ''; ?>>2 Stars</option>
                                                <option value="1" <?php echo (isset($_GET['rating']) && $_GET['rating'] == '1') ? 'selected' : ''; ?>>1 Star</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fa fa-filter me-1"></i>Filter
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feedback List -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="feedbackTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Rating</th>
                                    <th>Category</th>
                                    <th>Feedback</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($feedback = $feedbacks->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($feedback['patient_fname'] . ' ' . $feedback['patient_lname']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($feedback['feedback_type']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($feedback['doctor_fname'] . ' ' . $feedback['doctor_lname']); ?></td>
                                        <td>
                                            <?php
                                            $rating = $feedback['rating'];
                                            $stars = '';
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    $stars .= '<i class="fa fa-star text-warning"></i>';
                                                } else {
                                                    $stars .= '<i class="fa fa-star-o text-muted"></i>';
                                                }
                                            }
                                            echo $stars . ' (' . $rating . ')';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst($feedback['category']); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $feedback_text = htmlspecialchars($feedback['feedback_text']);
                                            echo strlen($feedback_text) > 100 ? substr($feedback_text, 0, 100) . '...' : $feedback_text;
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($feedback['status']) {
                                                case 'pending':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'reviewed':
                                                    $status_class = 'info';
                                                    break;
                                                case 'resolved':
                                                    $status_class = 'success';
                                                    break;
                                                default:
                                                    $status_class = 'secondary';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($feedback['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($feedback['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewFeedback(<?php echo $feedback['id']; ?>)">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        onclick="updateFeedbackStatus(<?php echo $feedback['id']; ?>, '<?php echo $feedback['status']; ?>')">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteFeedback(<?php echo $feedback['id']; ?>)">
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

<!-- Add Feedback Modal -->
<div class="modal fade" id="addFeedbackModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-plus me-2"></i>Add New Feedback
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_feedback">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Patient *</label>
                                <select class="form-control" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php while ($patient = $patients->fetch_assoc()): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="doctor_id" class="form-label">Doctor</label>
                                <select class="form-control" id="doctor_id" name="doctor_id">
                                    <option value="">Select Doctor</option>
                                    <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                        <option value="<?php echo $doctor['id']; ?>">
                                            <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="feedback_type" class="form-label">Feedback Type *</label>
                                <select class="form-control" id="feedback_type" name="feedback_type" required>
                                    <option value="">Select Type</option>
                                    <option value="patient">Patient Feedback</option>
                                    <option value="staff">Staff Feedback</option>
                                    <option value="general">General Feedback</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category" class="form-label">Category *</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="service">Service Quality</option>
                                    <option value="treatment">Treatment</option>
                                    <option value="facility">Facility</option>
                                    <option value="staff">Staff Behavior</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating *</label>
                                <select class="form-control" id="rating" name="rating" required>
                                    <option value="">Select Rating</option>
                                    <option value="5">5 Stars - Excellent</option>
                                    <option value="4">4 Stars - Very Good</option>
                                    <option value="3">3 Stars - Good</option>
                                    <option value="2">2 Stars - Fair</option>
                                    <option value="1">1 Star - Poor</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="feedback_text" class="form-label">Feedback Text *</label>
                        <textarea class="form-control" id="feedback_text" name="feedback_text" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i>Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Feedback Status Modal -->
<div class="modal fade" id="updateFeedbackStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-edit me-2"></i>Update Feedback Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_feedback_status">
                <input type="hidden" name="feedback_id" id="update_feedback_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="update_status" class="form-label">Status</label>
                        <select class="form-control" id="update_status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="admin_response" class="form-label">Admin Response</label>
                        <textarea class="form-control" id="admin_response" name="admin_response" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Feedback Modal -->
<div class="modal fade" id="viewFeedbackModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-eye me-2"></i>Feedback Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewFeedbackBody">
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
    $('#feedbackTable').DataTable({
        "order": [[6, "desc"]], // Sort by date
        "pageLength": 25,
        "responsive": true
    });
});

// Update feedback status function
function updateFeedbackStatus(feedbackId, currentStatus) {
    $('#update_feedback_id').val(feedbackId);
    $('#update_status').val(currentStatus);
    $('#updateFeedbackStatusModal').modal('show');
}

// View feedback function
function viewFeedback(feedbackId) {
    $.ajax({
        url: 'api/get_feedback.php',
        type: 'GET',
        data: { id: feedbackId },
        success: function(response) {
            if (response.success) {
                $('#viewFeedbackBody').html(response.html);
                $('#viewFeedbackModal').modal('show');
            } else {
                alert('Error loading feedback details: ' + response.message);
            }
        },
        error: function() {
            alert('Error loading feedback details');
        }
    });
}

// Delete feedback function
function deleteFeedback(feedbackId) {
    if (confirm('Are you sure you want to delete this feedback?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_feedback">
            <input type="hidden" name="feedback_id" value="${feedbackId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>