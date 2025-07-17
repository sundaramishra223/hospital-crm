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

function getDoctorById($doctor_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT d.*, u.username, u.email, u.phone, u.status, dep.name as department_name 
                          FROM doctors d 
                          JOIN users u ON d.user_id = u.id 
                          LEFT JOIN departments dep ON d.department_id = dep.id 
                          WHERE d.id = ?");
    $stmt->execute([$doctor_id]);
    return $stmt->fetch();
}

function updateDoctor($doctor_id, $data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get user_id
        $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();
        
        if (!$doctor) {
            return false;
        }
        
        // Update user record
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([
            $data['first_name'] . ' ' . $data['last_name'],
            $data['email'],
            $data['phone'],
            $doctor['user_id']
        ]);
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $image_path = uploadFile($_FILES['profile_image'], 'uploads/doctors/');
        }
        
        // Update doctor details
        $sql = "UPDATE doctors SET first_name = ?, middle_name = ?, last_name = ?, contact = ?, address = ?, education = ?, experience = ?, certificates = ?, awards = ?, vitals = ?, department_id = ?";
        $params = [
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
            $data['department_id']
        ];
        
        if ($image_path) {
            $sql .= ", image = ?";
            $params[] = $image_path;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $doctor_id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
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
function getPatients($type = 'all') {
    global $pdo;
    
    $sql = "SELECT * FROM patients WHERE status != 'deleted'";
    
    switch ($type) {
        case 'inpatient':
            $sql .= " AND patient_type = 'inpatient'";
            break;
        case 'outpatient':
            $sql .= " AND patient_type = 'outpatient'";
            break;
        case 'emergency':
            $sql .= " AND visit_reason LIKE '%emergency%'";
            break;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function addPatient($data) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO patients (first_name, middle_name, last_name, date_of_birth, gender, contact, email, address, emergency_contact, visit_reason, attendant_details, patient_type, insurance_provider_id, insurance_policy_number, insurance_coverage_amount, insurance_status, insurance_expiry_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
    
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
        $data['attendant_details'],
        $data['patient_type'] ?? 'outpatient',
        $data['insurance_provider_id'] ?: null,
        $data['insurance_policy_number'] ?: null,
        $data['insurance_coverage_amount'] ?: null,
        $data['insurance_status'] ?? 'none',
        $data['insurance_expiry_date'] ?: null
    ]);
}

function getPatientById($patient_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, ip.name as insurance_provider_name FROM patients p 
                          LEFT JOIN insurance_providers ip ON p.insurance_provider_id = ip.id 
                          WHERE p.id = ?");
    $stmt->execute([$patient_id]);
    return $stmt->fetch();
}

function updatePatient($patient_id, $data) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE patients SET first_name = ?, middle_name = ?, last_name = ?, date_of_birth = ?, gender = ?, contact = ?, email = ?, address = ?, emergency_contact = ?, visit_reason = ?, attendant_details = ?, patient_type = ?, insurance_provider_id = ?, insurance_policy_number = ?, insurance_coverage_amount = ?, insurance_status = ?, insurance_expiry_date = ? WHERE id = ?");
    
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
        $data['attendant_details'],
        $data['patient_type'] ?? 'outpatient',
        $data['insurance_provider_id'] ?: null,
        $data['insurance_policy_number'] ?: null,
        $data['insurance_coverage_amount'] ?: null,
        $data['insurance_status'] ?? 'none',
        $data['insurance_expiry_date'] ?: null,
        $patient_id
    ]);
}

function deletePatient($patient_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE patients SET status = 'deleted' WHERE id = ?");
    return $stmt->execute([$patient_id]);
}

function convertPatientType($patient_id, $new_type) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE patients SET patient_type = ? WHERE id = ?");
    return $stmt->execute([$new_type, $patient_id]);
}

function getPatientVitals($patient_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT pv.*, u.name as recorded_by FROM patient_vitals pv 
                          LEFT JOIN users u ON pv.recorded_by_user_id = u.id 
                          WHERE pv.patient_id = ? 
                          ORDER BY pv.recorded_at DESC LIMIT 10");
    $stmt->execute([$patient_id]);
    return $stmt->fetchAll();
}

function getPatientHistory($patient_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT ph.*, d.first_name as doctor_fname, d.last_name as doctor_lname, 
                          CONCAT(d.first_name, ' ', d.last_name) as doctor_name 
                          FROM patient_history ph 
                          LEFT JOIN doctors d ON ph.doctor_id = d.id 
                          WHERE ph.patient_id = ? 
                          ORDER BY ph.created_at DESC");
    $stmt->execute([$patient_id]);
    return $stmt->fetchAll();
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
    
    $stmt = $pdo->query("SELECT * FROM currencies WHERE status = 'active' ORDER BY code");
    return $stmt->fetchAll();
}

