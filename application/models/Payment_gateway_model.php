<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_gateway_model extends Base_Model {
    protected $table = 'payment_gateways';
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_active = 1 
                 ORDER BY is_default DESC, display_order ASC, gateway_name ASC"
            );
        } catch (Exception $e) {
            error_log('Payment_gateway_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getDefault() {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_active = 1 AND is_default = 1 
                 LIMIT 1"
            );
        } catch (Exception $e) {
            error_log('Payment_gateway_model getDefault error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByCode($code) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE gateway_code = ?",
                [$code]
            );
        } catch (Exception $e) {
            error_log('Payment_gateway_model getByCode error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function setDefault($gatewayId) {
        try {
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();
            
            // Remove default from all
            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . $this->table . "` 
                 SET is_default = 0",
                []
            );
            
            // Set new default
            $this->update($gatewayId, ['is_default' => 1]);
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Payment_gateway_model setDefault error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getSupportedCurrencies($gatewayId) {
        try {
            $gateway = $this->getById($gatewayId);
            if (!$gateway || !$gateway['supported_currencies']) {
                return [];
            }
            return json_decode($gateway['supported_currencies'], true) ?: [];
        } catch (Exception $e) {
            error_log('Payment_gateway_model getSupportedCurrencies error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getAdditionalConfig($gatewayId) {
        try {
            $gateway = $this->getById($gatewayId);
            if (!$gateway || !$gateway['additional_config']) {
                return [];
            }
            return json_decode($gateway['additional_config'], true) ?: [];
        } catch (Exception $e) {
            error_log('Payment_gateway_model getAdditionalConfig error: ' . $e->getMessage());
            return [];
        }
    }
}

