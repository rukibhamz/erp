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
    
    public function generateTransactionRef($prefix = 'TXN') {
        try {
            $timestamp = date('YmdHis');
            $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            return $prefix . '-' . $timestamp . '-' . $random;
        } catch (Exception $e) {
            return $prefix . '-' . time() . '-' . rand(1000, 9999);
        }
    }
}

