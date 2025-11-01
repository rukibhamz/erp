<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_group_model extends Base_Model {
    protected $table = 'tax_groups';
    
    public function getGroupTaxes($groupId) {
        try {
            return $this->db->fetchAll(
                "SELECT t.*, tgi.sequence
                 FROM `" . $this->db->getPrefix() . "tax_group_items` tgi
                 JOIN `" . $this->db->getPrefix() . "taxes` t ON tgi.tax_id = t.id
                 WHERE tgi.tax_group_id = ? AND t.status = 'active'
                 ORDER BY tgi.sequence",
                [$groupId]
            );
        } catch (Exception $e) {
            error_log('Tax_group_model getGroupTaxes error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function calculateGroupTax($amount, $groupId, $taxInclusive = false) {
        try {
            $taxes = $this->getGroupTaxes($groupId);
            if (empty($taxes)) {
                return ['tax_amount' => 0, 'base_amount' => $amount];
            }
            
            $totalTax = 0;
            $currentAmount = $amount;
            
            foreach ($taxes as $tax) {
                if ($tax['tax_type'] === 'compound') {
                    // Compound tax: calculated on base + previous taxes
                    $taxAmount = $currentAmount * (floatval($tax['rate']) / 100);
                } else {
                    // Regular tax: calculated on base amount
                    $taxAmount = ($taxInclusive ? $currentAmount : $amount) * (floatval($tax['rate']) / 100);
                }
                
                $totalTax += $taxAmount;
                
                if ($tax['tax_type'] === 'compound') {
                    $currentAmount += $taxAmount;
                }
            }
            
            $baseAmount = $taxInclusive ? $amount - $totalTax : $amount;
            
            return [
                'tax_amount' => round($totalTax, 2),
                'base_amount' => round($baseAmount, 2)
            ];
        } catch (Exception $e) {
            error_log('Tax_group_model calculateGroupTax error: ' . $e->getMessage());
            return ['tax_amount' => 0, 'base_amount' => $amount];
        }
    }
}

