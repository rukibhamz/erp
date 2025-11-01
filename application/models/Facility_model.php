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
    
    public function calculatePrice($facilityId, $bookingDate, $startTime, $endTime, $bookingType = 'hourly') {
        try {
            $facility = $this->getById($facilityId);
            if (!$facility) {
                return 0;
            }
            
            // Parse pricing rules
            $pricingRules = json_decode($facility['pricing_rules'] ?? '{}', true);
            
            // Calculate duration
            $start = new DateTime($bookingDate . ' ' . $startTime);
            $end = new DateTime($bookingDate . ' ' . $endTime);
            $duration = $end->diff($start);
            $hours = $duration->h + ($duration->i / 60);
            $days = ceil($hours / 24);
            
            // Check if weekend
            $dayOfWeek = date('w', strtotime($bookingDate));
            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
            
            // Check if peak time
            $isPeak = false;
            if (!empty($pricingRules['peak_hours'])) {
                $peakStart = $pricingRules['peak_hours']['start'] ?? '17:00';
                $peakEnd = $pricingRules['peak_hours']['end'] ?? '22:00';
                $isPeak = ($startTime >= $peakStart && $endTime <= $peakEnd);
            }
            
            $baseRate = 0;
            if ($bookingType === 'daily') {
                $baseRate = floatval($facility['daily_rate']);
                if ($isWeekend && $facility['weekend_rate'] > 0) {
                    $baseRate = floatval($facility['weekend_rate']);
                }
                return $baseRate * $days;
            } else {
                // Hourly booking
                $baseRate = floatval($facility['hourly_rate']);
                
                if ($isPeak && $facility['peak_rate'] > 0) {
                    $baseRate = floatval($facility['peak_rate']);
                } elseif ($isWeekend && $facility['weekend_rate'] > 0) {
                    // If hourly weekend rate is set
                    $baseRate = floatval($facility['weekend_rate']);
                }
                
                return $baseRate * $hours;
            }
        } catch (Exception $e) {
            error_log('Facility_model calculatePrice error: ' . $e->getMessage());
            return 0;
        }
    }
}

