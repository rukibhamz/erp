<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH . 'helpers/payment_config_helper.php';
require_once BASEPATH . 'helpers/flutterwave_split_helper.php';

/**
 * Start an online gateway payment for an existing booking balance.
 *
 * @return array{success:bool,authorization_url?:string,message?:string,gateway_code?:string,fallback_used?:bool}
 */
function initiate_booking_gateway_payment($bookingId, $requestedGatewayCode, $bookingModel, $gatewayModel, $paymentTransactionModel) {
    $bookingId = (int) $bookingId;
    if ($bookingId <= 0) {
        return ['success' => false, 'message' => 'Invalid booking.'];
    }

    $booking = $bookingModel->getById($bookingId);
    if (!$booking) {
        return ['success' => false, 'message' => 'Booking not found.'];
    }

    $balance = floatval($booking['balance_amount'] ?? $booking['total_amount'] ?? 0);
    if ($balance <= 0) {
        return ['success' => false, 'message' => 'This booking is already fully paid.', 'already_paid' => true];
    }

    if (in_array($booking['status'] ?? '', ['cancelled', 'refunded'], true)) {
        return ['success' => false, 'message' => 'Cannot pay for a cancelled booking.'];
    }

    $gatewayPath = BASEPATH . 'libraries/Payment_gateway.php';
    if (!file_exists($gatewayPath)) {
        return ['success' => false, 'message' => 'Payment gateway is not available.'];
    }

    require_once $gatewayPath;

    $resolved = resolve_payment_gateway($gatewayModel, $requestedGatewayCode);
    if (!$resolved) {
        return ['success' => false, 'message' => 'No online payment gateway is available.'];
    }

    $gateway = $resolved['gateway'];
    $gatewayCode = $resolved['gateway_code'];
    if ($resolved['fallback_used'] && $resolved['requested_code']) {
        error_log("Booking payment: gateway fallback {$resolved['requested_code']} -> {$gatewayCode}");
    }

    $gatewayConfig = merge_gateway_config($gatewayCode, $gateway);
    $gatewayConfig['callback_url'] = payment_callback_url($gatewayCode);

    $paymentGateway = new Payment_gateway($gatewayCode, $gatewayConfig);

    $bookingNumber = $booking['booking_number'] ?? ('BK-' . $bookingId);
    $transactionRef = 'BKG-' . $bookingNumber . '-' . time();

    $metadata = [
        'transaction_ref' => $transactionRef,
        'payment_type' => 'booking_payment',
        'reference_id' => $bookingId,
        'booking_id' => $bookingId,
        'description' => 'Payment for booking ' . $bookingNumber,
    ];

    $currency = 'NGN';
    $splitMeta = ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
    if ($gatewayCode === 'flutterwave') {
        $splitMeta = flutterwave_resolve_split_for_booking($bookingId, $currency, $gatewayConfig);
        if (!empty($splitMeta['subaccounts'])) {
            $metadata['subaccounts'] = $splitMeta['subaccounts'];
        }
    }

    $transactionId = $paymentTransactionModel->create([
        'transaction_ref' => $transactionRef,
        'payment_type' => 'booking_payment',
        'reference_id' => $bookingId,
        'gateway_code' => $gatewayCode,
        'amount' => $balance,
        'currency' => $currency,
        'status' => 'pending',
        'customer_email' => $booking['customer_email'] ?? '',
        'customer_name' => $booking['customer_name'] ?? '',
        'description' => 'Payment for booking ' . $bookingNumber,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    if ($transactionId && $gatewayCode === 'flutterwave') {
        flutterwave_log_split_on_transaction($paymentTransactionModel, $transactionId, $splitMeta, $gatewayConfig);
    }

    try {
        $paymentResult = $paymentGateway->initialize(
            $balance,
            $currency,
            [
                'email' => $booking['customer_email'] ?? '',
                'name' => $booking['customer_name'] ?? '',
                'phone' => $booking['customer_phone'] ?? '',
            ],
            $metadata
        );
    } catch (Exception $e) {
        error_log('initiate_booking_gateway_payment: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }

    if (!empty($paymentResult['success']) && !empty($paymentResult['authorization_url'])) {
        return [
            'success' => true,
            'authorization_url' => $paymentResult['authorization_url'],
            'gateway_code' => $gatewayCode,
            'fallback_used' => $resolved['fallback_used'],
            'transaction_ref' => $transactionRef,
        ];
    }

    return [
        'success' => false,
        'message' => $paymentResult['message'] ?? 'Could not start online payment.',
    ];
}

/**
 * Active gateways configured for checkout UI.
 */
function get_usable_payment_gateways($gatewayModel) {
    $gateways = [];
    foreach ($gatewayModel->getActive() as $gw) {
        if (payment_gateway_is_usable($gw)) {
            $gateways[] = $gw;
        }
    }
    return $gateways;
}

/**
 * Resolve payable amount on the server (never trust client POST amount).
 *
 * @return array{success:bool,amount?:float,currency?:string,message?:string}
 */
function resolve_server_payment_amount(string $paymentType, int $referenceId) {
    $referenceId = (int) $referenceId;
    if ($referenceId <= 0) {
        return ['success' => false, 'message' => 'Invalid payment reference.'];
    }

    $db = Database::getInstance();
    $prefix = $db->getPrefix();

    if ($paymentType === 'booking_payment') {
        $booking = $db->fetchOne(
            "SELECT balance_amount, total_amount, paid_amount, status FROM `{$prefix}bookings` WHERE id = ?",
            [$referenceId]
        );
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }
        if (in_array($booking['status'] ?? '', ['cancelled', 'refunded'], true)) {
            return ['success' => false, 'message' => 'Cannot pay for this booking.'];
        }
        $amount = floatval($booking['balance_amount'] ?? 0);
        if ($amount <= 0) {
            $amount = max(0, floatval($booking['total_amount'] ?? 0) - floatval($booking['paid_amount'] ?? 0));
        }
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'This booking has no outstanding balance.'];
        }
        return ['success' => true, 'amount' => round($amount, 2), 'currency' => 'NGN'];
    }

    if ($paymentType === 'invoice_payment') {
        $invoice = $db->fetchOne(
            "SELECT balance_amount, total_amount, paid_amount, status FROM `{$prefix}invoices` WHERE id = ?",
            [$referenceId]
        );
        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }
        $amount = floatval($invoice['balance_amount'] ?? 0);
        if ($amount <= 0) {
            $amount = max(0, floatval($invoice['total_amount'] ?? 0) - floatval($invoice['paid_amount'] ?? 0));
        }
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'This invoice has no outstanding balance.'];
        }
        return ['success' => true, 'amount' => round($amount, 2), 'currency' => 'NGN'];
    }

    if ($paymentType === 'rent_payment') {
        $rent = $db->fetchOne(
            "SELECT balance_amount, total_amount, amount, paid_amount FROM `{$prefix}rent_invoices` WHERE id = ?",
            [$referenceId]
        );
        if (!$rent) {
            return ['success' => false, 'message' => 'Rent invoice not found.'];
        }
        $amount = floatval($rent['balance_amount'] ?? 0);
        if ($amount <= 0) {
            $total = floatval($rent['total_amount'] ?? $rent['amount'] ?? 0);
            $amount = max(0, $total - floatval($rent['paid_amount'] ?? 0));
        }
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'No outstanding rent balance.'];
        }
        return ['success' => true, 'amount' => round($amount, 2), 'currency' => 'NGN'];
    }

    return ['success' => false, 'message' => 'Unsupported payment type.'];
}
