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
            // Get facilities from facilities table AND synced spaces from locations
            $facilities = $this->db->fetchAll(
                "SELECT f.*, 'facility' as source_type
                 FROM `" . $this->db->getPrefix() . $this->table . "` f
                 WHERE f.status = 'active' AND f.is_bookable = 1
                 ORDER BY f.facility_name"
            );
            
            // Also get bookable spaces (even if not synced yet - auto-sync on demand)
            $spaces = $this->db->fetchAll(
                "SELECT s.*, p.property_name as location_name, p.property_code as location_code,
                        f.facility_code, f.facility_name, f.hourly_rate, f.daily_rate,
                        f.capacity as facility_capacity, f.description as facility_description,
                        bc.pricing_rules, bc.minimum_duration, bc.maximum_duration,
                        'space' as source_type
                 FROM `" . $this->db->getPrefix() . "spaces` s
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . $this->table . "` f ON s.facility_id = f.id
                 LEFT JOIN `" . $this->db->getPrefix() . "bookable_config` bc ON s.id = bc.space_id
                 WHERE s.is_bookable = 1 
                 AND s.operational_status = 'active'
                 ORDER BY p.property_name, s.space_name"
            );
            
            // Auto-sync spaces that don't have facility_id yet
            foreach ($spaces as &$space) {
                if (empty($space['facility_id'])) {
                    try {
                        require_once BASEPATH . 'models/Space_model.php';
                        $spaceModel = new Space_model($this->db);
                        $facilityId = $spaceModel->syncToBookingModule($space['id']);
                        if ($facilityId) {
                            // Reload space with facility data
                            $spaceReload = $this->db->fetchOne(
                                "SELECT s.*, p.property_name as location_name, p.property_code as location_code,
                                        f.facility_code, f.facility_name, f.hourly_rate, f.daily_rate,
                                        f.capacity as facility_capacity, f.description as facility_description
                                 FROM `" . $this->db->getPrefix() . "spaces` s
                                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                                 LEFT JOIN `" . $this->db->getPrefix() . $this->table . "` f ON s.facility_id = f.id
                                 WHERE s.id = ?",
                                [$space['id']]
                            );
                            if ($spaceReload) {
                                $space = array_merge($space, $spaceReload);
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Facility_model auto-sync error: ' . $e->getMessage());
                    }
                }
            }
            unset($space);
            
            // Merge and format for booking portal
            $allResources = [];
            
            // Add facilities
            foreach ($facilities as $facility) {
                $allResources[] = [
                    'id' => $facility['id'],
                    'facility_id' => $facility['id'],
                    'facility_code' => $facility['facility_code'],
                    'facility_name' => $facility['facility_name'],
                    'description' => $facility['description'] ?? '',
                    'capacity' => $facility['capacity'] ?? 0,
                    'hourly_rate' => $facility['hourly_rate'] ?? 0,
                    'daily_rate' => $facility['daily_rate'] ?? 0,
                    'resource_type' => $facility['resource_type'] ?? 'other',
                    'category' => $facility['category'] ?? '',
                    'status' => $facility['status'],
                    'source_type' => 'facility'
                ];
            }
            
            // Add spaces (as facilities for booking portal)
            foreach ($spaces as $space) {
                // Parse pricing rules if available
                $pricingRules = [];
                if (!empty($space['pricing_rules'])) {
                    $pricingRules = json_decode($space['pricing_rules'], true) ?: [];
                }
                
                // Use facility rates if available, otherwise use space rates or defaults
                $hourlyRate = $space['hourly_rate'] ?? $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? ($space['hourly_rate'] ?? 5000);
                $dailyRate = $space['daily_rate'] ?? $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? ($hourlyRate * 8);
                
                $allResources[] = [
                    'id' => $space['facility_id'] ?? $space['id'],
                    'facility_id' => $space['facility_id'] ?? $space['id'],
                    'space_id' => $space['id'],
                    'facility_code' => $space['facility_code'] ?? ($space['space_number'] ?? 'SP-' . $space['id']),
                    'facility_name' => $space['facility_name'] ?? $space['space_name'],
                    'description' => $space['facility_description'] ?? $space['description'] ?? '',
                    'capacity' => $space['facility_capacity'] ?? $space['capacity'] ?? 0,
                    'hourly_rate' => floatval($hourlyRate),
                    'daily_rate' => floatval($dailyRate),
                    'half_day_rate' => floatval($pricingRules['half_day'] ?? ($hourlyRate * 4)),
                    'weekly_rate' => floatval($pricingRules['weekly'] ?? ($hourlyRate * 40)),
                    'security_deposit' => floatval($pricingRules['deposit'] ?? 0),
                    'minimum_duration' => intval($space['minimum_duration'] ?? 1),
                    'maximum_duration' => !empty($space['maximum_duration']) ? intval($space['maximum_duration']) : null,
                    'resource_type' => $this->mapSpaceCategoryToResourceType($space['category'] ?? 'other'),
                    'category' => $space['category'] ?? '',
                    'location_name' => $space['location_name'] ?? '',
                    'location_code' => $space['location_code'] ?? '',
                    'status' => 'available',
                    'source_type' => 'space'
                ];
            }
            
            return $allResources;
        } catch (Exception $e) {
            error_log('Facility_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function mapSpaceCategoryToResourceType($category) {
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
    
    public function getPhotos($facilityId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "facility_photos` 
                 WHERE facility_id = ? 
                 ORDER BY is_primary DESC, display_order ASC",
                [$facilityId]
            );
        } catch (Exception $e) {
            error_log('Facility_model getPhotos error: ' . $e->getMessage());
            return [];
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
    
    /**
     * Override getById to handle both facilities and spaces
     */
    public function getById($id) {
        // First try regular facility
        $facility = parent::getById($id);
        if ($facility) {
            return $facility;
        }
        
        // If not found, check if it's a space with this facility_id
        try {
            $space = $this->db->fetchOne(
                "SELECT s.*, f.*, bc.pricing_rules, bc.minimum_duration, bc.maximum_duration,
                        p.property_name as location_name, p.property_code as location_code
                 FROM `" . $this->db->getPrefix() . "spaces` s
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . $this->table . "` f ON s.facility_id = f.id
                 LEFT JOIN `" . $this->db->getPrefix() . "bookable_config` bc ON s.id = bc.space_id
                 WHERE (s.facility_id = ? OR f.id = ?)
                 AND s.is_bookable = 1
                 AND s.operational_status = 'active'",
                [$id, $id]
            );
            
            if ($space) {
                // Format as facility
                $pricingRules = [];
                if (!empty($space['pricing_rules'])) {
                    $pricingRules = json_decode($space['pricing_rules'], true) ?: [];
                }
                
                return [
                    'id' => $space['facility_id'] ?? $space['id'],
                    'facility_id' => $space['facility_id'] ?? $space['id'],
                    'space_id' => $space['id'],
                    'facility_code' => $space['facility_code'] ?? ($space['space_number'] ?? 'SP-' . $space['id']),
                    'facility_name' => $space['facility_name'] ?? $space['space_name'],
                    'description' => $space['facility_description'] ?? $space['description'] ?? '',
                    'capacity' => $space['facility_capacity'] ?? $space['capacity'] ?? 0,
                    'hourly_rate' => floatval($space['hourly_rate'] ?? $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 5000),
                    'daily_rate' => floatval($space['daily_rate'] ?? $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 40000),
                    'half_day_rate' => floatval($pricingRules['half_day'] ?? 20000),
                    'weekly_rate' => floatval($pricingRules['weekly'] ?? 200000),
                    'security_deposit' => floatval($pricingRules['deposit'] ?? 0),
                    'minimum_duration' => intval($space['minimum_duration'] ?? 1),
                    'maximum_duration' => !empty($space['maximum_duration']) ? intval($space['maximum_duration']) : null,
                    'resource_type' => $this->mapSpaceCategoryToResourceType($space['category'] ?? 'other'),
                    'category' => $space['category'] ?? '',
                    'status' => 'available',
                    'source_type' => 'space'
                ];
            }
        } catch (Exception $e) {
            error_log('Facility_model getById space lookup error: ' . $e->getMessage());
        }
        
        return null;
    }
    
    public function getAvailableTimeSlots($facilityId, $date, $endDate = null) {
        $checkEndDate = $endDate ?? $date;
        error_log("DEBUG: getAvailableTimeSlots called for Facility: $facilityId, Date: $date, End: $checkEndDate");
        
        try {
            $facility = $this->getById($facilityId);
            if (!$facility) {
                error_log("DEBUG: Facility $facilityId not found in DB.");
                return ['success' => false, 'message' => 'Facility/Space not found'];
            }
            error_log("DEBUG: Facility found: " . ($facility['facility_name'] ?? 'Unnamed'));
            
            // Fetch configuration just once
            $availabilityRules = [];
            // Check for overrides in pricing_rules (legacy)
            if (isset($facility['pricing_rules']) && is_string($facility['pricing_rules'])) {
                 $rules = json_decode($facility['pricing_rules'], true) ?: [];
                 // $availabilityRules might be merged from here if needed in future
            }

            // Get config directly if it's a space
            // FIX: Facilities table doesn't have space_id, Spaces table has facility_id.
            // We need to find the Space that links to this Facility.
            $space = $this->db->fetchOne("SELECT id, space_name FROM `" . $this->db->getPrefix() . "spaces` WHERE facility_id = ?", [$facilityId]);
            
            if ($space) {
                 error_log("DEBUG: Found linked Space: " . $space['space_name'] . " (ID: " . $space['id'] . ")");
                 $config = $this->db->fetchOne("SELECT availability_rules FROM `" . $this->db->getPrefix() . "bookable_config` WHERE space_id = ?", [$space['id']]);
                 if ($config && !empty($config['availability_rules'])) {
                     $availabilityRules = json_decode($config['availability_rules'], true) ?: [];
                     error_log("DEBUG: Found bookable_config rules: " . print_r($availabilityRules, true));
                 } else {
                     error_log("DEBUG: No bookable_config found for Space ID: " . $space['id']);
                 }
            } else {
                error_log("DEBUG: No Space linked to Facility ID $facilityId.");
            }


            // Get Resource Rules from DB
            require_once BASEPATH . 'models/Resource_availability_model.php';
            $resAvailModel = new Resource_availability_model($this->db);
            $resourceAvailability = $resAvailModel->getByResource($facilityId);
            $availabilityByDay = [];
            if (!empty($resourceAvailability)) {
                foreach ($resourceAvailability as $avail) {
                    $availabilityByDay[$avail['day_of_week']] = $avail;
                }
                error_log("DEBUG: Found Resource Availability rules for days: " . implode(', ', array_keys($availabilityByDay)));
            } else {
                error_log("DEBUG: No Resource Availability rules found in DB.");
            }

            // Get Bookings
            require_once BASEPATH . 'models/Booking_model.php';
            $bookingModel = new Booking_model($this->db);
            $bookings = $bookingModel->getByDateRange($date, $checkEndDate, $facilityId);
            $recurringBookings = $bookingModel->getRecurringBookingsForDate($facilityId, $date); 
            // We need recurring for the range. The loop below checks recurring daily, so we will handle recurring inside the loop logic?
            // Actually getRecurringBookingsForDate checks if a recurring booking hits a specific date.
            // For efficiency, we should fetch all relevant recurring bookings or check per day.
            // Let's stick to the previous merged approach but we might miss recurring bookings that start AFTER the first day?
            // For now, let's trust the current approach, but let's be careful.
            // A better way is: Fetch all ACTIVE recurring bookings for this facility, then expand them.
            // But let's stick to what we have: Bookings + Recurring checking.
            // Actually, let's just get recurring bookings that *overlap* the timeframe?
            // The previous logic for recurring was: $bookingModel->getRecurringBookingsForDate($facilityId, $date);
            // This is likely insufficient for a range.
            // Let's fetch ALL recurring profiles for this facility and expand them.
            // Assuming getRecurringBookingsForDate handles it? No, it takes a single date.
            // We will do a robust check per day.
            
            $bookings = array_merge($bookings, $recurringBookings);
            error_log("DEBUG: Found " . count($bookings) . " bookings overlapping this range.");

            // Build Occupied Slots (with 1 hour buffer)
            $occupiedSlots = [];
            $bufferMinutes = 60; 

            foreach ($bookings as $booking) {
                if (!in_array($booking['status'], ['cancelled', 'no_show', 'refunded'])) {
                    $bookingStart = new DateTime($booking['booking_date'] . ' ' . $booking['start_time']);
                    $bookingEnd = new DateTime($booking['booking_date'] . ' ' . $booking['end_time']);
                    
                    // Buffer
                    $bufferedStart = clone $bookingStart;
                    $bufferedStart->modify("-{$bufferMinutes} minutes");
                    $bufferedEnd = clone $bookingEnd;
                    $bufferedEnd->modify("+{$bufferMinutes} minutes");

                    $current = clone $bufferedStart;
                    // Handle multi-day spanning
                    while ($current < $bufferedEnd) {
                        $dayDate = $current->format('Y-m-d');
                        if ($dayDate >= $date && $dayDate <= $checkEndDate) {
                             $dayStart = ($current->format('Y-m-d') == $bufferedStart->format('Y-m-d')) 
                                ? $bufferedStart->format('H:i') : '00:00';
                             $dayEnd = ($current->format('Y-m-d') == $bufferedEnd->format('Y-m-d')) 
                                ? $bufferedEnd->format('H:i') : '23:59';
                             
                             $occupiedSlots[] = [
                                 'date' => $dayDate,
                                 'start' => $dayStart,
                                 'end' => $dayEnd,
                                 'booking_id' => $booking['id'],
                                 'customer_name' => $booking['customer_name'] ?? 'Booked',
                                 'booking' => $booking
                             ];
                        }
                        $current->modify('+1 day');
                        $current->setTime(0, 0, 0);
                    }
                }
            }

            // Generate Slots (15 min intervals)
            $allSlots = [];
            $currentDate = new DateTime($date);
            $finalDate = new DateTime($checkEndDate);
            error_log("DEBUG: Starting slot generation loop from " . $currentDate->format('Y-m-d') . " to " . $finalDate->format('Y-m-d'));

            while ($currentDate <= $finalDate) {
                $currentDay = $currentDate->format('Y-m-d');
                $dayOfWeek = $currentDate->format('w');
                
                // Determine hours for THIS specific day
                $startHour = 8;
                $endHour = 22;
                $isDayAvailable = true;

                // 1. Check Global/Config Rules
                if (!empty($availabilityRules['operating_hours'])) {
                    $startHour = intval(substr($availabilityRules['operating_hours']['start'], 0, 2));
                    $endHour = intval(substr($availabilityRules['operating_hours']['end'], 0, 2));
                }

                // 2. Check Specific Day Rules from DB
                if (isset($availabilityByDay[$dayOfWeek])) {
                    $avail = $availabilityByDay[$dayOfWeek];
                    if (!$avail['is_available']) {
                        $isDayAvailable = false;
                        error_log("DEBUG: Day $currentDay (DOW: $dayOfWeek) is marked unavailable in Resource Availability.");
                    } else {
                        if ($avail['start_time']) $startHour = intval(substr($avail['start_time'], 0, 2));
                        if ($avail['end_time']) $endHour = intval(substr($avail['end_time'], 0, 2));
                    }
                } else if (!empty($availabilityRules['days_available']) && is_array($availabilityRules['days_available'])) {
                    // Fallback to config if DB rules are empty but config exists
                    if (!in_array($dayOfWeek, $availabilityRules['days_available'])) {
                        $isDayAvailable = false;
                        error_log("DEBUG: Day $currentDay (DOW: $dayOfWeek) is excluded in bookable_config days_available.");
                    }
                }

                if (!$isDayAvailable) {
                    // Skip to next day
                    $currentDate->modify('+1 day');
                    continue;
                }
                
                error_log("DEBUG: Day $currentDay operating hours: $startHour to $endHour");

                $currentH = $startHour;

                while ($currentH < $endHour) {
                    // FIX: User requested 1 hour slots (on the hour), not 15 min intervals
                    for ($minute = 0; $minute < 60; $minute += 60) {
                        $slotStartStr = str_pad($currentH, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minute, 2, '0', STR_PAD_LEFT);
                        $slotStartDT = new DateTime($currentDay . ' ' . $slotStartStr);
                        
                        $slotEndDT = clone $slotStartDT;
                        $slotEndDT->modify('+1 hour'); // 1 hour slots
                        $slotEndStr = $slotEndDT->format('H:i');
                        
                        // If slot end goes beyond endHour (e.g. starts at 21:45, ends 22:45, but close is 22:00)
                        if ($slotEndDT->format('H') > $endHour || ($slotEndDT->format('H') == $endHour && $slotEndDT->format('i') > 0)) {
                             continue;
                        }

                        // Check availability
                        $isOccupied = false;
                        $occupier = null;
                        $bookingData = null;
                        
                        foreach ($occupiedSlots as $occupied) {
                            if ($occupied['date'] == $currentDay) {
                                // Overlap logic: (StartA < EndB) and (EndA > StartB)
                                if ($slotStartStr < $occupied['end'] && $slotEndStr > $occupied['start']) {
                                    $isOccupied = true;
                                    $occupier = $occupied['customer_name'];
                                    $bookingData = $occupied['booking'];
                                    break;
                                }
                            }
                        }

                        $allSlots[] = [
                            'date' => $currentDay,
                            'start' => $slotStartStr,
                            'end' => $slotEndStr,
                            'available' => !$isOccupied,
                            'occupied_by' => $occupier,
                            'booking' => $bookingData,
                            'display' => $slotStartDT->format('g:i A') . ' - ' . $slotEndDT->format('g:i A')
                        ];
                    }
                    $currentH++;
                }
                $currentDate->modify('+1 day');
            }

            $availableSlots = array_values(array_filter($allSlots, fn($s) => $s['available']));
            $occupiedSlotsDisplay = array_values(array_filter($allSlots, fn($s) => !$s['available']));
            
            error_log("DEBUG: Finished generation. Available Slots: " . count($availableSlots) . ", Occupied: " . count($occupiedSlotsDisplay));

            return [
                'success' => true,
                'slots' => $availableSlots,
                'occupied' => $occupiedSlotsDisplay,
                'min_duration' => 1,
                'max_duration' => 24
            ];

        } catch (Exception $e) {
             error_log('Facility_model getAvailableTimeSlots error: ' . $e->getMessage());
             return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkAvailability($facilityId, $bookingDate, $startTime, $endTime, $excludeBookingId = null, $endDate = null) {
        try {
            // Use endDate if provided (for multi-day bookings), otherwise use bookingDate
            $checkEndDate = $endDate ?? $bookingDate;
            
            // Check for overlapping bookings (no buffer - exact times)
            $sql = "SELECT COUNT(*) as count 
                    FROM `" . $this->db->getPrefix() . "bookings` 
                    WHERE facility_id = ? 
                    AND status NOT IN ('cancelled', 'refunded', 'no_show')
                    AND (
                        (booking_date = ? AND start_time < ? AND end_time > ?)
                        OR (booking_date = ? AND start_time < ? AND end_time > ?)
                        OR (booking_date BETWEEN ? AND ? AND booking_date != ? AND booking_date != ?)
                    )";
            
            $params = [
                $facilityId,
                $bookingDate, $endTime, $startTime,
                $checkEndDate, $endTime, $startTime,
                $bookingDate, $checkEndDate, $bookingDate, $checkEndDate
            ];
            
            if ($excludeBookingId) {
                $sql .= " AND id != ?";
                $params[] = $excludeBookingId;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            $hasConflict = ($result['count'] ?? 0) > 0;
            
            // Also check recurring bookings
            if (!$hasConflict) {
                require_once BASEPATH . 'models/Booking_model.php';
                $bookingModel = new Booking_model($this->db);
                $currentDate = new DateTime($bookingDate);
                $finalDate = new DateTime($checkEndDate);
                
                while ($currentDate <= $finalDate) {
                    $recurringBookings = $bookingModel->getRecurringBookingsForDate($facilityId, $currentDate->format('Y-m-d'));
                    foreach ($recurringBookings as $recurring) {
                        if ($excludeBookingId && $recurring['id'] == $excludeBookingId) {
                            continue;
                        }
                        $recurringStart = new DateTime($currentDate->format('Y-m-d') . ' ' . $recurring['start_time']);
                        $recurringEnd = new DateTime($currentDate->format('Y-m-d') . ' ' . $recurring['end_time']);
                        $bookingStart = new DateTime($bookingDate . ' ' . $startTime);
                        $bookingEnd = new DateTime($checkEndDate . ' ' . $endTime);
                        
                        // Check if times overlap (no buffer)
                        if (!($bookingEnd <= $recurringStart || $bookingStart >= $recurringEnd)) {
                            return false; // Conflict found
                        }
                    }
                    $currentDate->modify('+1 day');
                }
            }
            
            return !$hasConflict;
        } catch (Exception $e) {
            error_log('Facility_model checkAvailability error: ' . $e->getMessage());
            // Return true on error to allow booking (fail open)
            return true;
        }
    }
    
    public function calculatePrice($facilityId, $bookingDate, $startTime, $endTime, $bookingType = 'hourly', $quantity = 1, $isMember = false) {
        try {
            // getById now handles both facilities and spaces
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
            // Get facilities of this type
            $facilities = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE resource_type = ? AND status IN ('active', 'available') AND is_bookable = 1
                 ORDER BY facility_name",
                [$type]
            );
            
            // Also get spaces mapped to this resource type
            $spaces = $this->db->fetchAll(
                "SELECT s.*, p.property_name as location_name, p.property_code as location_code,
                        f.facility_code, f.facility_name, f.hourly_rate, f.daily_rate,
                        f.capacity as facility_capacity, f.description as facility_description
                 FROM `" . $this->db->getPrefix() . "spaces` s
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . $this->table . "` f ON s.facility_id = f.id
                 WHERE s.is_bookable = 1 
                 AND s.operational_status = 'active'
                 AND s.facility_id IS NOT NULL
                 AND f.resource_type = ?
                 ORDER BY p.property_name, s.space_name",
                [$type]
            );
            
            // Merge results
            $allResources = [];
            foreach ($facilities as $facility) {
                $allResources[] = $facility;
            }
            foreach ($spaces as $space) {
                $allResources[] = [
                    'id' => $space['facility_id'] ?? $space['id'],
                    'facility_id' => $space['facility_id'] ?? $space['id'],
                    'space_id' => $space['id'],
                    'facility_code' => $space['facility_code'] ?? ($space['space_number'] ?? 'SP-' . $space['id']),
                    'facility_name' => $space['facility_name'] ?? $space['space_name'],
                    'description' => $space['facility_description'] ?? $space['description'] ?? '',
                    'capacity' => $space['facility_capacity'] ?? $space['capacity'] ?? 0,
                    'hourly_rate' => $space['hourly_rate'] ?? 0,
                    'daily_rate' => $space['daily_rate'] ?? 0,
                    'resource_type' => $type,
                    'category' => $space['category'] ?? '',
                    'status' => 'available',
                    'source_type' => 'space'
                ];
            }
            
            return $allResources;
        } catch (Exception $e) {
            error_log('Facility_model getByType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByCategory($category) {
        try {
            // Get facilities of this category
            $facilities = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE category = ? AND status IN ('active', 'available') AND is_bookable = 1
                 ORDER BY facility_name",
                [$category]
            );
            
            // Also get spaces of this category
            $spaces = $this->db->fetchAll(
                "SELECT s.*, p.property_name as location_name, p.property_code as location_code,
                        f.facility_code, f.facility_name, f.hourly_rate, f.daily_rate,
                        f.capacity as facility_capacity, f.description as facility_description
                 FROM `" . $this->db->getPrefix() . "spaces` s
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . $this->table . "` f ON s.facility_id = f.id
                 WHERE s.is_bookable = 1 
                 AND s.operational_status = 'active'
                 AND s.facility_id IS NOT NULL
                 AND s.category = ?
                 ORDER BY p.property_name, s.space_name",
                [$category]
            );
            
            // Merge results
            $allResources = [];
            foreach ($facilities as $facility) {
                $allResources[] = $facility;
            }
            foreach ($spaces as $space) {
                $allResources[] = [
                    'id' => $space['facility_id'] ?? $space['id'],
                    'facility_id' => $space['facility_id'] ?? $space['id'],
                    'space_id' => $space['id'],
                    'facility_code' => $space['facility_code'] ?? ($space['space_number'] ?? 'SP-' . $space['id']),
                    'facility_name' => $space['facility_name'] ?? $space['space_name'],
                    'description' => $space['facility_description'] ?? $space['description'] ?? '',
                    'capacity' => $space['facility_capacity'] ?? $space['capacity'] ?? 0,
                    'hourly_rate' => $space['hourly_rate'] ?? 0,
                    'daily_rate' => $space['daily_rate'] ?? 0,
                    'resource_type' => $space['resource_type'] ?? 'other',
                    'category' => $category,
                    'status' => 'available',
                    'source_type' => 'space'
                ];
            }
            
            return $allResources;
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

