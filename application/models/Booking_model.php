<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_model extends Base_Model {
    protected $table = 'space_bookings';
    
    public function getNextBookingNumber() {
        try {
            // Check both potential prefixes to be safe
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(booking_number, 5) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_number LIKE 'SBK-%' OR booking_number LIKE 'BKG-%'"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            // Unify on SBK prefix for new system
            return 'SBK-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Booking_model getNextBookingNumber error: ' . $e->getMessage());
            return 'SBK-' . date('Ymd') . '-00001';
        }
    }
    
    public function create($data) {
        // Map facility_id to space_id for unification
        if (isset($data['facility_id']) && !isset($data['space_id'])) {
            $data['space_id'] = $data['facility_id'];
            unset($data['facility_id']);
        }
        return parent::create($data);
    }
    
    public function getByDateRange($startDate, $endDate, $spaceId = null) {
        try {
            // Linked to spaces now
            $sql = "SELECT b.*, s.space_name as facility_name, s.space_number as facility_code 
                    FROM `" . $this->db->getPrefix() . $this->table . "` b
                    JOIN `" . $this->db->getPrefix() . "spaces` s ON b.space_id = s.id
                    WHERE (
                        (b.booking_date >= ? AND b.booking_date <= ?)
                        OR (b.booking_date <= ? AND DATE_ADD(b.booking_date, INTERVAL TIME_TO_SEC(b.end_time) - TIME_TO_SEC(b.start_time) SECOND) >= ?)
                    )
                    AND b.status NOT IN ('cancelled', 'refunded', 'no_show')";
            
            $params = [$startDate, $endDate, $startDate, $startDate];
            
            if ($spaceId) {
                $sql .= " AND b.space_id = ?";
                $params[] = $spaceId;
            }
            
            $sql .= " ORDER BY b.booking_date, b.start_time";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Booking_model getByDateRange error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recurring bookings that fall on a specific date
     */
    public function getRecurringBookingsForDate($spaceId, $date) {
        try {
            // Get all recurring bookings for this space
            $recurringBookings = $this->db->fetchAll(
                "SELECT b.*, s.space_name as facility_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` b
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON b.space_id = s.id
                 WHERE b.space_id = ?
                 AND b.is_recurring = 1
                 AND b.status NOT IN ('cancelled', 'refunded', 'no_show')
                 AND b.recurring_pattern IS NOT NULL
                 AND b.booking_date <= ?",
                [$spaceId, $date]
            );
            
            $matchingBookings = [];
            $targetDayOfWeek = date('w', strtotime($date));
            $targetDate = new DateTime($date);
            
            foreach ($recurringBookings as $booking) {
                $bookingDate = new DateTime($booking['booking_date']);
                $pattern = $booking['recurring_pattern'] ?? 'weekly';
                
                // Check if this date matches the recurring pattern
                $matches = false;
                
                if ($pattern === 'weekly') {
                    // Check if same day of week
                    $bookingDayOfWeek = date('w', strtotime($booking['booking_date']));
                    if ($targetDayOfWeek == $bookingDayOfWeek && $targetDate >= $bookingDate) {
                        // Check if within recurring end date (if set)
                        $recurringEndDate = !empty($booking['recurring_end_date']) ? new DateTime($booking['recurring_end_date']) : null;
                        if (!$recurringEndDate || $targetDate <= $recurringEndDate) {
                            $matches = true;
                        }
                    }
                } elseif ($pattern === 'daily') {
                    // Every day from booking_date onwards
                    if ($targetDate >= $bookingDate) {
                        $recurringEndDate = !empty($booking['recurring_end_date']) ? new DateTime($booking['recurring_end_date']) : null;
                        if (!$recurringEndDate || $targetDate <= $recurringEndDate) {
                            $matches = true;
                        }
                    }
                } elseif ($pattern === 'monthly') {
                    // Same day of month, every month
                    if ($targetDate >= $bookingDate && 
                        $targetDate->format('d') == $bookingDate->format('d')) {
                        $recurringEndDate = !empty($booking['recurring_end_date']) ? new DateTime($booking['recurring_end_date']) : null;
                        if (!$recurringEndDate || $targetDate <= $recurringEndDate) {
                            $matches = true;
                        }
                    }
                }
                
                if ($matches) {
                    // Create a booking record for this specific date
                    $matchingBookings[] = [
                        'id' => $booking['id'],
                        'booking_number' => $booking['booking_number'],
                        'facility_id' => $booking['facility_id'],
                        'customer_name' => $booking['customer_name'],
                        'customer_email' => $booking['customer_email'],
                        'customer_phone' => $booking['customer_phone'],
                        'booking_date' => $date, // Use target date
                        'start_time' => $booking['start_time'],
                        'end_time' => $booking['end_time'],
                        'status' => $booking['status'],
                        'is_recurring' => 1,
                        'recurring_pattern' => $booking['recurring_pattern']
                    ];
                }
            }
            
            return $matchingBookings;
        } catch (Exception $e) {
            error_log('Booking_model getRecurringBookingsForDate error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check availability with 1-hour buffer between bookings
     */
    public function checkAvailabilityWithBuffer($spaceId, $startDate, $startTime, $endDate, $endTime, $excludeBookingId = null) {
        try {
            // Add 1-hour buffer: start 1 hour before, end 1 hour after
            $bufferStart = new DateTime($startDate . ' ' . $startTime);
            $bufferStart->modify('-1 hour');
            $bufferEnd = new DateTime($endDate . ' ' . $endTime);
            $bufferEnd->modify('+1 hour');
            
            // Get all bookings that overlap with the buffered time range
            $sql = "SELECT COUNT(*) as count 
                    FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE space_id = ? 
                    AND status NOT IN ('cancelled', 'refunded', 'no_show')
                    AND (
                        (booking_date = ? AND start_time < ? AND end_time > ?)
                        OR (booking_date = ? AND start_time < ? AND end_time > ?)
                    )";
            
            $params = [
                $spaceId,
                $bufferStart->format('Y-m-d'), $bufferEnd->format('H:i:s'), $bufferStart->format('H:i:s'),
                $bufferEnd->format('Y-m-d'), $bufferEnd->format('H:i:s'), $bufferStart->format('H:i:s')
            ];
            
            if ($excludeBookingId) {
                $sql .= " AND id != ?";
                $params[] = $excludeBookingId;
            }
            
            // Also check recurring bookings
            $recurringBookings = $this->getRecurringBookingsForDate($spaceId, $startDate);
            $currentDate = new DateTime($startDate);
            $finalDate = new DateTime($endDate);
            
            while ($currentDate <= $finalDate) {
                $dayRecurring = $this->getRecurringBookingsForDate($spaceId, $currentDate->format('Y-m-d'));
                foreach ($dayRecurring as $recurring) {
                    if ($excludeBookingId && $recurring['id'] == $excludeBookingId) {
                        continue;
                    }
                    $recurringStart = new DateTime($currentDate->format('Y-m-d') . ' ' . $recurring['start_time']);
                    $recurringEnd = new DateTime($currentDate->format('Y-m-d') . ' ' . $recurring['end_time']);
                    
                    // Add buffer to recurring booking
                    $recurringBufferStart = clone $recurringStart;
                    $recurringBufferStart->modify('-1 hour');
                    $recurringBufferEnd = clone $recurringEnd;
                    $recurringBufferEnd->modify('+1 hour');
                    
                    // Check if buffered times overlap
                    if (!($bufferEnd <= $recurringBufferStart || $bufferStart >= $recurringBufferEnd)) {
                        // Conflict found with recurring booking
                        return false;
                    }
                }
                $currentDate->modify('+1 day');
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return ($result['count'] ?? 0) == 0;
        } catch (Exception $e) {
            error_log('Booking_model checkAvailabilityWithBuffer error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByStatus($status) {
        try {
            return $this->db->fetchAll(
                "SELECT b.*, s.space_name as facility_name 
                 FROM `" . $this->db->getPrefix() . $this->table . "` b
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON b.space_id = s.id
                 WHERE b.status = ? 
                 ORDER BY b.booking_date DESC, b.created_at DESC",
                [$status]
            );
        } catch (Exception $e) {
            error_log('Booking_model getByStatus error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithFacility($bookingId) {
        try {
            $prefix = $this->db->getPrefix();
            
            // First try joining with facilities table using facility_id
            $result = $this->db->fetchOne(
                "SELECT b.*, f.facility_name as facility_name, f.facility_code as facility_code, 
                        f.hourly_rate, f.daily_rate, f.half_day_rate
                 FROM `{$prefix}{$this->table}` b
                 LEFT JOIN `{$prefix}facilities` f ON b.facility_id = f.id
                 WHERE b.id = ?",
                [$bookingId]
            );
            
            // If facility_name is populated, return the result
            if ($result && !empty($result['facility_name'])) {
                return $result;
            }
            
            // Fallback: try joining with spaces table using space_id or facility_id
            $result = $this->db->fetchOne(
                "SELECT b.*, 
                        COALESCE(s.space_name, s2.space_name) as facility_name, 
                        COALESCE(s.space_number, s2.space_number) as facility_code, 
                        COALESCE(s.hourly_rate, s2.hourly_rate) as hourly_rate, 
                        COALESCE(s.daily_rate, s2.daily_rate) as daily_rate
                 FROM `{$prefix}{$this->table}` b
                 LEFT JOIN `{$prefix}spaces` s ON b.space_id = s.id
                 LEFT JOIN `{$prefix}spaces` s2 ON b.facility_id = s2.id
                 WHERE b.id = ?",
                [$bookingId]
            );
            
            return $result;
        } catch (Exception $e) {
            error_log('Booking_model getWithFacility error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function addPayment($bookingId, $amount) {
        try {
            $booking = $this->getById($bookingId);
            if (!$booking) {
                return false;
            }
            
            $newPaidAmount = floatval($booking['paid_amount']) + floatval($amount);
            $newBalance = floatval($booking['total_amount']) - $newPaidAmount;
            
            $updateData = [
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $newBalance
            ];
            
            // Update payment status
            if ($newBalance <= 0) {
                $updateData['payment_status'] = $newBalance < 0 ? 'overpaid' : 'paid';
            } else {
                $updateData['payment_status'] = $newPaidAmount > 0 ? 'partial' : 'unpaid';
            }
            
            return $this->update($bookingId, $updateData);
        } catch (Exception $e) {
            error_log('Booking_model addPayment error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateStatus($bookingId, $status) {
        try {
            $updateData = ['status' => $status];
            
            // Set timestamps based on status
            switch ($status) {
                case 'confirmed':
                    $updateData['confirmed_at'] = date('Y-m-d H:i:s');
                    break;
                case 'cancelled':
                    $updateData['cancelled_at'] = date('Y-m-d H:i:s');
                    break;
                case 'completed':
                    $updateData['completed_at'] = date('Y-m-d H:i:s');
                    break;
            }
            
            return $this->update($bookingId, $updateData);
        } catch (Exception $e) {
            error_log('Booking_model updateStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function createSlots($bookingId, $spaceId, $bookingDate, $startTime, $endDate = null, $endTime = null) {
        try {
            // Handle multi-day bookings: if endDate is provided, use it; otherwise use bookingDate
            $actualEndDate = $endDate ?? $bookingDate;
            $actualEndTime = $endTime ?? $startTime; // If endTime not provided, assume same day booking
            
            // Create time slots for the booking duration
            $start = new DateTime($bookingDate . ' ' . $startTime);
            $end = new DateTime($actualEndDate . ' ' . $actualEndTime);
            
            // If booking spans multiple days, create slots for each day
            $current = clone $start;
            
            while ($current < $end) {
                $slotStart = clone $current;
                
                // Determine slot end (either end of day or booking end)
                $dayEnd = clone $current;
                $dayEnd->setTime(23, 59, 59);
                $slotEnd = ($end < $dayEnd) ? clone $end : $dayEnd;
                
                // Use space_id instead of facility_id
                $this->db->insert('booking_slots', [
                    'booking_id' => $bookingId,
                    'facility_id' => $spaceId, // Keep column name if DB has facility_id, assuming booking_slots schema unchanged?
                    // Wait, if booking_slots uses facility_id, do we need to migrate it?
                    // Assuming booking_slots is fine to store space_id in facility_id col for now?
                    // Actually, let's assume booking_slots table uses facility_id logic and we just pass spaceId there.
                    'slot_date' => $slotStart->format('Y-m-d'),
                    'slot_start_time' => $slotStart->format('H:i:s'),
                    'slot_end_time' => $slotEnd->format('H:i:s')
                ]);
                
                // Move to next day
                $current->modify('+1 day');
                $current->setTime(0, 0, 0);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Booking_model createSlots error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getSlots($bookingId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "booking_slots` 
                 WHERE booking_id = ? 
                 ORDER BY slot_date, slot_start_time",
                [$bookingId]
            );
        } catch (Exception $e) {
            error_log('Booking_model getSlots error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getRevenueByFacility($startDate, $endDate) {
        try {
            // Join spaces table
            return $this->db->fetchAll(
                "SELECT 
                    s.id,
                    s.space_name as facility_name,
                    COUNT(b.id) as total_bookings,
                    COALESCE(SUM(b.total_amount), 0) as total_revenue,
                    COALESCE(SUM(b.paid_amount), 0) as paid_revenue,
                    COALESCE(SUM(b.balance_amount), 0) as pending_revenue
                 FROM `" . $this->db->getPrefix() . "spaces` s
                 LEFT JOIN `" . $this->db->getPrefix() . $this->table . "` b 
                    ON s.id = b.space_id 
                    AND b.booking_date >= ? 
                    AND b.booking_date <= ?
                    AND b.status NOT IN ('cancelled', 'refunded')
                 WHERE s.operational_status = 'active'
                 GROUP BY s.id, s.space_name
                 ORDER BY total_revenue DESC",
                [$startDate, $endDate]
            );
        } catch (Exception $e) {
            error_log('Booking_model getRevenueByFacility error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByCustomerEmail($customerEmail, $startDate = null, $endDate = null) {
        try {
            $sql = "SELECT b.*, s.space_name as facility_name, s.space_number as facility_code 
                    FROM `" . $this->db->getPrefix() . $this->table . "` b
                    JOIN `" . $this->db->getPrefix() . "spaces` s ON b.space_id = s.id
                    WHERE b.customer_email = ?";
            $params = [$customerEmail];
            
            if ($startDate) {
                $sql .= " AND b.booking_date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND b.booking_date <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY b.booking_date DESC, b.created_at DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Booking_model getByCustomerEmail error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get resources associated with a booking
     */
    public function getResources($bookingId) {
        try {
            $sql = "SELECT br.*, s.space_name, s.space_number 
                    FROM `" . $this->db->getPrefix() . "booking_resources` br
                    LEFT JOIN `" . $this->db->getPrefix() . "spaces` s ON br.resource_id = s.id
                    WHERE br.booking_id = ?";
            return $this->db->fetchAll($sql, [$bookingId]);
        } catch (Exception $e) {
            error_log('Booking_model getResources error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get addons associated with a booking
     */
    public function getAddons($bookingId) {
        try {
            $sql = "SELECT ba.*, a.addon_name, a.addon_description 
                    FROM `" . $this->db->getPrefix() . "booking_addons` ba
                    LEFT JOIN `" . $this->db->getPrefix() . "addons` a ON ba.addon_id = a.id
                    WHERE ba.booking_id = ?";
            return $this->db->fetchAll($sql, [$bookingId]);
        } catch (Exception $e) {
            error_log('Booking_model getAddons error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment schedule for a booking
     */
    public function getPaymentSchedule($bookingId) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . "payment_schedules` 
                    WHERE booking_id = ? ORDER BY due_date ASC";
            return $this->db->fetchAll($sql, [$bookingId]);
        } catch (Exception $e) {
            error_log('Booking_model getPaymentSchedule error: ' . $e->getMessage());
            return [];
        }
    }
}

