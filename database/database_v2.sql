-- ============================================================================
-- RUSUMO HIGH SCHOOL (RHS) — Database Schema v2
-- Normalized, Role-Based Access Control, Production-Ready
-- ============================================================================
-- 
-- Author   : Hamis A. Rwemera
-- Project  : Rusumo High School Management System
-- Purpose  : Multi-role school management (Admin, Registrar, DOS, Discipline
--            Master, Bursar, Teacher, Parent) with full audit trail.
-- Engine   : InnoDB (transactional, foreign-key supported)
-- Charset  : utf8mb4 (full Unicode, emoji-safe)
--
-- HOW TO IMPORT:
--   mysql -u root -p < database_v2.sql
--
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================================
-- SCHEMA VERSIONING — tracks which migrations have been applied
-- ============================================================================
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `version`     VARCHAR(50)  NOT NULL PRIMARY KEY,
    `name`        VARCHAR(255) NOT NULL,
    `applied_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `checksum`    VARCHAR(64)  DEFAULT NULL COMMENT 'SHA-256 of migration content'
) ENGINE=InnoDB;

INSERT INTO `schema_migrations` (`version`, `name`) VALUES
('v2.0.0', 'Normalized multi-role schema with RBAC, audit, computed views');

-- ============================================================================
-- 1. CORE AUTHENTICATION & AUTHORIZATION
-- ============================================================================

-- 1a. Roles — the backbone of RBAC
CREATE TABLE IF NOT EXISTS `roles` (
    `id`          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug`        VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Programmatic identifier, e.g. "admin"',
    `name`        VARCHAR(100) NOT NULL        COMMENT 'Human-readable, e.g. "Administrator"',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_system`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'System roles cannot be deleted',
    `priority`    TINYINT UNSIGNED DEFAULT 0   COMMENT 'Lower = higher privilege level',
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Role definitions for RBAC';

-- 1b. Granular permissions (fine-grained, not just role names)
CREATE TABLE IF NOT EXISTS `permissions` (
    `id`          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug`        VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g. "students.create", "marks.read.all"',
    `name`        VARCHAR(150) NOT NULL,
    `group`       VARCHAR(50)  NOT NULL        COMMENT 'Group for UI organization, e.g. "students", "finance"',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Individual permission flags for granular access control';

-- 1c. Role-permission mapping (many-to-many)
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id`       TINYINT UNSIGNED NOT NULL,
    `permission_id` SMALLINT UNSIGNED NOT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`)       REFERENCES `roles`(`id`)       ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Links roles to their allowed permissions';

-- 1d. Users — staff, teachers, and parents who authenticate
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(50)  NOT NULL UNIQUE,
    `email`         VARCHAR(150) DEFAULT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Use password_hash()/password_verify() in PHP',
    `first_name`    VARCHAR(100) NOT NULL,
    `last_name`     VARCHAR(100) NOT NULL,
    `phone`         VARCHAR(30)  DEFAULT NULL,
    `role_id`       TINYINT UNSIGNED NOT NULL,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Force password change on first login',
    `last_login_at` TIMESTAMP    NULL DEFAULT NULL,
    `password_changed_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_at`    TIMESTAMP    NULL DEFAULT NULL COMMENT 'Soft delete — account disabled, not removed',
    `created_by`    INT UNSIGNED DEFAULT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`)    REFERENCES `roles`(`id`)         ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)         ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_users_active`   (`is_active`, `deleted_at`),
    INDEX `idx_users_role`     (`role_id`),
    INDEX `idx_users_email`    (`email`)
) ENGINE=InnoDB COMMENT='All authenticated users (staff, teachers, parents)';

-- 1e. Session management with expiry
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `token`         VARCHAR(128) NOT NULL UNIQUE COMMENT 'Random token, stored in PHP session or cookie',
    `ip_address`    VARCHAR(45)  DEFAULT NULL COMMENT 'IPv4 or IPv6',
    `user_agent`    VARCHAR(500) DEFAULT NULL,
    `expires_at`    TIMESTAMP    NOT NULL,
    `last_activity` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_sessions_token`    (`token`),
    INDEX `idx_sessions_user`     (`user_id`),
    INDEX `idx_sessions_expiry`   (`expires_at`)
) ENGINE=InnoDB COMMENT='Active user sessions with automatic expiry';

-- 1f. Password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `token`      VARCHAR(128) NOT NULL UNIQUE,
    `expires_at` TIMESTAMP    NOT NULL,
    `used_at`    TIMESTAMP    NULL DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_resets_token` (`token`)
) ENGINE=InnoDB COMMENT='Password reset tokens with expiry';

-- ============================================================================
-- 2. ACADEMIC STRUCTURE
-- ============================================================================

