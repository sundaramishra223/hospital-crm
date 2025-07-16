<?php
require_once 'config/database.php';

// Sample salary slips
echo "Adding sample salary slips...\n";
$salary_slips = [
    [1, 11, 2024, 45000, 5000, 2000, 50000, 48000, 'paid'],        // Dr. Sharma
    [2, 11, 2024, 35000, 3000, 1500, 38000, 36500, 'paid'],       // Dr. Patel
    [1, 12, 2024, 45000, 5000, 2000, 50000, 48000, 'generated'],  // Dr. Sharma - Current month
    [2, 12, 2024, 35000, 3000, 1500, 38000, 36500, 'generated'],  // Dr. Patel - Current month
    [6, 11, 2024, 25000, 2000, 1000, 27000, 26000, 'paid'],       // Nurse Priya
    [6, 12, 2024, 25000, 2000, 1000, 27000, 26000, 'generated'],  // Nurse Priya - Current month
    [7, 11, 2024, 22000, 1500, 800, 23500, 22700, 'paid'],        // Pharmacy Raj
    [7, 12, 2024, 22000, 1500, 800, 23500, 22700, 'generated'],   // Pharmacy Raj - Current month
    [8, 11, 2024, 20000, 1000, 500, 21000, 20500, 'paid'],        // Reception Neha
    [8, 12, 2024, 20000, 1000, 500, 21000, 20500, 'generated'],   // Reception Neha - Current month
];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS salary_slips (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        month INT(2) NOT NULL,
        year INT(4) NOT NULL,
        basic_salary DECIMAL(10,2) NOT NULL,
        allowances DECIMAL(10,2) DEFAULT 0,
        deductions DECIMAL(10,2) DEFAULT 0,
        gross_salary DECIMAL(10,2) NOT NULL,
        net_salary DECIMAL(10,2) NOT NULL,
        status ENUM('generated', 'paid', 'cancelled') DEFAULT 'generated',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_salary (user_id, month, year),
        KEY user_id (user_id),
        KEY status (status)
    )");

    $stmt = $pdo->prepare("INSERT INTO salary_slips (user_id, month, year, basic_salary, allowances, deductions, gross_salary, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)");
    
    foreach ($salary_slips as $slip) {
        $stmt->execute($slip);
    }
    echo "✓ Sample salary slips added\n";
} catch (Exception $e) {
    echo "Error adding salary slips: " . $e->getMessage() . "\n";
}

// Sample attendance records
echo "Adding sample attendance records...\n";
$attendance_records = [
    // Today's attendance
    [1, date('Y-m-d 09:00:00'), date('Y-m-d 09:00:00'), date('Y-m-d 18:00:00'), 'present', 'Regular day'],
    [2, date('Y-m-d 09:15:00'), date('Y-m-d 09:15:00'), date('Y-m-d 17:45:00'), 'late', '15 minutes late'],
    [6, date('Y-m-d 08:45:00'), date('Y-m-d 08:45:00'), date('Y-m-d 17:30:00'), 'present', 'Early arrival'],
    [7, date('Y-m-d 10:00:00'), date('Y-m-d 10:00:00'), null, 'present', 'Not checked out yet'],
    [8, date('Y-m-d 09:30:00'), date('Y-m-d 09:30:00'), date('Y-m-d 18:15:00'), 'late', '30 minutes late'],
    
    // Yesterday's attendance
    [1, date('Y-m-d 09:00:00', strtotime('-1 day')), date('Y-m-d 09:00:00', strtotime('-1 day')), date('Y-m-d 18:00:00', strtotime('-1 day')), 'present', ''],
    [2, date('Y-m-d 09:00:00', strtotime('-1 day')), date('Y-m-d 09:00:00', strtotime('-1 day')), date('Y-m-d 17:30:00', strtotime('-1 day')), 'present', ''],
    [6, date('Y-m-d 08:45:00', strtotime('-1 day')), date('Y-m-d 08:45:00', strtotime('-1 day')), date('Y-m-d 17:00:00', strtotime('-1 day')), 'present', ''],
    [7, date('Y-m-d 00:00:00', strtotime('-1 day')), null, null, 'absent', 'Sick leave'],
    [8, date('Y-m-d 09:00:00', strtotime('-1 day')), date('Y-m-d 09:00:00', strtotime('-1 day')), date('Y-m-d 13:00:00', strtotime('-1 day')), 'half_day', 'Half day leave'],
];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        attendance_date DATETIME NOT NULL,
        check_in_time DATETIME,
        check_out_time DATETIME,
        status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_attendance (user_id, DATE(attendance_date)),
        KEY user_id (user_id),
        KEY attendance_date (attendance_date),
        KEY status (status)
    )");

    $stmt = $pdo->prepare("INSERT INTO attendance (user_id, attendance_date, check_in_time, check_out_time, status, notes) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)");
    
    foreach ($attendance_records as $record) {
        $stmt->execute($record);
    }
    echo "✓ Sample attendance records added\n";
} catch (Exception $e) {
    echo "Error adding attendance records: " . $e->getMessage() . "\n";
}

