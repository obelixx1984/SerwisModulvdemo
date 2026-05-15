-- ============================================================
-- Moduł Serwis v2 — Schemat bazy danych
-- Kompatybilny z MySQL 5.7+ / MariaDB 10.3+
-- ZMIANA: dodano kolumnę other_symptom do tabeli failures
--         (scalono z migration_other_symptom.sql)
--         Pliki install.sql i migration_other_symptom.sql
--         zostały usunięte — ten plik jest jedynym źródłem schematu.
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ────────────────────────────────────────────────────────────
-- role systemowe
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(50)  NOT NULL COMMENT 'admin | mechanic | operator',
    `label`      VARCHAR(100) NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- użytkownicy
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `role_id`       INT UNSIGNED  NOT NULL,
    `name`          VARCHAR(150)  NOT NULL,
    `login`         VARCHAR(80)   NOT NULL COMMENT 'Używany do logowania zamiast e-mail',
    `email`         VARCHAR(200)  NOT NULL,
    `password_hash` VARCHAR(255)  NOT NULL,
    `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
    `last_login_at` DATETIME      NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_login` (`login`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role`   (`role_id`),
    KEY `idx_users_active` (`is_active`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- linie produkcyjne
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `production_lines` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150)  NOT NULL,
    `prefix`      VARCHAR(10)   NOT NULL COMMENT 'Prefix numerów zgłoszeń, np. A1, L2, P3',
    `description` TEXT          NULL,
    `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_line_prefix` (`prefix`),
    KEY `idx_lines_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- podzespoły linii (opcjonalne)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `line_subsystems` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `production_line_id`  INT UNSIGNED NOT NULL,
    `name`                VARCHAR(150) NOT NULL,
    `sort_order`          INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`           TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sub_line`   (`production_line_id`),
    KEY `idx_sub_active` (`is_active`),
    CONSTRAINT `fk_sub_line` FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- liczniki zgłoszeń per linia per rok
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ticket_counters` (
    `id`                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `production_line_id`  INT UNSIGNED      NOT NULL,
    `year`                SMALLINT UNSIGNED NOT NULL,
    `counter`             INT UNSIGNED      NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_counter_line_year` (`production_line_id`, `year`),
    CONSTRAINT `fk_counter_line` FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- kategorie awarii
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_categories` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL COMMENT 'Slug techniczny, np. electrical',
    `label`      VARCHAR(150) NOT NULL COMMENT 'Etykieta wyświetlana, np. Elektryczna',
    `color`      VARCHAR(7)   NOT NULL DEFAULT '#6c757d',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cat_name`  (`name`),
    KEY `idx_cat_order`  (`sort_order`),
    KEY `idx_cat_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- słownik usterek
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_dictionary` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `title`       VARCHAR(255) NOT NULL,
    `description` TEXT         NULL,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dict_category` (`category_id`),
    KEY `idx_dict_active`   (`is_active`),
    CONSTRAINT `fk_dict_category` FOREIGN KEY (`category_id`) REFERENCES `failure_categories` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- statusy zgłoszeń
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_statuses` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `label`      VARCHAR(150) NOT NULL,
    `color`      VARCHAR(7)   NOT NULL DEFAULT '#6c757d',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_initial` TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Status dla nowych zgłoszeń',
    `is_final`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Status końcowy (zamknięcia)',
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status_order`   (`sort_order`),
    KEY `idx_status_initial` (`is_initial`),
    KEY `idx_status_active`  (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- objawy awarii — wybierane przez zgłaszającego
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_symptoms` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(200) NOT NULL COMMENT 'Nazwa objawu widoczna dla zgłaszającego',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_symptoms_active` (`is_active`),
    KEY `idx_symptoms_order`  (`sort_order`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- zgłoszenia awarii
-- ZMIANA: dodano kolumnę other_symptom po symptom_id
--         (wcześniej w migration_other_symptom.sql)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failures` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_number`       VARCHAR(30)  NOT NULL COMMENT 'Format: 0001/PREFIX/ROK',
    `production_line_id`  INT UNSIGNED NOT NULL,
    `subsystem_id`        INT UNSIGNED NULL  COMMENT 'Opcjonalny podzespół linii',
    `symptom_id`          INT UNSIGNED NULL  COMMENT 'Objaw wybrany przez zgłaszającego',
    `other_symptom`       TINYINT(1)   NOT NULL DEFAULT 0
                          COMMENT '1 = zgłaszający wybrał "Inne objawy" — opis w polu description',
    `category_id`         INT UNSIGNED NULL  COMMENT 'Rodzaj awarii — ustawia mechanik',
    `status_id`           INT UNSIGNED NOT NULL,
    `dictionary_item_id`  INT UNSIGNED NULL,
    `other_failure`       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = mechanik zaznaczył Inna usterka',
    `mechanic_note`       TEXT         NULL   COMMENT 'Notatka mechanika przy Inna usterka',
    `reporter_acronym`    VARCHAR(10)  NULL   COMMENT 'Login/akronim zgłaszającego',
    `reporter_name`       VARCHAR(150) NULL   COMMENT 'Pełna nazwa zgłaszającego',
    `reporter_user_id`    INT UNSIGNED NULL   COMMENT 'ID konta zgłaszającego (users.id)',
    `description`         TEXT         NULL,
    `closed_at`           DATETIME     NULL   COMMENT 'Wypełniany przy statusie końcowym',
    `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ticket`                  (`ticket_number`),
    KEY `idx_failures_line`                 (`production_line_id`),
    KEY `idx_failures_subsystem`            (`subsystem_id`),
    KEY `idx_failures_symptom`              (`symptom_id`),
    KEY `idx_failures_category`             (`category_id`),
    KEY `idx_failures_status`               (`status_id`),
    KEY `idx_failures_reporter_user`        (`reporter_user_id`),
    KEY `idx_failures_created`              (`created_at`),
    KEY `idx_failures_closed`               (`closed_at`),
    CONSTRAINT `fk_failures_line`          FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`),
    CONSTRAINT `fk_failures_subsystem`     FOREIGN KEY (`subsystem_id`)       REFERENCES `line_subsystems` (`id`)      ON DELETE SET NULL,
    CONSTRAINT `fk_failures_symptom`       FOREIGN KEY (`symptom_id`)         REFERENCES `failure_symptoms` (`id`)     ON DELETE SET NULL,
    CONSTRAINT `fk_failures_category`      FOREIGN KEY (`category_id`)        REFERENCES `failure_categories` (`id`)   ON DELETE SET NULL,
    CONSTRAINT `fk_failures_status`        FOREIGN KEY (`status_id`)          REFERENCES `failure_statuses` (`id`),
    CONSTRAINT `fk_failures_dict`          FOREIGN KEY (`dictionary_item_id`) REFERENCES `failure_dictionary` (`id`)   ON DELETE SET NULL,
    CONSTRAINT `fk_failures_reporter_user` FOREIGN KEY (`reporter_user_id`)   REFERENCES `users` (`id`)                ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- komentarze do zgłoszeń
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_comments` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `failure_id` INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NULL   COMMENT 'NULL jeśli komentarz systemowy',
    `author`     VARCHAR(150) NOT NULL COMMENT 'Wyświetlana nazwa autora',
    `comment`    TEXT         NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_comments_failure` (`failure_id`),
    KEY `idx_comments_user`    (`user_id`),
    CONSTRAINT `fk_comments_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- historia zdarzeń zgłoszeń
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `failure_history` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `failure_id`    INT UNSIGNED NOT NULL,
    `user_id`       INT UNSIGNED NULL,
    `actor_name`    VARCHAR(150) NOT NULL DEFAULT 'System' COMMENT 'Wyświetlana nazwa aktora',
    `action`        VARCHAR(50)  NOT NULL COMMENT 'created|status_changed|comment_added|edited',
    `old_status_id` INT UNSIGNED NULL,
    `new_status_id` INT UNSIGNED NULL,
    `note`          TEXT         NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_history_failure` (`failure_id`),
    KEY `idx_history_user`    (`user_id`),
    CONSTRAINT `fk_history_failure`    FOREIGN KEY (`failure_id`)    REFERENCES `failures` (`id`)         ON DELETE CASCADE,
    CONSTRAINT `fk_history_user`       FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`)            ON DELETE SET NULL,
    CONSTRAINT `fk_history_old_status` FOREIGN KEY (`old_status_id`) REFERENCES `failure_statuses` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_history_new_status` FOREIGN KEY (`new_status_id`) REFERENCES `failure_statuses` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- szablony przeglądów DUR
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `maintenance_templates` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `review_type` ENUM(
        'weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc'
    ) NOT NULL DEFAULT 'monthly',
    `description` TEXT         NULL,
    `checklist`   TEXT         NULL COMMENT 'Jedna czynność per linia tekstu',
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_by`  INT UNSIGNED NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_tmpl_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- harmonogram przeglądów DUR
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `maintenance_schedules` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `production_line_id`  INT UNSIGNED NOT NULL,
    `template_id`         INT UNSIGNED NULL,
    `review_type`         ENUM(
        'weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc'
    ) NOT NULL,
    `interval_days`  INT UNSIGNED NOT NULL DEFAULT 30,
    `next_due_date`  DATE         NULL,
    `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sched_line` (`production_line_id`),
    KEY `idx_sched_due`  (`next_due_date`),
    CONSTRAINT `fk_sched_line`     FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`),
    CONSTRAINT `fk_sched_template` FOREIGN KEY (`template_id`)        REFERENCES `maintenance_templates` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- raporty z wykonanych przeglądów DUR
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `maintenance_reviews` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `production_line_id`  INT UNSIGNED NOT NULL,
    `subsystem_id`        INT UNSIGNED NULL,
    `template_id`         INT UNSIGNED NULL,
    `schedule_id`         INT UNSIGNED NULL,
    `performed_by`        INT UNSIGNED NOT NULL,
    `review_type`         ENUM(
        'weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc'
    ) NOT NULL,
    `review_date`         DATE         NOT NULL,
    `duration_minutes`    INT UNSIGNED NULL,
    `activities`          TEXT         NOT NULL,
    `parts_used`          TEXT         NULL,
    `notes`               TEXT         NULL,
    `status`              ENUM('completed', 'partial', 'interrupted') NOT NULL DEFAULT 'completed',
    `next_review_date`    DATE         NULL,
    `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rev_line`     (`production_line_id`),
    KEY `idx_rev_date`     (`review_date`),
    KEY `idx_rev_type`     (`review_type`),
    KEY `idx_rev_performer`(`performed_by`),
    CONSTRAINT `fk_rev_line`      FOREIGN KEY (`production_line_id`) REFERENCES `production_lines` (`id`),
    CONSTRAINT `fk_rev_subsystem` FOREIGN KEY (`subsystem_id`)       REFERENCES `line_subsystems` (`id`)      ON DELETE SET NULL,
    CONSTRAINT `fk_rev_template`  FOREIGN KEY (`template_id`)        REFERENCES `maintenance_templates` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rev_schedule`  FOREIGN KEY (`schedule_id`)        REFERENCES `maintenance_schedules` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rev_performer` FOREIGN KEY (`performed_by`)       REFERENCES `users` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- ustawienia systemowe
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `skey`       VARCHAR(100)  NOT NULL,
    `svalue`     TEXT          NULL,
    `label`      VARCHAR(200)  NULL,
    `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`skey`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
