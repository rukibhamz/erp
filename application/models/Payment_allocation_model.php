<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_allocation_model extends Base_Model {
    protected $table = 'payment_allocations';
    
    public function getByPayment($paymentId) {
        try {
            return $this->db->fetchAll(
                "SELECT pa.*, 
                        i.invoice_number as invoice_number, i.invoice_date as invoice_date, i.customer_id as invoice_customer_id,
                        b.bill_number as bill_number, b.bill_date as bill_date, b.vendor_id as bill_vendor_id,
                        c.company_name as customer_name,
                        v.company_name as vendor_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` pa
                 LEFT JOIN `" . $this->db->getPrefix() . "invoices` i ON pa.invoice_id = i.id
                 LEFT JOIN `" . $this->db->getPrefix() . "bills` b ON pa.bill_id = b.id
                 LEFT JOIN `" . $this->db->getPrefix() . "customers` c ON i.customer_id = c.id
                 LEFT JOIN `" . $this->db->getPrefix() . "vendors` v ON b.vendor_id = v.id
                 WHERE pa.payment_id = ?
                 ORDER BY pa.id",
                [$paymentId]
            );
        } catch (Exception $e) {
            error_log('Payment_allocation_model getByPayment error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByInvoice($invoiceId) {
        try {
            return $this->db->fetchAll(
                "SELECT pa.*, p.payment_number, p.payment_date, p.payment_method
                 FROM `" . $this->db->getPrefix() . $this->table . "` pa
                 JOIN `" . $this->db->getPrefix() . "payments` p ON pa.payment_id = p.id
                 WHERE pa.invoice_id = ?
                 ORDER BY p.payment_date DESC",
                [$invoiceId]
            );
        } catch (Exception $e) {
            error_log('Payment_allocation_model getByInvoice error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByBill($billId) {
        try {
            return $this->db->fetchAll(
                "SELECT pa.*, p.payment_number, p.payment_date, p.payment_method
                 FROM `" . $this->db->getPrefix() . $this->table . "` pa
                 JOIN `" . $this->db->getPrefix() . "payments` p ON pa.payment_id = p.id
                 WHERE pa.bill_id = ?
                 ORDER BY p.payment_date DESC",
                [$billId]
            );
        } catch (Exception $e) {
            error_log('Payment_allocation_model getByBill error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function allocate($paymentId, $invoiceId = null, $billId = null, $amount, $discountTaken = 0) {
        try {
            if (!$invoiceId && !$billId) {
                return false;
            }
            
            $data = [
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'bill_id' => $billId,
                'amount' => floatval($amount),
                'discount_taken' => floatval($discountTaken)
            ];
            
            return $this->create($data);
        } catch (Exception $e) {
            error_log('Payment_allocation_model allocate error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAllocatedAmount($invoiceId = null, $billId = null) {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total
                    FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE ";
            $params = [];
            
            if ($invoiceId) {
                $sql .= "invoice_id = ?";
                $params[] = $invoiceId;
            } elseif ($billId) {
                $sql .= "bill_id = ?";
                $params[] = $billId;
            } else {
                return 0;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return $result ? floatval($result['total']) : 0;
        } catch (Exception $e) {
            error_log('Payment_allocation_model getAllocatedAmount error: ' . $e->getMessage());
            return 0;
        }
    }
}

