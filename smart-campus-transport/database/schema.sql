-- =============================================================================
-- Smart Campus Transportation System
-- Federal Polytechnic Ede
-- Database: smart_campus_transport
-- Engine: InnoDB | Charset: utf8mb4
-- =============================================================================
-- INSTALL NOTE:
-- Sample user passwords in this file use a placeholder bcrypt hash for the
-- word "password". Run database/install.php to create the database and set
-- real passwords via PHP password_hash():
--   admin@fedpolyede.edu.ng  -> Admin@123
--   student1@fedpolyede.edu.ng, student2@fedpolyede.edu.ng -> Student@123
--   staff1@fedpolyede.edu.ng -> Staff@123
--   driver1@fedpolyede.edu.ng, driver2@fedpolyede.edu.ng -> Driver@123
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

DROP DATABASE IF EXISTS `smart_campus_transport`;
CREATE DATABASE `smart_campus_transport`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `smart_campus_transport`;

-- -----------------------------------------------------------------------------
-- 1. users
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'student', 'staff', 'driver') NOT NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME DEFAULT NULL,
  `verification_token` VARCHAR(100) DEFAULT NULL,
  `reset_token` VARCHAR(100) DEFAULT NULL,
  `reset_token_expires` DATETIME DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. admins
-- -----------------------------------------------------------------------------
CREATE TABLE `admins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `employee_id` VARCHAR(50) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_user_id` (`user_id`),
  UNIQUE KEY `uk_admins_employee_id` (`employee_id`),
  CONSTRAINT `fk_admins_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. students
-- -----------------------------------------------------------------------------
CREATE TABLE `students` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `matric_number` VARCHAR(50) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `level` VARCHAR(20) NOT NULL,
  `faculty` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_students_user_id` (`user_id`),
  UNIQUE KEY `uk_students_matric_number` (`matric_number`),
  KEY `idx_students_department` (`department`),
  KEY `idx_students_faculty` (`faculty`),
  CONSTRAINT `fk_students_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. staff
-- -----------------------------------------------------------------------------
CREATE TABLE `staff` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `staff_id` VARCHAR(50) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `priority_booking` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_user_id` (`user_id`),
  UNIQUE KEY `uk_staff_staff_id` (`staff_id`),
  KEY `idx_staff_department` (`department`),
  CONSTRAINT `fk_staff_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. drivers
-- -----------------------------------------------------------------------------
CREATE TABLE `drivers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `license_number` VARCHAR(50) NOT NULL,
  `license_expiry` DATE NOT NULL,
  `experience_years` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('available', 'on_trip', 'off_duty') NOT NULL DEFAULT 'available',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_drivers_user_id` (`user_id`),
  UNIQUE KEY `uk_drivers_license_number` (`license_number`),
  KEY `idx_drivers_status` (`status`),
  CONSTRAINT `fk_drivers_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. vehicles
-- -----------------------------------------------------------------------------
CREATE TABLE `vehicles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plate_number` VARCHAR(30) NOT NULL,
  `model` VARCHAR(80) NOT NULL,
  `make` VARCHAR(80) NOT NULL,
  `capacity` INT UNSIGNED NOT NULL,
  `year` YEAR NOT NULL,
  `color` VARCHAR(40) NOT NULL,
  `status` ENUM('available', 'in_use', 'maintenance', 'unavailable') NOT NULL DEFAULT 'available',
  `current_driver_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vehicles_plate_number` (`plate_number`),
  KEY `idx_vehicles_status` (`status`),
  KEY `idx_vehicles_current_driver` (`current_driver_id`),
  CONSTRAINT `fk_vehicles_driver`
    FOREIGN KEY (`current_driver_id`) REFERENCES `drivers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. locations
-- -----------------------------------------------------------------------------
CREATE TABLE `locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(30) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `latitude` DECIMAL(10, 7) DEFAULT NULL,
  `longitude` DECIMAL(10, 7) DEFAULT NULL,
  `location_type` ENUM('gate', 'academic', 'admin', 'hostel', 'facility', 'religious', 'other') NOT NULL DEFAULT 'other',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_locations_code` (`code`),
  KEY `idx_locations_type` (`location_type`),
  KEY `idx_locations_status` (`status`),
  KEY `idx_locations_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. roads
