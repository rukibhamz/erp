<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once BASEPATH . 'helpers/payment_config_helper.php';

$runner = new SimpleTestRunner();

class Payment_gateway_model_stub {
    public $gateways = [];
    public $default = null;

    public function getByCode($code) {
        foreach ($this->gateways as $g) {
            if (strtolower($g['gateway_code']) === strtolower($code)) {
                return $g;
            }
        }
        return false;
    }

    public function getDefault() {
        return $this->default;
    }

    public function getActive() {
        return $this->gateways;
    }
}

$model = new Payment_gateway_model_stub();
$model->gateways = [
    [
        'gateway_code' => 'paystack',
        'gateway_name' => 'Paystack',
        'is_active' => 0,
        'is_default' => 0,
        'public_key' => 'pk',
        'private_key' => 'sk',
    ],
    [
        'gateway_code' => 'flutterwave',
        'gateway_name' => 'Flutterwave',
        'is_active' => 1,
        'is_default' => 1,
        'public_key' => 'pk',
        'private_key' => 'sk',
    ],
];
$model->default = $model->gateways[1];

$resolved = resolve_payment_gateway($model, 'paystack');
$runner->assertTrue($resolved !== null, 'Resolves when requested gateway is inactive');
$runner->assertEquals('flutterwave', $resolved['gateway_code'] ?? '', 'Falls back to default gateway');
$runner->assertTrue($resolved['fallback_used'] ?? false, 'Marks fallback as used');

$direct = resolve_payment_gateway($model, 'flutterwave');
$runner->assertTrue($direct !== null && !($direct['fallback_used'] ?? true), 'Uses requested gateway when active');

exit($runner->summary());
