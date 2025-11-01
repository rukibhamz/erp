<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function runPaymentGatewayMigrations($pdo, $prefix = '') {
    try {
        // Payment Gateways table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}payment_gateways` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `gateway_code` varchar(50) NOT NULL,
            `gateway_name` varchar(255) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 0,
            `is_default` tinyint(1) NOT NULL DEFAULT 0,
            `public_key` text DEFAULT NULL,
            `private_key` text DEFAULT NULL,
            `secret_key` text DEFAULT NULL,
            `webhook_url` varchar(500) DEFAULT NULL,
            `callback_url` varchar(500) DEFAULT NULL,
            `supported_currencies` text DEFAULT NULL COMMENT 'JSON array of currency codes',
            `test_mode` tinyint(1) NOT NULL DEFAULT 1,
            `additional_config` text DEFAULT NULL COMMENT 'JSON object for gateway-specific settings',
            `logo_url` varchar(500) DEFAULT NULL,
            `display_order` int(11) NOT NULL DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `gateway_code` (`gateway_code`),
            KEY `is_active` (`is_active`),
            KEY `is_default` (`is_default`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Payment Transactions table (for tracking gateway payments)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}payment_transactions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `transaction_ref` varchar(255) NOT NULL,
            `gateway_code` varchar(50) NOT NULL,
            `gateway_transaction_id` varchar(255) DEFAULT NULL,
            `payment_type` varchar(50) NOT NULL COMMENT 'booking_payment, invoice_payment, etc',
            `reference_id` int(11) DEFAULT NULL COMMENT 'ID of the related booking/invoice',
            `amount` decimal(15,2) NOT NULL,
            `currency` varchar(10) NOT NULL DEFAULT 'NGN',
            `customer_email` varchar(255) DEFAULT NULL,
            `customer_name` varchar(255) DEFAULT NULL,
            `customer_phone` varchar(50) DEFAULT NULL,
            `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, success, failed, cancelled',
            `gateway_response` text DEFAULT NULL COMMENT 'JSON response from gateway',
            `failure_reason` text DEFAULT NULL,
            `webhook_received` tinyint(1) NOT NULL DEFAULT 0,
            `paid_at` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `transaction_ref` (`transaction_ref`),
            KEY `gateway_code` (`gateway_code`),
            KEY `payment_type` (`payment_type`),
            KEY `reference_id` (`reference_id`),
            KEY `status` (`status`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insert default payment gateway configurations
        $gateways = [
            [
                'gateway_code' => 'paystack',
                'gateway_name' => 'Paystack',
                'is_active' => 0,
                'is_default' => 0,
                'test_mode' => 1,
                'supported_currencies' => json_encode(['NGN', 'GHS', 'ZAR', 'KES']),
                'display_order' => 1,
                'logo_url' => 'https://paystack.com/assets/img/logo.png'
            ],
            [
                'gateway_code' => 'flutterwave',
                'gateway_name' => 'Flutterwave',
                'is_active' => 0,
                'is_default' => 0,
                'test_mode' => 1,
                'supported_currencies' => json_encode(['NGN', 'USD', 'GBP', 'EUR', 'KES', 'ZAR', 'GHS']),
                'display_order' => 2,
                'logo_url' => 'https://flutterwave.com/images/logo.svg'
            ],
            [
                'gateway_code' => 'stripe',
                'gateway_name' => 'Stripe',
                'is_active' => 0,
                'is_default' => 0,
                'test_mode' => 1,
                'supported_currencies' => json_encode(['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY']),
                'display_order' => 3,
                'logo_url' => 'https://stripe.com/img/v3/home/social.png'
            ],
            [
                'gateway_code' => 'paypal',
                'gateway_name' => 'PayPal',
                'is_active' => 0,
                'is_default' => 0,
                'test_mode' => 1,
                'supported_currencies' => json_encode(['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY']),
                'display_order' => 4,
                'logo_url' => 'https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg'
            ],
            [
                'gateway_code' => 'monnify',
                'gateway_name' => 'Monnify',
                'is_active' => 0,
                'is_default' => 0,
                'test_mode' => 1,
                'supported_currencies' => json_encode(['NGN']),
                'display_order' => 5,
                'logo_url' => 'https://monnify.com/images/logo.png'
            ]
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}payment_gateways` 
            (gateway_code, gateway_name, is_active, is_default, test_mode, supported_currencies, display_order, logo_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($gateways as $gateway) {
            $stmt->execute([
                $gateway['gateway_code'],
                $gateway['gateway_name'],
                $gateway['is_active'],
                $gateway['is_default'],
                $gateway['test_mode'],
                $gateway['supported_currencies'],
                $gateway['display_order'],
                $gateway['logo_url']
            ]);
        }

        echo "Payment gateway tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Payment gateway migration error: " . $e->getMessage());
        throw $e;
    }
}

