<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$hospital_id = (int)($input['hospital_id'] ?? 0);

if (!$hospital_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid hospital ID']);
    exit();
}

// Verify hospital exists
$stmt = $pdo->prepare("SELECT id, name FROM hospitals WHERE id = ? AND status = 'active'");
$stmt->execute([$hospital_id]);
$hospital = $stmt->fetch();

if (!$hospital) {
    echo json_encode(['success' => false, 'message' => 'Hospital not found or inactive']);
    exit();
}

// Update session with new hospital ID
$_SESSION['hospital_id'] = $hospital_id;

// Log the hospital switch
addActivityLog($_SESSION['user_id'], 'hospital_switched', "Switched to hospital: " . $hospital['name']);

echo json_encode([
    'success' => true, 
    'message' => 'Hospital switched successfully',
    'hospital_name' => $hospital['name']
]);
?>