<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_payment_model extends Base_Model {
    protected $table = 'booking_payments';
    
    public function getNextPaymentNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(payment_number, 5) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE payment_number LIKE 'BPAY-%'"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            return 'BPAY-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Booking_payment_model getNextPaymentNumber error: ' . $e->getMessage());
            return 'BPAY-' . date('Ymd') . '-00001';
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

