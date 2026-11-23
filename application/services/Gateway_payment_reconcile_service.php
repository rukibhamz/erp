<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Re-verify stale pending gateway transactions and fulfill successful ones
 * that never created booking_payments rows.
 *
 * Payment controller wires verify/fulfill callbacks; Cron uses the same path.
 */
class Gateway_payment_reconcile_service {
    /** @var Payment_transaction_model */
    private $paymentTransactionModel;
    /** @var callable */
    private $verifyPayment;
    /** @var callable */
    private $fulfillmentNeeded;
    /** @var callable */
    private $processSuccess;

    public function __construct(
        Payment_transaction_model $paymentTransactionModel,
        callable $verifyPayment,
        callable $fulfillmentNeeded,
        callable $processSuccess
    ) {
        $this->paymentTransactionModel = $paymentTransactionModel;
        $this->verifyPayment = $verifyPayment;
        $this->fulfillmentNeeded = $fulfillmentNeeded;
        $this->processSuccess = $processSuccess;
    }

    /**
     * @return array{pending_checked:int,pending_verified:int,fulfillment_retried:int,errors:array}
     */
    public function run(int $limit = 100): array {
        $stats = [
            'pending_checked' => 0,
            'pending_verified' => 0,
            'fulfillment_retried' => 0,
            'errors' => [],
        ];

        $limit = max(1, min(1000, $limit));

        try {
            $pending = $this->paymentTransactionModel->getStalePendingBookingPayments($limit);

            foreach ($pending as $txn) {
                $stats['pending_checked']++;
                $ref = $txn['transaction_ref'] ?? '';
                if ($ref === '') {
                    continue;
                }
                try {
                    $gatewayCode = $txn['gateway_code'] ?? 'paystack';
                    $result = ($this->verifyPayment)($ref, $gatewayCode);
                    if (empty($result['pending'])) {
                        $stats['pending_verified']++;
                    }
                } catch (Exception $e) {
                    $stats['errors'][] = $ref . ': ' . $e->getMessage();
                }
            }

            $unfulfilled = $this->paymentTransactionModel->getUnfulfilledSuccessfulBookingPayments($limit);

            foreach ($unfulfilled as $txn) {
                try {
                    if (($this->fulfillmentNeeded)($txn)) {
                        ($this->processSuccess)($txn);
                        $stats['fulfillment_retried']++;
                    }
                } catch (Exception $e) {
                    $stats['errors'][] = ($txn['transaction_ref'] ?? '?') . ': ' . $e->getMessage();
                }
            }
        } catch (Exception $e) {
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Bootstrap from a Payment controller instance (cron / admin).
     *
     * @param object $paymentController Payment controller with public reconcile helpers
     */
    public static function runViaPaymentController($paymentController, int $limit = 100): array {
        if (!is_object($paymentController) || !method_exists($paymentController, 'reconcileStaleGatewayPayments')) {
            return [
                'pending_checked' => 0,
                'pending_verified' => 0,
                'fulfillment_retried' => 0,
                'errors' => ['Payment controller unavailable for gateway reconcile'],
            ];
        }
        return $paymentController->reconcileStaleGatewayPayments($limit);
    }
}