-- 2a. Academic years (e.g. "2025-2026")
CREATE TABLE IF NOT EXISTS `academic_years` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g. "2025-2026"',
    `start_date`  DATE        NOT NULL,
    `end_date`    DATE        NOT NULL,
    `is_current`  TINYINT(1)  NOT NULL DEFAULT 0 COMMENT 'Only one year should be current',
    `is_open`     TINYINT(1)  NOT NULL DEFAULT 1 COMMENT 'If closed, no new data entry allowed',
    `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_academic_current` (`is_current`),
    INDEX `idx_academic_dates`   (`start_date`, `end_date`)
) ENGINE=InnoDB COMMENT='Academic year sessions';

-- 2b. Terms within an academic year
CREATE TABLE IF NOT EXISTS `terms` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `academic_year_id` INT UNSIGNED NOT NULL,
    `name`             VARCHAR(50) NOT NULL COMMENT 'e.g. "Term 1", "Term 2", "Term 3"',
    `short_name`       VARCHAR(20) DEFAULT NULL COMMENT 'e.g. "T1"',
    `start_date`       DATE        NOT NULL,
    `end_date`         DATE        NOT NULL,
    `is_current`       TINYINT(1)  NOT NULL DEFAULT 0,
    `is_open`          TINYINT(1)  NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_terms_year`    (`academic_year_id`),
    INDEX `idx_terms_current` (`is_current`),
    UNIQUE KEY `uq_term_year_name` (`academic_year_id`, `name`)
) ENGINE=InnoDB COMMENT='School terms within academic years';

-- 2c. Classes (e.g. "Senior 5 MCB", "Senior 3 MEG")
CREATE TABLE IF NOT EXISTS `classes` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL COMMENT 'Full name e.g. "Senior 5 — Mathematics, Chemistry, Biology"',
    `short_name`  VARCHAR(20)  NOT NULL UNIQUE COMMENT 'e.g. "S5 MCB"',
    `level`       TINYINT UNSIGNED NOT NULL COMMENT '1-6 representing Senior 1 through Senior 6',
    `stream`      VARCHAR(50)  DEFAULT NULL COMMENT 'e.g. "MCB", "MEG", "LCD" — NULL for junior classes',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `deleted_at`  TIMESTAMP    NULL DEFAULT NULL COMMENT 'Soft delete',
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_classes_level` (`level`),
    INDEX `idx_classes_stream` (`stream`),
    INDEX `idx_classes_active` (`is_active`, `deleted_at`)
) ENGINE=InnoDB COMMENT='Class/grades — the academic groupings';

-- 2d. Subjects (e.g. "Mathematics", "Physics")
CREATE TABLE IF NOT EXISTS `subjects` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL,
    `code`        VARCHAR(20)  NOT NULL UNIQUE COMMENT 'e.g. "MATH", "PHY"',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_core`     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Core/compulsory subject',
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `deleted_at`  TIMESTAMP    NULL DEFAULT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_subjects_active` (`is_active`, `deleted_at`)
) ENGINE=InnoDB COMMENT='Subjects offered at the school';

-- 2e. Which subjects belong to which classes
CREATE TABLE IF NOT EXISTS `class_subjects` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_id`   INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `is_optional` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_class_subject` (`class_id`, `subject_id`),
    FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)  ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Many-to-many: subjects offered per class';

-- 2f. Teacher profiles (extend user data for teaching staff)
CREATE TABLE IF NOT EXISTS `teachers` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL UNIQUE,
    `staff_id`      VARCHAR(50)  DEFAULT NULL UNIQUE COMMENT 'Official staff/employee ID',
    `qualification` VARCHAR(255) DEFAULT NULL COMMENT 'Highest qualification',
    `specialization` VARCHAR(255) DEFAULT NULL COMMENT 'Area of expertise',
    `date_hired`    DATE         DEFAULT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Teacher-specific profile data';

