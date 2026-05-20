<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once BASEPATH . 'helpers/payment_config_helper.php';

/**
 * Flutterwave subaccount / split payment helpers.
 */

function flutterwave_env_flag($name) {
    $val = getenv($name);
    if ($val === false || $val === '') {
        return null;
    }
    return in_array(strtolower((string) $val), ['1', 'true', 'yes', 'on'], true);
}

function flutterwave_subaccounts_enabled(array $gatewayConfig) {
    $env = flutterwave_env_flag('FLUTTERWAVE_ENABLE_SUBACCOUNTS');
    if ($env !== null) {
        return $env;
    }
    $additional = $gatewayConfig['additional_config'] ?? [];
    return !empty($additional['enable_subaccounts']);
}

function flutterwave_should_log_split(array $gatewayConfig) {
    $env = flutterwave_env_flag('FLUTTERWAVE_LOG_SPLIT');
    if ($env !== null) {
        return $env;
    }
    $additional = $gatewayConfig['additional_config'] ?? [];
    return !empty($additional['log_split_on_transactions']);
}

/**
 * Build subaccounts[] for Flutterwave Standard from a resolved split rule.
 *
 * @return array{subaccounts:array, rule_id:?int, subaccount_id:?string}
 */
function flutterwave_build_subaccounts_from_rule($rule) {
    if (!$rule || empty($rule['subaccount_id'])) {
        return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
    }

    $entry = ['id' => $rule['subaccount_id']];

    $overrideType = trim($rule['override_charge_type'] ?? '');
    if ($overrideType !== '' && isset($rule['override_charge']) && $rule['override_charge'] !== '' && $rule['override_charge'] !== null) {
        $entry['transaction_charge_type'] = $overrideType;
        $entry['transaction_charge'] = (float) $rule['override_charge'];
    }

    if (!empty($rule['split_ratio'])) {
        $entry['transaction_split_ratio'] = (int) $rule['split_ratio'];
    }

    return [
        'subaccounts' => [$entry],
        'rule_id' => isset($rule['id']) ? (int) $rule['id'] : null,
        'subaccount_id' => $rule['subaccount_id'],
    ];
}

/**
 * Resolve split for a booking payment.
 *
 * @return array{subaccounts:array, rule_id:?int, subaccount_id:?string}
 */
function flutterwave_resolve_split_for_booking($bookingId, $currency, $gatewayConfig) {
    if (!flutterwave_subaccounts_enabled($gatewayConfig)) {
        return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
    }

    $bookingId = (int) $bookingId;
    if ($bookingId <= 0) {
        return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
    }

    try {
        $db = Database::getInstance();
        $prefix = $db->getPrefix();

        $booking = $db->fetchOne(
            "SELECT b.id, b.space_id, b.facility_id, s.property_id
             FROM `{$prefix}bookings` b
             LEFT JOIN `{$prefix}spaces` s ON s.id = COALESCE(b.space_id, b.facility_id)
             WHERE b.id = ?",
            [$bookingId]
        );

        if (!$booking) {
            return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
        }

        $spaceId = (int) ($booking['space_id'] ?: $booking['facility_id'] ?: 0);
        $propertyId = (int) ($booking['property_id'] ?? 0);

        require_once BASEPATH . 'models/Flutterwave_split_rule_model.php';
        $ruleModel = new Flutterwave_split_rule_model();
        $rule = $ruleModel->resolveForBooking($spaceId ?: null, $propertyId ?: null, $currency);

        return flutterwave_build_subaccounts_from_rule($rule ?: false);
    } catch (Exception $e) {
        error_log('flutterwave_resolve_split_for_booking: ' . $e->getMessage());
        return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
    }
}

/**
 * Persist split audit on payment_transactions when logging is enabled.
 */
function flutterwave_log_split_on_transaction($paymentTransactionModel, $transactionId, array $splitMeta, array $gatewayConfig) {
    if (!flutterwave_should_log_split($gatewayConfig)) {
        return;
    }
    if (empty($splitMeta['subaccounts'])) {
        return;
    }

    $payload = [
        'subaccounts' => $splitMeta['subaccounts'],
        'rule_id' => $splitMeta['rule_id'] ?? null,
        'subaccount_id' => $splitMeta['subaccount_id'] ?? null,
        'logged_at' => date('c'),
    ];

    try {
        $paymentTransactionModel->update((int) $transactionId, [
            'split_applied' => 1,
            'split_payload' => json_encode($payload),
        ]);
    } catch (Exception $e) {
        error_log('flutterwave_log_split_on_transaction: ' . $e->getMessage());
    }
}

function flutterwave_mask_account_number($accountNumber) {
    $accountNumber = preg_replace('/\s+/', '', (string) $accountNumber);
    $len = strlen($accountNumber);
    if ($len <= 4) {
        return str_repeat('*', $len);
    }
    return str_repeat('*', max(0, $len - 4)) . substr($accountNumber, -4);
}
