SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `gate_approvals`;
CREATE TABLE `gate_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `outpass_id` int NOT NULL,
  `student_id` int NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `exit_time` timestamp NULL DEFAULT NULL,
  `return_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `outpass_id` (`outpass_id`),
  CONSTRAINT `gate_approvals_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gate_approvals_ibfk_2` FOREIGN KEY (`outpass_id`) REFERENCES `outpass_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `outpass_requests`;
CREATE TABLE `outpass_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `department` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `leave_date` date NOT NULL,
  `leave_time` time DEFAULT NULL,
  `return_date` date NOT NULL,
  `return_time` time DEFAULT NULL,
  `teacher_id` int NOT NULL,
  `warden_approved` tinyint(1) DEFAULT '0',
  `teacher_approved` tinyint(1) DEFAULT '0',
  `status` varchar(20) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `teacher_comment` text,
  `warden_comment` text,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `outpass_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `outpass_requests_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `student_gatepass`;
CREATE TABLE `student_gatepass` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `outpass_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('approved','pending') DEFAULT 'approved',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `outpass_id` (`outpass_id`),
  CONSTRAINT `student_gatepass_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_gatepass_ibfk_2` FOREIGN KEY (`outpass_id`) REFERENCES `outpass_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('student','teacher','warden','gate_security') DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `year_of_study` varchar(10) DEFAULT NULL,
  `room_no` varchar(10) DEFAULT NULL,
  `roll_no` varchar(20) DEFAULT NULL,
  `hostel_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;