function formatCurrency($amount, $currency = 'INR') {
    $symbols = [
        'INR' => '₹',
        'USD' => '$',
        'EUR' => '€',
        'BTC' => '₿'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
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

// Get recent activities for admin dashboard
function getRecentActivities($limit = 10) {
    global $pdo;
    $limit = (int)$limit; // Ensure it's an integer
    $stmt = $pdo->query("SELECT al.*, u.name as user_name FROM activity_logs al 
                          JOIN users u ON al.user_id = u.id 
                          ORDER BY al.created_at DESC LIMIT $limit");
    return $stmt->fetchAll();
}

// Get activity icon based on action
function getActivityIcon($action) {
    $icons = [
        'login' => 'sign-in',
        'logout' => 'sign-out',
        'add' => 'plus',
        'edit' => 'edit',
        'delete' => 'trash',
        'view' => 'eye',
        'payment' => 'money',
        'appointment' => 'calendar',
        'prescription' => 'file-text'
    ];
    
    return $icons[$action] ?? 'info';
}

// Chart data functions
function getRevenueChartLabels($days = 30) {
    $labels = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $labels[] = date('M d', strtotime("-$i days"));
    }
    return $labels;
}

function getRevenueChartData($days = 30) {
    global $pdo;
    
    $data = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as revenue FROM bills WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        $result = $stmt->fetch();
        $data[] = $result['revenue'] ?? 0;
    }
    return $data;
}

// Multi-hospital system functions
function getHospitals() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM hospitals WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

function addHospital($data) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO hospitals (name, address, phone, email, license_number, established_date, type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
    return $stmt->execute([
        $data['name'],
        $data['address'],
        $data['phone'],
        $data['email'],
        $data['license_number'],
        $data['established_date'],
        $data['type']
    ]);
}

// Role management functions
function getRoles() {
    return [
        'admin' => 'Administrator',
        'doctor' => 'Doctor',
        'nurse' => 'Nurse',
        'staff' => 'Staff',
        'pharmacy' => 'Pharmacy',
        'lab_tech' => 'Lab Technician',
        'receptionist' => 'Receptionist',
        'patient' => 'Patient',
        'intern' => 'Intern'
    ];
}

function enableRole($role) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO role_permissions (role, module, status, created_at) VALUES (?, 'system', 'active', NOW()) ON DUPLICATE KEY UPDATE status = 'active'");
    return $stmt->execute([$role]);
}

function disableRole($role) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE role_permissions SET status = 'inactive' WHERE role = ?");
    return $stmt->execute([$role]);
}

// Equipment management functions
function getEquipments() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM equipments WHERE status != 'deleted' ORDER BY name");
    return $stmt->fetchAll();
}

function addEquipment($data) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO equipments (name, model, serial_number, purchase_date, cost, department_id, maintenance_schedule, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
    return $stmt->execute([
        $data['name'],
        $data['model'],
        $data['serial_number'],
        $data['purchase_date'],
        $data['cost'],
        $data['department_id'],
        $data['maintenance_schedule']
    ]);
}

// Insurance management functions
function getInsuranceProviders() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM insurance_providers WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

function getInsuranceProviderById($provider_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM insurance_providers WHERE id = ?");
    $stmt->execute([$provider_id]);
    return $stmt->fetch();
}

function addInsuranceProvider($data) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO insurance_providers (name, contact_person, phone, email, address, coverage_details, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
    
    return $stmt->execute([
        $data['name'],
        $data['contact_person'],
        $data['phone'],
        $data['email'],
        $data['address'],
        $data['coverage_details']
    ]);
}

function updateInsuranceProvider($provider_id, $data) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE insurance_providers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?, coverage_details = ? WHERE id = ?");
    
    return $stmt->execute([
        $data['name'],
        $data['contact_person'],
        $data['phone'],
        $data['email'],
        $data['address'],
        $data['coverage_details'],
        $provider_id
    ]);
}

function deleteInsuranceProvider($provider_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE insurance_providers SET status = 'inactive' WHERE id = ?");
    return $stmt->execute([$provider_id]);
}

function getPatientsByInsurance($provider_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, ip.name as insurance_provider_name FROM patients p 
                          JOIN insurance_providers ip ON p.insurance_provider_id = ip.id 
                          WHERE p.insurance_provider_id = ? AND p.status != 'deleted'
                          ORDER BY p.first_name, p.last_name");
    $stmt->execute([$provider_id]);
    return $stmt->fetchAll();
}

function checkInsuranceExpiry() {
    global $pdo;
    
    // Get patients with insurance expiring in next 30 days
    $stmt = $pdo->query("SELECT p.*, ip.name as insurance_provider_name FROM patients p 
                        JOIN insurance_providers ip ON p.insurance_provider_id = ip.id 
                        WHERE p.insurance_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        AND p.insurance_status = 'active'
                        ORDER BY p.insurance_expiry_date");
    return $stmt->fetchAll();
}

function getInsuranceStats() {
    global $pdo;
    
    $stats = [];
    
    // Total insured patients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients WHERE insurance_status = 'active'");
    $stats['total_insured'] = $stmt->fetch()['count'];
    
    // Total uninsured patients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients WHERE insurance_status = 'none'");
    $stats['total_uninsured'] = $stmt->fetch()['count'];
    
    // Expired insurance
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients WHERE insurance_status = 'expired'");
    $stats['expired_insurance'] = $stmt->fetch()['count'];
    
    // Total coverage amount
    $stmt = $pdo->query("SELECT SUM(insurance_coverage_amount) as total FROM patients WHERE insurance_status = 'active'");
    $stats['total_coverage'] = $stmt->fetch()['total'] ?? 0;
    
    return $stats;
}
?>
