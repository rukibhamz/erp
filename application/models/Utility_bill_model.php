<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_bill_model extends Base_Model {
    protected $table = 'utility_bills';
    
    public function getNextBillNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT bill_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE bill_number LIKE 'UBIL-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['bill_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "UBIL-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "UBIL-{$year}-00001";
        } catch (Exception $e) {
            error_log('Utility_bill_model getNextBillNumber error: ' . $e->getMessage());
            return 'UBIL-' . date('Y') . '-00001';
        }
    }
    
    public function getByMeter($meterId, $status = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE meter_id = ?";
            $params = [$meterId];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY billing_period_start DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Utility_bill_model getByMeter error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getOverdue() {
        try {
            return $this->db->fetchAll(
                "SELECT ub.*, m.meter_number, ut.name as utility_type_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` ub
                 JOIN `" . $this->db->getPrefix() . "meters` m ON ub.meter_id = m.id
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 WHERE ub.status IN ('sent', 'partial') 
                 AND ub.due_date < CURDATE()
                 AND ub.balance_amount > 0
                 ORDER BY ub.due_date ASC"
            );
        } catch (Exception $e) {
            error_log('Utility_bill_model getOverdue error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updatePaymentStatus($billId, $paidAmount) {
        try {
            $bill = $this->getById($billId);
            if (!$bill) {
                return false;
            }
            
            $newPaidAmount = floatval($bill['paid_amount']) + floatval($paidAmount);
            $balanceAmount = floatval($bill['total_amount']) - $newPaidAmount;
            
            $status = 'paid';
            if ($balanceAmount > 0) {
                $status = $newPaidAmount > 0 ? 'partial' : $bill['status'];
            }
            
            return $this->update($billId, [
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $balanceAmount,
                'status' => $status,
                'paid_date' => $balanceAmount <= 0 ? date('Y-m-d H:i:s') : null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Utility_bill_model updatePaymentStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getWithDetails($billId) {
        try {
            return $this->db->fetchOne(
                "SELECT ub.*, m.meter_number, m.meter_location, ut.name as utility_type_name, ut.unit_of_measure,
                        p.property_name, s.space_name, t.business_name as tenant_name,
                        pr.provider_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` ub
                 JOIN `" . $this->db->getPrefix() . "meters` m ON ub.meter_id = m.id
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 LEFT JOIN `" . $this->db->getPrefix() . "properties` p ON m.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . "spaces` s ON m.space_id = s.id
                 LEFT JOIN `" . $this->db->getPrefix() . "tenants` t ON m.tenant_id = t.id
                 LEFT JOIN `" . $this->db->getPrefix() . "utility_providers` pr ON ub.provider_id = pr.id
                 WHERE ub.id = ?",
                [$billId]
            );
        } catch (Exception $e) {
            error_log('Utility_bill_model getWithDetails error: ' . $e->getMessage());
            return false;
        }
    }
}

