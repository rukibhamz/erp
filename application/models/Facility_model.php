<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Facility_model extends Base_Model {
    protected $table = 'facilities';
    
    public function getNextFacilityCode() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(facility_code, 4) AS UNSIGNED)) as max_code 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE facility_code LIKE 'FAC-%'"
            );
            $nextNum = ($result['max_code'] ?? 0) + 1;
            return 'FAC-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Facility_model getNextFacilityCode error: ' . $e->getMessage());
            return 'FAC-00001';
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'active' 
                 ORDER BY facility_name"
            );
        } catch (Exception $e) {
            error_log('Facility_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithPhotos($facilityId) {
        try {
            $facility = $this->getById($facilityId);
            if (!$facility) {
                return false;
            }
            
            $photos = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "facility_photos` 
                 WHERE facility_id = ? 
                 ORDER BY is_primary DESC, display_order ASC",
                [$facilityId]
            );
            
            $facility['photos'] = $photos;
            return $facility;
        } catch (Exception $e) {
            error_log('Facility_model getWithPhotos error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function addPhoto($facilityId, $photoPath, $photoName = null, $isPrimary = false) {
        try {
            // If this is primary, unset other primary photos
            if ($isPrimary) {
                $this->db->query(
                    "UPDATE `" . $this->db->getPrefix() . "facility_photos` 
                     SET is_primary = 0 
                     WHERE facility_id = ?",
                    [$facilityId]
                );
            }
            
            return $this->db->insert('facility_photos', [
                'facility_id' => $facilityId,
                'photo_path' => $photoPath,
                'photo_name' => $photoName,
                'is_primary' => $isPrimary ? 1 : 0,
                'display_order' => 0
            ]);
        } catch (Exception $e) {
            error_log('Facility_model addPhoto error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function deletePhoto($photoId) {
        try {
            return $this->db->delete('facility_photos', "id = ?", [$photoId]);
        } catch (Exception $e) {
            error_log('Facility_model deletePhoto error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function checkAvailability($facilityId, $bookingDate, $startTime, $endTime, $excludeBookingId = null) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM `" . $this->db->getPrefix() . "bookings` 
                    WHERE facility_id = ? 
                    AND booking_date = ? 
                    AND status NOT IN ('cancelled', 'refunded')
                    AND (
                        (start_time <= ? AND end_time > ?) 
                        OR (start_time < ? AND end_time >= ?)
                        OR (start_time >= ? AND end_time <= ?)
                    )";
            
            $params = [$facilityId, $bookingDate, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime];
            
            if ($excludeBookingId) {
                $sql .= " AND id != ?";
                $params[] = $excludeBookingId;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return ($result['count'] ?? 0) == 0;
        } catch (Exception $e) {
            error_log('Facility_model checkAvailability error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function calculatePrice($facilityId, $bookingDate, $startTime, $endTime, $bookingType = 'hourly', $quantity = 1, $isMember = false) {
        try {
            $facility = $this->getById($facilityId);
            if (!$facility) {
                return 0;
            }
            
            // Calculate duration
            $start = new DateTime($bookingDate . ' ' . $startTime);
            $end = new DateTime($bookingDate . ' ' . $endTime);
            $duration = $end->diff($start);
            $hours = $duration->h + ($duration->i / 60);
            $days = ceil($hours / 24);
            
            // Check for custom pricing rules from resource_pricing table
            $dayOfWeek = date('w', strtotime($bookingDate));
            $customPrice = $this->getCustomPrice($facilityId, $bookingDate, $dayOfWeek, $bookingType);
            
            if ($customPrice) {
                $baseRate = floatval($customPrice['price']);
                
                // Check for peak pricing
                if ($customPrice['peak_price'] && $this->isPeakTime($startTime, $endTime, $facility)) {
                    $baseRate = floatval($customPrice['peak_price']);
                }
                
                // Check for member pricing
                if ($isMember && $customPrice['member_price']) {
                    $baseRate = floatval($customPrice['member_price']);
                }
            } else {
                // Use facility default rates
                $baseRate = 0;
                switch ($bookingType) {
                    case 'hourly':
                        $baseRate = floatval($facility['hourly_rate']);
                        break;
                    case 'half_day':
                        $baseRate = floatval($facility['half_day_rate'] ?: ($facility['daily_rate'] / 2));
                        break;
                    case 'daily':
                        $baseRate = floatval($facility['daily_rate']);
                        break;
                    case 'weekly':
                        $baseRate = floatval($facility['weekly_rate'] ?: ($facility['daily_rate'] * 7));
                        break;
                }
                
                // Apply member rate if applicable
                if ($isMember && $facility['member_rate']) {
                    $baseRate = floatval($facility['member_rate']);
                }
            }
            
            // Apply duration-based calculation
            $totalPrice = 0;
            if ($bookingType === 'hourly') {
                $totalPrice = $baseRate * $hours;
            } elseif ($bookingType === 'half_day') {
                $totalPrice = $baseRate;
            } elseif ($bookingType === 'daily') {
                $totalPrice = $baseRate * $days;
            } elseif ($bookingType === 'weekly') {
                $totalPrice = $baseRate;
            }
            
            // Apply quantity
            $totalPrice *= $quantity;
            
            // Apply duration discounts if applicable
            if ($customPrice && $customPrice['duration_discount']) {
                $discounts = json_decode($customPrice['duration_discount'], true);
                foreach ($discounts as $discount) {
                    if ($hours >= ($discount['min_hours'] ?? 0) && $hours <= ($discount['max_hours'] ?? 999)) {
                        $totalPrice *= (1 - ($discount['discount_percent'] ?? 0) / 100);
                        break;
                    }
                }
            }
            
            // Apply quantity discounts if applicable
            if ($customPrice && $customPrice['quantity_discount']) {
                $discounts = json_decode($customPrice['quantity_discount'], true);
                foreach ($discounts as $discount) {
                    if ($quantity >= ($discount['min_qty'] ?? 0) && $quantity <= ($discount['max_qty'] ?? 999)) {
                        $totalPrice *= (1 - ($discount['discount_percent'] ?? 0) / 100);
                        break;
                    }
                }
            }
            
            return $totalPrice;
        } catch (Exception $e) {
            error_log('Facility_model calculatePrice error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getCustomPrice($facilityId, $bookingDate, $dayOfWeek, $rateType) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . "resource_pricing` 
                    WHERE resource_id = ? AND rate_type = ?
                    AND (start_date IS NULL OR start_date <= ?)
                    AND (end_date IS NULL OR end_date >= ?)
                    AND (day_of_week IS NULL OR day_of_week = ?)
                    ORDER BY day_of_week DESC, start_date DESC
                    LIMIT 1";
            
            return $this->db->fetchOne($sql, [$facilityId, $rateType, $bookingDate, $bookingDate, $dayOfWeek]);
        } catch (Exception $e) {
            error_log('Facility_model getCustomPrice error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function isPeakTime($startTime, $endTime, $facility) {
        $pricingRules = json_decode($facility['pricing_rules'] ?? '{}', true);
        if (!empty($pricingRules['peak_hours'])) {
            $peakStart = $pricingRules['peak_hours']['start'] ?? '17:00';
            $peakEnd = $pricingRules['peak_hours']['end'] ?? '22:00';
            return ($startTime >= $peakStart && $endTime <= $peakEnd);
        }
        return false;
    }
    
    public function getByType($type) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE resource_type = ? AND status = 'available'
                 ORDER BY facility_name",
                [$type]
            );
        } catch (Exception $e) {
            error_log('Facility_model getByType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByCategory($category) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE category = ? AND status = 'available'
                 ORDER BY facility_name",
                [$category]
            );
        } catch (Exception $e) {
            error_log('Facility_model getByCategory error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getAmenities($facilityId) {
        try {
            $facility = $this->getById($facilityId);
            if (!$facility || !$facility['amenities']) {
                return [];
            }
            return json_decode($facility['amenities'], true) ?: [];
        } catch (Exception $e) {
            error_log('Facility_model getAmenities error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function checkAdvancedAvailability($facilityId, $startDateTime, $endDateTime, $excludeBookingId = null, $quantity = 1) {
        try {
            $facility = $this->getById($facilityId);
            if (!$facility || $facility['status'] !== 'available') {
                return false;
            }
            
            $startDate = date('Y-m-d', strtotime($startDateTime));
            $endDate = date('Y-m-d', strtotime($endDateTime));
            $startTime = date('H:i:s', strtotime($startDateTime));
            $endTime = date('H:i:s', strtotime($endDateTime));
            
            // Check blockouts
            $blockoutCheck = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "resource_blockouts`
                 WHERE resource_id = ?
                 AND ((start_date <= ? AND end_date >= ?)
                 OR (start_date = ? AND start_time <= ? AND (end_time IS NULL OR end_time >= ?))
                 OR (end_date = ? AND (start_time IS NULL OR start_time <= ?) AND end_time >= ?))",
                [$facilityId, $endDate, $startDate, $startDate, $startTime, $endTime, $endDate, $endTime, $startTime]
            );
            
            if ($blockoutCheck && $blockoutCheck['count'] > 0) {
                return false;
            }
            
            // Check day-of-week availability
            $dayOfWeek = date('w', strtotime($startDate));
            $dayAvailability = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "resource_availability`
                 WHERE resource_id = ? AND day_of_week = ?",
                [$facilityId, $dayOfWeek]
            );
            
            if ($dayAvailability && !$dayAvailability['is_available']) {
                return false;
            }
            
            if ($dayAvailability && $dayAvailability['start_time'] && $dayAvailability['end_time']) {
                if ($startTime < $dayAvailability['start_time'] || $endTime > $dayAvailability['end_time']) {
                    return false;
                }
                
                // Check break times
                if ($dayAvailability['break_start'] && $dayAvailability['break_end']) {
                    if (($startTime >= $dayAvailability['break_start'] && $startTime < $dayAvailability['break_end']) ||
                        ($endTime > $dayAvailability['break_start'] && $endTime <= $dayAvailability['break_end'])) {
                        return false;
                    }
                }
            }
            
            // Check existing bookings (with simultaneous limit)
            $simultaneousLimit = intval($facility['simultaneous_limit'] ?? 1);
            $bookingCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "bookings`
                 WHERE facility_id = ?
                 AND booking_date BETWEEN ? AND ?
                 AND status NOT IN ('cancelled', 'no_show')
                 AND (start_time < ? AND end_time > ?)",
                [$facilityId, $startDate, $endDate, $endTime, $startTime]
            );
            
            if ($bookingCount && intval($bookingCount['count']) >= $simultaneousLimit) {
                return false;
            }
            
            // Check lead time
            if ($facility['lead_time'] > 0) {
                $daysInAdvance = (strtotime($startDate) - time()) / (60 * 60 * 24);
                if ($daysInAdvance > $facility['lead_time']) {
                    return false;
                }
            }
            
            // Check cutoff time
            if ($facility['cutoff_time'] > 0) {
                $hoursUntilBooking = (strtotime($startDateTime) - time()) / (60 * 60);
                if ($hoursUntilBooking < $facility['cutoff_time']) {
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Facility_model checkAdvancedAvailability error: ' . $e->getMessage());
            return false;
        }
    }
}

