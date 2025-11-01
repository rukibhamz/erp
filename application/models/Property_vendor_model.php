<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Property_vendor_model extends Base_Model {
    protected $table = 'property_vendors';
    
    public function getNextVendorCode() {
        try {
            $lastCode = $this->db->fetchOne(
                "SELECT vendor_code FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE vendor_code LIKE 'VEND-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastCode) {
                $number = intval(substr($lastCode['vendor_code'], 5)) + 1;
                return 'VEND-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
            return 'VEND-0001';
        } catch (Exception $e) {
            error_log('Property_vendor_model getNextVendorCode error: ' . $e->getMessage());
            return 'VEND-0001';
        }
    }
    
    public function getByCategory($category) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE category = ? AND status = 'active' 
                 ORDER BY is_preferred DESC, name",
                [$category]
            );
        } catch (Exception $e) {
            error_log('Property_vendor_model getByCategory error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getPreferred() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_preferred = 1 AND status = 'active' 
                 ORDER BY category, name"
            );
        } catch (Exception $e) {
            error_log('Property_vendor_model getPreferred error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updatePerformance($vendorId, $completionTime, $rating = null) {
        try {
            $vendor = $this->getById($vendorId);
            if (!$vendor) {
                return false;
            }
            
            $totalJobs = intval($vendor['total_jobs'] ?? 0) + 1;
            $currentAvgTime = floatval($vendor['avg_completion_time'] ?? 0);
            
            // Calculate new average
            $newAvgTime = $currentAvgTime > 0 
                ? (($currentAvgTime * ($totalJobs - 1)) + $completionTime) / $totalJobs
                : $completionTime;
            
            $updateData = [
                'total_jobs' => $totalJobs,
                'avg_completion_time' => $newAvgTime
            ];
            
            if ($rating !== null) {
                $currentRating = floatval($vendor['rating'] ?? 0);
                $newRating = $currentRating > 0
                    ? (($currentRating * ($totalJobs - 1)) + $rating) / $totalJobs
                    : $rating;
                $updateData['rating'] = $newRating;
            }
            
            return $this->update($vendorId, $updateData);
        } catch (Exception $e) {
            error_log('Property_vendor_model updatePerformance error: ' . $e->getMessage());
            return false;
        }
    }
}

