<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Unified_customer_identity {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        // 1. Add customer_source to customers
        try {
            $this->db->query(
                "ALTER TABLE `" . $this->db->getPrefix() . "customers`
                 ADD COLUMN `customer_source` ENUM('invoice','booking','tenant') NOT NULL DEFAULT 'invoice'"
            );
            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . "customers`
                 SET customer_source = 'invoice' WHERE customer_source IS NULL"
            );
            error_log('Migration 009: Added customer_source to customers');
        } catch (Exception $e) {
            error_log('Migration 009: customer_source already exists or error: ' . $e->getMessage());
        }

        // 2. Add customer_id to bookings
        try {
            $this->db->query(
                "ALTER TABLE `" . $this->db->getPrefix() . "bookings`
                 ADD COLUMN `customer_id` INT UNSIGNED NULL DEFAULT NULL"
            );
            error_log('Migration 009: Added customer_id to bookings');
        } catch (Exception $e) {
            error_log('Migration 009: bookings.customer_id already exists or error: ' . $e->getMessage());
        }

        // 3. Add customer_id to tenants
        try {
            $this->db->query(
                "ALTER TABLE `" . $this->db->getPrefix() . "tenants`
                 ADD COLUMN `customer_id` INT UNSIGNED NULL DEFAULT NULL"
            );
            error_log('Migration 009: Added customer_id to tenants');
        } catch (Exception $e) {
            error_log('Migration 009: tenants.customer_id already exists or error: ' . $e->getMessage());
        }

        // 4. Add lease_reference to invoices
        try {
            $this->db->query(
                "ALTER TABLE `" . $this->db->getPrefix() . "invoices`
                 ADD COLUMN `lease_reference` VARCHAR(50) NULL DEFAULT NULL"
            );
            error_log('Migration 009: Added lease_reference to invoices');
        } catch (Exception $e) {
            error_log('Migration 009: invoices.lease_reference already exists or error: ' . $e->getMessage());
        }

        return true;
    }

    public function down() {
        // Drop lease_reference from invoices
        try {
            $this->db->query(
                "ALTER TABLE `" . $this->db->getPrefix() . "invoices`
                 DROP COLUMN `lease_reference`"
            );
        } catch (Exception $e) {
            error_log('Migration 009 down: lease_reference drop error: ' . $e->getMessage());
        }

        // Drop customer_id from tenants
        try {
            $this->db->query(
                "ALTER TABLE `" . $this->db->getPrefix() . "tenants`
                 DROP COLUMN `customer_id`"
            );
        } catch (Exception $e) {
            error_log('Migration 009 down: tenants.customer_id drop error: ' . $e->getMessage());
        }

        // Drop customer_id from bookings
        try {
            $this->db->query(
                "ALTER TABLE `" . $this->db->getPrefix() . "bookings`
                 DROP COLUMN `customer_id`"
            );
        } catch (Exception $e) {
            error_log('Migration 009 down: bookings.customer_id drop error: ' . $e->getMessage());
        }

        // Drop customer_source from customers
        try {
            $this->db->query(
                "ALTER TABLE `" . $this->db->getPrefix() . "customers`
                 DROP COLUMN `customer_source`"
            );
        } catch (Exception $e) {
            error_log('Migration 009 down: customer_source drop error: ' . $e->getMessage());
        }

        return true;
    }
}
