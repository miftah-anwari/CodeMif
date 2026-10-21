-- ============================================================
-- CodeNexa - schema.sql
-- Database : codenexa_db
-- Engine   : InnoDB | Charset: utf8mb4
-- Cara pakai: impor lewat phpMyAdmin, lalu impor seed.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `codenexa_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `codenexa_db`;

SET NAMES utf8mb4;

-- 1. categories ------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) NULL,
  `icon`        VARCHAR(50)  NOT NULL DEFAULT 'code',
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schools -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `schools` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(160) NOT NULL,
  `status`     ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_schools_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `school_classes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_id`  INT UNSIGNED NOT NULL,
  `name`       VARCHAR(80) NOT NULL,
  `status`     ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_school_classes_school_name` (`school_id`, `name`),
  KEY `idx_school_classes_school_status` (`school_id`, `status`),
  CONSTRAINT `fk_school_classes_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. users -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`           VARCHAR(120) NOT NULL,
  `username`            VARCHAR(40)  NOT NULL,
  `email`               VARCHAR(190) NOT NULL,
  `password_hash`       VARCHAR(255) NOT NULL,
  `role`                ENUM('user','admin','super_admin','school_admin') NOT NULL DEFAULT 'user',
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
  `school_id`           INT UNSIGNED NULL,
  `class_id`            INT UNSIGNED NULL,
  `grade_level`          ENUM('X','XI','XII') NULL,
  `status`              ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `avatar`              VARCHAR(255) NULL,
  `bio`                 VARCHAR(500) NULL,
  `theme`               ENUM('dark','light') NOT NULL DEFAULT 'dark',
  `email_notifications` TINYINT(1)   NOT NULL DEFAULT 1,
  `daily_goal_minutes`  SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  `xp`                  INT UNSIGNED NOT NULL DEFAULT 0,
  `level`               SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `streak_current`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `streak_longest`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `last_activity_date`  DATE NULL,
  `last_login_at`       DATETIME NULL,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role_status` (`role`, `status`),
  KEY `idx_users_xp` (`xp`),
  KEY `idx_users_school_role` (`school_id`, `role`),
  KEY `idx_users_class_role` (`class_id`, `role`),
  CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_school_class` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. courses ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`       INT UNSIGNED NOT NULL,
  `title`             VARCHAR(160) NOT NULL,
  `slug`              VARCHAR(180) NOT NULL,
  `description`       TEXT NOT NULL,
  `thumbnail`         VARCHAR(255) NULL,
  `language`          VARCHAR(40)  NOT NULL DEFAULT 'Umum',
  `level`             ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `estimated_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`        INT UNSIGNED NULL,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_courses_slug` (`slug`),
  KEY `idx_courses_category` (`category_id`),
  KEY `idx_courses_level_active` (`level`, `is_active`),
  KEY `idx_courses_title` (`title`),
  CONSTRAINT `fk_courses_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_courses_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `school_course_visibility` (
  `school_id`  INT UNSIGNED NOT NULL,
  `course_id`  INT UNSIGNED NOT NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`school_id`, `course_id`),
  KEY `idx_school_course_visibility_course` (`course_id`),
  CONSTRAINT `fk_school_course_visibility_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_school_course_visibility_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. modules ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `modules` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id`   INT UNSIGNED NOT NULL,
  `title`       VARCHAR(160) NOT NULL,
  `description` VARCHAR(255) NULL,
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_modules_course_order` (`course_id`, `sort_order`),
  CONSTRAINT `fk_modules_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. lessons ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `lessons` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id`        INT UNSIGNED NOT NULL,
  `title`            VARCHAR(160) NOT NULL,
  `slug`             VARCHAR(180) NOT NULL,
  `content_theory`   MEDIUMTEXT NOT NULL,
  `code_example`     MEDIUMTEXT NULL,
  `code_language`    VARCHAR(30) NULL,
  `code_explanation` TEXT NULL,
  `exercise`         TEXT NULL,
  `project_task`     TEXT NULL,
  `duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  `xp_reward`        SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  `sort_order`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lessons_module_slug` (`module_id`, `slug`),
  KEY `idx_lessons_module_order` (`module_id`, `sort_order`),
  KEY `idx_lessons_title` (`title`),
  CONSTRAINT `fk_lessons_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. enrollments -----------------------------------------------
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `course_id`      INT UNSIGNED NOT NULL,
  `status`         ENUM('active','completed') NOT NULL DEFAULT 'active',
  `last_lesson_id` INT UNSIGNED NULL,
  `last_activity_at` DATETIME NULL,
  `enrolled_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollments_user_course` (`user_id`, `course_id`),
  KEY `idx_enrollments_course` (`course_id`),
  CONSTRAINT `fk_enrollments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollments_last_lesson` FOREIGN KEY (`last_lesson_id`) REFERENCES `lessons` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. lesson_progress -------------------------------------------
CREATE TABLE IF NOT EXISTS `lesson_progress` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`            INT UNSIGNED NOT NULL,
  `lesson_id`          INT UNSIGNED NOT NULL,
  `status`             ENUM('in_progress','pending','completed','rejected') NOT NULL DEFAULT 'in_progress',
  `time_spent_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `xp_awarded`         TINYINT(1) NOT NULL DEFAULT 0,
  `submitted_at`       DATETIME NULL,
  `evidence_image`     VARCHAR(255) NULL,
  `submitted_code`     MEDIUMTEXT NULL,
  `reviewed_by`        INT UNSIGNED NULL,
  `reviewed_at`        DATETIME NULL,
  `review_note`        VARCHAR(500) NULL,
  `started_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at`       DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_progress_user_lesson` (`user_id`, `lesson_id`),
  KEY `idx_progress_lesson` (`lesson_id`),
  KEY `idx_progress_user_status` (`user_id`, `status`),
  KEY `idx_progress_review_queue` (`status`, `submitted_at`),
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_progress_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_progress_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. quizzes ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id`   INT UNSIGNED NOT NULL,
  `lesson_id`   INT UNSIGNED NULL,
  `title`       VARCHAR(160) NOT NULL,
  `description` VARCHAR(255) NULL,
  `difficulty`  ENUM('easy','medium','hard') NOT NULL DEFAULT 'easy',
  `pass_score`  TINYINT UNSIGNED NOT NULL DEFAULT 70,
  `xp_reward`   SMALLINT UNSIGNED NOT NULL DEFAULT 50,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quizzes_course` (`course_id`),
  KEY `idx_quizzes_lesson` (`lesson_id`),
  CONSTRAINT `fk_quizzes_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quizzes_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. questions -------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quiz_id`        INT UNSIGNED NOT NULL,
  `type`           ENUM('multiple_choice','true_false','code_completion','debugging') NOT NULL DEFAULT 'multiple_choice',
  `question_text`  TEXT NOT NULL,
  `code_snippet`   TEXT NULL,
  `correct_answer` VARCHAR(255) NULL COMMENT 'Hanya untuk code_completion',
  `explanation`    TEXT NULL,
  `points`         SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  `sort_order`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_questions_quiz_order` (`quiz_id`, `sort_order`),
  CONSTRAINT `fk_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. question_options -----------------------------------------
