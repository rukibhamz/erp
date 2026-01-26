<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Space_booking_model extends Base_Model {
    protected $table = 'space_bookings';
    
    public function getNextBookingNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(booking_number, 5) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_number LIKE 'SBK-%'"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            return 'SBK-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Space_booking_model getNextBookingNumber error: ' . $e->getMessage());
            return 'SBK-' . date('Ymd') . '-00001';
        }
    }
    
    /**
     * Check if a time slot is available for booking
     * Returns true if available, false if conflicting booking exists
     */
    public function checkAvailability($spaceId, $bookingDate, $startTime, $endTime, $excludeBookingId = null, $checkEndDate = null) {
        try {
            $endDate = $checkEndDate ?? $bookingDate;
            
            // Normalize dates/times for comparison
            $startDateTime = $bookingDate . ' ' . $startTime;
            $endDateTime = $endDate . ' ' . $endTime;
            
            // SQL to check for ANY overlap
            // Overlap logic: (StartA < EndB) AND (EndA > StartB)
            $sql = "SELECT COUNT(*) as count 
                    FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE space_id = ? 
                    AND status NOT IN ('cancelled')
                    AND (
                        CONCAT(booking_date, ' ', start_time) < ? 
                        AND 
                        CONCAT(COALESCE(end_date, booking_date), ' ', end_time) > ?
                    )";
            
            $params = [$spaceId, $endDateTime, $startDateTime];
            
            if ($excludeBookingId) {
                $sql .= " AND id != ?";
                $params[] = $excludeBookingId;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return ($result['count'] ?? 0) == 0;
        } catch (Exception $e) {
            error_log('Space_booking_model checkAvailability error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get bookings for a space on a specific date
     */
    public function getBySpaceAndDate($spaceId, $bookingDate) {
        try {
            return $this->db->fetchAll(
                "SELECT sb.*, 
                        t.tenant_code, t.tenant_name, t.email, t.phone,
                        s.space_name, s.space_number
                 FROM `" . $this->db->getPrefix() . $this->table . "` sb
                 JOIN `" . $this->db->getPrefix() . "tenants` t ON sb.tenant_id = t.id
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON sb.space_id = s.id
                 WHERE sb.space_id = ? 
                 AND sb.booking_date = ?
                 AND sb.status NOT IN ('cancelled')
                 ORDER BY sb.start_time ASC",
                [$spaceId, $bookingDate]
            );
        } catch (Exception $e) {
            error_log('Space_booking_model getBySpaceAndDate error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get bookings for a tenant
     */
    public function getByTenant($tenantId, $status = null) {
        try {
            $sql = "SELECT sb.*, 
                        s.space_name, s.space_number,
                        p.property_name as location_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` sb
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON sb.space_id = s.id
                 LEFT JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 WHERE sb.tenant_id = ?";
            
            $params = [$tenantId];
            
            if ($status) {
                $sql .= " AND sb.status = ?";
                $params[] = $status;
            } else {
                $sql .= " AND sb.status NOT IN ('cancelled')";
            }
            
            $sql .= " ORDER BY sb.booking_date DESC, sb.start_time DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Space_booking_model getByTenant error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get availability calendar for a space
     */
    public function getAvailabilityCalendar($spaceId, $startDate, $endDate) {
        try {
            return $this->db->fetchAll(
                "SELECT sb.*, t.tenant_name as business_name, t.tenant_name as contact_person
                 FROM `" . $this->db->getPrefix() . $this->table . "` sb
                 LEFT JOIN `" . $this->db->getPrefix() . "tenants` t ON sb.tenant_id = t.id
                 WHERE sb.space_id = ? 
                 AND sb.booking_date >= ? 
                 AND sb.booking_date <= ?
                 AND sb.status NOT IN ('cancelled')
                 ORDER BY sb.booking_date, sb.start_time",
                [$spaceId, $startDate, $endDate]
            );
        } catch (Exception $e) {
            error_log('Space_booking_model getAvailabilityCalendar error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get bookings with tenant and space info for display
     */
    public function getAllWithDetails() {
        $logFile = 'debug_model_error.txt';
        try {
            $sql = "SELECT sb.*, 
                        COALESCE(t.tenant_name, sb.customer_name) as tenant_name,
                        COALESCE(t.email, sb.customer_email) as email,
                        COALESCE(t.phone, sb.customer_phone) as phone,
                        s.space_name, s.space_number,
                        p.property_name as location_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` sb
                 LEFT JOIN `" . $this->db->getPrefix() . "tenants` t ON sb.tenant_id = t.id
                 LEFT JOIN `" . $this->db->getPrefix() . "spaces` s ON sb.space_id = s.id
                 LEFT JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 ORDER BY sb.booking_date DESC, sb.start_time DESC";
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Running Query: " . $sql . "\n", FILE_APPEND);
            
            $result = $this->db->fetchAll($sql);
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Result Count: " . count($result) . "\n", FILE_APPEND);
            
            return $result;
        } catch (Exception $e) {
            error_log('Space_booking_model getAllWithDetails error: ' . $e->getMessage());
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            return [];
        }
    }
    
    /**
     * Get bookings for multiple spaces
     */
    public function getBySpaces($spaceIds) {
        if (empty($spaceIds)) {
            return [];
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($spaceIds), '?'));
            return $this->db->fetchAll(
                "SELECT sb.*, 
                        COALESCE(t.business_name, t.contact_person, sb.customer_name) as tenant_name,
                        COALESCE(t.email, sb.customer_email) as email,
                        COALESCE(t.phone, sb.customer_phone) as phone,
                        s.space_name, s.space_number,
                        p.property_name as location_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` sb
                 LEFT JOIN `" . $this->db->getPrefix() . "tenants` t ON sb.tenant_id = t.id
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON sb.space_id = s.id
                 LEFT JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 WHERE sb.space_id IN ($placeholders)
                 ORDER BY sb.booking_date DESC, sb.start_time DESC",
                $spaceIds
            );
        } catch (Exception $e) {
            error_log('Space_booking_model getBySpaces error: ' . $e->getMessage());
            return [];
        }
    }
}

