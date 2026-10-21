USE `codenexa_db`;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='enrollments' AND column_name='last_activity_at') = 0,
  'ALTER TABLE `enrollments` ADD COLUMN `last_activity_at` DATETIME NULL AFTER `last_lesson_id`',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;
