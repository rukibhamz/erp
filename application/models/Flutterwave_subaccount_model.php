<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Flutterwave_subaccount_model extends Base_Model {
    protected $table = 'flutterwave_subaccounts';

    public function getAllActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE is_active = 1
                 ORDER BY business_name ASC"
            );
        } catch (Exception $e) {
            error_log('Flutterwave_subaccount_model getAllActive: ' . $e->getMessage());
            return [];
        }
    }

    public function getAllForAdmin() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`
                 ORDER BY is_active DESC, business_name ASC"
            );
        } catch (Exception $e) {
            error_log('Flutterwave_subaccount_model getAllForAdmin: ' . $e->getMessage());
            return [];
        }
    }

    public function getBySubaccountId($subaccountId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE subaccount_id = ?",
                [$subaccountId]
            );
        } catch (Exception $e) {
            return false;
        }
    }

    public function getDefaultActive() {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE is_active = 1 AND is_default = 1
                 ORDER BY id ASC LIMIT 1"
            );
        } catch (Exception $e) {
            return false;
        }
    }

    public function setDefault($rowId) {
        try {
            $prefix = $this->db->getPrefix();
            $this->db->query(
                "UPDATE `{$prefix}{$this->table}` SET is_default = 0",
                []
            );
            return $this->update((int) $rowId, ['is_default' => 1]);
        } catch (Exception $e) {
            error_log('Flutterwave_subaccount_model setDefault: ' . $e->getMessage());
            return false;
        }
    }

    public function countActive() {
        try {
            $row = $this->db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM `" . $this->db->getPrefix() . $this->table . "` WHERE is_active = 1"
            );
            return (int) ($row['cnt'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
}
