<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_model extends Base_Model {
    protected $table = 'taxes';
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'active' 
                 ORDER BY tax_name"
            );
        } catch (Exception $e) {
            error_log('Tax_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function calculateTax($amount, $taxId, $taxInclusive = false) {
        try {
            $tax = $this->getById($taxId);
            if (!$tax || $tax['status'] !== 'active') {
                return ['tax_amount' => 0, 'base_amount' => $amount];
            }
            
            $rate = floatval($tax['rate']);
            
            if ($tax['tax_type'] === 'fixed') {
                $taxAmount = $rate;
                $baseAmount = $taxInclusive ? $amount - $taxAmount : $amount;
            } else {
                if ($taxInclusive) {
                    // Tax included in amount
                    $baseAmount = $amount / (1 + ($rate / 100));
                    $taxAmount = $amount - $baseAmount;
                } else {
                    // Tax added to amount
                    $baseAmount = $amount;
                    $taxAmount = $amount * ($rate / 100);
                }
            }
            
            return [
                'tax_amount' => round($taxAmount, 2),
                'base_amount' => round($baseAmount, 2)
            ];
        } catch (Exception $e) {
            error_log('Tax_model calculateTax error: ' . $e->getMessage());
            return ['tax_amount' => 0, 'base_amount' => $amount];
        }
    }
    
    public function getByCode($code) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE tax_code = ? AND status = 'active'",
                [$code]
            );
        } catch (Exception $e) {
            error_log('Tax_model getByCode error: ' . $e->getMessage());
            return false;
        }
    }
}