CREATE TABLE IF NOT EXISTS `question_options` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED NOT NULL,
  `option_text` VARCHAR(500) NOT NULL,
  `is_correct`  TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_options_question` (`question_id`, `sort_order`),
  CONSTRAINT `fk_options_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. quiz_attempts --------------------------------------------
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `quiz_id`     INT UNSIGNED NOT NULL,
  `status`      ENUM('in_progress','submitted') NOT NULL DEFAULT 'in_progress',
  `score`       INT UNSIGNED NOT NULL DEFAULT 0,
  `max_score`   INT UNSIGNED NOT NULL DEFAULT 0,
  `percentage`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `passed`      TINYINT(1) NOT NULL DEFAULT 0,
  `xp_earned`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `started_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_user_quiz` (`user_id`, `quiz_id`),
  KEY `idx_attempts_quiz` (`quiz_id`),
  CONSTRAINT `fk_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attempts_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. user_answers ---------------------------------------------
CREATE TABLE IF NOT EXISTS `user_answers` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id`    INT UNSIGNED NOT NULL,
  `question_id`   INT UNSIGNED NOT NULL,
  `option_id`     INT UNSIGNED NULL,
  `answer_text`   VARCHAR(500) NULL,
  `is_correct`    TINYINT(1) NOT NULL DEFAULT 0,
  `points_earned` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `answered_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_answers_attempt_question` (`attempt_id`, `question_id`),
  KEY `idx_answers_question` (`question_id`),
  KEY `idx_answers_option` (`option_id`),
  CONSTRAINT `fk_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_answers_option` FOREIGN KEY (`option_id`) REFERENCES `question_options` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. user_projects --------------------------------------------
