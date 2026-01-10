-- ============================================================================
-- ADD REFERENCE COLUMNS TO JOURNAL ENTRIES
-- ============================================================================
-- Fixes "Unknown column 'je.reference_type'" error in General Ledger report
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Add reference columns if they don't exist
SET @dbname = DATABASE();
SET @tablename = "erp_journal_entries";
SET @columnname = "reference_type";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE erp_journal_entries ADD COLUMN reference_type VARCHAR(50) DEFAULT NULL AFTER description;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "reference_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE erp_journal_entries ADD COLUMN reference_id INT(11) DEFAULT NULL AFTER reference_type;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add indexes for performance
SET @indexName = "idx_reference";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexName)
  ) > 0,
  "SELECT 1",
  "CREATE INDEX idx_reference ON erp_journal_entries(reference_type, reference_id);"
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- Record migration
INSERT INTO `erp_migrations` (`migration`, `batch`, `executed_at`) VALUES ('021_add_journal_entry_references.sql', 100, NOW());

SET FOREIGN_KEY_CHECKS = 1;
