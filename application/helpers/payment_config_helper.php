<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Load payment.php config once per request.
 */
function payment_env_config() {
    static $config = null;
    if ($config === null) {
        $path = BASEPATH . 'config/payment.php';
        $config = file_exists($path) ? require $path : [];
    }
    return $config;
}

/**
 * Merge database gateway row with optional environment overrides.
 */
function merge_gateway_config($gatewayCode, array $dbGateway) {
    $env = payment_env_config();
    $code = strtolower($gatewayCode);
    $providerEnv = $env[$code] ?? [];

    $merged = [
        'public_key' => $dbGateway['public_key'] ?? '',
        'private_key' => $dbGateway['private_key'] ?? '',
        'secret_key' => $dbGateway['secret_key'] ?? '',
        'test_mode' => !empty($dbGateway['test_mode']),
        'callback_url' => $dbGateway['callback_url'] ?? '',
        'additional_config' => json_decode($dbGateway['additional_config'] ?? '{}', true) ?: [],
    ];

    if (!empty($providerEnv['public_key'])) {
        $merged['public_key'] = $providerEnv['public_key'];
    }
    if (!empty($providerEnv['secret_key'])) {
        $merged['private_key'] = $providerEnv['secret_key'];
    }
    if ($code === 'paystack' && !empty($providerEnv['test_secret_key'])) {
        $merged['test_secret_key'] = $providerEnv['test_secret_key'];
    }
    if ($code === 'flutterwave') {
        if (!empty($providerEnv['encryption_key'])) {
            $merged['additional_config']['encryption_key'] = $providerEnv['encryption_key'];
        }
        if (!empty($providerEnv['webhook_secret_hash'])) {
            $merged['secret_key'] = $providerEnv['webhook_secret_hash'];
        }
    }

    return $merged;
}

/**
 * Resolve active gateway code (env PAYMENT_PROVIDER or DB default).
 */
function resolve_payment_provider($gatewayModel) {
    $env = payment_env_config();
    if (!empty($env['default_provider'])) {
        return strtolower($env['default_provider']);
    }
    $default = $gatewayModel->getDefault();
    return $default ? strtolower($default['gateway_code']) : 'paystack';
}