-- 2g. Teacher → Subject → Class assignment (per term)
--     THIS is the table that enforces teacher scope for permissions.
CREATE TABLE IF NOT EXISTS `teacher_subject_class` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `class_id`   INT UNSIGNED NOT NULL,
    `term_id`    INT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Primary teacher for this subject/class',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_teacher_subject_class_term` (`teacher_id`, `subject_id`, `class_id`, `term_id`),
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`)   ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`)   ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`term_id`)    REFERENCES `terms`(`id`)      ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_tsc_term` (`term_id`),
    INDEX `idx_tsc_teacher` (`teacher_id`),
    INDEX `idx_tsc_subject_class` (`subject_id`, `class_id`)
) ENGINE=InnoDB COMMENT='Who teaches what subject to which class in a given term';

-- ============================================================================
-- 3. STUDENTS & PARENTS
-- ============================================================================

-- 3a. Students — normalized, no flat score/conduct/balance columns
CREATE TABLE IF NOT EXISTS `students` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`     VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Official admission number',
    `first_name`     VARCHAR(100) NOT NULL,
    `last_name`      VARCHAR(100) NOT NULL,
    `full_name`      VARCHAR(201) GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED COMMENT 'Auto-generated full name',
    `gender`         ENUM('Male','Female','Other') NOT NULL,
    `date_of_birth`  DATE         DEFAULT NULL,
    `class_id`       INT UNSIGNED NOT NULL,
    `enrollment_date` DATE        DEFAULT NULL,
    `status`         ENUM('active','graduated','transferred','suspended','expelled','withdrawn') NOT NULL DEFAULT 'active',
    `photo`          VARCHAR(255) DEFAULT 'default.png' COMMENT 'Stored filename (randomized, not user-supplied)',
    `parent_phone`   VARCHAR(30)  DEFAULT NULL COMMENT 'Primary contact phone',
    `parent_email`   VARCHAR(150) DEFAULT NULL,
    `address`        TEXT         DEFAULT NULL,
    `national_id`    VARCHAR(50)  DEFAULT NULL UNIQUE COMMENT 'National ID or passport number',
    `deleted_at`     TIMESTAMP    NULL DEFAULT NULL COMMENT 'Soft delete — keeps audit trail intact',
    `created_by`     INT UNSIGNED DEFAULT NULL,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`class_id`)   REFERENCES `classes`(`id`)      ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_students_class`   (`class_id`),
    INDEX `idx_students_status`  (`status`),
    INDEX `idx_students_active`  (`deleted_at`),
    INDEX `idx_students_name`    (`last_name`, `first_name`),
    FULLTEXT INDEX `ft_students_name` (`first_name`, `last_name`, `full_name`)
) ENGINE=InnoDB COMMENT='Student records — clean, normalized, with soft delete';

-- 3b. Parents (linked to user accounts — no plaintext PINs)
CREATE TABLE IF NOT EXISTS `parents` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL UNIQUE,
    `first_name`    VARCHAR(100) NOT NULL,
    `last_name`     VARCHAR(100) NOT NULL,
    `full_name`     VARCHAR(201) GENERATED ALWAYS AS (CONCAT(`first_name`, ' ', `last_name`)) STORED,
    `phone`         VARCHAR(30)  NOT NULL,
    `email`         VARCHAR(150) DEFAULT NULL,
    `occupation`    VARCHAR(100) DEFAULT NULL,
    `address`       TEXT         DEFAULT NULL,
    `national_id`   VARCHAR(50)  DEFAULT NULL UNIQUE,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_parents_phone` (`phone`),
    INDEX `idx_parents_name`  (`last_name`, `first_name`)
) ENGINE=InnoDB COMMENT='Parent/guardian profiles linked to user accounts';

-- 3c. Parent ↔ Student relationship (supports multiple children & multiple guardians)
CREATE TABLE IF NOT EXISTS `parent_student` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id`    INT UNSIGNED NOT NULL,
    `student_id`   INT UNSIGNED NOT NULL,
    `relationship` ENUM('father','mother','guardian','other') NOT NULL DEFAULT 'guardian',
    `is_primary`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Primary contact for this student',
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_parent_student` (`parent_id`, `student_id`),
    FOREIGN KEY (`parent_id`)  REFERENCES `parents`(`id`)  ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_parent_student_student` (`student_id`)
) ENGINE=InnoDB COMMENT='Many-to-many linking parents to their children';

-- 3d. Student class history (tracks promotions/transfers across academic years)
CREATE TABLE IF NOT EXISTS `student_class_history` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`       INT UNSIGNED NOT NULL,
    `class_id`         INT UNSIGNED NOT NULL,
    `academic_year_id` INT UNSIGNED NOT NULL,
    `term_id`          INT UNSIGNED DEFAULT NULL,
    `changed_by`       INT UNSIGNED DEFAULT NULL,
    `reason`           VARCHAR(255) DEFAULT NULL COMMENT 'e.g. "Promotion", "Transfer", "New enrollment"',
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`)       REFERENCES `students`(`id`)       ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`class_id`)         REFERENCES `classes`(`id`)        ON UPDATE CASCADE,
    FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`term_id`)          REFERENCES `terms`(`id`)          ON UPDATE CASCADE,
    FOREIGN KEY (`changed_by`)       REFERENCES `users`(`id`)          ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_sch_student`  (`student_id`),
    INDEX `idx_sch_class`    (`class_id`),
    INDEX `idx_sch_year`     (`academic_year_id`)
) ENGINE=InnoDB COMMENT='Tracks student class assignments over time';

-- ============================================================================
-- 4. ACADEMIC DOMAIN — MARKS & ASSESSMENTS
-- ============================================================================

