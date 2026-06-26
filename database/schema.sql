-- ============================================================
-- Moduł Serwis — Schemat bazy danych
-- ============================================================
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `label` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `login` VARCHAR(80) NOT NULL,
    `email` VARCHAR(200) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_login` (`login`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role_id`),
    KEY `idx_users_active` (`is_active`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `production_lines` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `prefix` VARCHAR(10) NOT NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_line_prefix` (`prefix`),
    KEY `idx_lines_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `line_subsystems` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `production_line_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sub_line` (`production_line_id`),
    KEY `idx_sub_active` (`is_active`),
    CONSTRAINT `fk_sub_line` FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_counters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `production_line_id` INT UNSIGNED NOT NULL,
    `year` SMALLINT UNSIGNED NOT NULL,
    `counter` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_counter_line_year` (`production_line_id`, `year`),
    CONSTRAINT `fk_counter_line` FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failure_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `label` VARCHAR(150) NOT NULL,
    `color` VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cat_name` (`name`),
    KEY `idx_cat_order` (`sort_order`),
    KEY `idx_cat_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failure_dictionary` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dict_category` (`category_id`),
    KEY `idx_dict_active` (`is_active`),
    CONSTRAINT `fk_dict_category` FOREIGN KEY (`category_id`) REFERENCES `failure_categories` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failure_statuses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `label` VARCHAR(150) NOT NULL,
    `color` VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_initial` TINYINT(1) NOT NULL DEFAULT 0,
    `is_final` TINYINT(1) NOT NULL DEFAULT 0,
    `is_observed` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status_order` (`sort_order`),
    KEY `idx_status_initial` (`is_initial`),
    KEY `idx_status_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failure_symptoms` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_symptoms_active` (`is_active`),
    KEY `idx_symptoms_order` (`sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failures` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_number` VARCHAR(30) NOT NULL,
    `production_line_id` INT UNSIGNED NOT NULL,
    `subsystem_id` INT UNSIGNED NULL,
    `symptom_id` INT UNSIGNED NULL,
    `other_symptom` TINYINT(1) NOT NULL DEFAULT 0,
    `category_id` INT UNSIGNED NULL,
    `status_id` INT UNSIGNED NOT NULL,
    `dictionary_item_id` INT UNSIGNED NULL,
    `other_failure` TINYINT(1) NOT NULL DEFAULT 0,
    `mechanic_note` TEXT NULL,
    `reporter_acronym` VARCHAR(10) NULL,
    `reporter_name` VARCHAR(150) NULL,
    `reporter_user_id` INT UNSIGNED NULL,
    `description` TEXT NULL,
    `closed_at` DATETIME NULL,
    `observation_started_at` DATETIME NULL,
    `observation_until` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ticket` (`ticket_number`),
    KEY `idx_failures_line` (`production_line_id`),
    KEY `idx_failures_subsystem` (`subsystem_id`),
    KEY `idx_failures_symptom` (`symptom_id`),
    KEY `idx_failures_category` (`category_id`),
    KEY `idx_failures_status` (`status_id`),
    KEY `idx_failures_reporter_user` (`reporter_user_id`),
    KEY `idx_failures_created` (`created_at`),
    KEY `idx_failures_closed` (`closed_at`),
    CONSTRAINT `fk_failures_line` FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`),
    CONSTRAINT `fk_failures_subsystem` FOREIGN KEY (`subsystem_id`) REFERENCES `line_subsystems` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_failures_symptom` FOREIGN KEY (`symptom_id`) REFERENCES `failure_symptoms` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_failures_category` FOREIGN KEY (`category_id`) REFERENCES `failure_categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_failures_status` FOREIGN KEY (`status_id`) REFERENCES `failure_statuses` (`id`),
    CONSTRAINT `fk_failures_dict` FOREIGN KEY (`dictionary_item_id`) REFERENCES `failure_dictionary` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_failures_reporter_user` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failure_assignments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `failure_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `user_name` VARCHAR(150) NOT NULL,
    `is_first` TINYINT(1) NOT NULL DEFAULT 0,
    `added_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_assignment` (`failure_id`, `user_id`),
    KEY `idx_assign_failure` (`failure_id`),
    KEY `idx_assign_user` (`user_id`),
    CONSTRAINT `fk_assign_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assign_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assign_added` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failure_comments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `failure_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `author` VARCHAR(150) NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_comments_failure` (`failure_id`),
    KEY `idx_comments_user` (`user_id`),
    CONSTRAINT `fk_comments_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failure_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `failure_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `actor_name` VARCHAR(150) NOT NULL DEFAULT 'System',
    `action` VARCHAR(50) NOT NULL,
    `old_status_id` INT UNSIGNED NULL,
    `new_status_id` INT UNSIGNED NULL,
    `note` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_history_failure` (`failure_id`),
    KEY `idx_history_user` (`user_id`),
    CONSTRAINT `fk_history_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_history_old_status` FOREIGN KEY (`old_status_id`) REFERENCES `failure_statuses` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_history_new_status` FOREIGN KEY (`new_status_id`) REFERENCES `failure_statuses` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Uwagi do obserwacji awarii
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_observation_notes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `failure_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `user_name` VARCHAR(150) NOT NULL,
    `note` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_obs_notes_failure` (`failure_id`),
    KEY `idx_obs_notes_user` (`user_id`),
    CONSTRAINT `fk_obs_notes_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_obs_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Szablony DUR
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `maintenance_templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `review_type` ENUM(
        'weekly',
        'monthly',
        'quarterly',
        'biannual',
        'annual',
        'ad_hoc',
        'periodic'
    ) NOT NULL DEFAULT 'monthly',
    `description` TEXT NULL,
    `checklist` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_tmpl_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Harmonogram DUR
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `maintenance_schedules` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `production_line_id` INT UNSIGNED NOT NULL,
    `template_id` INT UNSIGNED NULL,
    `review_type` ENUM(
        'weekly',
        'monthly',
        'quarterly',
        'biannual',
        'annual',
        'ad_hoc',
        'periodic'
    ) NOT NULL,
    `interval_days` INT UNSIGNED NOT NULL DEFAULT 30,
    `next_due_date` DATE NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sched_line` (`production_line_id`),
    KEY `idx_sched_due` (`next_due_date`),
    CONSTRAINT `fk_sched_line` FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`),
    CONSTRAINT `fk_sched_template` FOREIGN KEY (`template_id`) REFERENCES `maintenance_templates` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Raporty DUR
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `maintenance_reviews` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `production_line_id` INT UNSIGNED NOT NULL,
    `subsystem_id` INT UNSIGNED NULL,
    `template_id` INT UNSIGNED NULL,
    `schedule_id` INT UNSIGNED NULL,
    `performed_by` INT UNSIGNED NOT NULL,
    `review_type` ENUM(
        'weekly',
        'monthly',
        'quarterly',
        'biannual',
        'annual',
        'ad_hoc',
        'periodic'
    ) NOT NULL DEFAULT 'monthly',
    `review_date` DATE NOT NULL,
    `duration_minutes` INT UNSIGNED NULL,
    `activities` TEXT NOT NULL,
    `parts_used` TEXT NULL,
    `notes` TEXT NULL,
    `status` ENUM(
        'completed',
        'partial',
        'interrupted'
    ) NOT NULL DEFAULT 'completed',
    `next_review_date` DATE NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rev_line` (`production_line_id`),
    KEY `idx_rev_date` (`review_date`),
    KEY `idx_rev_performer` (`performed_by`),
    CONSTRAINT `fk_rev_line` FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`),
    CONSTRAINT `fk_rev_subsystem` FOREIGN KEY (`subsystem_id`) REFERENCES `line_subsystems` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rev_template` FOREIGN KEY (`template_id`) REFERENCES `maintenance_templates` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rev_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rev_performer` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Uwagi do harmonogramów DUR
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `schedule_notes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `schedule_id` INT UNSIGNED NOT NULL,
    `review_id` INT UNSIGNED NULL COMMENT 'Raport DUR po wykonaniu — archiwum uwag',
    `user_id` INT UNSIGNED NOT NULL,
    `user_name` VARCHAR(150) NOT NULL,
    `note` TEXT NOT NULL,
    `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sched_notes_schedule` (`schedule_id`),
    KEY `idx_sched_notes_review` (`review_id`),
    KEY `idx_sched_notes_user` (`user_id`),
    KEY `idx_sched_notes_archived` (`is_archived`),
    CONSTRAINT `fk_sched_notes_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sched_notes_review` FOREIGN KEY (`review_id`) REFERENCES `maintenance_reviews` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sched_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `skey` VARCHAR(100) NOT NULL,
    `svalue` TEXT NULL,
    `label` VARCHAR(200) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`skey`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Kategorie części zamiennych
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `spare_part_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_spc_name` (`name`),
    KEY `idx_spc_order` (`sort_order`),
    KEY `idx_spc_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Części zamienne użyte w zgłoszeniu awarii
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_spare_parts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `failure_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `part_name` VARCHAR(255) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `added_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_fsp_failure` (`failure_id`),
    KEY `idx_fsp_category` (`category_id`),
    CONSTRAINT `fk_fsp_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fsp_category` FOREIGN KEY (`category_id`) REFERENCES `spare_part_categories` (`id`),
    CONSTRAINT `fk_fsp_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Zdjęcia do zgłoszeń awarii
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_photos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `failure_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `username` VARCHAR(80) NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `path` VARCHAR(500) NOT NULL COMMENT 'foto/{username}/{ticket_number}/{filename}',
    `filesize` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_public` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=tylko uprawnieni, 1=wszyscy',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_photos_failure` (`failure_id`),
    KEY `idx_photos_user` (`user_id`),
    KEY `idx_photos_public` (`is_public`),
    CONSTRAINT `fk_photos_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_photos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `photo_upload_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` VARCHAR(64) NOT NULL,
    `failure_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token` (`token`),
    KEY `idx_token_failure` (`failure_id`),
    CONSTRAINT `fk_token_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- Części zamienne użyte w przeglądach DUR
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `dur_spare_parts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `part_name` VARCHAR(255) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `added_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dsp_review` (`review_id`),
    KEY `idx_dsp_category` (`category_id`),
    CONSTRAINT `fk_dsp_review` FOREIGN KEY (`review_id`) REFERENCES `maintenance_reviews` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dsp_category` FOREIGN KEY (`category_id`) REFERENCES `spare_part_categories` (`id`),
    CONSTRAINT `fk_dsp_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;