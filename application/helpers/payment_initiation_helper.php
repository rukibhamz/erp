<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH . 'helpers/payment_config_helper.php';

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

    $paymentTransactionModel->create([
        'transaction_ref' => $transactionRef,
        'payment_type' => 'booking_payment',
        'reference_id' => $bookingId,
        'gateway_code' => $gatewayCode,
        'amount' => $balance,
        'currency' => 'NGN',
        'status' => 'pending',
        'customer_email' => $booking['customer_email'] ?? '',
        'customer_name' => $booking['customer_name'] ?? '',
        'description' => 'Payment for booking ' . $bookingNumber,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    try {
        $paymentResult = $paymentGateway->initialize(
            $balance,
            'NGN',
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
