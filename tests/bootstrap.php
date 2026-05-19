<?php
define('BASEPATH', dirname(__DIR__) . '/application/');
define('APPPATH', dirname(__DIR__) . '/application/');
define('ROOTPATH', dirname(__DIR__) . '/');

require_once BASEPATH . 'libraries/payment/Payment_provider_factory.php';

class SimpleTestRunner {
    private $passed = 0;
    private $failed = 0;

    public function assertTrue($condition, $message) {
        if ($condition) {
            $this->passed++;
            echo "  OK: {$message}\n";
        } else {
            $this->failed++;
            echo "  FAIL: {$message}\n";
        }
    }

    public function assertEquals($expected, $actual, $message) {
        $this->assertTrue($expected === $actual, $message . " (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")");
    }

    public function summary() {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        return $this->failed === 0 ? 0 : 1;
    }
}