-- 4a. Assessment types (exam, test, assignment, project)
CREATE TABLE IF NOT EXISTS `assessment_types` (
    `id`          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g. "End of Term Exam", "Midterm Test", "Assignment"',
    `short_name`  VARCHAR(20) NOT NULL UNIQUE COMMENT 'e.g. "EXAM", "TEST", "ASSIGN"',
    `weight`      DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Weight factor for final grade computation',
    `max_score`   DECIMAL(6,2) NOT NULL DEFAULT 100.00,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Types of assessments for grade calculation';

-- 4b. Marks/assessments per student per subject per term
CREATE TABLE IF NOT EXISTS `marks` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`        INT UNSIGNED NOT NULL,
    `subject_id`        INT UNSIGNED NOT NULL,
    `class_id`          INT UNSIGNED NOT NULL COMMENT 'Denormalized for query performance',
    `term_id`           INT UNSIGNED NOT NULL,
    `assessment_type_id` TINYINT UNSIGNED DEFAULT NULL,
    `score`             DECIMAL(6,2) NOT NULL,
    `max_score`         DECIMAL(6,2) NOT NULL DEFAULT 100.00,
    `grade`             VARCHAR(5)   DEFAULT NULL COMMENT 'Computed grade e.g. "A", "B+", "C"',
    `remarks`           VARCHAR(255) DEFAULT NULL,
    `entered_by`        INT UNSIGNED NOT NULL,
    `entered_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_by`        INT UNSIGNED DEFAULT NULL,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_mark_per_assessment` (`student_id`, `subject_id`, `term_id`, `assessment_type_id`),
    FOREIGN KEY (`student_id`)        REFERENCES `students`(`id`)        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`subject_id`)        REFERENCES `subjects`(`id`)         ON UPDATE CASCADE,
    FOREIGN KEY (`class_id`)          REFERENCES `classes`(`id`)          ON UPDATE CASCADE,
    FOREIGN KEY (`term_id`)           REFERENCES `terms`(`id`)            ON UPDATE CASCADE,
    FOREIGN KEY (`assessment_type_id`) REFERENCES `assessment_types`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`entered_by`)        REFERENCES `users`(`id`)            ON UPDATE CASCADE,
    FOREIGN KEY (`updated_by`)        REFERENCES `users`(`id`)            ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_marks_student`   (`student_id`),
    INDEX `idx_marks_subject`   (`subject_id`),
    INDEX `idx_marks_class`     (`class_id`),
    INDEX `idx_marks_term`      (`term_id`),
    INDEX `idx_marks_entered_by` (`entered_by`),
    INDEX `idx_marks_lookup`    (`student_id`, `term_id`, `subject_id`)
) ENGINE=InnoDB COMMENT='Per-student, per-subject marks for each assessment type';

-- ============================================================================
-- 5. DISCIPLINE DOMAIN
-- ============================================================================

-- 5a. Discipline incident types (lookup table)
CREATE TABLE IF NOT EXISTS `discipline_types` (
    `id`          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g. "Late coming", "Uniform violation", "Fighting"',
    `category`    ENUM('minor','major','critical') NOT NULL DEFAULT 'minor',
    `default_points` SMALLINT NOT NULL DEFAULT 0 COMMENT 'Default demerit points',
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Types/categories of discipline incidents';

-- 5b. Discipline records — one row per incident
CREATE TABLE IF NOT EXISTS `discipline` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`         INT UNSIGNED NOT NULL,
    `discipline_type_id` TINYINT UNSIGNED DEFAULT NULL,
    `incident_date`      DATE         NOT NULL,
    `conduct_score`      ENUM('A','B','C','D','E') DEFAULT NULL COMMENT 'Overall conduct rating for the period',
    `demerit_points`     SMALLINT     NOT NULL DEFAULT 0,
    `observation`        TEXT         NOT NULL COMMENT 'Description of the incident',
    `action_taken`       VARCHAR(255) DEFAULT NULL COMMENT 'e.g. "Verbal warning", "Suspension 3 days"',
    `status`             ENUM('open','resolved','appealed') NOT NULL DEFAULT 'open',
    `recorded_by`        INT UNSIGNED NOT NULL,
    `resolved_by`        INT UNSIGNED DEFAULT NULL,
    `resolved_at`        TIMESTAMP    NULL DEFAULT NULL,
    `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`)         REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`discipline_type_id`) REFERENCES `discipline_types`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`recorded_by`)        REFERENCES `users`(`id`)    ON UPDATE CASCADE,
    FOREIGN KEY (`resolved_by`)        REFERENCES `users`(`id`)    ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_discipline_student`   (`student_id`),
    INDEX `idx_discipline_date`      (`incident_date`),
    INDEX `idx_discipline_status`    (`status`),
    INDEX `idx_discipline_recorded`  (`recorded_by`)
) ENGINE=InnoDB COMMENT='Discipline/conduct incident records';

-- ============================================================================
-- 6. FINANCE DOMAIN
-- ============================================================================

-- 6a. Fee structure items
CREATE TABLE IF NOT EXISTS `fee_items` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(150) NOT NULL COMMENT 'e.g. "Tuition", "Boarding", "Lab Fee"',
    `code`        VARCHAR(30)  NOT NULL UNIQUE COMMENT 'e.g. "TUITION", "BOARDING"',
    `amount`      DECIMAL(12,2) NOT NULL,
    `is_optional` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Defined fee items/types';

-- 6b. Fee assignment per class per term (how much each class pays)
CREATE TABLE IF NOT EXISTS `fee_structure` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_id`         INT UNSIGNED NOT NULL,
    `term_id`          INT UNSIGNED NOT NULL,
    `fee_item_id`      INT UNSIGNED NOT NULL,
    `amount`           DECIMAL(12,2) NOT NULL,
    `is_required`      TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_fee_structure` (`class_id`, `term_id`, `fee_item_id`),
    FOREIGN KEY (`class_id`)    REFERENCES `classes`(`id`)  ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`term_id`)     REFERENCES `terms`(`id`)    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`fee_item_id`) REFERENCES `fee_items`(`id`) ON UPDATE CASCADE,
    INDEX `idx_fee_structure_class_term` (`class_id`, `term_id`)
) ENGINE=InnoDB COMMENT='Fee amounts per class per term';

-- 6c. Invoices generated per student per term
CREATE TABLE IF NOT EXISTS `finance_invoices` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`     INT UNSIGNED NOT NULL,
    `term_id`        INT UNSIGNED NOT NULL,
    `invoice_number` VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Generated sequentially, e.g. "INV-2025-0001"',
    `description`    VARCHAR(255) DEFAULT NULL,
    `amount`         DECIMAL(12,2) NOT NULL,
    `due_date`       DATE         DEFAULT NULL,
    `status`         ENUM('pending','paid','partial','overdue','cancelled') NOT NULL DEFAULT 'pending',
    `notes`          TEXT         DEFAULT NULL,
    `created_by`     INT UNSIGNED NOT NULL,
    `cancelled_by`   INT UNSIGNED DEFAULT NULL,
    `cancelled_at`   TIMESTAMP    NULL DEFAULT NULL,
    `cancellation_reason` VARCHAR(255) DEFAULT NULL,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`)   REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`term_id`)      REFERENCES `terms`(`id`)    ON UPDATE CASCADE,
    FOREIGN KEY (`created_by`)   REFERENCES `users`(`id`)    ON UPDATE CASCADE,
    FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_invoices_student`  (`student_id`),
    INDEX `idx_invoices_term`     (`term_id`),
    INDEX `idx_invoices_status`   (`status`),
    INDEX `idx_invoices_due_date` (`due_date`)
) ENGINE=InnoDB COMMENT='Student invoices — balance is computed, not stored';

