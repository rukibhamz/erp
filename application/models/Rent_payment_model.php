<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rent_payment_model extends Base_Model {
    protected $table = 'rent_payments';
    
    public function getNextPaymentNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT payment_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE payment_number LIKE 'RPAY-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['payment_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "RPAY-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "RPAY-{$year}-00001";
        } catch (Exception $e) {
            error_log('Rent_payment_model getNextPaymentNumber error: ' . $e->getMessage());
            return 'RPAY-' . date('Y') . '-00001';
        }
    }
    
    public function getNextReceiptNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT receipt_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE receipt_number LIKE 'RRCP-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['receipt_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "RRCP-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "RRCP-{$year}-00001";
        } catch (Exception $e) {
            error_log('Rent_payment_model getNextReceiptNumber error: ' . $e->getMessage());
            return 'RRCP-' . date('Y') . '-00001';
        }
    }
    
    public function getByInvoice($invoiceId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE invoice_id = ? 
                 ORDER BY payment_date DESC, created_at DESC",
                [$invoiceId]
            );
        } catch (Exception $e) {
            error_log('Rent_payment_model getByInvoice error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByTenant($tenantId, $startDate = null, $endDate = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE tenant_id = ?";
            $params = [$tenantId];
            
            if ($startDate) {
                $sql .= " AND payment_date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND payment_date <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY payment_date DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Rent_payment_model getByTenant error: ' . $e->getMessage());
            return [];
        }
    }
}

