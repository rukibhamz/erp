<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_payment_model extends Base_Model {
    protected $table = 'booking_payments';
    
    public function getNextPaymentNumber() {
        try {
            // Only consider records where payment_number looks like BPAY-{digits}
            // to avoid corrupt/scientific notation values being picked up by MAX()
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(payment_number, 6) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE payment_number REGEXP '^BPAY-[0-9]+$'"
            );
            $nextNum = intval($result['max_num'] ?? 0) + 1;
            return 'BPAY-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Booking_payment_model getNextPaymentNumber error: ' . $e->getMessage());
            return 'BPAY-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    public function getByBooking($bookingId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_id = ? 
                 ORDER BY payment_date DESC, created_at DESC",
                [$bookingId]
            );
        } catch (Exception $e) {
            error_log('Booking_payment_model getByBooking error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalPaid($bookingId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_id = ? AND status = 'completed'",
                [$bookingId]
            );
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Booking_payment_model getTotalPaid error: ' . $e->getMessage());
            return 0;
        }
    }
}

