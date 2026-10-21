USE `codenexa_db`;

ALTER TABLE `lesson_progress`
  MODIFY `status` ENUM('in_progress','pending','completed','rejected') NOT NULL DEFAULT 'in_progress';

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='lesson_progress' AND column_name='submitted_at') = 0,
  'ALTER TABLE `lesson_progress` ADD COLUMN `submitted_at` DATETIME NULL AFTER `xp_awarded`',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='lesson_progress' AND column_name='reviewed_by') = 0,
  'ALTER TABLE `lesson_progress` ADD COLUMN `reviewed_by` INT UNSIGNED NULL AFTER `submitted_at`',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='lesson_progress' AND column_name='reviewed_at') = 0,
  'ALTER TABLE `lesson_progress` ADD COLUMN `reviewed_at` DATETIME NULL AFTER `reviewed_by`',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='lesson_progress' AND column_name='review_note') = 0,
  'ALTER TABLE `lesson_progress` ADD COLUMN `review_note` VARCHAR(500) NULL AFTER `reviewed_at`',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='lesson_progress' AND index_name='idx_progress_review_queue') = 0,
  'ALTER TABLE `lesson_progress` ADD KEY `idx_progress_review_queue` (`status`, `submitted_at`)',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='lesson_progress' AND constraint_name='fk_progress_reviewer') = 0,
  'ALTER TABLE `lesson_progress` ADD CONSTRAINT `fk_progress_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;