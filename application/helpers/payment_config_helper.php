<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Load payment.php config once per request.
 */
/**
 * Customer redirect URL after gateway checkout (absolute).
 */
function payment_callback_url($gatewayCode = null) {
    $url = rtrim(base_url('payment/callback'), '/');
    if ($gatewayCode && strtolower($gatewayCode) === 'flutterwave') {
        if (stripos($url, 'gateway=flutterwave') === false) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'gateway=flutterwave';
        }
    }
    return $url;
}

/**
 * Server webhook URL for gateway dashboards.
 */
function payment_webhook_url($gatewayCode) {
    $code = strtolower($gatewayCode);
    if ($code === 'flutterwave') {
        return base_url('webhooks/flutterwave');
    }
    return base_url('payment/webhook?gateway=' . rawurlencode($code));
}

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
        $webhookHash = trim($providerEnv['webhook_secret_hash'] ?? '');
        if ($webhookHash === '') {
            $webhookHash = trim($dbGateway['secret_key'] ?? '');
        }
        if ($webhookHash === '' && !empty($merged['additional_config']['webhook_secret_hash'])) {
            $webhookHash = trim($merged['additional_config']['webhook_secret_hash']);
        }
        if ($webhookHash !== '') {
            $merged['webhook_secret_hash'] = $webhookHash;
            $merged['secret_key'] = $webhookHash;
        }
        $merged['callback_url'] = payment_callback_url('flutterwave');
    }

    if ($code === 'paystack' && ($merged['callback_url'] === '' || stripos($merged['callback_url'], 'payment/callback') === false)) {
        $merged['callback_url'] = payment_callback_url('paystack');
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

/**
 * Whether a gateway row is active and has API credentials configured.
 */
function payment_gateway_is_usable(array $gateway) {
    if (empty($gateway['is_active'])) {
        return false;
    }

    $code = strtolower($gateway['gateway_code'] ?? '');
    $hasSecret = trim($gateway['private_key'] ?? '') !== '' || trim($gateway['secret_key'] ?? '') !== '';

    if ($code === 'paystack') {
        return $hasSecret && trim($gateway['public_key'] ?? '') !== '';
    }

    return $hasSecret;
}

/**
 * Pick an active gateway: requested → default → env → first active.
 *
 * @return array{gateway:array,gateway_code:string,requested_code:?string,fallback_used:bool}|null
 */
function resolve_payment_gateway($gatewayModel, $requestedCode = null) {
    $requestedCode = $requestedCode !== null && $requestedCode !== ''
        ? strtolower(trim((string) $requestedCode))
        : null;

    $pick = function (array $gateway, $requested, $fallback) {
        return [
            'gateway' => $gateway,
            'gateway_code' => strtolower($gateway['gateway_code']),
            'requested_code' => $requested,
            'fallback_used' => $fallback,
        ];
    };

    if ($requestedCode) {
        $requested = $gatewayModel->getByCode($requestedCode);
        if ($requested && payment_gateway_is_usable($requested)) {
            return $pick($requested, $requestedCode, false);
        }
    }

    $default = $gatewayModel->getDefault();
    if ($default && payment_gateway_is_usable($default)) {
        $defaultCode = strtolower($default['gateway_code']);
        return $pick($default, $requestedCode, $requestedCode !== null && $requestedCode !== $defaultCode);
    }

    $envCode = payment_env_config()['default_provider'] ?? null;
    if ($envCode) {
        $envCode = strtolower(trim($envCode));
        if ($envCode !== $requestedCode) {
            $envGateway = $gatewayModel->getByCode($envCode);
            if ($envGateway && payment_gateway_is_usable($envGateway)) {
                return $pick($envGateway, $requestedCode, true);
            }
        }
    }

    foreach ($gatewayModel->getActive() as $gateway) {
        if (!payment_gateway_is_usable($gateway)) {
            continue;
        }
        $code = strtolower($gateway['gateway_code']);
        return $pick($gateway, $requestedCode, $requestedCode !== null && $requestedCode !== $code);
    }

    return null;
}
