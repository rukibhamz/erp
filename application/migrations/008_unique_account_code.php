<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Add unique constraint on accounts.account_code
 * 
 * Prevents duplicate account codes from being created, which was the root
 * cause of duplicate revenue/liability accounts appearing in the COA.
 * 
 * Before adding the constraint, deduplicates any existing rows by keeping
 * the one with the lowest id (the original seeded account) and soft-deleting
 * the duplicates by appending _DUP_{id} to their code.
 */
class Migration_Unique_account_code {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        $prefix = $this->db->getPrefix();

        // Step 1: Find duplicate account_codes and rename the extras
        $duplicates = $this->db->fetchAll(
            "SELECT account_code, COUNT(*) as cnt
             FROM `{$prefix}accounts`
             GROUP BY account_code
             HAVING cnt > 1"
        );

        foreach ($duplicates as $dup) {
            // Keep the row with the lowest id, rename the rest
            $rows = $this->db->fetchAll(
                "SELECT id FROM `{$prefix}accounts`
                 WHERE account_code = ?
                 ORDER BY id ASC",
                [$dup['account_code']]
            );

            // Skip the first (canonical) row
            array_shift($rows);

            foreach ($rows as $row) {
                $newCode = $dup['account_code'] . '_DUP_' . $row['id'];
                $this->db->query(
                    "UPDATE `{$prefix}accounts`
                     SET account_code = ?, status = 'inactive'
                     WHERE id = ?",
                    [$newCode, $row['id']]
                );
                error_log("Migration 008: Renamed duplicate account id={$row['id']} code={$dup['account_code']} -> {$newCode}");
            }
        }

        // Step 2: Add unique constraint (ignore if it already exists)
        try {
            $this->db->query(
                "ALTER TABLE `{$prefix}accounts`
                 ADD UNIQUE KEY `unique_account_code` (`account_code`)"
            );
            error_log("Migration 008: Added unique constraint on accounts.account_code");
        } catch (Exception $e) {
            if (stripos($e->getMessage(), 'Duplicate key name') !== false ||
                stripos($e->getMessage(), 'already exists') !== false) {
                error_log("Migration 008: Unique constraint already exists, skipping");
            } else {
                throw $e;
            }
        }
    }

    public function down() {
        $prefix = $this->db->getPrefix();
        try {
            $this->db->query(
                "ALTER TABLE `{$prefix}accounts` DROP INDEX `unique_account_code`"
            );
        } catch (Exception $e) {
            error_log("Migration 008 down: " . $e->getMessage());
        }
    }
}