CREATE TABLE IF NOT EXISTS `user_projects` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `html_code`  MEDIUMTEXT NULL,
  `css_code`   MEDIUMTEXT NULL,
  `js_code`    MEDIUMTEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_projects_user_updated` (`user_id`, `updated_at`),
  CONSTRAINT `fk_projects_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. achievements ---------------------------------------------
CREATE TABLE IF NOT EXISTS `achievements` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`           VARCHAR(50)  NOT NULL,
  `name`           VARCHAR(100) NOT NULL,
  `description`    VARCHAR(255) NOT NULL,
  `icon`           VARCHAR(50)  NOT NULL DEFAULT 'award',
  `xp_reward`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `criteria_type`  ENUM('lessons_completed','quizzes_passed','perfect_quiz','streak_days','projects_saved','courses_completed') NOT NULL,
  `criteria_value` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_achievements_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. user_achievements ----------------------------------------
CREATE TABLE IF NOT EXISTS `user_achievements` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `achievement_id` INT UNSIGNED NOT NULL,
  `earned_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_achievement` (`user_id`, `achievement_id`),
  KEY `idx_user_achievements_achievement` (`achievement_id`),
  CONSTRAINT `fk_ua_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ua_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. learning_activity ----------------------------------------
CREATE TABLE IF NOT EXISTS `learning_activity` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED NOT NULL,
  `activity_date`     DATE NOT NULL,
  `minutes_spent`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `lessons_completed` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `xp_earned`         INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_user_date` (`user_id`, `activity_date`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. activity_log ---------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `type`        VARCHAR(40)  NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `ref_type`    VARCHAR(40)  NULL,
  `ref_id`      INT UNSIGNED NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user_created` (`user_id`, `created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. password_resets ------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 dari token; token asli tidak disimpan',
  `expires_at` DATETIME NOT NULL,
  `used_at`    DATETIME NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resets_token` (`token_hash`),
  KEY `idx_resets_user` (`user_id`),
  KEY `idx_resets_expires` (`expires_at`),
  CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. notifications --------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `message`    VARCHAR(500) NOT NULL,
  `type`       ENUM('info','success','achievement','warning') NOT NULL DEFAULT 'info',
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`, `is_read`, `created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. login_attempts -------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier`    VARCHAR(190) NOT NULL COMMENT 'email atau username yang dicoba',
  `ip_address`    VARCHAR(45)  NOT NULL,
  `was_successful` TINYINT(1)  NOT NULL DEFAULT 0,
  `attempted_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_identifier_time` (`identifier`, `attempted_at`),
  KEY `idx_attempts_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. announcements --------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(160) NOT NULL,
  `body`         TEXT NOT NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`   INT UNSIGNED NULL,
  `published_at` DATETIME NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announcements_active` (`is_active`, `published_at`),
  CONSTRAINT `fk_announcements_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
