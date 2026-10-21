USE `codenexa_db`;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='lesson_progress' AND column_name='evidence_image') = 0,
  'ALTER TABLE `lesson_progress` ADD COLUMN `evidence_image` VARCHAR(255) NULL AFTER `submitted_at`',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;
