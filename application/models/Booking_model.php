<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_model extends Base_Model {
    protected $table = 'bookings';
    
    public function getNextBookingNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(booking_number, 4) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_number LIKE 'BKG-%'"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            return 'BKG-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Booking_model getNextBookingNumber error: ' . $e->getMessage());
            return 'BKG-' . date('Ymd') . '-00001';
        }
    }
    
    public function getByDateRange($startDate, $endDate, $facilityId = null) {
        try {
            // Get bookings that overlap with the date range
            // This includes bookings that start before endDate and end after startDate
            $sql = "SELECT b.*, f.facility_name, f.facility_code 
                    FROM `" . $this->db->getPrefix() . $this->table . "` b
                    JOIN `" . $this->db->getPrefix() . "facilities` f ON b.facility_id = f.id
                    WHERE (
                        (b.booking_date >= ? AND b.booking_date <= ?)
                        OR (b.booking_date <= ? AND DATE_ADD(b.booking_date, INTERVAL TIME_TO_SEC(b.end_time) - TIME_TO_SEC(b.start_time) SECOND) >= ?)
                    )
                    AND b.status NOT IN ('cancelled', 'refunded', 'no_show')";
            
            $params = [$startDate, $endDate, $startDate, $startDate];
            
            if ($facilityId) {
                $sql .= " AND b.facility_id = ?";
                $params[] = $facilityId;
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
    public function getRecurringBookingsForDate($facilityId, $date) {
        try {
            // Get all recurring bookings for this facility
            $recurringBookings = $this->db->fetchAll(
                "SELECT b.*, f.facility_name, f.facility_code 
                 FROM `" . $this->db->getPrefix() . $this->table . "` b
                 JOIN `" . $this->db->getPrefix() . "facilities` f ON b.facility_id = f.id
                 WHERE b.facility_id = ?
                 AND b.is_recurring = 1
                 AND b.status NOT IN ('cancelled', 'refunded', 'no_show')
                 AND b.recurring_pattern IS NOT NULL
                 AND b.booking_date <= ?",
                [$facilityId, $date]
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
    public function checkAvailabilityWithBuffer($facilityId, $startDate, $startTime, $endDate, $endTime, $excludeBookingId = null) {
        try {
            // Add 1-hour buffer: start 1 hour before, end 1 hour after
            $bufferStart = new DateTime($startDate . ' ' . $startTime);
            $bufferStart->modify('-1 hour');
            $bufferEnd = new DateTime($endDate . ' ' . $endTime);
            $bufferEnd->modify('+1 hour');
            
            // Get all bookings that overlap with the buffered time range
            $sql = "SELECT COUNT(*) as count 
                    FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE facility_id = ? 
                    AND status NOT IN ('cancelled', 'refunded', 'no_show')
                    AND (
                        (booking_date = ? AND start_time < ? AND end_time > ?)
                        OR (booking_date = ? AND start_time < ? AND end_time > ?)
                    )";
            
            $params = [
                $facilityId,
                $bufferStart->format('Y-m-d'), $bufferEnd->format('H:i:s'), $bufferStart->format('H:i:s'),
                $bufferEnd->format('Y-m-d'), $bufferEnd->format('H:i:s'), $bufferStart->format('H:i:s')
            ];
            
            if ($excludeBookingId) {
                $sql .= " AND id != ?";
                $params[] = $excludeBookingId;
            }
            
            // Also check recurring bookings
            $recurringBookings = $this->getRecurringBookingsForDate($facilityId, $startDate);
            $currentDate = new DateTime($startDate);
            $finalDate = new DateTime($endDate);
            
            while ($currentDate <= $finalDate) {
                $dayRecurring = $this->getRecurringBookingsForDate($facilityId, $currentDate->format('Y-m-d'));
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
                "SELECT b.*, f.facility_name 
                 FROM `" . $this->db->getPrefix() . $this->table . "` b
                 JOIN `" . $this->db->getPrefix() . "facilities` f ON b.facility_id = f.id
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
            return $this->db->fetchOne(
                "SELECT b.*, f.facility_name, f.facility_code, f.hourly_rate, f.daily_rate 
                 FROM `" . $this->db->getPrefix() . $this->table . "` b
                 JOIN `" . $this->db->getPrefix() . "facilities` f ON b.facility_id = f.id
                 WHERE b.id = ?",
                [$bookingId]
            );
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
    
    public function createSlots($bookingId, $facilityId, $bookingDate, $startTime, $endDate = null, $endTime = null) {
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
                
                $this->db->insert('booking_slots', [
                    'booking_id' => $bookingId,
                    'facility_id' => $facilityId,
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
            return $this->db->fetchAll(
                "SELECT 
                    f.id,
                    f.facility_name,
                    COUNT(b.id) as total_bookings,
                    COALESCE(SUM(b.total_amount), 0) as total_revenue,
                    COALESCE(SUM(b.paid_amount), 0) as paid_revenue,
                    COALESCE(SUM(b.balance_amount), 0) as pending_revenue
                 FROM `" . $this->db->getPrefix() . "facilities` f
                 LEFT JOIN `" . $this->db->getPrefix() . $this->table . "` b 
                    ON f.id = b.facility_id 
                    AND b.booking_date >= ? 
                    AND b.booking_date <= ?
                    AND b.status NOT IN ('cancelled', 'refunded')
                 WHERE f.status = 'active' OR f.status = 'available'
                 GROUP BY f.id, f.facility_name
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
            $sql = "SELECT b.*, f.facility_name, f.facility_code 
                    FROM `" . $this->db->getPrefix() . $this->table . "` b
                    JOIN `" . $this->db->getPrefix() . "facilities` f ON b.facility_id = f.id
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
}

