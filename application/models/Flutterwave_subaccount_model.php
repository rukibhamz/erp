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
}
