-- Run this in phpMyAdmin (or any MySQL client) on your Hostinger database.
-- It adds the three pricing columns to the guides table if they are missing,
-- and also adds the DB-level defaults that prevent NULL errors.

-- Step 1: Add pricing columns (safe – skips if they already exist)
SET @db = DATABASE();

-- half_day_price
SET @col = 'half_day_price';
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'guides' AND COLUMN_NAME = @col) = 0,
    'ALTER TABLE `guides` ADD COLUMN `half_day_price` DECIMAL(10,2) NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- full_day_price
SET @col = 'full_day_price';
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'guides' AND COLUMN_NAME = @col) = 0,
    'ALTER TABLE `guides` ADD COLUMN `full_day_price` DECIMAL(10,2) NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- extra_hour_price
SET @col = 'extra_hour_price';
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'guides' AND COLUMN_NAME = @col) = 0,
    'ALTER TABLE `guides` ADD COLUMN `extra_hour_price` DECIMAL(10,2) NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Step 2: Give verification_status a safe default so NULL is never inserted
ALTER TABLE `guides`
    MODIFY `verification_status` VARCHAR(50) NOT NULL DEFAULT 'pending';

-- Backfill any existing NULL rows
UPDATE `guides` SET `verification_status` = 'pending' WHERE `verification_status` IS NULL OR `verification_status` = '';

-- Step 3: Mark the two new migrations as "already run" in the migrations table
-- so artisan migrate does not try to re-run them later and cause duplicates.
INSERT IGNORE INTO `migrations` (`migration`, `batch`)
SELECT m.migration, IFNULL((SELECT MAX(batch) FROM `migrations`), 1)
FROM (
    SELECT '2026_03_20_120000_add_new_pricing_columns_to_guides_table' AS migration
    UNION ALL
    SELECT '2026_04_27_000001_fix_guides_nullable_columns'
) m
WHERE m.migration NOT IN (SELECT migration FROM `migrations`);
