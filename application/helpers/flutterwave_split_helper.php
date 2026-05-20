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
 * Build subaccounts[] for Flutterwave — ID only; split %/amount is configured on Flutterwave.
 *
 * @param string[] $subaccountIds Flutterwave RS_… ids
 * @return array{subaccounts:array, rule_id:?int, subaccount_id:?string}
 */
function flutterwave_build_subaccounts_payload(array $subaccountIds, $ruleId = null) {
    $subaccountIds = array_values(array_filter(array_map('strval', $subaccountIds)));
    if ($subaccountIds === []) {
        return ['subaccounts' => [], 'rule_id' => $ruleId, 'subaccount_id' => null];
    }

    $subaccounts = [];
    foreach ($subaccountIds as $id) {
        $subaccounts[] = ['id' => $id];
    }

    return [
        'subaccounts' => $subaccounts,
        'rule_id' => $ruleId,
        'subaccount_id' => $subaccountIds[0],
    ];
}

/**
 * Active linked subaccounts to attach when splits are enabled (Flutterwave handles settlement).
 */
function flutterwave_resolve_linked_subaccounts(array $gatewayConfig) {
    if (!flutterwave_subaccounts_enabled($gatewayConfig)) {
        return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
    }

    try {
        require_once BASEPATH . 'models/Flutterwave_subaccount_model.php';
        $model = new Flutterwave_subaccount_model();
        $active = $model->getAllActive();
        if ($active === []) {
            return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
        }

        $defaultRow = $model->getDefaultActive();
        if ($defaultRow && !empty($defaultRow['subaccount_id'])) {
            return flutterwave_build_subaccounts_payload([$defaultRow['subaccount_id']]);
        }

        if (count($active) === 1) {
            return flutterwave_build_subaccounts_payload([$active[0]['subaccount_id']]);
        }

        $ids = array_column($active, 'subaccount_id');
        return flutterwave_build_subaccounts_payload($ids);
    } catch (Exception $e) {
        error_log('flutterwave_resolve_linked_subaccounts: ' . $e->getMessage());
        return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
    }
}

/**
 * Resolve subaccounts for a booking payment (optional per-scope rule, else linked codes).
 *
 * @return array{subaccounts:array, rule_id:?int, subaccount_id:?string}
 */
function flutterwave_resolve_split_for_booking($bookingId, $currency, $gatewayConfig) {
    if (!flutterwave_subaccounts_enabled($gatewayConfig)) {
        return ['subaccounts' => [], 'rule_id' => null, 'subaccount_id' => null];
    }

    $bookingId = (int) $bookingId;
    if ($bookingId > 0) {
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

            if ($booking) {
                $spaceId = (int) ($booking['space_id'] ?: $booking['facility_id'] ?: 0);
                $propertyId = (int) ($booking['property_id'] ?? 0);

                require_once BASEPATH . 'models/Flutterwave_split_rule_model.php';
                $ruleModel = new Flutterwave_split_rule_model();
                $rule = $ruleModel->resolveForBooking($spaceId ?: null, $propertyId ?: null, $currency);

                if ($rule && !empty($rule['subaccount_id'])) {
                    return flutterwave_build_subaccounts_payload(
                        [$rule['subaccount_id']],
                        isset($rule['id']) ? (int) $rule['id'] : null
                    );
                }
            }
        } catch (Exception $e) {
            error_log('flutterwave_resolve_split_for_booking: ' . $e->getMessage());
        }
    }

    return flutterwave_resolve_linked_subaccounts($gatewayConfig);
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
