USE `codenexa_db`;

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

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='class_id') = 0,
  'ALTER TABLE `users` ADD COLUMN `class_id` INT UNSIGNED NULL AFTER `school_id`',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='users' AND index_name='idx_users_class_role') = 0,
  'ALTER TABLE `users` ADD KEY `idx_users_class_role` (`class_id`, `role`)',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='users' AND constraint_name='fk_users_school_class') = 0,
  'ALTER TABLE `users` ADD CONSTRAINT `fk_users_school_class` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;
