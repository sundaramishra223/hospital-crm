<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user has permission
if (!hasPermission($_SESSION['user_role'], 'interns', 'read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = sanitize($_GET['id']);
    
    $sql = "SELECT i.*, d.name as department_name, CONCAT(dr.first_name, ' ', dr.last_name) as supervisor_name 
            FROM interns i 
            LEFT JOIN departments d ON i.department_id = d.id 
            LEFT JOIN doctors dr ON i.supervisor_id = dr.id 
            WHERE i.id = ? AND i.hospital_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $intern = $result->fetch_assoc();
        echo json_encode(['success' => true, 'intern' => $intern]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Intern not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>