-- -----------------------------------------------------------------------------
CREATE TABLE `roads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_location_id` BIGINT UNSIGNED NOT NULL,
  `to_location_id` BIGINT UNSIGNED NOT NULL,
  `distance_metres` DECIMAL(10, 2) NOT NULL,
  `travel_time_minutes` INT UNSIGNED NOT NULL,
  `is_bidirectional` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roads_from_to` (`from_location_id`, `to_location_id`),
  KEY `idx_roads_to_location` (`to_location_id`),
  KEY `idx_roads_status` (`status`),
  -- NOTE: ON UPDATE RESTRICT (not CASCADE) is required here because MySQL 8.0.16+
  -- forbids using a foreign-key column in a CHECK constraint when the FK has a
  -- CASCADE/SET NULL referential action (error 3823). locations.id is an
  -- AUTO_INCREMENT surrogate key that never changes, so CASCADE is unnecessary.
  CONSTRAINT `fk_roads_from_location`
    FOREIGN KEY (`from_location_id`) REFERENCES `locations` (`id`)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_roads_to_location`
    FOREIGN KEY (`to_location_id`) REFERENCES `locations` (`id`)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_roads_different_ends`
    CHECK (`from_location_id` <> `to_location_id`),
  CONSTRAINT `chk_roads_distance_positive`
    CHECK (`distance_metres` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. routes
-- -----------------------------------------------------------------------------
CREATE TABLE `routes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(30) NOT NULL,
  `start_location_id` BIGINT UNSIGNED NOT NULL,
  `end_location_id` BIGINT UNSIGNED NOT NULL,
  `path_json` JSON NOT NULL,
  `total_distance` DECIMAL(10, 2) NOT NULL COMMENT 'metres',
  `estimated_time` INT UNSIGNED NOT NULL COMMENT 'minutes',
  `description` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_routes_code` (`code`),
  KEY `idx_routes_start` (`start_location_id`),
  KEY `idx_routes_end` (`end_location_id`),
  KEY `idx_routes_status` (`status`),
  CONSTRAINT `fk_routes_start_location`
    FOREIGN KEY (`start_location_id`) REFERENCES `locations` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_routes_end_location`
    FOREIGN KEY (`end_location_id`) REFERENCES `locations` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. schedules
-- -----------------------------------------------------------------------------
CREATE TABLE `schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `route_id` BIGINT UNSIGNED NOT NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `driver_id` BIGINT UNSIGNED NOT NULL,
  `departure_time` TIME NOT NULL,
  `arrival_time` TIME NOT NULL,
  `days_of_week` VARCHAR(50) NOT NULL COMMENT 'e.g. Mon,Tue,Wed,Thu,Fri',
  `available_seats` INT UNSIGNED NOT NULL,
  `status` ENUM('active', 'inactive', 'cancelled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_schedules_route` (`route_id`),
  KEY `idx_schedules_vehicle` (`vehicle_id`),
  KEY `idx_schedules_driver` (`driver_id`),
  KEY `idx_schedules_status` (`status`),
  KEY `idx_schedules_departure` (`departure_time`),
  CONSTRAINT `fk_schedules_route`
    FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_schedules_vehicle`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_schedules_driver`
    FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. bookings
