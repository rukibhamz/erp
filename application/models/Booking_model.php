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
            $sql = "SELECT b.*, f.facility_name, f.facility_code 
                    FROM `" . $this->db->getPrefix() . $this->table . "` b
                    JOIN `" . $this->db->getPrefix() . "facilities` f ON b.facility_id = f.id
                    WHERE b.booking_date >= ? AND b.booking_date <= ?
                    AND b.status NOT IN ('cancelled')";
            
            $params = [$startDate, $endDate];
            
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
    
    public function createSlots($bookingId, $facilityId, $bookingDate, $startTime, $endTime) {
        try {
            // Create time slots for the booking duration
            $start = new DateTime($bookingDate . ' ' . $startTime);
            $end = new DateTime($bookingDate . ' ' . $endTime);
            
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
                 WHERE f.status = 'active'
                 GROUP BY f.id, f.facility_name
                 ORDER BY total_revenue DESC",
                [$startDate, $endDate]
            );
        } catch (Exception $e) {
            error_log('Booking_model getRevenueByFacility error: ' . $e->getMessage());
            return [];
        }
    }
}

