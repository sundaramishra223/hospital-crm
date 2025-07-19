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
if (!hasPermission($_SESSION['user_role'], 'feedback')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $feedback_id = sanitize($_GET['id']);
    
    // Get feedback details
    $sql = "SELECT f.*, p.first_name as patient_fname, p.last_name as patient_lname, 
            d.first_name as doctor_fname, d.last_name as doctor_lname 
            FROM feedbacks f 
            LEFT JOIN patients p ON f.patient_id = p.id 
            LEFT JOIN doctors d ON f.doctor_id = d.id 
            WHERE f.id = ? AND f.hospital_id = ? AND f.deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $feedback_id, $hospital_id);
    $stmt->execute();
    $feedback = $stmt->get_result()->fetch_assoc();
    
    if (!$feedback) {
        echo json_encode(['success' => false, 'message' => 'Feedback not found']);
        exit();
    }
    
    // Generate star rating HTML
    $rating = $feedback['rating'];
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fa fa-star text-warning"></i>';
        } else {
            $stars .= '<i class="fa fa-star-o text-muted"></i>';
        }
    }
    
    // Generate status badge
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
    
    // Return view HTML
    $html = '
    <div class="row">
        <div class="col-md-6">
            <h6 class="text-primary">Feedback Information</h6>
            <table class="table table-borderless">
                <tr>
                    <td><strong>Patient:</strong></td>
                    <td>' . htmlspecialchars($feedback['patient_fname'] . ' ' . $feedback['patient_lname']) . '</td>
                </tr>
                <tr>
                    <td><strong>Doctor:</strong></td>
                    <td>' . htmlspecialchars($feedback['doctor_fname'] . ' ' . $feedback['doctor_lname']) . '</td>
                </tr>
                <tr>
                    <td><strong>Type:</strong></td>
                    <td>' . htmlspecialchars(ucfirst($feedback['feedback_type'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Category:</strong></td>
                    <td><span class="badge bg-info">' . ucfirst($feedback['category']) . '</span></td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td><span class="badge bg-' . $status_class . '">' . ucfirst($feedback['status']) . '</span></td>
                </tr>
                <tr>
                    <td><strong>Rating:</strong></td>
                    <td>' . $stars . ' (' . $rating . '/5)</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="text-primary">Feedback Details</h6>
            <table class="table table-borderless">
                <tr>
                    <td><strong>Submitted:</strong></td>
                    <td>' . date('M d, Y H:i', strtotime($feedback['created_at'])) . '</td>
                </tr>';
    
    if ($feedback['updated_at']) {
        $html .= '
                <tr>
                    <td><strong>Last Updated:</strong></td>
                    <td>' . date('M d, Y H:i', strtotime($feedback['updated_at'])) . '</td>
                </tr>';
    }
    
    $html .= '
            </table>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="text-primary">Feedback Text</h6>
            <div class="card">
                <div class="card-body">
                    <p class="mb-0">' . nl2br(htmlspecialchars($feedback['feedback_text'])) . '</p>
                </div>
            </div>
        </div>
    </div>';
    
    if ($feedback['admin_response']) {
        $html .= '
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="text-primary">Admin Response</h6>
                <div class="card bg-light">
                    <div class="card-body">
                        <p class="mb-0">' . nl2br(htmlspecialchars($feedback['admin_response'])) . '</p>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>