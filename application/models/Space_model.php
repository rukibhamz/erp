<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Space_model extends Base_Model {
    protected $table = 'spaces';
    private $lastSyncError = null;

    public function getLastSyncError() {
        return $this->lastSyncError;
    }
    
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
            // Check if is_bookable column exists first to avoid fatal errors during migration
            $checkColumn = $this->db->fetchOne("SHOW COLUMNS FROM `" . $this->db->getPrefix() . $this->table . "` LIKE 'is_bookable'");
            
            if (!$checkColumn) {
                error_log("Space_model: is_bookable column missing in " . $this->table);
                return [];
            }

            $sql = "SELECT s.*, p.property_name, p.property_code 
                    FROM `" . $this->db->getPrefix() . $this->table . "` s
                    JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                    WHERE s.is_bookable = 1
                    AND s.operational_status NOT IN ('decommissioned','temporarily_closed')";
            $params = [];
            
            if ($propertyId) {
                $sql .= " AND s.property_id = ?";
                $params[] = $propertyId;
            }
            
            $sql .= " ORDER BY p.property_name, s.space_name";
            
            $spaces = $this->db->fetchAll($sql, $params);

            // Fallback: if no bookable spaces found for this location, return all non-decommissioned spaces
            if (empty($spaces) && $propertyId) {
                $sql = "SELECT s.*, p.property_name, p.property_code 
                        FROM `" . $this->db->getPrefix() . $this->table . "` s
                        JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                        WHERE s.property_id = ?
                        AND s.operational_status NOT IN ('decommissioned')
                        ORDER BY s.space_name";
                $spaces = $this->db->fetchAll($sql, [$propertyId]);
            }

            return $spaces;
        } catch (Exception $e) {
            error_log('Space_model getBookableSpaces error: ' . $e->getMessage());
            return [];
        }
    }

    public function getBookingTypes($spaceId) {
        try {
            $space = $this->getById($spaceId);
            if (!$space) return [];
            
            $config = $this->db->fetchOne(
                "SELECT booking_types FROM `" . $this->db->getPrefix() . "bookable_config` 
                 WHERE space_id = ?", 
                [$spaceId]
            );
            
            if ($config && $config['booking_types']) {
                return json_decode($config['booking_types'], true) ?: [];
            }
            
            return ['hourly', 'daily']; // Default fallback
        } catch (Exception $e) {
            error_log('Space_model getBookingTypes error: ' . $e->getMessage());
            return ['hourly', 'daily'];
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

    public function addPhoto($data) {
        try {
            return $this->db->insert('space_photos', $data);
        } catch (Exception $e) {
            error_log('Space_model addPhoto error: ' . $e->getMessage());
            return false;
        }
    }

    public function deletePhoto($photoId) {
        try {
            // Get photo info first to delete file
            $photo = $this->db->fetchOne(
                "SELECT photo_url FROM `" . $this->db->getPrefix() . "space_photos` WHERE id = ?",
                [$photoId]
            );
            
            if ($photo && !empty($photo['photo_url'])) {
                $filePath = ROOTPATH . $photo['photo_url'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            return $this->db->delete('space_photos', 'id = ?', [$photoId]);
        } catch (Exception $e) {
            error_log('Space_model deletePhoto error: ' . $e->getMessage());
            return false;
        }
    }

    public function setPrimaryPhoto($spaceId, $photoId) {
        try {
            $this->db->beginTransaction();
            
            // Unset current primary
            $this->db->update('space_photos', ['is_primary' => 0], 'space_id = ?', [$spaceId]);
            
            // Set new primary
            $this->db->update('space_photos', ['is_primary' => 1], 'id = ?', [$photoId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Space_model setPrimaryPhoto error: ' . $e->getMessage());
            return false;
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
        $this->lastSyncError = null;
        try {
            $space = $this->getWithProperty($spaceId);
            if (!$space) {
                $this->lastSyncError = 'Space ID ' . $spaceId . ' not found in database.';
                error_log('Space_model syncToBookingModule: ' . $this->lastSyncError);
                return false;
            }
            
            // Check if bookable config exists - if it does, allow sync even if is_bookable is 0
            $config = $this->getBookableConfig($spaceId);
            $hasBookableConfig = !empty($config);
            
            // Use strict comparison to handle string "1" or integer 1
            // Also accept truthy values like "1", 1, true
            $isBookable = !empty($space['is_bookable']) && (intval($space['is_bookable']) == 1 || $space['is_bookable'] === true);
            
            // Always allow sync — mark space as bookable if it isn't already
            if (!$isBookable) {
                error_log('Space_model syncToBookingModule: Space is_bookable=0, marking as bookable to allow sync');
                $this->update($spaceId, ['is_bookable' => 1]);
                $space['is_bookable'] = 1;
            }
            
            error_log('Space_model syncToBookingModule: Space is bookable or has config, proceeding with sync');
            
            // If no config exists, create a default one
            if (!$config) {
                $defaultConfig = [
                    'space_id' => $spaceId,
                    'is_bookable' => 1,
                    'booking_types' => json_encode(['hourly', 'daily', 'half_day', 'weekly', 'multi_day']),
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
                    if (!class_exists('Bookable_config_model')) {
                        require_once APPPATH . 'models/Bookable_config_model.php';
                    }
                    $configModel = new Bookable_config_model();
                    $configModel->create($defaultConfig);
                    $config = $this->getBookableConfig($spaceId);
                } catch (Exception $e) {
                    error_log('Space_model syncToBookingModule create default config error: ' . $e->getMessage());
                    // Continue with default values
                    $config = $defaultConfig;
                }
            }
            
            // Load Facility_model
            if (!class_exists('Facility_model')) {
                require_once APPPATH . 'models/Facility_model.php';
            }
            $facilityModel = new Facility_model();
            
            // Check if facility already exists - use direct DB query to avoid recursion
            $existingFacility = null;
            if (!empty($space['facility_id'])) {
                $existingFacility = $this->db->fetchOne(
                    "SELECT * FROM `" . $this->db->getPrefix() . "facilities` WHERE id = ?",
                    [$space['facility_id']]
                );
            }
            
            // Prepare facility data
            $pricingRules = json_decode($config['pricing_rules'] ?? '{}', true) ?: [];
            $bookingTypes = json_decode($config['booking_types'] ?? '[]', true) ?: [];
            
            // Generate facility code only for new facilities
            $facilityCode = $existingFacility
                ? $existingFacility['facility_code']
                : ('FAC-' . $spaceId . '-' . substr(md5(uniqid()), 0, 6));
            
            // Extract pricing - check both key formats (base_hourly and hourly)
            $hourlyRate = floatval($pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 0);
            $dailyRate = floatval($pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 0);
            $halfDayRate = floatval($pricingRules['half_day'] ?? 0);
            $weeklyRate = floatval($pricingRules['weekly'] ?? 0);
            $deposit = floatval($pricingRules['deposit'] ?? $pricingRules['security_deposit'] ?? 0);
            
            error_log('Space_model syncToBookingModule: Extracted rates - hourly=' . $hourlyRate . ', daily=' . $dailyRate . ', half_day=' . $halfDayRate . ', weekly=' . $weeklyRate);
            
            $facilityDataFull = [
                'facility_code' => $facilityCode,
                'facility_name' => $space['space_name'],
                'description' => $space['description'] ?? '',
                'capacity' => $space['capacity'] ?? 0,
                'hourly_rate' => $hourlyRate,
                'daily_rate' => $dailyRate,
                'half_day_rate' => $halfDayRate,
                'weekly_rate' => $weeklyRate,
                'minimum_duration' => $config['minimum_duration'] ?? 1,
                'max_duration' => $config['maximum_duration'] ?? null,
                'setup_time' => $config['setup_time_buffer'] ?? 0,
                'cleanup_time' => $config['cleanup_time_buffer'] ?? 0,
                'security_deposit' => $deposit,
                'resource_type' => $this->mapCategoryToResourceType($space['category']),
                'status' => $space['operational_status'] === 'active' ? 'available' : 'under_maintenance',
                'is_bookable' => 1,
                'pricing_rules' => json_encode($pricingRules)
            ];

            // Filter to only columns that actually exist in the facilities table
            // This prevents INSERT/UPDATE failures on older production schemas
            $existingColumns = [];
            try {
                $cols = $this->db->fetchAll(
                    "SHOW COLUMNS FROM `" . $this->db->getPrefix() . "facilities`"
                );
                foreach ($cols as $col) {
                    $existingColumns[] = $col['Field'];
                }
            } catch (Exception $e) {
                // If we can't check columns, use all data and let DB handle it
                $existingColumns = array_keys($facilityDataFull);
            }
            $facilityData = array_intersect_key($facilityDataFull, array_flip($existingColumns));

            error_log('Space_model syncToBookingModule: Pricing rules from config: ' . json_encode($pricingRules));
            
            if ($existingFacility) {
                // Update existing facility
                error_log('Space_model syncToBookingModule: Updating existing facility ID ' . $space['facility_id']);
                $facilityModel->update($space['facility_id'], $facilityData);
                $facilityId = $space['facility_id'];
            } else {
                // Create new facility
                error_log('Space_model syncToBookingModule: Creating new facility');
                $facilityId = $facilityModel->create($facilityData);
                
                if (!$facilityId) {
                    $this->lastSyncError = 'Failed to create facility record in database. Check that the facilities table exists and has required columns.';
                    error_log('Space_model syncToBookingModule: ' . $this->lastSyncError);
                    return false;
                }
                
                // Update space with facility_id
                $this->update($spaceId, ['facility_id' => $facilityId]);
                error_log('Space_model syncToBookingModule: Created facility ID ' . $facilityId);
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
            
            error_log('Space_model syncToBookingModule: Sync completed successfully for space ' . $spaceId . ', facility ID ' . $facilityId);
            
            return $facilityId;
        } catch (Exception $e) {
            $this->lastSyncError = $e->getMessage();
            error_log('Space_model syncToBookingModule error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync availability rules from bookable_config to Resource_availability table
     */
    private function syncAvailabilityRules($facilityId, $config) {
        try {
            if (!class_exists('Resource_availability_model')) {
                require_once APPPATH . 'models/Resource_availability_model.php';
            }
            $availabilityModel = new Resource_availability_model();
            
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

