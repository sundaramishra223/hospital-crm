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

// Get recent activities for admin dashboard
function getRecentActivities($limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT al.*, u.name as user_name FROM activity_logs al 
                          JOIN users u ON al.user_id = u.id 
                          ORDER BY al.created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
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

// Attendance system functions
function markAttendance($user_id, $type = 'check_in') {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, check_in, status, created_at) VALUES (?, CURDATE(), NOW(), 'present', NOW()) ON DUPLICATE KEY UPDATE check_out = IF(? = 'check_out', NOW(), check_out)");
    return $stmt->execute([$user_id, $type]);
}

function getAttendanceReport($date_from, $date_to) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT a.*, u.name, u.role FROM attendance a JOIN users u ON a.user_id = u.id WHERE a.date BETWEEN ? AND ? ORDER BY a.date DESC, u.name");
    $stmt->execute([$date_from, $date_to]);
    return $stmt->fetchAll();
}

// Feedback management functions
function getFeedbacks($limit = 50) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT f.*, p.first_name, p.last_name, d.first_name as doctor_fname, d.last_name as doctor_lname FROM feedbacks f 
                          LEFT JOIN patients p ON f.patient_id = p.id 
                          LEFT JOIN doctors d ON f.doctor_id = d.id 
                          ORDER BY f.created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Salary management functions
function getSalarySlips($month = null, $year = null) {
    global $pdo;
    
    $month = $month ?? date('m');
    $year = $year ?? date('Y');
    
    $stmt = $pdo->prepare("SELECT s.*, u.name, u.role, d.name as department_name FROM salary_slips s 
                          JOIN users u ON s.user_id = u.id 
                          LEFT JOIN departments d ON u.department_id = d.id 
                          WHERE s.month = ? AND s.year = ? 
                          ORDER BY u.name");
    $stmt->execute([$month, $year]);
    return $stmt->fetchAll();
}

function generateSalarySlip($user_id, $month, $year, $data) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO salary_slips (user_id, month, year, basic_salary, allowances, deductions, gross_salary, net_salary, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'generated', NOW())");
    return $stmt->execute([
        $user_id,
        $month,
        $year,
        $data['basic_salary'],
        $data['allowances'],
        $data['deductions'],
        $data['gross_salary'],
        $data['net_salary']
    ]);
}

// Home visit and video consultation functions
function getHomeVisitRequests() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT hv.*, p.first_name, p.last_name, p.contact, d.first_name as doctor_fname, d.last_name as doctor_lname FROM home_visits hv 
                        JOIN patients p ON hv.patient_id = p.id 
                        LEFT JOIN doctors d ON hv.doctor_id = d.id 
                        ORDER BY hv.visit_date DESC");
    return $stmt->fetchAll();
}

function getVideoConsultations() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT vc.*, p.first_name, p.last_name, d.first_name as doctor_fname, d.last_name as doctor_lname FROM video_consultations vc 
                        JOIN patients p ON vc.patient_id = p.id 
                        JOIN doctors d ON vc.doctor_id = d.id 
                        ORDER BY vc.consultation_date DESC");
    return $stmt->fetchAll();
}

// Advanced stats for admin dashboard
function getAdvancedStats() {
    global $pdo;
    
    $stats = [];
    
    // Today's stats
    $today = date('Y-m-d');
    
    // New patients today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM patients WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $stats['new_patients_today'] = $stmt->fetch()['count'];
    
    // Emergency cases today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM patients WHERE visit_reason LIKE '%emergency%' AND DATE(created_at) = ?");
    $stmt->execute([$today]);
    $stats['emergency_cases_today'] = $stmt->fetch()['count'];
    
    // Surgeries today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM surgeries WHERE DATE(surgery_date) = ?");
    $stmt->execute([$today]);
    $stats['surgeries_today'] = $stmt->fetch()['count'];
    
    // Lab tests pending
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM lab_tests WHERE status = 'pending'");
    $stats['pending_tests'] = $stmt->fetch()['count'];
    
    // Pharmacy stock alerts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medicines WHERE quantity <= minimum_stock");
    $stats['stock_alerts'] = $stmt->fetch()['count'];
    
    // Monthly comparisons
    $current_month = date('Y-m');
    $last_month = date('Y-m', strtotime('-1 month'));
    
    // Revenue comparison
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as revenue FROM bills WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$current_month]);
    $stats['current_month_revenue'] = $stmt->fetch()['revenue'] ?? 0;
    
    $stmt->execute([$last_month]);
    $stats['last_month_revenue'] = $stmt->fetch()['revenue'] ?? 0;
    
    // Patient growth
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM patients WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$current_month]);
    $stats['current_month_patients'] = $stmt->fetch()['count'];
    
    $stmt->execute([$last_month]);
    $stats['last_month_patients'] = $stmt->fetch()['count'];
    
    return $stats;
}

// System health check
function getSystemHealth() {
    global $pdo;
    
    $health = [];
    
    // Database connection
    try {
        $pdo->query("SELECT 1");
        $health['database'] = 'healthy';
    } catch (Exception $e) {
        $health['database'] = 'error';
    }
    
    // Disk space
    $free_space = disk_free_space('.');
    $total_space = disk_total_space('.');
    $health['disk_usage'] = round((($total_space - $free_space) / $total_space) * 100, 2);
    
    // Log file sizes
    $log_files = glob('logs/*.log');
    $total_log_size = 0;
    foreach ($log_files as $file) {
        $total_log_size += filesize($file);
    }
    $health['log_size'] = round($total_log_size / (1024 * 1024), 2); // MB
    
    return $health;
}
?>