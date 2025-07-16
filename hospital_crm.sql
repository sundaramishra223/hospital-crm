-- Hospital CRM Database Structure
-- Complete SQL file with all tables and sample data

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create Database
CREATE DATABASE IF NOT EXISTS `hospital_crm` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hospital_crm`;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','doctor','nurse','staff','pharmacy','lab_tech','receptionist','patient','intern') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `hospital_id` int(11) DEFAULT 1,
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role` (`role`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `hospitals`
-- --------------------------------------------------------

CREATE TABLE `hospitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `established_date` date DEFAULT NULL,
  `type` enum('hospital','clinic','specialty_center') DEFAULT 'hospital',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `departments`
-- --------------------------------------------------------

CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `head_doctor_id` int(11) DEFAULT NULL,
  `hospital_id` int(11) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `doctors`
-- --------------------------------------------------------

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `contact` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `certificates` text DEFAULT NULL,
  `awards` text DEFAULT NULL,
  `vitals` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `patients`
-- --------------------------------------------------------

CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `contact` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `visit_reason` text NOT NULL,
  `attendant_details` text DEFAULT NULL,
  `patient_type` enum('inpatient','outpatient') DEFAULT 'outpatient',
  `insurance_provider_id` int(11) DEFAULT NULL,
  `insurance_policy_number` varchar(100) DEFAULT NULL,
  `insurance_coverage_amount` decimal(12,2) DEFAULT NULL,
  `insurance_status` enum('active','expired','suspended','none') DEFAULT 'none',
  `insurance_expiry_date` date DEFAULT NULL,
  `status` enum('active','discharged','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_type` (`patient_type`),
  KEY `status` (`status`),
  KEY `insurance_provider_id` (`insurance_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `patient_vitals`
-- --------------------------------------------------------

CREATE TABLE `patient_vitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by_user_id` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `recorded_by_user_id` (`recorded_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `patient_history`
-- --------------------------------------------------------

CREATE TABLE `patient_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `type` enum('consultation','prescription','surgery','test','admission','discharge') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `appointments`
-- --------------------------------------------------------

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `duration` int(11) DEFAULT 30,
  `type` enum('consultation','followup','emergency','surgery') DEFAULT 'consultation',
  `status` enum('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `appointment_date` (`appointment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `beds`
-- --------------------------------------------------------

CREATE TABLE `beds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bed_number` varchar(20) NOT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `bed_type` enum('general','private','icu','emergency') DEFAULT 'general',
  `patient_id` int(11) DEFAULT NULL,
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `price_per_day` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bed_number` (`bed_number`),
  KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bills`
-- --------------------------------------------------------

CREATE TABLE `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_percentage` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'INR',
  `status` enum('pending','partial','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bill_items`
-- --------------------------------------------------------

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_type` enum('consultation','medicine','test','equipment','bed','other') NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bill_payments`
-- --------------------------------------------------------

CREATE TABLE `bill_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','upi','netbanking','cheque','crypto') NOT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `medicines`
-- --------------------------------------------------------

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `generic_name` varchar(200) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `strength` varchar(50) DEFAULT NULL,
  `form` enum('tablet','capsule','syrup','injection','cream','drops') DEFAULT 'tablet',
  `price` decimal(8,2) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `minimum_stock` int(11) DEFAULT 10,
  `batch_number` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `lab_tests`
-- --------------------------------------------------------

CREATE TABLE `lab_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `normal_range` varchar(100) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `services`
-- --------------------------------------------------------

CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) DEFAULT 30,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `equipments`
-- --------------------------------------------------------

CREATE TABLE `equipments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `cost` decimal(12,2) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `maintenance_schedule` varchar(100) DEFAULT NULL,
  `status` enum('active','maintenance','retired','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `currencies`
-- --------------------------------------------------------

CREATE TABLE `currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(5) NOT NULL,
  `exchange_rate` decimal(10,4) DEFAULT 1.0000,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `settings`
-- --------------------------------------------------------

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `activity_logs`
-- --------------------------------------------------------

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `role_permissions`
-- --------------------------------------------------------

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(20) NOT NULL,
  `module` varchar(50) NOT NULL,
  `permissions` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `insurance_providers`
-- --------------------------------------------------------

CREATE TABLE `insurance_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `coverage_details` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `attendance`
-- --------------------------------------------------------

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half_day') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_date` (`user_id`,`date`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `feedbacks`
-- --------------------------------------------------------

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `feedback_text` text NOT NULL,
  `type` enum('service','doctor','facility','general') DEFAULT 'general',
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `salary_slips`
-- --------------------------------------------------------

CREATE TABLE `salary_slips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `gross_salary` decimal(10,2) NOT NULL,
  `net_salary` decimal(10,2) NOT NULL,
  `status` enum('generated','paid','cancelled') DEFAULT 'generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_month_year` (`user_id`,`month`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `home_visits`
-- --------------------------------------------------------

CREATE TABLE `home_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `visit_date` datetime NOT NULL,
  `address` text NOT NULL,
  `reason` text NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `charges` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `video_consultations`
-- --------------------------------------------------------

CREATE TABLE `video_consultations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `consultation_date` datetime NOT NULL,
  `duration` int(11) DEFAULT 30,
  `meeting_link` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `charges` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `surgeries`
-- --------------------------------------------------------

CREATE TABLE `surgeries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `surgery_name` varchar(200) NOT NULL,
  `surgery_date` datetime NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `operation_theater` varchar(50) DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `charges` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert Sample Data
-- --------------------------------------------------------

-- Insert Hospitals
INSERT INTO `hospitals` (`name`, `address`, `phone`, `email`, `type`, `status`) VALUES
('City General Hospital', '123 Main Street, City Center', '+91-9876543210', 'info@citygeneral.com', 'hospital', 'active'),
('Metro Clinic', '456 Park Avenue, Metro Area', '+91-9876543211', 'contact@metroclinic.com', 'clinic', 'active');

-- Insert Departments
INSERT INTO `departments` (`name`, `description`, `hospital_id`, `status`) VALUES
('Cardiology', 'Heart and cardiovascular diseases', 1, 'active'),
('Neurology', 'Brain and nervous system disorders', 1, 'active'),
('Orthopedics', 'Bone and joint related treatments', 1, 'active'),
('General Medicine', 'General medical consultations', 1, 'active'),
('Emergency', 'Emergency medical services', 1, 'active'),
('Pediatrics', 'Child healthcare', 1, 'active'),
('Gynecology', 'Women\'s health', 1, 'active'),
('Laboratory', 'Diagnostic tests and analysis', 1, 'active'),
('Pharmacy', 'Medicine dispensing', 1, 'active');

-- Insert Admin User
INSERT INTO `users` (`username`, `password`, `role`, `name`, `email`, `phone`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'admin@hospital.com', '+91-9999999999', 'active');

-- Insert Sample Doctor
INSERT INTO `users` (`username`, `password`, `role`, `name`, `email`, `phone`, `department_id`, `status`) VALUES
('dr.sharma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Dr. Rajesh Sharma', 'dr.sharma@hospital.com', '+91-9876543210', 1, 'active');

INSERT INTO `doctors` (`user_id`, `first_name`, `last_name`, `education`, `experience`, `department_id`, `status`) VALUES
(2, 'Dr. Rajesh', 'Sharma', 'MBBS, MD Cardiology', '15 years experience in cardiology', 1, 'active');

-- Insert Sample Staff Users (Nurse, Pharmacy, Receptionist)
INSERT INTO `users` (`username`, `password`, `role`, `name`, `email`, `phone`, `status`) VALUES
('nurse.priya', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse', 'Priya Sharma (Nurse)', 'nurse.priya@hospital.com', '+91-9876543211', 'active'),
('pharmacy.raj', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacy', 'Raj Kumar (Pharmacist)', 'pharmacy.raj@hospital.com', '+91-9876543212', 'active'),
('reception.neha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', 'Neha Gupta (Receptionist)', 'reception.neha@hospital.com', '+91-9876543213', 'active'),
('lab.suresh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lab_tech', 'Suresh Kumar (Lab Tech)', 'lab.suresh@hospital.com', '+91-9876543214', 'active'),
('staff.anjali', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Anjali Singh (Staff)', 'staff.anjali@hospital.com', '+91-9876543215', 'active'),
('intern.rahul', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'intern', 'Rahul Verma (Intern)', 'intern.rahul@hospital.com', '+91-9876543216', 'active');

-- Insert Sample Insurance Providers
INSERT INTO `insurance_providers` (`name`, `contact_person`, `phone`, `email`, `coverage_details`, `status`) VALUES
('HDFC ERGO Health Insurance', 'Rajesh Kumar', '+91-9876543220', 'support@hdfcergo.com', 'Comprehensive health coverage up to 10 lakhs', 'active'),
('ICICI Lombard Health Care', 'Priya Sharma', '+91-9876543221', 'care@icicilombard.com', 'Individual and family health plans', 'active'),
('Star Health Insurance', 'Amit Verma', '+91-9876543222', 'help@starhealth.in', 'Senior citizen and critical illness coverage', 'active'),
('National Insurance Company', 'Sunita Devi', '+91-9876543223', 'info@nationalinsurance.nic.co.in', 'Government health insurance schemes', 'active');

-- Insert Sample Patient
INSERT INTO `patients` (`first_name`, `last_name`, `date_of_birth`, `gender`, `contact`, `email`, `visit_reason`, `patient_type`, `insurance_provider_id`, `insurance_policy_number`, `insurance_coverage_amount`, `insurance_status`, `insurance_expiry_date`, `status`) VALUES
('John', 'Doe', '1985-06-15', 'male', '+91-9876543211', 'john.doe@email.com', 'Routine checkup', 'outpatient', 1, 'HDFC001234567', 500000.00, 'active', '2025-06-15', 'active'),
('Jane', 'Smith', '1990-03-22', 'female', '+91-9876543212', 'jane.smith@email.com', 'Chest pain consultation', 'outpatient', 2, 'ICICI987654321', 300000.00, 'active', '2025-03-22', 'active'),
('Rajesh', 'Patel', '1975-12-10', 'male', '+91-9876543213', 'rajesh.patel@email.com', 'Diabetes management', 'outpatient', NULL, NULL, NULL, 'none', NULL, 'active');

-- Insert Currencies
INSERT INTO `currencies` (`code`, `name`, `symbol`, `status`) VALUES
('INR', 'Indian Rupee', '₹', 'active'),
('USD', 'US Dollar', '$', 'active'),
('EUR', 'Euro', '€', 'active'),
('BTC', 'Bitcoin', '₿', 'active');

-- Insert Settings
INSERT INTO `settings` (`setting_key`, `value`, `description`) VALUES
('site_title', 'Hospital CRM', 'Website title'),
('hospital_name', 'City General Hospital', 'Hospital name'),
('logo', 'assets/images/logo.png', 'Hospital logo path'),
('favicon', 'assets/images/favicon.ico', 'Website favicon'),
('theme_color', '#007bff', 'Primary theme color'),
('theme_mode', 'light', 'Theme mode (light/dark)'),
('hospital_address', 'City General Hospital, 123 Main Street', 'Hospital address'),
('hospital_phone', '+91-9876543210', 'Hospital phone number'),
('hospital_email', 'admin@hospital.com', 'Hospital email'),
('system_version', '1.0.0', 'System version'),
('last_backup', 'Never', 'Last backup date'),
('currency_symbol', '₹', 'Currency symbol'),
('default_currency', 'INR', 'Default currency code'),
('enable_departments', '1', 'Enable department management'),
('enable_insurance', '1', 'Enable insurance management'),
('allow_online_payments', '1', 'Allow online payment methods'),
('pharmacy_auto_deduct', '1', 'Auto deduct medicine stock on prescription'),
('appointment_slot_duration', '30', 'Appointment slot duration in minutes'),
('patient_id_prefix', 'PID', 'Patient ID prefix'),
('bill_number_prefix', 'INV', 'Bill number prefix');

-- Insert Services
INSERT INTO `services` (`name`, `category`, `price`, `duration`, `description`, `department_id`, `status`) VALUES
('General Consultation', 'Consultation', 500.00, 30, 'General medical consultation', 4, 'active'),
('Cardiology Consultation', 'Consultation', 1000.00, 45, 'Heart specialist consultation', 1, 'active'),
('Emergency Consultation', 'Emergency', 1500.00, 60, 'Emergency medical consultation', 5, 'active'),
('Follow-up Consultation', 'Consultation', 300.00, 20, 'Follow-up visit', 4, 'active');

-- Insert Lab Tests
INSERT INTO `lab_tests` (`name`, `category`, `price`, `normal_range`, `unit`, `department_id`, `status`) VALUES
('Complete Blood Count', 'Blood Test', 300.00, '4.5-11.0', '10^3/μL', 8, 'active'),
('Blood Sugar', 'Blood Test', 150.00, '70-100', 'mg/dL', 8, 'active'),
('Lipid Profile', 'Blood Test', 500.00, 'Various', 'mg/dL', 8, 'active'),
('ECG', 'Cardiac', 200.00, 'Normal sinus rhythm', NULL, 1, 'active'),
('Chest X-Ray', 'Radiology', 400.00, 'Clear lungs', NULL, 8, 'active');

-- Insert Medicines
INSERT INTO `medicines` (`name`, `generic_name`, `brand`, `strength`, `form`, `price`, `quantity`, `minimum_stock`, `status`) VALUES
('Paracetamol', 'Acetaminophen', 'Crocin', '500mg', 'tablet', 2.50, 1000, 100, 'active'),
('Aspirin', 'Acetylsalicylic acid', 'Disprin', '325mg', 'tablet', 3.00, 500, 50, 'active'),
('Amoxicillin', 'Amoxicillin', 'Novamox', '500mg', 'capsule', 15.00, 200, 20, 'active'),
('Cough Syrup', 'Dextromethorphan', 'Benadryl', '100ml', 'syrup', 85.00, 50, 10, 'active');

-- Insert Beds
INSERT INTO `beds` (`bed_number`, `bed_type`, `status`, `price_per_day`) VALUES
('G001', 'general', 'available', 1000.00),
('G002', 'general', 'available', 1000.00),
('P001', 'private', 'available', 2500.00),
('P002', 'private', 'available', 2500.00),
('ICU001', 'icu', 'available', 5000.00),
('ICU002', 'icu', 'available', 5000.00),
('E001', 'emergency', 'available', 3000.00);

-- Insert Role Permissions
INSERT INTO `role_permissions` (`role`, `module`, `status`) VALUES
('admin', 'system', 'active'),
('doctor', 'patients', 'active'),
('nurse', 'patients', 'active'),
('receptionist', 'patients', 'active'),
('pharmacy', 'medicines', 'active'),
('lab_tech', 'lab_tests', 'active'),
('intern', 'limited_access', 'active');

-- Add Foreign Key Constraints
ALTER TABLE `users` ADD CONSTRAINT `users_hospital_fk` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL;
ALTER TABLE `users` ADD CONSTRAINT `users_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
ALTER TABLE `doctors` ADD CONSTRAINT `doctors_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `doctors` ADD CONSTRAINT `doctors_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
ALTER TABLE `patient_vitals` ADD CONSTRAINT `vitals_patient_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
ALTER TABLE `patient_history` ADD CONSTRAINT `history_patient_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
ALTER TABLE `appointments` ADD CONSTRAINT `appointments_patient_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
ALTER TABLE `appointments` ADD CONSTRAINT `appointments_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;
ALTER TABLE `patients` ADD CONSTRAINT `patients_insurance_fk` FOREIGN KEY (`insurance_provider_id`) REFERENCES `insurance_providers` (`id`) ON DELETE SET NULL;
ALTER TABLE `bills` ADD CONSTRAINT `bills_patient_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
ALTER TABLE `bill_items` ADD CONSTRAINT `bill_items_bill_fk` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;
ALTER TABLE `bill_payments` ADD CONSTRAINT `payments_bill_fk` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;
ALTER TABLE `activity_logs` ADD CONSTRAINT `logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

-- End of Hospital CRM Database Structure