-- 6d. Payment methods (lookup table)
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id`          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g. "Cash", "Mobile Money", "Bank Transfer", "Cheque"',
    `short_name`  VARCHAR(20) NOT NULL UNIQUE,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Accepted payment methods';

-- 6e. Payments made toward invoices
CREATE TABLE IF NOT EXISTS `finance_payments` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`       INT UNSIGNED NOT NULL,
    `invoice_id`       INT UNSIGNED DEFAULT NULL COMMENT 'NULL = unallocated/ad hoc payment',
    `payment_method_id` TINYINT UNSIGNED NOT NULL,
    `receipt_number`   VARCHAR(50)  DEFAULT NULL UNIQUE COMMENT 'School-issued receipt number',
    `amount`           DECIMAL(12,2) NOT NULL,
    `paid_at`          DATETIME     NOT NULL,
    `reference`        VARCHAR(255) DEFAULT NULL COMMENT 'Mobile money ref / cheque number / bank ref',
    `notes`            TEXT         DEFAULT NULL,
    `recorded_by`      INT UNSIGNED NOT NULL,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`)        REFERENCES `students`(`id`)  ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`invoice_id`)        REFERENCES `finance_invoices`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`) ON UPDATE CASCADE,
    FOREIGN KEY (`recorded_by`)       REFERENCES `users`(`id`)     ON UPDATE CASCADE,
    INDEX `idx_payments_student`  (`student_id`),
    INDEX `idx_payments_invoice`  (`invoice_id`),
    INDEX `idx_payments_date`     (`paid_at`),
    INDEX `idx_payments_method`   (`payment_method_id`)
) ENGINE=InnoDB COMMENT='Payment records — auditable trail for all transactions';

-- ============================================================================
-- 7. COMMUNICATIONS & CONTENT
-- ============================================================================

