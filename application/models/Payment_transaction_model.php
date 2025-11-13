<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_transaction_model extends Base_Model {
    protected $table = 'payment_transactions';
    
    public function getByReference($paymentType, $referenceId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE payment_type = ? AND reference_id = ? 
                 ORDER BY created_at DESC",
                [$paymentType, $referenceId]
            );
        } catch (Exception $e) {
            error_log('Payment_transaction_model getByReference error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByTransactionRef($transactionRef) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE transaction_ref = ?",
                [$transactionRef]
            );
        } catch (Exception $e) {
            error_log('Payment_transaction_model getByTransactionRef error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateStatus($id, $status, $gatewayTransactionId = null, $gatewayResponse = null) {
        try {
            $updateData = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($status === 'success') {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
            }
            
            if ($gatewayTransactionId) {
                $updateData['gateway_transaction_id'] = $gatewayTransactionId;
            }
            
            if ($gatewayResponse) {
                $updateData['gateway_response'] = is_array($gatewayResponse) ? 
                    json_encode($gatewayResponse) : $gatewayResponse;
            }
            
            return $this->update($id, $updateData);
        } catch (Exception $e) {
            error_log('Payment_transaction_model updateStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate a cryptographically secure transaction reference
     * SECURITY: Uses random_bytes() instead of MD5 for secure random generation
     * 
     * @param string $prefix Transaction reference prefix (default: 'TXN')
     * @return string Generated transaction reference in format: PREFIX-YYYYMMDDHHMMSS-RANDOM
     */
    public function generateTransactionRef($prefix = 'TXN') {
        try {
            $timestamp = date('YmdHis');
            // SECURITY: Use cryptographically secure random_bytes() instead of MD5
            // Generate 4 bytes (8 hex characters) of secure random data
            $random = strtoupper(bin2hex(random_bytes(4)));
            return $prefix . '-' . $timestamp . '-' . $random;
        } catch (Exception $e) {
            // Fallback: Use time-based reference if random_bytes() fails
            // Still avoid MD5 - use time + cryptographically secure random if available
            try {
                $random = strtoupper(bin2hex(random_bytes(4)));
            } catch (Exception $e2) {
                // Last resort: use time-based random (not cryptographically secure but better than MD5)
                $random = strtoupper(substr(base64_encode(time() . uniqid()), 0, 8));
            }
            return $prefix . '-' . time() . '-' . $random;
        }
    }
}

