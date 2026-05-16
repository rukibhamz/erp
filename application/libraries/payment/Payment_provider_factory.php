<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Payment_provider_interface.php';
require_once __DIR__ . '/Paystack_provider.php';
require_once __DIR__ . '/Flutterwave_provider.php';

class Payment_provider_factory {
    /**
     * @return Payment_provider_interface
     */
    public static function create($gatewayCode, array $config) {
        $code = strtolower($gatewayCode);
        switch ($code) {
            case 'paystack':
                return new Paystack_provider($config);
            case 'flutterwave':
                return new Flutterwave_provider($config);
            default:
                throw new Exception("Payment provider {$gatewayCode} is not supported");
        }
    }

    public static function supports($gatewayCode) {
        return in_array(strtolower($gatewayCode), ['paystack', 'flutterwave'], true);
    }
}