-- 7a. News/announcements (public landing page CMS)
CREATE TABLE IF NOT EXISTS `news` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`        VARCHAR(255) NOT NULL,
    `slug`         VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly identifier',
    `summary`      TEXT         DEFAULT NULL COMMENT 'Short excerpt for listings',
    `body`         LONGTEXT     NOT NULL,
    `featured_image` VARCHAR(255) DEFAULT NULL,
    `category`     VARCHAR(50)  DEFAULT NULL COMMENT 'e.g. "academic", "sports", "general"',
    `is_published` TINYINT(1)   NOT NULL DEFAULT 0,
    `published_at` TIMESTAMP    NULL DEFAULT NULL,
    `author_id`    INT UNSIGNED NOT NULL,
    `deleted_at`   TIMESTAMP    NULL DEFAULT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE,
    INDEX `idx_news_published`  (`is_published`, `published_at`),
    INDEX `idx_news_category`   (`category`),
    INDEX `idx_news_slug`       (`slug`),
    FULLTEXT INDEX `ft_news_content` (`title`, `summary`, `body`)
) ENGINE=InnoDB COMMENT='News and announcements for the public landing page';

-- ============================================================================
-- 8. AUDIT & SYSTEM
-- ============================================================================

-- 8a. Audit log — tracks all sensitive changes
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED DEFAULT NULL COMMENT 'NULL if action by system/anonymous',
    `action`      ENUM('create','update','delete','restore','login','logout','password_change','export','other') NOT NULL,
    `entity`      VARCHAR(50)  NOT NULL COMMENT 'Table name, e.g. "marks", "finance_payments"',
    `entity_id`   INT UNSIGNED DEFAULT NULL COMMENT 'Primary key of the affected row',
    `old_values`  JSON         DEFAULT NULL COMMENT 'Previous state of modified fields',
    `new_values`  JSON         DEFAULT NULL COMMENT 'New state of modified fields',
    `description` VARCHAR(500) DEFAULT NULL COMMENT 'Human-readable summary',
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `user_agent`  VARCHAR(500) DEFAULT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_audit_user`    (`user_id`),
    INDEX `idx_audit_entity`  (`entity`, `entity_id`),
    INDEX `idx_audit_action`  (`action`),
    INDEX `idx_audit_time`    (`created_at`)
) ENGINE=InnoDB COMMENT='Immutable audit trail for all sensitive operations';

-- ============================================================================
-- 9. COMPUTED VIEWS — for read-only convenience
-- ============================================================================

-- 9a. Student balance view: total invoiced − total paid per student
CREATE OR REPLACE VIEW `v_student_balance` AS
SELECT
    s.id AS student_id,
    s.student_id AS admission_number,
    s.full_name AS student_name,
    c.short_name AS class_name,
    COALESCE(inv.total_invoiced, 0) AS total_invoiced,
    COALESCE(pay.total_paid, 0) AS total_paid,
    (COALESCE(inv.total_invoiced, 0) - COALESCE(pay.total_paid, 0)) AS balance,
    CASE
        WHEN COALESCE(inv.total_invoiced, 0) = 0 THEN 'no_invoices'
        WHEN (COALESCE(inv.total_invoiced, 0) - COALESCE(pay.total_paid, 0)) <= 0 THEN 'fully_paid'
        WHEN COALESCE(pay.total_paid, 0) > 0 THEN 'partial'
        ELSE 'unpaid'
    END AS payment_status
FROM students s
LEFT JOIN classes c ON s.class_id = c.id
LEFT JOIN (
    SELECT student_id, SUM(amount) AS total_invoiced
    FROM finance_invoices
    WHERE status != 'cancelled'
    GROUP BY student_id
) inv ON s.id = inv.student_id
LEFT JOIN (
    SELECT student_id, SUM(amount) AS total_paid
    FROM finance_payments
    GROUP BY student_id
) pay ON s.id = pay.student_id
WHERE s.deleted_at IS NULL;

-- 9b. Student term performance view (aggregated marks per term)
CREATE OR REPLACE VIEW `v_student_performance` AS
SELECT
    s.id AS student_id,
    s.full_name AS student_name,
    c.short_name AS class_name,
    t.name AS term_name,
    ay.name AS academic_year,
    sub.name AS subject_name,
    sub.code AS subject_code,
    m.score,
    m.max_score,
    ROUND((m.score / m.max_score) * 100, 2) AS percentage,
    m.grade,
    m.entered_by,
    u.full_name AS entered_by_name,
    m.entered_at
FROM marks m
JOIN students s ON m.student_id = s.id
JOIN classes c ON m.class_id = c.id
JOIN subjects sub ON m.subject_id = sub.id
JOIN terms t ON m.term_id = t.id
JOIN academic_years ay ON t.academic_year_id = ay.id
LEFT JOIN users u ON m.entered_by = u.id
WHERE s.deleted_at IS NULL;

-- 9c. Student discipline summary view
CREATE OR REPLACE VIEW `v_student_discipline` AS
SELECT
    s.id AS student_id,
    s.full_name AS student_name,
    c.short_name AS class_name,
    COUNT(d.id) AS total_incidents,
    SUM(d.demerit_points) AS total_demerits,
    SUM(CASE WHEN d.status = 'open' THEN 1 ELSE 0 END) AS open_cases,
    SUM(CASE WHEN d.status = 'resolved' THEN 1 ELSE 0 END) AS resolved_cases
FROM students s
JOIN classes c ON s.class_id = c.id
LEFT JOIN discipline d ON s.id = d.student_id
WHERE s.deleted_at IS NULL
GROUP BY s.id, s.full_name, c.short_name;

-- ============================================================================
-- 10. INDEXES FOR COMMON QUERIES
-- ============================================================================