-- -----------------------------------------------------------------------------
CREATE TABLE `bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_number` VARCHAR(40) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `schedule_id` BIGINT UNSIGNED NOT NULL,
  `pickup_location_id` BIGINT UNSIGNED NOT NULL,
  `destination_location_id` BIGINT UNSIGNED NOT NULL,
  `booking_date` DATE NOT NULL,
  `seat_number` VARCHAR(10) DEFAULT NULL,
  `is_priority` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('pending', 'approved', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `cancelled_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bookings_booking_number` (`booking_number`),
  KEY `idx_bookings_user` (`user_id`),
  KEY `idx_bookings_schedule` (`schedule_id`),
  KEY `idx_bookings_pickup` (`pickup_location_id`),
  KEY `idx_bookings_destination` (`destination_location_id`),
  KEY `idx_bookings_date` (`booking_date`),
  KEY `idx_bookings_status` (`status`),
  KEY `idx_bookings_cancelled_by` (`cancelled_by`),
  CONSTRAINT `fk_bookings_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_schedule`
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_pickup`
    FOREIGN KEY (`pickup_location_id`) REFERENCES `locations` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_destination`
    FOREIGN KEY (`destination_location_id`) REFERENCES `locations` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_cancelled_by`
    FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. booking_details
-- -----------------------------------------------------------------------------
CREATE TABLE `booking_details` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` BIGINT UNSIGNED NOT NULL,
  `passenger_name` VARCHAR(150) NOT NULL,
  `passenger_phone` VARCHAR(30) DEFAULT NULL,
  `special_request` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_details_booking` (`booking_id`),
  CONSTRAINT `fk_booking_details_booking`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. notifications
-- -----------------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_is_read` (`is_read`),
  KEY `idx_notifications_type` (`type`),
  KEY `idx_notifications_created` (`created_at`),
  CONSTRAINT `fk_notifications_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. trip_tracking
-- -----------------------------------------------------------------------------
CREATE TABLE `trip_tracking` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` BIGINT UNSIGNED NOT NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `driver_id` BIGINT UNSIGNED NOT NULL,
  `current_location_id` BIGINT UNSIGNED DEFAULT NULL,
  `latitude` DECIMAL(10, 7) DEFAULT NULL,
  `longitude` DECIMAL(10, 7) DEFAULT NULL,
  `trip_date` DATE NOT NULL,
  `status` ENUM('scheduled', 'boarding', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
  `started_at` DATETIME DEFAULT NULL,
  `ended_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trip_tracking_schedule` (`schedule_id`),
  KEY `idx_trip_tracking_vehicle` (`vehicle_id`),
  KEY `idx_trip_tracking_driver` (`driver_id`),
  KEY `idx_trip_tracking_location` (`current_location_id`),
  KEY `idx_trip_tracking_date` (`trip_date`),
  KEY `idx_trip_tracking_status` (`status`),
  CONSTRAINT `fk_trip_tracking_schedule`
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trip_tracking_vehicle`
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trip_tracking_driver`
    FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trip_tracking_location`
    FOREIGN KEY (`current_location_id`) REFERENCES `locations` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 15. reports
-- -----------------------------------------------------------------------------
CREATE TABLE `reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_type` VARCHAR(80) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `parameters_json` JSON DEFAULT NULL,
  `generated_by` BIGINT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reports_type` (`report_type`),
  KEY `idx_reports_generated_by` (`generated_by`),
  KEY `idx_reports_created` (`created_at`),
  CONSTRAINT `fk_reports_generated_by`
    FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 16. activity_logs
-- -----------------------------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `module` VARCHAR(80) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user` (`user_id`),
  KEY `idx_activity_logs_action` (`action`),
  KEY `idx_activity_logs_module` (`module`),
  KEY `idx_activity_logs_created` (`created_at`),
  CONSTRAINT `fk_activity_logs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 17. system_settings
-- -----------------------------------------------------------------------------
CREATE TABLE `system_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_system_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 18. seats
-- -----------------------------------------------------------------------------
CREATE TABLE `seats` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` BIGINT UNSIGNED NOT NULL,
  `seat_number` VARCHAR(10) NOT NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `booking_id` BIGINT UNSIGNED DEFAULT NULL,
  `trip_date` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_seats_schedule_seat_date` (`schedule_id`, `seat_number`, `trip_date`),
  KEY `idx_seats_booking` (`booking_id`),
  KEY `idx_seats_available` (`is_available`),
  KEY `idx_seats_trip_date` (`trip_date`),
  CONSTRAINT `fk_seats_schedule`
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_seats_booking`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SAMPLE DATA
-- Placeholder password hash = bcrypt("password")
-- Run install.php to set Admin@123 / Student@123 / Staff@123 / Driver@123
-- =============================================================================

-- Users (roles: 1 admin, 2 students, 1 staff, 2 drivers)
INSERT INTO `users`
(`id`, `full_name`, `email`, `phone`, `password`, `role`, `status`, `email_verified_at`, `avatar`, `last_login`, `created_at`, `updated_at`)
VALUES
(1, 'System Administrator', 'admin@fedpolyede.edu.ng', '08031234567',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin', 'active', NOW(), NULL, NOW(), NOW(), NOW()),
(2, 'Adebayo Temitope', 'student1@fedpolyede.edu.ng', '08012340001',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'student', 'active', NOW(), NULL, NULL, NOW(), NOW()),
(3, 'Oluwaseun Afolabi', 'student2@fedpolyede.edu.ng', '08012340002',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'student', 'active', NOW(), NULL, NULL, NOW(), NOW()),
(4, 'Mrs. Funmilayo Adeyemi', 'staff1@fedpolyede.edu.ng', '08012340003',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'staff', 'active', NOW(), NULL, NULL, NOW(), NOW()),
(5, 'Ibrahim Musa', 'driver1@fedpolyede.edu.ng', '08012340004',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'driver', 'active', NOW(), NULL, NULL, NOW(), NOW()),
(6, 'Chukwuemeka Okoro', 'driver2@fedpolyede.edu.ng', '08012340005',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'driver', 'active', NOW(), NULL, NULL, NOW(), NOW());

INSERT INTO `admins` (`id`, `user_id`, `employee_id`, `department`, `position`, `created_at`) VALUES
(1, 1, 'ADM-FPE-001', 'ICT Directorate', 'Transport System Administrator', NOW());

INSERT INTO `students` (`id`, `user_id`, `matric_number`, `department`, `level`, `faculty`, `created_at`) VALUES
(1, 2, 'FPE/ND/2024/001234', 'Computer Science', 'ND II', 'School of ICT', NOW()),
(2, 3, 'FPE/HND/2023/004567', 'Electrical Engineering', 'HND I', 'School of Engineering', NOW());

INSERT INTO `staff` (`id`, `user_id`, `staff_id`, `department`, `position`, `priority_booking`, `created_at`) VALUES
(1, 4, 'STF-FPE-00821', 'Student Affairs', 'Senior Administrative Officer', 1, NOW());

INSERT INTO `drivers` (`id`, `user_id`, `license_number`, `license_expiry`, `experience_years`, `status`, `created_at`) VALUES
(1, 5, 'LAG-DRV-998877', '2028-06-30', 8, 'available', NOW()),
(2, 6, 'OSU-DRV-554433', '2027-11-15', 5, 'available', NOW());

INSERT INTO `vehicles`
(`id`, `plate_number`, `model`, `make`, `capacity`, `year`, `color`, `status`, `current_driver_id`, `created_at`, `updated_at`)
VALUES
(1, 'FPE-TR-001', 'Hiace', 'Toyota', 14, 2021, 'White', 'available', 1, NOW(), NOW()),
(2, 'FPE-TR-002', 'Coaster', 'Toyota', 30, 2019, 'Blue', 'available', 2, NOW(), NOW()),
(3, 'FPE-TR-003', 'Sprinter', 'Mercedes-Benz', 18, 2022, 'Silver', 'maintenance', NULL, NOW(), NOW());

-- Campus locations (Federal Polytechnic Ede)
INSERT INTO `locations`
(`id`, `name`, `code`, `description`, `latitude`, `longitude`, `location_type`, `status`, `created_at`)
VALUES
(1,  'Main Gate',                      'GATE',   'Primary campus entrance / exit',                 7.7389000, 4.4352000, 'gate',      'active', NOW()),
(2,  'Administrative Building',        'ADMIN',  'Central administration offices',                 7.7398000, 4.4361000, 'admin',     'active', NOW()),
(3,  'School of Science',              'SOS',    'School of Science academic complex',             7.7405000, 4.4370000, 'academic',  'active', NOW()),
(4,  'School of Engineering',          'SOE',    'School of Engineering workshops and labs',       7.7412000, 4.4378000, 'academic',  'active', NOW()),
(5,  'School of Business',             'SOB',    'School of Business Studies',                     7.7401000, 4.4385000, 'academic',  'active', NOW()),
(6,  'School of Environmental Studies','SOES',   'School of Environmental Studies',                7.7418000, 4.4365000, 'academic',  'active', NOW()),
(7,  'School of ICT',                  'SICT',   'School of Information and Communication Tech',  7.7395000, 4.4392000, 'academic',  'active', NOW()),
(8,  'Library',                        'LIB',    'Central Library and e-resource centre',          7.7408000, 4.4358000, 'facility',  'active', NOW()),
(9,  'Medical Centre',                 'MED',    'Campus medical / health centre',                 7.7385000, 4.4375000, 'facility',  'active', NOW()),
(10, 'Hostels (Male)',                 'HMALE',  'Male students hostel complex',                   7.7378000, 4.4345000, 'hostel',    'active', NOW()),
(11, 'Hostels (Female)',               'HFEM',   'Female students hostel complex',                 7.7372000, 4.4355000, 'hostel',    'active', NOW()),
(12, 'Sport Complex',                  'SPORT',  'Sports and recreation facilities',               7.7425000, 4.4350000, 'facility',  'active', NOW()),
(13, 'Central Mosque',                 'MOSQ',   'Campus central mosque',                          7.7392000, 4.4348000, 'religious', 'active', NOW()),
(14, 'Lecture Theatre',                'LT',     'Main lecture theatre complex',                   7.7403000, 4.4368000, 'academic',  'active', NOW()),
(15, 'Student Affairs',                'SA',     'Student Affairs Division',                       7.7390000, 4.4365000, 'admin',     'active', NOW()),
(16, 'Rector''s Office',               'RECT',   'Office of the Rector',                           7.7397000, 4.4355000, 'admin',     'active', NOW()),
(17, 'Cafeteria',                      'CAFE',   'Main student / staff cafeteria',                 7.7388000, 4.4380000, 'facility',  'active', NOW());

-- Bidirectional road network (distances in metres)
INSERT INTO `roads`
(`from_location_id`, `to_location_id`, `distance_metres`, `travel_time_minutes`, `is_bidirectional`, `status`, `created_at`)
VALUES
(1,  2,   450.00, 2, 1, 'active', NOW()),
(1, 10,   520.00, 3, 1, 'active', NOW()),
(1, 11,   610.00, 3, 1, 'active', NOW()),
(1, 13,   380.00, 2, 1, 'active', NOW()),
(1, 15,   400.00, 2, 1, 'active', NOW()),
(2, 16,   220.00, 1, 1, 'active', NOW()),
(2, 15,   280.00, 2, 1, 'active', NOW()),
(2, 14,   350.00, 2, 1, 'active', NOW()),
(2,  8,   420.00, 2, 1, 'active', NOW()),
(3, 14,   300.00, 2, 1, 'active', NOW()),
(3,  8,   340.00, 2, 1, 'active', NOW()),
(3,  5,   550.00, 3, 1, 'active', NOW()),
(4,  6,   480.00, 3, 1, 'active', NOW()),
(4, 14,   620.00, 4, 1, 'active', NOW()),
(4, 12,   700.00, 4, 1, 'active', NOW()),
(5,  7,   510.00, 3, 1, 'active', NOW()),
(5, 17,   430.00, 2, 1, 'active', NOW()),
(6, 12,   580.00, 3, 1, 'active', NOW()),
(6,  8,   650.00, 4, 1, 'active', NOW()),
(7, 17,   360.00, 2, 1, 'active', NOW()),
(7,  9,   720.00, 4, 1, 'active', NOW()),
(8, 14,   250.00, 1, 1, 'active', NOW()),
(9, 17,   410.00, 2, 1, 'active', NOW()),
(9, 11,   890.00, 5, 1, 'active', NOW()),
(10, 11,  350.00, 2, 1, 'active', NOW()),
(10, 13,  400.00, 2, 1, 'active', NOW()),
(11, 17,  780.00, 4, 1, 'active', NOW()),
(12,  3,  900.00, 5, 1, 'active', NOW()),
(13, 16,  320.00, 2, 1, 'active', NOW()),
(14,  7,  680.00, 4, 1, 'active', NOW()),
(15, 17,  540.00, 3, 1, 'active', NOW()),
(16,  8,  390.00, 2, 1, 'active', NOW());

INSERT INTO `routes`
(`id`, `name`, `code`, `start_location_id`, `end_location_id`, `path_json`, `total_distance`, `estimated_time`, `description`, `status`, `created_at`, `updated_at`)
VALUES
(1, 'Gate to Engineering Loop', 'RTE-GATE-SOE', 1, 4,
 CAST('[1,2,14,4]' AS JSON), 1420.00, 8,
 'Main Gate via Admin Building and Lecture Theatre to School of Engineering',
 'active', NOW(), NOW()),
(2, 'Hostel to ICT Shuttle', 'RTE-HOST-SICT', 10, 7,
 CAST('[10,1,15,17,7]' AS JSON), 1750.00, 10,
 'Male Hostels via Main Gate, Student Affairs and Cafeteria to School of ICT',
 'active', NOW(), NOW()),
(3, 'Admin Campus Circuit', 'RTE-ADMIN-CIR', 2, 2,
 CAST('[2,16,8,14,3,5,7,17,15,2]' AS JSON), 3200.00, 18,
 'Administrative Building circuit covering major academic and facility stops',
 'active', NOW(), NOW());

INSERT INTO `schedules`
(`id`, `route_id`, `vehicle_id`, `driver_id`, `departure_time`, `arrival_time`, `days_of_week`, `available_seats`, `status`, `created_at`, `updated_at`)
VALUES
(1, 1, 1, 1, '07:30:00', '07:45:00', 'Mon,Tue,Wed,Thu,Fri', 14, 'active', NOW(), NOW()),
(2, 2, 2, 2, '08:00:00', '08:20:00', 'Mon,Tue,Wed,Thu,Fri', 30, 'active', NOW(), NOW()),
(3, 3, 1, 1, '12:00:00', '12:30:00', 'Mon,Wed,Fri', 14, 'active', NOW(), NOW()),
(4, 1, 2, 2, '15:30:00', '15:45:00', 'Mon,Tue,Wed,Thu,Fri', 30, 'active', NOW(), NOW());

INSERT INTO `bookings`
(`id`, `booking_number`, `user_id`, `schedule_id`, `pickup_location_id`, `destination_location_id`,
 `booking_date`, `seat_number`, `is_priority`, `status`, `notes`, `cancelled_at`, `cancelled_by`, `created_at`, `updated_at`)
VALUES
(1, 'BK-2026-0001', 2, 1, 1, 4, CURDATE(), 'A3', 0, 'approved',
 'Morning trip to Engineering lectures', NULL, NULL, NOW(), NOW()),
(2, 'BK-2026-0002', 3, 2, 10, 7, CURDATE(), 'B7', 0, 'pending',
 'Hostel to ICT for practical session', NULL, NULL, NOW(), NOW()),
(3, 'BK-2026-0003', 4, 3, 2, 7, CURDATE(), 'A1', 1, 'approved',
 'Priority staff booking for campus circuit', NULL, NULL, NOW(), NOW()),
(4, 'BK-2026-0004', 2, 4, 4, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'C2', 0, 'cancelled',
 'Changed lecture venue', NOW(), 2, NOW(), NOW());

INSERT INTO `booking_details`
(`id`, `booking_id`, `passenger_name`, `passenger_phone`, `special_request`, `created_at`)
VALUES
(1, 1, 'Adebayo Temitope', '08012340001', NULL, NOW()),
(2, 2, 'Oluwaseun Afolabi', '08012340002', 'Needs space for project bag', NOW()),
(3, 3, 'Mrs. Funmilayo Adeyemi', '08012340003', 'Priority boarding preferred', NOW()),
(4, 4, 'Adebayo Temitope', '08012340001', NULL, NOW());

INSERT INTO `notifications`
(`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`)
VALUES
(1, 2, 'Booking Approved', 'Your booking BK-2026-0001 has been approved.', 'success', '/bookings/1', 0, NOW()),
(2, 3, 'Booking Received', 'Your booking BK-2026-0002 is pending approval.', 'info', '/bookings/2', 0, NOW()),
(3, 4, 'Priority Seat Confirmed', 'Your priority booking BK-2026-0003 is confirmed.', 'success', '/bookings/3', 1, NOW()),
(4, 1, 'Maintenance Alert', 'Vehicle FPE-TR-003 is marked for maintenance.', 'warning', '/vehicles/3', 0, NOW()),
(5, 5, 'Trip Assigned', 'You are assigned to schedule #1 tomorrow morning.', 'info', '/schedules/1', 0, NOW());

INSERT INTO `trip_tracking`
(`id`, `schedule_id`, `vehicle_id`, `driver_id`, `current_location_id`, `latitude`, `longitude`,
 `trip_date`, `status`, `started_at`, `ended_at`, `notes`, `updated_at`, `created_at`)
VALUES
(1, 1, 1, 1, 1, 7.7389000, 4.4352000, CURDATE(), 'scheduled', NULL, NULL,
 'Morning Gate–Engineering trip ready for boarding', NOW(), NOW()),
(2, 2, 2, 2, 10, 7.7378000, 4.4345000, CURDATE(), 'boarding', NOW(), NULL,
 'Passengers boarding at Male Hostels', NOW(), NOW()),
(3, 3, 1, 1, 14, 7.7403000, 4.4368000, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'completed',
 DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 1 DAY), INTERVAL 30 MINUTE),
 'Campus circuit completed successfully', NOW(), NOW());

INSERT INTO `reports`
(`id`, `report_type`, `title`, `parameters_json`, `generated_by`, `file_path`, `created_at`)
VALUES
(1, 'bookings_summary', 'Daily Bookings Summary',
 JSON_OBJECT('date', CURDATE(), 'status', 'all'), 1, NULL, NOW()),
(2, 'vehicle_utilization', 'Vehicle Utilization Report',
 JSON_OBJECT('period', 'monthly', 'month', 7, 'year', 2026), 1,
 'storage/reports/vehicle_utilization_2026_07.pdf', NOW());

INSERT INTO `activity_logs`
(`id`, `user_id`, `action`, `description`, `module`, `ip_address`, `user_agent`, `created_at`)
VALUES
(1, 1, 'login', 'Administrator logged in successfully', 'auth', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW()),
(2, 2, 'booking_create', 'Created booking BK-2026-0001', 'bookings', '127.0.0.1', 'Mozilla/5.0 (Android 14)', NOW()),
(3, 1, 'booking_approve', 'Approved booking BK-2026-0001', 'bookings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW()),
(4, 4, 'booking_create', 'Created priority booking BK-2026-0003', 'bookings', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW()),
(5, NULL, 'system_seed', 'Database seeded with Federal Polytechnic Ede sample data', 'system', '127.0.0.1', 'install.php', NOW());

INSERT INTO `system_settings`
(`id`, `setting_key`, `setting_value`, `description`, `updated_at`)
VALUES
(1, 'app_name', 'Smart Campus Transportation System', 'Application display name', NOW()),
(2, 'institution_name', 'Federal Polytechnic Ede', 'Host institution', NOW()),
(3, 'booking_window_hours', '24', 'How many hours ahead bookings can be made', NOW()),
(4, 'priority_booking_enabled', '1', 'Enable priority booking for staff', NOW()),
(5, 'max_seats_per_booking', '1', 'Maximum seats a user can book per schedule/day', NOW()),
(6, 'timezone', 'Africa/Lagos', 'System timezone', NOW()),
(7, 'support_email', 'transport@fedpolyede.edu.ng', 'Support contact email', NOW()),
(8, 'maintenance_mode', '0', 'Toggle maintenance mode (0=off, 1=on)', NOW());

-- Seat allocation for schedule 1 (today) – seats A1–A14
INSERT INTO `seats`
(`schedule_id`, `seat_number`, `is_available`, `booking_id`, `trip_date`, `created_at`)
VALUES
(1, 'A1', 1, NULL, CURDATE(), NOW()),
(1, 'A2', 1, NULL, CURDATE(), NOW()),
(1, 'A3', 0, 1, CURDATE(), NOW()),
(1, 'A4', 1, NULL, CURDATE(), NOW()),
(1, 'A5', 1, NULL, CURDATE(), NOW()),
(1, 'A6', 1, NULL, CURDATE(), NOW()),
(1, 'A7', 1, NULL, CURDATE(), NOW()),
(1, 'A8', 1, NULL, CURDATE(), NOW()),
(1, 'A9', 1, NULL, CURDATE(), NOW()),
(1, 'A10', 1, NULL, CURDATE(), NOW()),
(1, 'A11', 1, NULL, CURDATE(), NOW()),
(1, 'A12', 1, NULL, CURDATE(), NOW()),
(1, 'A13', 1, NULL, CURDATE(), NOW()),
(1, 'A14', 1, NULL, CURDATE(), NOW()),
(2, 'B1', 1, NULL, CURDATE(), NOW()),
(2, 'B2', 1, NULL, CURDATE(), NOW()),
(2, 'B3', 1, NULL, CURDATE(), NOW()),
(2, 'B4', 1, NULL, CURDATE(), NOW()),
(2, 'B5', 1, NULL, CURDATE(), NOW()),
(2, 'B6', 1, NULL, CURDATE(), NOW()),
(2, 'B7', 0, 2, CURDATE(), NOW()),
(2, 'B8', 1, NULL, CURDATE(), NOW()),
(3, 'A1', 0, 3, CURDATE(), NOW()),
(3, 'A2', 1, NULL, CURDATE(), NOW()),
(3, 'A3', 1, NULL, CURDATE(), NOW()),
(3, 'A4', 1, NULL, CURDATE(), NOW());

-- =============================================================================
-- END OF SCHEMA
-- =============================================================================
