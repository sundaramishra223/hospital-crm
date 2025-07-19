-- Hospital CRM Updated Database Structure
-- Complete SQL file with all tables, new features, and sample data

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
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role` (`role`),
  KEY `status` (`status`),
  KEY `hospital_id` (`hospital_id`)
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
  `deleted_at` timestamp NULL DEFAULT NULL,
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
  `deleted_at` timestamp NULL DEFAULT NULL,
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
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `appointment_date` (`appointment_date`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `beds`
-- --------------------------------------------------------

CREATE TABLE `beds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bed_number` varchar(20) NOT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `bed_type` enum('general','private','icu','emergency') DEFAULT 'general',
  `patient_id` int(11) DEFAULT NULL,
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `price_per_day` decimal(10,2) DEFAULT 0.00,
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bed_number` (`bed_number`),
  KEY `patient_id` (`patient_id`),
  KEY `room_id` (`room_id`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `rooms`
-- --------------------------------------------------------

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `room_type` enum('general','private','icu','emergency','operation_theater','laboratory','consultation') DEFAULT 'general',
  `floor` int(11) DEFAULT 1,
  `capacity` int(11) DEFAULT 1,
  `charges_per_day` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bills`
-- --------------------------------------------------------

CREATE TABLE `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_number` varchar(50) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'INR',
  `status` enum('pending','paid','cancelled','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_number` (`bill_number`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bill_items`
-- --------------------------------------------------------

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `item_type` enum('consultation','medicine','test','service','room','bed') NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bill_payments`
-- --------------------------------------------------------

CREATE TABLE `bill_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
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
  `brand_name` varchar(200) DEFAULT NULL,
  `strength` varchar(100) DEFAULT NULL,
  `form` enum('tablet','capsule','syrup','injection','cream','drops') DEFAULT 'tablet',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `unit_price` decimal(10,2) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `lab_tests`
-- --------------------------------------------------------

CREATE TABLE `lab_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `normal_range` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `services`
-- --------------------------------------------------------

CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `hospital_id` (`hospital_id`)
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
  `maintenance_schedule` text DEFAULT NULL,
  `status` enum('active','maintenance','inactive') DEFAULT 'active',
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `currencies`
-- --------------------------------------------------------

CREATE TABLE `currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `exchange_rate` decimal(10,4) DEFAULT 1.0000,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
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
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key_hospital` (`setting_key`,`hospital_id`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `activity_logs`
-- --------------------------------------------------------

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action_type` (`action_type`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `role_permissions`
-- --------------------------------------------------------

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(50) NOT NULL,
  `module` varchar(100) NOT NULL,
  `permission` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_module_permission` (`role`,`module`,`permission`)
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
  `check_in` timestamp NULL DEFAULT NULL,
  `check_out` timestamp NULL DEFAULT NULL,
  `status` enum('present','absent','late','half_day') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_date` (`user_id`,`date`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `shifts`
-- --------------------------------------------------------

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `shift_type` enum('morning','afternoon','night','custom') DEFAULT 'morning',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `days` json DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `department_id` (`department_id`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `feedbacks`
-- --------------------------------------------------------

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `type` enum('patient','staff','doctor') DEFAULT 'patient',
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
  `basic_salary` decimal(12,2) NOT NULL,
  `allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_salary` decimal(12,2) NOT NULL,
  `net_salary` decimal(12,2) NOT NULL,
  `status` enum('generated','paid','cancelled') DEFAULT 'generated',
  `payment_date` date DEFAULT NULL,
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
  `doctor_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `address` text NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `charges` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
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
  `appointment_id` int(11) DEFAULT NULL,
  `meeting_url` varchar(500) DEFAULT NULL,
  `meeting_id` varchar(100) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `appointment_id` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `surgeries`
-- --------------------------------------------------------

CREATE TABLE `surgeries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `surgery_type` varchar(200) NOT NULL,
  `surgery_date` date NOT NULL,
  `surgery_time` time NOT NULL,
  `theater_room` varchar(50) DEFAULT NULL,
  `anesthesiologist` varchar(100) DEFAULT NULL,
  `assistant_doctors` text DEFAULT NULL,
  `estimated_duration` int(11) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `ambulances`
-- --------------------------------------------------------

CREATE TABLE `ambulances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_number` varchar(20) NOT NULL,
  `vehicle_type` enum('emergency_response','advanced_life_support','basic_life_support') DEFAULT 'basic_life_support',
  `driver_name` varchar(100) NOT NULL,
  `driver_phone` varchar(20) NOT NULL,
  `driver_license` varchar(50) DEFAULT NULL,
  `equipment_details` text DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `status` enum('available','busy','maintenance','inactive') DEFAULT 'available',
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_number` (`vehicle_number`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `ambulance_bookings`
-- --------------------------------------------------------

CREATE TABLE `ambulance_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ambulance_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `patient_phone` varchar(20) NOT NULL,
  `pickup_address` text NOT NULL,
  `destination_address` text NOT NULL,
  `emergency_level` enum('critical','high','medium','low') DEFAULT 'medium',
  `estimated_distance` decimal(8,2) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','assigned','in_transit','completed','cancelled') DEFAULT 'pending',
  `booking_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `pickup_time` timestamp NULL DEFAULT NULL,
  `completion_time` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ambulance_id` (`ambulance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `notification_templates`
-- --------------------------------------------------------

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `type` enum('email','sms','system') DEFAULT 'email',
  `subject` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `variables` text DEFAULT NULL,
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `notifications`
-- --------------------------------------------------------

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_type` enum('user','patient','all_users','all_patients') NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `type` enum('email','sms','system') DEFAULT 'system',
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `sent_by` int(11) NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recipient_type` (`recipient_type`),
  KEY `recipient_id` (`recipient_id`),
  KEY `sent_by` (`sent_by`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `system_backups`
-- --------------------------------------------------------

CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `type` enum('settings','data','full') DEFAULT 'full',
  `description` text DEFAULT NULL,
  `backup_data` longtext NOT NULL,
  `created_by` int(11) NOT NULL,
  `hospital_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `hospital_id` (`hospital_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert sample data
-- --------------------------------------------------------

-- Insert default hospital
INSERT INTO `hospitals` (`id`, `name`, `address`, `phone`, `email`, `license_number`, `established_date`, `type`, `status`) VALUES
(1, 'City General Hospital', '123 Main Street, City Center', '+91-1234567890', 'info@cityhospital.com', 'HOSP001', '2020-01-01', 'hospital', 'active');

-- Insert default admin user
INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `phone`, `status`, `hospital_id`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'admin@hospital.com', '+91-9876543210', 'active', 1);

-- Insert default departments
INSERT INTO `departments` (`id`, `name`, `description`, `hospital_id`, `status`) VALUES
(1, 'General Medicine', 'General medical services', 1, 'active'),
(2, 'Cardiology', 'Heart and cardiovascular services', 1, 'active'),
(3, 'Orthopedics', 'Bone and joint services', 1, 'active'),
(4, 'Pediatrics', 'Children healthcare services', 1, 'active'),
(5, 'Emergency', 'Emergency medical services', 1, 'active');

-- Insert default currencies
INSERT INTO `currencies` (`id`, `code`, `name`, `symbol`, `exchange_rate`, `status`) VALUES
(1, 'INR', 'Indian Rupee', '₹', 1.0000, 'active'),
(2, 'USD', 'US Dollar', '$', 0.0120, 'active'),
(3, 'EUR', 'Euro', '€', 0.0110, 'active'),
(4, 'BTC', 'Bitcoin', '₿', 0.00000045, 'active');

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `value`, `description`, `hospital_id`) VALUES
('hospital_name', 'City General Hospital', 'Hospital name', 1),
('hospital_logo', '', 'Hospital logo path', 1),
('hospital_favicon', '', 'Hospital favicon path', 1),
('theme_color', '#007bff', 'Primary theme color', 1),
('currency', 'INR', 'Default currency', 1),
('patient_id_prefix', 'PID', 'Patient ID prefix', 1),
('bill_number_prefix', 'INV', 'Bill number prefix', 1),
('last_backup', 'Never', 'Last backup timestamp', 1);

-- Insert sample notification templates
INSERT INTO `notification_templates` (`name`, `type`, `subject`, `content`, `variables`, `hospital_id`) VALUES
('Appointment Reminder', 'email', 'Appointment Reminder', 'Dear {name}, your appointment with Dr. {doctor_name} is scheduled for {appointment_date}. Please arrive 15 minutes early.', '{name}, {doctor_name}, {appointment_date}', 1),
('Payment Confirmation', 'sms', 'Payment Confirmation', 'Thank you {name}. Payment of ₹{amount} received for bill #{bill_number}.', '{name}, {amount}, {bill_number}', 1),
('System Alert', 'system', 'System Alert', 'System maintenance scheduled for {date} from {start_time} to {end_time}.', '{date}, {start_time}, {end_time}', 1);

COMMIT;