<?php
// Security functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function encryptPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Authentication functions
function authenticateUser($username, $password, $role) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ? AND status = 'active'");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch();
    
    if ($user && verifyPassword($password, $user['password'])) {
        return $user;
    }
    return false;
}

function getUserDetails($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Settings functions
function getSetting($key, $default = '') {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    
    return $result ? $result['value'] : $default;
}

function updateSetting($key, $value) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Activity logging
function logActivity($user_id, $action, $description) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())");
    return $stmt->execute([$user_id, $action, $description]);
}

// Dashboard stats for admin
function getAdminStats() {
    global $pdo;
    
    $stats = [];
    
    // Total users by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role");
    while ($row = $stmt->fetch()) {
        $stats['users'][$row['role']] = $row['count'];
    }
    
    // Total patients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients WHERE status = 'active'");
    $stats['total_patients'] = $stmt->fetch()['count'];
    
    // Today's appointments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()");
    $stats['today_appointments'] = $stmt->fetch()['count'];
    
    // Monthly revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as revenue FROM bills WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stats['monthly_revenue'] = $stmt->fetch()['revenue'] ?? 0;
    
    // Occupied beds
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM beds WHERE status = 'occupied'");
    $stats['occupied_beds'] = $stmt->fetch()['count'];
    
    return $stats;
}

// Doctor management functions
function addDoctor($data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Insert user record
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, email, phone, status, created_at) VALUES (?, ?, 'doctor', ?, ?, ?, 'active', NOW())");
        $stmt->execute([$data['username'], encryptPassword($data['password']), $data['name'], $data['email'], $data['phone']]);
        $user_id = $pdo->lastInsertId();
        
        // Insert doctor details
        $stmt = $pdo->prepare("INSERT INTO doctors (user_id, first_name, middle_name, last_name, contact, address, education, experience, certificates, awards, vitals, image, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['contact'],
            $data['address'],
            $data['education'],
            $data['experience'],
            $data['certificates'],
            $data['awards'],
            $data['vitals'],
            $data['image'],
            $data['department_id']
        ]);
        
        $pdo->commit();
        return $user_id;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

function getDoctors() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT d.*, u.username, u.email, u.phone, u.status, dep.name as department_name 
                        FROM doctors d 
                        JOIN users u ON d.user_id = u.id 
                        LEFT JOIN departments dep ON d.department_id = dep.id 
                        ORDER BY d.first_name");
    return $stmt->fetchAll();
}

function deleteDoctor($doctor_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get user_id
        $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();
        
        if ($doctor) {
            // Soft delete - mark as deleted instead of actually deleting
            $stmt = $pdo->prepare("UPDATE users SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$doctor['user_id']]);
            
            $stmt = $pdo->prepare("UPDATE doctors SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$doctor_id]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

// Department functions
function getDepartments() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM departments WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

function addDepartment($name, $description) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO departments (name, description, status, created_at) VALUES (?, ?, 'active', NOW())");
    return $stmt->execute([$name, $description]);
}

// Patient functions
function getPatients() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM patients WHERE status = 'active' ORDER BY first_name");
    return $stmt->fetchAll();
}

function addPatient($data) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO patients (first_name, middle_name, last_name, date_of_birth, gender, contact, email, address, emergency_contact, visit_reason, attendant_details, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    return $stmt->execute([
        $data['first_name'],
        $data['middle_name'],
        $data['last_name'],
        $data['date_of_birth'],
        $data['gender'],
        $data['contact'],
        $data['email'],
        $data['address'],
        $data['emergency_contact'],
        $data['visit_reason'],
        $data['attendant_details']
    ]);
}

// Bed management
function getBeds() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT b.*, p.first_name, p.last_name FROM beds b LEFT JOIN patients p ON b.patient_id = p.id ORDER BY b.bed_number");
    return $stmt->fetchAll();
}

// Currency and billing functions
function getCurrencies() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM currencies WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

function formatCurrency($amount, $currency_code = 'INR') {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT symbol FROM currencies WHERE code = ?");
    $stmt->execute([$currency_code]);
    $currency = $stmt->fetch();
    
    $symbol = $currency ? $currency['symbol'] : '₹';
    return $symbol . number_format($amount, 2);
}

// Check if user has permission
function hasPermission($role, $module) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM role_permissions WHERE role = ? AND module = ? AND status = 'active'");
    $stmt->execute([$role, $module]);
    return $stmt->fetch() ? true : false;
}

// Generate unique ID
function generateUniqueId($prefix = '') {
    return $prefix . date('Ymd') . substr(time(), -4) . rand(10, 99);
}

// File upload function
function uploadFile($file, $folder = 'uploads/') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    $filename = generateUniqueId() . '_' . basename($file['name']);
    $upload_path = $folder . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $upload_path;
    }
    
    return false;
}
?>