// Sample ambulances and bookings
echo "Adding sample ambulances...\n";
$ambulances = [
    ['MH01-AB-1234', 'Rajesh Kumar', '+91-9876543210', 'DL123456789', 'emergency', 'available', 'Defibrillator, Oxygen tank, First aid kit, Ventilator', '2025-06-15', '2024-11-01'],
    ['MH01-AB-5678', 'Suresh Patil', '+91-9876543211', 'DL987654321', 'advanced', 'available', 'Oxygen tank, First aid kit, Stretcher, IV equipment', '2025-08-20', '2024-10-15'],
    ['MH01-AB-9012', 'Amit Sharma', '+91-9876543212', 'DL456789123', 'basic', 'busy', 'First aid kit, Oxygen tank, Basic monitoring', '2025-04-10', '2024-09-20'],
    ['MH01-AB-3456', 'Ravi Singh', '+91-9876543213', 'DL789123456', 'emergency', 'maintenance', 'Defibrillator, Oxygen tank, Advanced life support', '2025-12-01', '2024-11-20'],
];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ambulances (
        id INT(11) NOT NULL AUTO_INCREMENT,
        vehicle_number VARCHAR(20) NOT NULL UNIQUE,
        driver_name VARCHAR(100) NOT NULL,
        driver_phone VARCHAR(20) NOT NULL,
        driver_license VARCHAR(50),
        vehicle_type ENUM('basic', 'advanced', 'emergency') DEFAULT 'basic',
        status ENUM('available', 'busy', 'maintenance', 'inactive') DEFAULT 'available',
        equipment TEXT,
        insurance_expiry DATE,
        last_service_date DATE,
        next_service_date DATE,
        hospital_id INT(11) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY vehicle_number (vehicle_number),
        KEY status (status)
    )");

    $stmt = $pdo->prepare("INSERT INTO ambulances (vehicle_number, driver_name, driver_phone, driver_license, vehicle_type, status, equipment, insurance_expiry, last_service_date, hospital_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE status = VALUES(status)");
    
    foreach ($ambulances as $ambulance) {
        $stmt->execute($ambulance);
    }
    echo "✓ Sample ambulances added\n";
} catch (Exception $e) {
    echo "Error adding ambulances: " . $e->getMessage() . "\n";
}

// Sample ambulance bookings
echo "Adding sample ambulance bookings...\n";
$ambulance_bookings = [
    [3, 'Ramesh Gupta', '+91-9123456789', '123 Main Street, Andheri, Mumbai', 'KEM Hospital, Parel, Mumbai', date('Y-m-d H:i:s', strtotime('+2 hours')), 'high', 'in_transit', 15.5, 587.50, 0, 'Patient with chest pain', 1],
    [1, 'Priya Mehta', '+91-9234567890', '456 Park Road, Bandra, Mumbai', 'Lilavati Hospital, Bandra, Mumbai', date('Y-m-d H:i:s', strtotime('-2 hours')), 'medium', 'completed', 8.2, 405.00, 420.00, 'Routine transfer', 1],
    [2, 'Anil Joshi', '+91-9345678901', '789 Hill View, Juhu, Mumbai', 'Hinduja Hospital, Mahim, Mumbai', date('Y-m-d H:i:s', strtotime('+4 hours')), 'critical', 'assigned', 12.0, 500.00, 0, 'Emergency cardiac case', 1],
];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ambulance_bookings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        ambulance_id INT(11) NOT NULL,
        patient_name VARCHAR(100) NOT NULL,
        patient_phone VARCHAR(20) NOT NULL,
        pickup_address TEXT NOT NULL,
        destination_address TEXT NOT NULL,
        booking_date DATETIME NOT NULL,
        emergency_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        status ENUM('pending', 'assigned', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
        distance_km DECIMAL(8,2),
        estimated_cost DECIMAL(10,2),
        actual_cost DECIMAL(10,2),
        notes TEXT,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ambulance_id (ambulance_id),
        KEY status (status),
        KEY booking_date (booking_date)
    )");

    $stmt = $pdo->prepare("INSERT INTO ambulance_bookings (ambulance_id, patient_name, patient_phone, pickup_address, destination_address, booking_date, emergency_level, status, distance_km, estimated_cost, actual_cost, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($ambulance_bookings as $booking) {
        $stmt->execute($booking);
    }
    echo "✓ Sample ambulance bookings added\n";
} catch (Exception $e) {
    echo "Error adding ambulance bookings: " . $e->getMessage() . "\n";
}

echo "\n🎉 Sample data for Salary Management, Attendance Management, and Ambulance Management added successfully!\n";
echo "\n📋 New Modules Summary:\n";
echo "1. 💰 Salary Management - Generate and manage employee salary slips\n";
echo "2. 🕒 Attendance Management - Track employee check-in/check-out with real-time clock\n";
echo "3. 🚑 Ambulance Management - Manage ambulance fleet, emergency bookings, and dispatch\n";
echo "\n✅ All modules are now ready for use!\n";
?>