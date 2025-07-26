<?php
require_once 'config/database.php';

// Create tables for Hospital CRM SQLite database
try {
    // Users table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role TEXT CHECK(role IN ('admin','doctor','nurse','staff','pharmacy','lab_tech','receptionist','patient','intern')) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        status TEXT CHECK(status IN ('active','inactive','deleted')) DEFAULT 'active',
        hospital_id INTEGER DEFAULT 1,
        department_id INTEGER DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Hospitals table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS hospitals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(200) NOT NULL,
        address TEXT DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        email VARCHAR(100) DEFAULT NULL,
        license_number VARCHAR(100) DEFAULT NULL,
        established_date DATE DEFAULT NULL,
        type TEXT CHECK(type IN ('hospital','clinic','specialty_center')) DEFAULT 'hospital',
        status TEXT CHECK(status IN ('active','inactive')) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Departments table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS departments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        head_doctor_id INTEGER DEFAULT NULL,
        hospital_id INTEGER DEFAULT 1,
        status TEXT CHECK(status IN ('active','inactive')) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Patients table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS patients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_id VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        date_of_birth DATE DEFAULT NULL,
        gender TEXT CHECK(gender IN ('male','female','other')) DEFAULT NULL,
        blood_group VARCHAR(5) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        emergency_contact_name VARCHAR(100) DEFAULT NULL,
        emergency_contact_phone VARCHAR(20) DEFAULT NULL,
        medical_history TEXT DEFAULT NULL,
        allergies TEXT DEFAULT NULL,
        current_medications TEXT DEFAULT NULL,
        insurance_provider VARCHAR(100) DEFAULT NULL,
        insurance_number VARCHAR(100) DEFAULT NULL,
        status TEXT CHECK(status IN ('active','inactive','discharged')) DEFAULT 'active',
        patient_type TEXT CHECK(patient_type IN ('inpatient','outpatient')) DEFAULT 'outpatient',
        admission_date DATETIME DEFAULT NULL,
        discharge_date DATETIME DEFAULT NULL,
        assigned_doctor_id INTEGER DEFAULT NULL,
        assigned_nurse_id INTEGER DEFAULT NULL,
        bed_id INTEGER DEFAULT NULL,
        hospital_id INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Doctors table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS doctors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        specialization VARCHAR(100) DEFAULT NULL,
        qualification VARCHAR(200) DEFAULT NULL,
        experience_years INTEGER DEFAULT NULL,
        license_number VARCHAR(100) DEFAULT NULL,
        consultation_fee DECIMAL(10,2) DEFAULT NULL,
        available_days VARCHAR(100) DEFAULT NULL,
        available_time_start TIME DEFAULT NULL,
        available_time_end TIME DEFAULT NULL,
        department_id INTEGER DEFAULT NULL,
        status TEXT CHECK(status IN ('active','inactive','on_leave')) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Medicines table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS medicines (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(200) NOT NULL,
        brand_name VARCHAR(200) DEFAULT NULL,
        generic_name VARCHAR(200) DEFAULT NULL,
        strength VARCHAR(50) DEFAULT NULL,
        form TEXT CHECK(form IN ('tablet','capsule','syrup','injection','cream','drops','inhaler','powder')) DEFAULT 'tablet',
        manufacturer VARCHAR(200) DEFAULT NULL,
        batch_number VARCHAR(100) DEFAULT NULL,
        expiry_date DATE DEFAULT NULL,
        quantity_in_stock INTEGER DEFAULT 0,
        minimum_stock_level INTEGER DEFAULT 10,
        price_per_unit DECIMAL(10,2) DEFAULT NULL,
        purchase_price DECIMAL(10,2) DEFAULT NULL,
        supplier VARCHAR(200) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        side_effects TEXT DEFAULT NULL,
        dosage_instructions TEXT DEFAULT NULL,
        storage_instructions TEXT DEFAULT NULL,
        prescription_required BOOLEAN DEFAULT true,
        status TEXT CHECK(status IN ('active','inactive','expired','out_of_stock')) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Beds table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS beds (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bed_number VARCHAR(20) NOT NULL,
        bed_type TEXT CHECK(bed_type IN ('general','private','icu','emergency','maternity','pediatric')) DEFAULT 'general',
        room_number VARCHAR(20) DEFAULT NULL,
        floor_number INTEGER DEFAULT NULL,
        department_id INTEGER DEFAULT NULL,
        daily_rate DECIMAL(10,2) DEFAULT NULL,
        is_occupied BOOLEAN DEFAULT false,
        current_patient_id INTEGER DEFAULT NULL,
        last_cleaned DATETIME DEFAULT NULL,
        equipment_available TEXT DEFAULT NULL,
        status TEXT CHECK(status IN ('active','maintenance','out_of_order')) DEFAULT 'active',
        hospital_id INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Appointments table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS appointments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        appointment_number VARCHAR(20) NOT NULL UNIQUE,
        patient_id INTEGER NOT NULL,
        doctor_id INTEGER NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        appointment_type TEXT CHECK(appointment_type IN ('consultation','follow_up','emergency','routine_checkup')) DEFAULT 'consultation',
        status TEXT CHECK(status IN ('scheduled','confirmed','in_progress','completed','cancelled','no_show')) DEFAULT 'scheduled',
        reason_for_visit TEXT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        consultation_fee DECIMAL(10,2) DEFAULT NULL,
        created_by INTEGER DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");

    // Bills table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS bills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bill_number VARCHAR(50) NOT NULL UNIQUE,
        patient_id INTEGER NOT NULL,
        appointment_id INTEGER DEFAULT NULL,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        tax_amount DECIMAL(10,2) DEFAULT 0,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        net_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        payment_status TEXT CHECK(payment_status IN ('pending','partial','paid','refunded')) DEFAULT 'pending',
        payment_method TEXT CHECK(payment_method IN ('cash','card','upi','net_banking','cheque','crypto')) DEFAULT NULL,
        payment_date DATETIME DEFAULT NULL,
        created_by INTEGER DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES patients(id),
        FOREIGN KEY (appointment_id) REFERENCES appointments(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");

    // Settings table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT DEFAULT NULL,
        category VARCHAR(50) DEFAULT 'general',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Lab tests table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS lab_tests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        test_name VARCHAR(200) NOT NULL,
        test_code VARCHAR(50) DEFAULT NULL,
        category TEXT CHECK(category IN ('blood','urine','microbiology','biochemistry','radiology','pathology')) DEFAULT 'blood',
        normal_range VARCHAR(100) DEFAULT NULL,
        price DECIMAL(10,2) DEFAULT NULL,
        sample_type VARCHAR(100) DEFAULT NULL,
        preparation_instructions TEXT DEFAULT NULL,
        reporting_time_hours INTEGER DEFAULT 24,
        department_id INTEGER DEFAULT NULL,
        status TEXT CHECK(status IN ('active','inactive')) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Activity logs table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Insert default hospital
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO hospitals (id, name, address, phone, email, type) VALUES (1, 'City General Hospital', '123 Main Street, City', '+1-234-567-8900', 'info@cityhospital.com', 'hospital')");
    $stmt->execute();

    // Insert default admin user
    $admin_password = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password, role, name, email, phone, hospital_id) VALUES ('admin', ?, 'admin', 'Administrator', 'admin@hospital.com', '1234567890', 1)");
    $stmt->execute([$admin_password]);

    // Insert sample doctor
    $doctor_password = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password, role, name, email, phone, hospital_id) VALUES ('dr.sharma', ?, 'doctor', 'Dr. Rajesh Sharma', 'dr.sharma@hospital.com', '9876543210', 1)");
    $stmt->execute([$doctor_password]);

    // Insert sample users for other roles
    $roles_data = [
        ['nurse.priya', 'nurse', 'Priya Nurse', 'nurse.priya@hospital.com', '9876543211'],
        ['pharmacy.raj', 'pharmacy', 'Raj Pharmacy', 'pharmacy.raj@hospital.com', '9876543212'],
        ['reception.neha', 'receptionist', 'Neha Receptionist', 'reception.neha@hospital.com', '9876543213'],
        ['lab.suresh', 'lab_tech', 'Suresh Lab Tech', 'lab.suresh@hospital.com', '9876543214'],
        ['staff.anjali', 'staff', 'Anjali Staff', 'staff.anjali@hospital.com', '9876543215'],
        ['intern.rahul', 'intern', 'Rahul Intern', 'intern.rahul@hospital.com', '9876543216']
    ];

    foreach ($roles_data as $role_data) {
        $role_password = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, password, role, name, email, phone, hospital_id) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$role_data[0], $role_password, $role_data[1], $role_data[2], $role_data[3], $role_data[4]]);
    }

    // Insert default settings
    $default_settings = [
        ['site_title', 'Hospital CRM', 'general'],
        ['hospital_name', 'City General Hospital', 'general'],
        ['theme_mode', 'light', 'appearance'],
        ['primary_color', '#007bff', 'appearance'],
        ['currency_symbol', '₹', 'billing'],
        ['patient_id_prefix', 'PID', 'general'],
        ['bill_number_prefix', 'INV', 'billing'],
        ['favicon', 'assets/images/favicon.ico', 'appearance']
    ];

    foreach ($default_settings as $setting) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value, category) VALUES (?, ?, ?)");
        $stmt->execute($setting);
    }

    // Insert sample medicines
    $sample_medicines = [
        ['Paracetamol', 'Crocin', 'Paracetamol', '500mg', 'tablet', 'GSK', 'BATCH001', '2025-12-31', 100, 10, 5.00, 3.50],
        ['Amoxicillin', 'Amoxil', 'Amoxicillin', '250mg', 'capsule', 'Pfizer', 'BATCH002', '2025-06-30', 50, 5, 15.00, 12.00],
        ['Ibuprofen', 'Brufen', 'Ibuprofen', '400mg', 'tablet', 'Abbott', 'BATCH003', '2025-09-15', 75, 10, 8.00, 6.00],
        ['Cough Syrup', 'Benadryl', 'Diphenhydramine', '100ml', 'syrup', 'J&J', 'BATCH004', '2025-03-20', 30, 5, 45.00, 35.00]
    ];

    foreach ($sample_medicines as $medicine) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO medicines (name, brand_name, generic_name, strength, form, manufacturer, batch_number, expiry_date, quantity_in_stock, minimum_stock_level, price_per_unit, purchase_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($medicine);
    }

    // Insert sample beds
    $bed_types = ['general', 'private', 'icu', 'emergency'];
    for ($i = 1; $i <= 20; $i++) {
        $bed_type = $bed_types[array_rand($bed_types)];
        $daily_rate = $bed_type === 'icu' ? 2000 : ($bed_type === 'private' ? 1500 : ($bed_type === 'emergency' ? 1000 : 500));
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO beds (bed_number, bed_type, room_number, floor_number, daily_rate, hospital_id) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute(["B" . str_pad($i, 3, '0', STR_PAD_LEFT), $bed_type, "R" . str_pad($i, 3, '0', STR_PAD_LEFT), ($i <= 10 ? 1 : 2), $daily_rate]);
    }

    // Insert sample lab tests
    $lab_tests = [
        ['Complete Blood Count', 'CBC', 'blood', '4.5-11.0 x10³/µL', 300.00, 'Blood', 'Fasting not required', 4],
        ['Blood Sugar Fasting', 'BSF', 'blood', '70-100 mg/dL', 150.00, 'Blood', '8-12 hours fasting', 2],
        ['Urine Routine', 'UR', 'urine', 'Normal', 200.00, 'Urine', 'Mid-stream sample', 2],
        ['Liver Function Test', 'LFT', 'biochemistry', 'Normal range varies', 450.00, 'Blood', 'Fasting recommended', 6],
        ['Kidney Function Test', 'KFT', 'biochemistry', 'Normal range varies', 400.00, 'Blood', 'No special preparation', 4]
    ];

    foreach ($lab_tests as $test) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO lab_tests (test_name, test_code, category, normal_range, price, sample_type, preparation_instructions, reporting_time_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($test);
    }

    echo "Database setup completed successfully!\n";
    echo "You can now login with:\n";
    echo "Admin: username=admin, password=password, role=admin\n";
    echo "Doctor: username=dr.sharma, password=password, role=doctor\n";
    echo "Other roles: nurse.priya, pharmacy.raj, reception.neha, lab.suresh, staff.anjali, intern.rahul (all with password=password)\n";

} catch (PDOException $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
}
?>