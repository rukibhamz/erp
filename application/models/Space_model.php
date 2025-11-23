<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Space_model extends Base_Model {
    protected $table = 'spaces';
    
    public function getNextSpaceNumber($propertyId) {
        try {
            $lastNumber = $this->db->fetchOne(
                "SELECT space_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE property_id = ? AND space_number IS NOT NULL
                 ORDER BY id DESC LIMIT 1",
                [$propertyId]
            );
            
            if ($lastNumber && $lastNumber['space_number']) {
                // Extract number and increment
                $number = intval(preg_replace('/[^0-9]/', '', $lastNumber['space_number'])) + 1;
                return 'SP-' . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
            return 'SP-001';
        } catch (Exception $e) {
            error_log('Space_model getNextSpaceNumber error: ' . $e->getMessage());
            return 'SP-001';
        }
    }
    
    public function getByProperty($propertyId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE property_id = ? 
                 ORDER BY space_number, space_name",
                [$propertyId]
            );
        } catch (Exception $e) {
            error_log('Space_model getByProperty error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getBookableSpaces($propertyId = null) {
        try {
            $sql = "SELECT s.*, p.property_name, p.property_code 
                    FROM `" . $this->db->getPrefix() . $this->table . "` s
                    JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                    WHERE s.is_bookable = 1 AND s.operational_status = 'active'";
            $params = [];
            
            if ($propertyId) {
                $sql .= " AND s.property_id = ?";
                $params[] = $propertyId;
            }
            
            $sql .= " ORDER BY p.property_name, s.space_name";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Space_model getBookableSpaces error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithProperty($spaceId) {
        try {
            return $this->db->fetchOne(
                "SELECT s.*, p.property_name, p.property_code, p.address 
                 FROM `" . $this->db->getPrefix() . $this->table . "` s
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 WHERE s.id = ?",
                [$spaceId]
            );
        } catch (Exception $e) {
            error_log('Space_model getWithProperty error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getPhotos($spaceId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "space_photos` 
                 WHERE space_id = ? 
                 ORDER BY is_primary DESC, display_order ASC",
                [$spaceId]
            );
        } catch (Exception $e) {
            error_log('Space_model getPhotos error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getBookableConfig($spaceId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "bookable_config` 
                 WHERE space_id = ?",
                [$spaceId]
            );
        } catch (Exception $e) {
            error_log('Space_model getBookableConfig error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync space to booking module facilities table
     */
    public function syncToBookingModule($spaceId) {
        try {
            $space = $this->getWithProperty($spaceId);
            if (!$space || empty($space['is_bookable']) || $space['is_bookable'] == 0) {
                error_log('Space_model syncToBookingModule: Space ' . $spaceId . ' is not bookable (is_bookable=' . ($space['is_bookable'] ?? 'null') . ')');
                return false;
            }
            
            $config = $this->getBookableConfig($spaceId);
            // If no config exists, create a default one
            if (!$config) {
                $defaultConfig = [
                    'space_id' => $spaceId,
                    'is_bookable' => 1,
                    'booking_types' => json_encode(['hourly', 'daily']),
                    'minimum_duration' => 1,
                    'maximum_duration' => null,
                    'advance_booking_days' => 365,
                    'pricing_rules' => json_encode([
                        'base_hourly' => floatval($space['hourly_rate'] ?? 5000),
                        'base_daily' => floatval($space['hourly_rate'] ?? 5000) * 8,
                        'half_day' => floatval($space['hourly_rate'] ?? 5000) * 4,
                        'weekly' => floatval($space['hourly_rate'] ?? 5000) * 40,
                        'deposit' => 0
                    ]),
                    'availability_rules' => json_encode([
                        'operating_hours' => ['start' => '08:00', 'end' => '22:00'],
                        'days_available' => [0,1,2,3,4,5,6],
                        'blackout_dates' => []
                    ]),
                    'setup_time_buffer' => 0,
                    'cleanup_time_buffer' => 0,
                    'simultaneous_limit' => 1
                ];
                
                try {
                    require_once BASEPATH . 'models/Bookable_config_model.php';
                    $configModel = new Bookable_config_model($this->db);
                    $configModel->create($defaultConfig);
                    $config = $this->getBookableConfig($spaceId);
                } catch (Exception $e) {
                    error_log('Space_model syncToBookingModule create default config error: ' . $e->getMessage());
                    // Continue with default values
                    $config = $defaultConfig;
                }
            }
            
            // Load Facility_model using database connection
            require_once BASEPATH . 'models/Facility_model.php';
            $facilityModel = new Facility_model($this->db);
            
            // Check if facility already exists
            $existingFacility = $space['facility_id'] 
                ? $facilityModel->getById($space['facility_id']) 
                : false;
            
            // Prepare facility data
            $pricingRules = json_decode($config['pricing_rules'] ?? '{}', true) ?: [];
            $bookingTypes = json_decode($config['booking_types'] ?? '[]', true) ?: [];
            
            $facilityData = [
                'facility_code' => $space['space_number'] ?: ('SP-' . $spaceId),
                'facility_name' => $space['space_name'],
                'description' => $space['description'] ?? '',
                'capacity' => $space['capacity'] ?? 0,
                'hourly_rate' => $pricingRules['hourly'] ?? $pricingRules['base_hourly'] ?? 0,
                'daily_rate' => $pricingRules['daily'] ?? $pricingRules['base_daily'] ?? 0,
                'half_day_rate' => $pricingRules['half_day'] ?? 0,
                'weekly_rate' => $pricingRules['weekly'] ?? 0,
                'minimum_duration' => $config['minimum_duration'] ?? 1,
                'max_duration' => $config['maximum_duration'] ?? null,
                'setup_time' => $config['setup_time_buffer'] ?? 0,
                'cleanup_time' => $config['cleanup_time_buffer'] ?? 0,
                'security_deposit' => $pricingRules['deposit'] ?? 0,
                'resource_type' => $this->mapCategoryToResourceType($space['category']),
                'status' => $space['operational_status'] === 'active' ? 'available' : 'under_maintenance',
                'is_bookable' => 1
            ];
            
            if ($existingFacility) {
                // Update existing facility
                $facilityModel->update($space['facility_id'], $facilityData);
                $facilityId = $space['facility_id'];
            } else {
                // Create new facility
                $facilityId = $facilityModel->create($facilityData);
                
                // Update space with facility_id
                $this->update($spaceId, ['facility_id' => $facilityId]);
            }
            
            // Update last synced time
            $this->db->update(
                'bookable_config',
                ['last_synced_at' => date('Y-m-d H:i:s')],
                "space_id = ?",
                [$spaceId]
            );
            
            // Sync availability rules to Resource_availability table
            $this->syncAvailabilityRules($facilityId, $config);
            
            return $facilityId;
        } catch (Exception $e) {
            error_log('Space_model syncToBookingModule error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync availability rules from bookable_config to Resource_availability table
     */
    private function syncAvailabilityRules($facilityId, $config) {
        try {
            require_once BASEPATH . 'models/Resource_availability_model.php';
            $availabilityModel = new Resource_availability_model($this->db);
            
            $availabilityRules = json_decode($config['availability_rules'] ?? '{}', true) ?: [];
            $operatingHours = $availabilityRules['operating_hours'] ?? ['start' => '08:00', 'end' => '22:00'];
            $daysAvailable = $availabilityRules['days_available'] ?? [0,1,2,3,4,5,6];
            
            // Sync availability for each day of the week (0 = Sunday, 6 = Saturday)
            for ($day = 0; $day <= 6; $day++) {
                $isAvailable = in_array($day, $daysAvailable);
                $availabilityModel->setDayAvailability(
                    $facilityId,
                    $day,
                    $isAvailable,
                    $isAvailable ? $operatingHours['start'] : null,
                    $isAvailable ? $operatingHours['end'] : null
                );
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Space_model syncAvailabilityRules error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function mapCategoryToResourceType($category) {
        $mapping = [
            'event_space' => 'hall',
            'commercial' => 'meeting_room',
            'hospitality' => 'other',
            'storage' => 'equipment',
            'parking' => 'other',
            'residential' => 'other',
            'other' => 'other'
        ];
        
        return $mapping[$category] ?? 'other';
    }
}

