<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once BASEPATH . 'helpers/url_helper.php';

$runner = new SimpleTestRunner();

$runner->assertTrue(
    is_trusted_payment_gateway_url('https://checkout.flutterwave.com/v3/hosted/pay/abc'),
    'Production Flutterwave checkout is trusted'
);
$runner->assertTrue(
    is_trusted_payment_gateway_url('https://checkout-v2.dev-flutterwave.com/v3/hosted/pay/abc'),
    'Sandbox Flutterwave checkout is trusted'
);
$runner->assertTrue(
    is_trusted_payment_gateway_url('https://checkout.paystack.com/abc'),
    'Paystack checkout is trusted'
);
$runner->assertTrue(
    is_trusted_payment_gateway_url('https://dashboard.flutterwave.com/login'),
    'Flutterwave domains are whitelisted for redirect()'
);
$runner->assertTrue(
    !is_trusted_payment_gateway_url('https://evil.example/phish'),
    'Unknown hosts are not trusted'
);

exit($runner->summary());
