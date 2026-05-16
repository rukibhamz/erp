<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payment provider configuration.
 * Secrets are read from the environment when set; otherwise gateway credentials
 * from Settings → Payment Gateways (database) are used.
 */
return [
    // Optional override: paystack | flutterwave | monnify
    'default_provider' => getenv('PAYMENT_PROVIDER') ?: null,

    'paystack' => [
        'public_key' => getenv('PAYSTACK_PUBLIC_KEY') ?: '',
        'secret_key' => getenv('PAYSTACK_SECRET_KEY') ?: '',
        'test_secret_key' => getenv('PAYSTACK_TEST_SECRET_KEY') ?: '',
    ],

    'flutterwave' => [
        'public_key' => getenv('FLUTTERWAVE_PUBLIC_KEY') ?: '',
        'secret_key' => getenv('FLUTTERWAVE_SECRET_KEY') ?: '',
        'encryption_key' => getenv('FLUTTERWAVE_ENCRYPTION_KEY') ?: '',
        // Webhook secret hash from Flutterwave dashboard (verif-hash header)
        'webhook_secret_hash' => getenv('FLUTTERWAVE_WEBHOOK_SECRET_HASH') ?: '',
    ],
];
