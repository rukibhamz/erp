<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 013: Add promo_codes.apply_to_addons toggle
 *
 * Default behavior is resource-only discount.
 * When enabled per promo code, discount base includes add-ons.
 */
class Migration_Add_promo_apply_to_addons {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        $prefix = $this->db->getPrefix();
        try {
            $this->db->query(
                "ALTER TABLE `{$prefix}promo_codes`
                 ADD COLUMN `apply_to_addons` TINYINT(1) NOT NULL DEFAULT 0 AFTER `applicable_to`"
            );
            error_log('Migration 013: Added promo_codes.apply_to_addons');
        } catch (Exception $e) {
            // Idempotent on reruns / already exists.
            error_log('Migration 013: apply_to_addons exists or error: ' . $e->getMessage());
        }
    }

    public function down() {
        $prefix = $this->db->getPrefix();
        try {
            $this->db->query(
                "ALTER TABLE `{$prefix}promo_codes`
                 DROP COLUMN `apply_to_addons`"
            );
        } catch (Exception $e) {
            error_log('Migration 013 down: drop apply_to_addons error: ' . $e->getMessage());
        }
    }
}