-- Composite indexes for dashboard/report queries
CREATE INDEX idx_dashboard_marks_lookup ON marks(student_id, term_id, subject_id, class_id);
CREATE INDEX idx_dashboard_discipline_lookup ON discipline(student_id, incident_date, status);
CREATE INDEX idx_dashboard_invoices_lookup ON finance_invoices(student_id, term_id, status);
CREATE INDEX idx_dashboard_payments_lookup ON finance_payments(student_id, paid_at);

-- ============================================================================
-- 11. SEED DATA — roles and default admin account
-- ============================================================================

-- Seed roles (system roles with priority levels)
INSERT INTO `roles` (`slug`, `name`, `description`, `is_system`, `priority`) VALUES
('admin',               'Administrator',         'Full system access — manage users, roles, and all data',              1, 1),
('registrar',           'Registrar',             'Student registration, enrollment, and records management',           1, 10),
('director_of_studies', 'Director of Studies',   'Academic oversight — enter/edit marks for all subjects',              1, 20),
('discipline_master',   'Discipline Master',     'Student discipline, conduct records, and observations',              1, 30),
('bursar',              'Bursar',                'Finance management — invoices, payments, fee structure',              1, 40),
('teacher',             'Teacher',               'Enter marks only for assigned subjects/classes per term',            1, 50),
('parent',              'Parent',                'View own children — marks, discipline, finance (read-only)',          1, 60);

-- Seed default permissions
INSERT INTO `permissions` (`slug`, `name`, `group`, `description`) VALUES
-- User management
('users.manage',        'Manage Users',        'users',        'Create, edit, activate/deactivate user accounts'),
('users.view',          'View Users',          'users',        'View user list and profiles'),
-- Student management
('students.create',     'Create Students',     'students',     'Register new students'),
('students.read',       'View Students',       'students',     'View student records'),
('students.update',     'Edit Students',       'students',     'Modify student details'),
('students.delete',     'Delete Students',     'students',     'Soft-delete student records'),
-- Academic (marks)
('marks.create.all',    'Enter All Marks',     'marks',        'Enter marks for any subject/class'),
('marks.create.own',    'Enter Own Marks',     'marks',        'Enter marks only for assigned subject/class'),
('marks.read.all',      'View All Marks',      'marks',        'View marks for any student/subject'),
('marks.read.own',      'View Own Children',   'marks',        'View marks only for own children'),
-- Discipline
('discipline.create',   'Record Discipline',   'discipline',   'Record discipline incidents'),
('discipline.read',     'View Discipline',     'discipline',   'View discipline records'),
('discipline.update',   'Edit Discipline',     'discipline',   'Modify discipline observations'),
('discipline.resolve',  'Resolve Cases',       'discipline',   'Mark discipline cases as resolved'),
-- Finance
('finance.invoices',    'Manage Invoices',     'finance',      'Create, edit, cancel invoices'),
('finance.payments',    'Record Payments',     'finance',      'Record and manage payments'),
('finance.read',        'View Finance',        'finance',      'View financial records'),
-- News/content
('news.create',         'Create News',         'news',         'Write and publish announcements'),
('news.update',         'Edit News',           'news',         'Modify published news'),
('news.delete',         'Delete News',         'news',         'Remove news articles'),
-- Reports & exports
('reports.view',        'View Reports',        'reports',      'Access dashboard and report pages'),
('reports.export',      'Export Data',         'reports',      'Export data to PDF/Excel');

-- Assign permissions to roles
-- Admin: everything
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.slug = 'admin';

-- Registrar: student CRUD + news
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'registrar' AND p.slug IN ('students.create','students.read','students.update','students.delete','news.create','news.update','reports.view');

-- Director of Studies: all marks + reports
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'director_of_studies' AND p.slug IN ('marks.create.all','marks.read.all','reports.view','reports.export');

-- Discipline Master: discipline + reports
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'discipline_master' AND p.slug IN ('discipline.create','discipline.read','discipline.update','discipline.resolve','reports.view');

-- Bursar: finance + reports
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'bursar' AND p.slug IN ('finance.invoices','finance.payments','finance.read','reports.view','reports.export');

-- Teacher: own marks only
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'teacher' AND p.slug IN ('marks.create.own','marks.read.own');

-- Parent: read-only for own children
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.slug = 'parent' AND p.slug IN ('marks.read.own','discipline.read','finance.read');

-- Seed assessment types
INSERT INTO `assessment_types` (`name`, `short_name`, `weight`, `max_score`) VALUES
('End of Term Exam',  'EXAM',   0.60, 100.00),
('Midterm Test',      'TEST',   0.20, 100.00),
('Assignment',        'ASSIGN', 0.10, 100.00),
('Project',           'PROJ',   0.10, 100.00);

-- Seed payment methods
INSERT INTO `payment_methods` (`name`, `short_name`) VALUES
('Cash',              'CASH'),
('Mobile Money',      'MOMO'),
('Bank Transfer',     'BANK'),
('Cheque',            'CHEQUE');

