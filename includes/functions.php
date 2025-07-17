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

// Billing functions
function getBills() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT b.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                        FROM bills b 
                        JOIN patients p ON b.patient_id = p.id 
                        ORDER BY b.created_at DESC");
    return $stmt->fetchAll();
}

function getBillById($bill_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT b.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, 
                          p.contact as patient_contact, p.email as patient_email 
                          FROM bills b 
                          JOIN patients p ON b.patient_id = p.id 
                          WHERE b.id = ?");
    $stmt->execute([$bill_id]);
    return $stmt->fetch();
}

function addBill($data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $items = json_decode($data['items'], true);
        $subtotal = array_sum(array_column($items, 'total'));
        $tax_amount = ($subtotal * ($data['tax_percentage'] ?? 0)) / 100;
        $total_amount = $subtotal + $tax_amount - ($data['discount'] ?? 0);
        
        // Insert bill
        $stmt = $pdo->prepare("INSERT INTO bills (patient_id, subtotal, tax_percentage, tax_amount, discount, total_amount, currency, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([
            $data['patient_id'],
            $subtotal,
            $data['tax_percentage'] ?? 0,
            $tax_amount,
            $data['discount'] ?? 0,
            $total_amount,
            $data['currency'] ?? 'INR'
        ]);
        
        $bill_id = $pdo->lastInsertId();
        
        // Insert bill items
        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO bill_items (bill_id, item_name, item_type, price, quantity, total) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $bill_id,
                $item['name'],
                $item['type'],
                $item['price'],
                $item['quantity'],
                $item['total']
            ]);
        }
        
        $pdo->commit();
        return $bill_id;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

function getBillItems($bill_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
    $stmt->execute([$bill_id]);
    return $stmt->fetchAll();
}

function getBillPayments($bill_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM bill_payments WHERE bill_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$bill_id]);
    return $stmt->fetchAll();
}

function recordPayment($bill_id, $data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Insert payment
        $stmt = $pdo->prepare("INSERT INTO bill_payments (bill_id, amount, payment_method, transaction_ref, notes, payment_date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $bill_id,
            $data['amount'],
            $data['payment_method'],
            $data['transaction_ref'],
            $data['notes']
        ]);
        
        // Update bill paid amount and status
        $stmt = $pdo->prepare("UPDATE bills SET paid_amount = paid_amount + ? WHERE id = ?");
        $stmt->execute([$data['amount'], $bill_id]);
        
        // Check if bill is fully paid
        $stmt = $pdo->prepare("SELECT total_amount, paid_amount FROM bills WHERE id = ?");
        $stmt->execute([$bill_id]);
        $bill = $stmt->fetch();
        
        if ($bill['paid_amount'] >= $bill['total_amount']) {
            $stmt = $pdo->prepare("UPDATE bills SET status = 'paid' WHERE id = ?");
            $stmt->execute([$bill_id]);
        } elseif ($bill['paid_amount'] > 0) {
            $stmt = $pdo->prepare("UPDATE bills SET status = 'partial' WHERE id = ?");
            $stmt->execute([$bill_id]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

function getTotalRevenue() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT SUM(paid_amount) as revenue FROM bills");
    $result = $stmt->fetch();
    return $result['revenue'] ?? 0;
}

function getPendingBillsCount() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bills WHERE status = 'pending'");
    return $stmt->fetch()['count'];
}

function getPaidBillsCount() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bills WHERE status = 'paid'");
    return $stmt->fetch()['count'];
}

function getOverdueBillsCount() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bills WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    return $stmt->fetch()['count'];
}

function getServices() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM services WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

function getMedicines() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM medicines WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

function getLabTests() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM lab_tests WHERE status = 'active' ORDER BY name");
    return $stmt->fetchAll();
}

function getPaymentMethods() {
    return [
        ['code' => 'cash', 'name' => 'Cash', 'icon' => 'money'],
        ['code' => 'card', 'name' => 'Credit/Debit Card', 'icon' => 'credit-card'],
        ['code' => 'upi', 'name' => 'UPI', 'icon' => 'mobile'],
        ['code' => 'netbanking', 'name' => 'Net Banking', 'icon' => 'bank'],
        ['code' => 'cheque', 'name' => 'Cheque', 'icon' => 'file-text'],
        ['code' => 'crypto', 'name' => 'Cryptocurrency', 'icon' => 'bitcoin']
    ];
}

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

// Insurance functions
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
