<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_schedule_model extends Base_Model {
    protected $table = 'payment_schedule';
    
    public function getByBooking($bookingId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_id = ? 
                 ORDER BY payment_number ASC",
                [$bookingId]
            );
        } catch (Exception $e) {
            error_log('Payment_schedule_model getByBooking error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function createSchedule($bookingId, $paymentPlan, $totalAmount, $depositPercentage = null) {
        try {
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();
            
            // Delete existing schedule
            $this->db->delete($this->table, "booking_id = ?", [$bookingId]);
            
            $schedules = [];
            
            switch ($paymentPlan) {
                case 'full':
                    // Single payment due immediately
                    $schedules[] = [
                        'booking_id' => $bookingId,
                        'payment_number' => 1,
                        'due_date' => date('Y-m-d'),
                        'amount' => $totalAmount,
                        'status' => 'pending'
                    ];
                    break;
                    
                case 'deposit':
                    $depositPercent = $depositPercentage ?: 50;
                    $depositAmount = ($totalAmount * $depositPercent) / 100;
                    $balanceAmount = $totalAmount - $depositAmount;
                    
                    // Deposit due immediately
                    $schedules[] = [
                        'booking_id' => $bookingId,
                        'payment_number' => 1,
                        'due_date' => date('Y-m-d'),
                        'amount' => $depositAmount,
                        'status' => 'pending'
                    ];
                    
                    // Balance due 7 days before booking
                    $booking = $this->loadModel('Booking_model')->getById($bookingId);
                    if ($booking) {
                        $dueDate = date('Y-m-d', strtotime($booking['booking_date'] . ' -7 days'));
                        $schedules[] = [
                            'booking_id' => $bookingId,
                            'payment_number' => 2,
                            'due_date' => $dueDate,
                            'amount' => $balanceAmount,
                            'status' => 'pending'
                        ];
                    }
                    break;
                    
                case 'installment':
                    // Split into 3 equal payments
                    $installmentAmount = $totalAmount / 3;
                    $booking = $this->loadModel('Booking_model')->getById($bookingId);
                    
                    if ($booking) {
                        $bookingDate = strtotime($booking['booking_date']);
                        
                        // First payment: immediate
                        $schedules[] = [
                            'booking_id' => $bookingId,
                            'payment_number' => 1,
                            'due_date' => date('Y-m-d'),
                            'amount' => $installmentAmount,
                            'status' => 'pending'
                        ];
                        
                        // Second payment: 30 days before booking
                        $schedules[] = [
                            'booking_id' => $bookingId,
                            'payment_number' => 2,
                            'due_date' => date('Y-m-d', $bookingDate - (30 * 24 * 60 * 60)),
                            'amount' => $installmentAmount,
                            'status' => 'pending'
                        ];
                        
                        // Third payment: 7 days before booking
                        $schedules[] = [
                            'booking_id' => $bookingId,
                            'payment_number' => 3,
                            'due_date' => date('Y-m-d', $bookingDate - (7 * 24 * 60 * 60)),
                            'amount' => $installmentAmount,
                            'status' => 'pending'
                        ];
                    }
                    break;
                    
                case 'pay_later':
                    // Single payment due on booking date
                    $booking = $this->loadModel('Booking_model')->getById($bookingId);
                    $dueDate = $booking ? $booking['booking_date'] : date('Y-m-d');
                    
                    $schedules[] = [
                        'booking_id' => $bookingId,
                        'payment_number' => 1,
                        'due_date' => $dueDate,
                        'amount' => $totalAmount,
                        'status' => 'pending'
                    ];
                    break;
            }
            
            // Insert schedules
            foreach ($schedules as $schedule) {
                $this->db->insert($this->table, $schedule);
            }
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Payment_schedule_model createSchedule error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function recordPayment($scheduleId, $amount) {
        try {
            $schedule = $this->getById($scheduleId);
            if (!$schedule) {
                return false;
            }
            
            $newPaidAmount = floatval($schedule['paid_amount']) + $amount;
            $remainingAmount = floatval($schedule['amount']) - $newPaidAmount;
            
            $updateData = [
                'paid_amount' => $newPaidAmount,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($remainingAmount <= 0) {
                $updateData['status'] = 'paid';
                $updateData['paid_at'] = date('Y-m-d H:i:s');
            } elseif ($newPaidAmount > 0) {
                $updateData['status'] = 'partial';
            }
            
            return $this->update($scheduleId, $updateData);
        } catch (Exception $e) {
            error_log('Payment_schedule_model recordPayment error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getOverdue() {
        try {
            return $this->db->fetchAll(
                "SELECT ps.*, b.booking_number, b.booking_date, b.customer_name, b.customer_email 
                 FROM `" . $this->db->getPrefix() . $this->table . "` ps
                 JOIN `" . $this->db->getPrefix() . "bookings` b ON ps.booking_id = b.id
                 WHERE ps.status IN ('pending', 'partial')
                 AND ps.due_date < CURDATE()
                 ORDER BY ps.due_date ASC"
            );
        } catch (Exception $e) {
            error_log('Payment_schedule_model getOverdue error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }
}

