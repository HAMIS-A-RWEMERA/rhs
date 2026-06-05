-- Rusumo High School — database schema
-- Import this file into MySQL/MariaDB to create the required tables.
-- Usage: mysql -u root -p rhs < database/schema.sql

CREATE DATABASE IF NOT EXISTS `rhs`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `rhs`;

-- Students table (matches all column references across the codebase)
CREATE TABLE IF NOT EXISTS `students` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`     VARCHAR(50)  NOT NULL UNIQUE,
    `full_name`      VARCHAR(150) NOT NULL,
    `class_name`     VARCHAR(100) NOT NULL,
    `division_name`  VARCHAR(100) DEFAULT NULL,
    `gender`         ENUM('Male','Female') NOT NULL,
    `parent_phone`   VARCHAR(30)  DEFAULT NULL,
    `fees_balance`   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `score`          VARCHAR(20)  DEFAULT NULL,
    `conduct`        VARCHAR(100) DEFAULT NULL,
    `pin`            VARCHAR(20)  DEFAULT NULL,
    `profile_photo`  VARCHAR(255) NOT NULL DEFAULT 'default.png',
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Note: The API (api/get_student.php) reads `fees_balance` as "balance" in its JSON output.
-- In Phase 2 this table will be normalized (separate marks, discipline, finance tables).

-- Users table for role-based authentication (Phase 1)
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(50)  NOT NULL UNIQUE,
    `email`         VARCHAR(150) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name`     VARCHAR(150) NOT NULL,
    `role`          ENUM('admin','registrar','discipline_master','bursar','director_of_studies','teacher','parent') NOT NULL DEFAULT 'admin',
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
