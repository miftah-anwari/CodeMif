USE `codenexa_db`;

SET @migration_sql = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='grade_level') = 0,
  "ALTER TABLE `users` ADD COLUMN `grade_level` ENUM('X','XI','XII') NULL AFTER `class_id`",
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;