-- Seed discipline types
INSERT INTO `discipline_types` (`name`, `category`, `default_points`) VALUES
('Late coming',                 'minor',    1),
('Uniform violation',           'minor',    1),
('Missing homework',            'minor',    2),
('Disruptive behavior',         'major',    3),
('Dishonesty / Cheating',       'major',    5),
('Bullying',                    'major',    5),
('Vandalism',                   'major',    5),
('Fighting',                    'critical', 8),
('Theft',                       'critical', 10),
('Substance abuse',             'critical', 10);

-- ============================================================================
-- 12. TRIGGERS — automatic audit logging
-- ============================================================================

-- Trigger: Auto-log marks changes
DELIMITER //
CREATE TRIGGER `trg_marks_insert` AFTER INSERT ON `marks` FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`user_id`, `action`, `entity`, `entity_id`, `new_values`, `description`)
    VALUES (NEW.entered_by, 'create', 'marks', NEW.id,
            JSON_OBJECT('student_id', NEW.student_id, 'subject_id', NEW.subject_id, 'score', NEW.score, 'term_id', NEW.term_id),
            CONCAT('Marks entered: student=', NEW.student_id, ', subject=', NEW.subject_id, ', score=', NEW.score));
END//

CREATE TRIGGER `trg_marks_update` AFTER UPDATE ON `marks` FOR EACH ROW
BEGIN
    IF OLD.score != NEW.score OR OLD.grade != NEW.grade THEN
        INSERT INTO `audit_log` (`user_id`, `action`, `entity`, `entity_id`, `old_values`, `new_values`, `description`)
        VALUES (NEW.updated_by, 'update', 'marks', NEW.id,
                JSON_OBJECT('score', OLD.score, 'grade', OLD.grade),
                JSON_OBJECT('score', NEW.score, 'grade', NEW.grade),
                CONCAT('Marks updated: student=', NEW.student_id, ', subject=', NEW.subject_id));
    END IF;
END//

-- Trigger: Auto-log invoice changes
CREATE TRIGGER `trg_invoice_insert` AFTER INSERT ON `finance_invoices` FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`user_id`, `action`, `entity`, `entity_id`, `new_values`, `description`)
    VALUES (NEW.created_by, 'create', 'finance_invoices', NEW.id,
            JSON_OBJECT('student_id', NEW.student_id, 'amount', NEW.amount, 'status', NEW.status),
            CONCAT('Invoice created: ', NEW.invoice_number, ', amount=', NEW.amount));
END//

-- Trigger: Auto-log payments
CREATE TRIGGER `trg_payment_insert` AFTER INSERT ON `finance_payments` FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`user_id`, `action`, `entity`, `entity_id`, `new_values`, `description`)
    VALUES (NEW.recorded_by, 'create', 'finance_payments', NEW.id,
            JSON_OBJECT('student_id', NEW.student_id, 'amount', NEW.amount, 'method', NEW.payment_method_id),
            CONCAT('Payment recorded: receipt=', NEW.receipt_number, ', amount=', NEW.amount));
END//

-- Trigger: Auto-log discipline records
CREATE TRIGGER `trg_discipline_insert` AFTER INSERT ON `discipline` FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`user_id`, `action`, `entity`, `entity_id`, `new_values`, `description`)
    VALUES (NEW.recorded_by, 'create', 'discipline', NEW.id,
            JSON_OBJECT('student_id', NEW.student_id, 'demerit_points', NEW.demerit_points, 'status', NEW.status),
            CONCAT('Discipline recorded: student=', NEW.student_id, ', points=', NEW.demerit_points));
END//
DELIMITER ;

-- ============================================================================
-- 13. RE-ENABLE FOREIGN KEY CHECKS
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF SCHEMA v2.0.0
-- ============================================================================
-- 
-- CHANGELOG:
-- v2.0.0 — 2025-06-06
--   • Complete normalization: marks, discipline, finance separated from students
--   • Granular RBAC with roles, permissions, and role_permissions tables
--   • Proper teacher_subject_class scoping for per-teacher access
--   • Academic year/term management with current-term tracking
--   • Parent-student many-to-many relationship
--   • Computed finance balance (invoices − payments) via view
--   • Student performance and discipline summary views
--   • Full audit trail with triggers on marks, finance, discipline
--   • Soft deletes on critical tables
--   • Schema versioning for migrations
--   • Seed data: roles, permissions, assessment types, payment methods, discipline types
--   • Comprehensive indexing strategy
--   • Full-text search indexes for students and news
--   • Session management with expiry
--   • Password reset support
-- 
-- UPGRADE FROM v1 (schema.sql):
--   1. Run this file to create the new normalized schema
--   2. Run a migration script to transfer data from the flat 'students' table
--      into the new 'students', 'marks', 'discipline', 'finance_invoices' tables
--   3. Create user accounts from the existing admin credentials
--   4. Assign parent accounts from student parent_phone/pin data
-- 
-- ============================================================================