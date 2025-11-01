<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cancellation_policy_model extends Base_Model {
    protected $table = 'cancellation_policies';
    
    public function getDefault() {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_default = 1 
                 LIMIT 1"
            );
        } catch (Exception $e) {
            error_log('Cancellation_policy_model getDefault error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function calculateRefund($policyId, $bookingDate, $cancellationDate, $bookingAmount) {
        try {
            $policy = $this->getById($policyId);
            if (!$policy) {
                return 0;
            }
            
            $rules = json_decode($policy['rules'], true);
            if (!$rules) {
                return 0;
            }
            
            $daysBefore = (strtotime($bookingDate) - strtotime($cancellationDate)) / (60 * 60 * 24);
            
            // Find applicable rule (rules should be sorted by days_before descending)
            foreach ($rules as $rule) {
                if ($daysBefore >= ($rule['days_before'] ?? 0)) {
                    $refundPercent = floatval($rule['refund_percentage'] ?? 0);
                    return ($bookingAmount * $refundPercent) / 100;
                }
            }
            
            // If no rule matches, no refund
            return 0;
        } catch (Exception $e) {
            error_log('Cancellation_policy_model calculateRefund error: ' . $e->getMessage());
            return 0;
        }
    }
}

