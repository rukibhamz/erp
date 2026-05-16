<?php
defined('BASEPATH') OR exit('No direct script access allowed');

abstract class Abstract_payment_provider implements Payment_provider_interface {
    protected $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    protected function getSecretKey() {
        if (!empty($this->config['test_mode'])) {
            return $this->config['test_secret_key'] ?? $this->config['private_key'] ?? '';
        }
        return $this->config['private_key'] ?? '';
    }

    /**
     * @return array{body:string, http_code:int, curl_error:string}
     */
    protected function httpRequest($url, array $options = []) {
        $ch = curl_init($url);
        $headers = $options['headers'] ?? [];
        $method = strtoupper($options['method'] ?? 'GET');
        $body = $options['body'] ?? null;
        $timeout = $options['timeout'] ?? 30;

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'body' => $response === false ? '' : $response,
            'http_code' => $httpCode,
            'curl_error' => $curlError,
        ];
    }

    protected function timingSafeEquals($known, $user) {
        if (!is_string($known) || !is_string($user)) {
            return false;
        }
        if (function_exists('hash_equals')) {
            return hash_equals($known, $user);
        }
        if (strlen($known) !== strlen($user)) {
            return false;
        }
        $result = 0;
        for ($i = 0, $len = strlen($known); $i < $len; $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }
        return $result === 0;
    }
}
