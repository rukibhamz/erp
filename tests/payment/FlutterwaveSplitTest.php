<?php
/**
 * Unit tests for Flutterwave split helpers (no HTTP).
 * Run: php tests/payment/FlutterwaveSplitTest.php
 */

defined('BASEPATH') or define('BASEPATH', dirname(__DIR__, 2) . '/application/');
require_once BASEPATH . 'helpers/flutterwave_split_helper.php';

class Flutterwave_split_test_runner {
    private $passed = 0;
    private $failed = 0;

    public function assertTrue($cond, $msg) {
        if ($cond) {
            $this->passed++;
            echo "PASS: {$msg}\n";
        } else {
            $this->failed++;
            echo "FAIL: {$msg}\n";
        }
    }

    public function assertEquals($expected, $actual, $msg) {
        $this->assertTrue($expected === $actual, $msg . " (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")");
    }

    public function summary() {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        return $this->failed === 0 ? 0 : 1;
    }
}

$runner = new Flutterwave_split_test_runner();

$runner->assertTrue(
    flutterwave_subaccounts_enabled(['additional_config' => ['enable_subaccounts' => 1]]),
    'Subaccounts enabled from additional_config'
);
$runner->assertTrue(
    !flutterwave_subaccounts_enabled(['additional_config' => []]),
    'Subaccounts disabled when flag off'
);
$runner->assertTrue(
    flutterwave_should_log_split(['additional_config' => ['log_split_on_transactions' => 1]]),
    'Log split enabled from config'
);
$runner->assertTrue(
    !flutterwave_should_log_split(['additional_config' => []]),
    'Log split off by default'
);

$built = flutterwave_build_subaccounts_payload(['RS_TEST123'], 5);
$runner->assertEquals(5, $built['rule_id'], 'Rule id preserved');
$runner->assertEquals('RS_TEST123', $built['subaccount_id'], 'Subaccount id preserved');
$runner->assertEquals(1, count($built['subaccounts']), 'One subaccount entry');
$runner->assertEquals('RS_TEST123', $built['subaccounts'][0]['id'], 'Subaccount id in payload');
$runner->assertTrue(
    !isset($built['subaccounts'][0]['transaction_charge']),
    'No ERP split override — Flutterwave handles split'
);

$masked = flutterwave_mask_account_number('0690000037');
$runner->assertTrue(str_ends_with($masked, '0037'), 'Mask keeps last 4 digits');

exit($runner->summary());
