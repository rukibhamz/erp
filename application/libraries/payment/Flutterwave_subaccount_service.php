<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Flutterwave v3 collection subaccounts API.
 * @see https://developer.flutterwave.com/v3.0.0/reference/create-a-sub-account
 */
class Flutterwave_subaccount_service {
    private const API_BASE = 'https://api.flutterwave.com/v3';

    private $secretKey;

    public function __construct(array $gatewayConfig) {
        $this->secretKey = trim($gatewayConfig['private_key'] ?? $gatewayConfig['secret_key'] ?? '');
    }

    public function isConfigured() {
        return $this->secretKey !== '';
    }

    public function createSubaccount(array $payload) {
        return $this->request('POST', '/subaccounts', $payload);
    }

    public function listSubaccounts() {
        return $this->request('GET', '/subaccounts');
    }

    public function getSubaccount($id) {
        return $this->request('GET', '/subaccounts/' . rawurlencode((string) $id));
    }

    public function updateSubaccount($id, array $payload) {
        return $this->request('PUT', '/subaccounts/' . rawurlencode((string) $id), $payload);
    }

    public function deleteSubaccount($id) {
        return $this->request('DELETE', '/subaccounts/' . rawurlencode((string) $id));
    }

    public function getBanks($country = 'NG') {
        return $this->request('GET', '/banks/' . rawurlencode(strtoupper($country)));
    }

    public function resolveAccount($accountNumber, $accountBank) {
        return $this->request('POST', '/accounts/resolve', [
            'account_number' => $accountNumber,
            'account_bank' => $accountBank,
        ]);
    }

    private function request($method, $path, $body = null) {
        if ($this->secretKey === '') {
            return ['success' => false, 'message' => 'Flutterwave secret key is not configured'];
        }

        $url = self::API_BASE . $path;
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json',
        ];

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if ($body !== null && in_array($method, ['POST', 'PUT'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => 'cURL error: ' . $curlError];
        }

        $decoded = json_decode($response ?: '{}', true);
        $ok = ($decoded['status'] ?? '') === 'success';

        return [
            'success' => $ok,
            'message' => $decoded['message'] ?? ($ok ? 'OK' : 'Request failed'),
            'data' => $decoded['data'] ?? null,
            'http_code' => $httpCode,
            'raw' => $decoded,
        ];
    }
}
