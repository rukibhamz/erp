<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rent_invoice_model extends Base_Model {
    protected $table = 'rent_invoices';
    
    public function getNextInvoiceNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT invoice_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE invoice_number LIKE 'RINV-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['invoice_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "RINV-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "RINV-{$year}-00001";
        } catch (Exception $e) {
            error_log('Rent_invoice_model getNextInvoiceNumber error: ' . $e->getMessage());
            return 'RINV-' . date('Y') . '-00001';
        }
    }
    
    public function getByTenant($tenantId, $status = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE tenant_id = ?";
            $params = [$tenantId];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY period_start DESC, invoice_date DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Rent_invoice_model getByTenant error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByLease($leaseId, $status = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE lease_id = ?";
            $params = [$leaseId];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY period_start DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Rent_invoice_model getByLease error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getOverdue() {
        try {
            return $this->db->fetchAll(
                "SELECT ri.*, t.business_name, t.contact_person, s.space_name, p.property_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` ri
                 JOIN `" . $this->db->getPrefix() . "tenants` t ON ri.tenant_id = t.id
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON ri.space_id = s.id
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 WHERE ri.status IN ('sent', 'partial') 
                 AND ri.due_date < CURDATE()
                 AND ri.balance_amount > 0
                 ORDER BY ri.due_date ASC"
            );
        } catch (Exception $e) {
            error_log('Rent_invoice_model getOverdue error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updatePaymentStatus($invoiceId, $paidAmount) {
        try {
            $invoice = $this->getById($invoiceId);
            if (!$invoice) {
                return false;
            }
            
            $newPaidAmount = floatval($invoice['paid_amount']) + floatval($paidAmount);
            $balanceAmount = floatval($invoice['total_amount']) - $newPaidAmount;
            
            $status = 'paid';
            if ($balanceAmount > 0) {
                $status = $newPaidAmount > 0 ? 'partial' : 'sent';
            }
            
            return $this->update($invoiceId, [
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $balanceAmount,
                'status' => $status,
                'paid_date' => $balanceAmount <= 0 ? date('Y-m-d H:i:s') : null
            ]);
        } catch (Exception $e) {
            error_log('Rent_invoice_model updatePaymentStatus error: ' . $e->getMessage());
            return false;
        }
    }
}

