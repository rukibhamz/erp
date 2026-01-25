-- ============================================================================
-- FIX: ADD CUSTOMER_ID TO BOOKINGS (WITH COLLATION FIX)
-- ============================================================================
-- 1. Fixes collation mismatch (general_ci vs unicode_ci)
-- 2. Adds customer_id link
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Standardize Collation for erp_bookings to match erp_customers
-- This prevents "Illegal mix of collations" errors during joins
ALTER TABLE `erp_bookings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Add customer_id column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = "erp_bookings";
SET @columnname = "customer_id";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT(11) NULL AFTER facility_id")
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Add index for customer_id
SET @indexname = "idx_customer_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  "SELECT 1",
  CONCAT("CREATE INDEX ", @indexname, " ON ", @tablename, " (", @columnname, ")")
));

PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- 4. Attempt to link existing bookings to customers table by email
-- Now safe due to collation fix
UPDATE `erp_bookings` b
JOIN `erp_customers` c ON b.customer_email = c.email
SET b.customer_id = c.id
WHERE b.customer_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
