USE `codenexa_db`;

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
