<?php
defined('BASEPATH') OR exit('No direct script access allowed');

interface Payment_provider_interface {
    /**
     * @return array{success:bool, authorization_url?:string, reference?:string, access_code?:string}
     */
    public function initialize($amount, $currency, array $customer, array $metadata = []);

    /**
     * @param string $reference Gateway reference or transaction id
     * @param array $options e.g. ['tx_ref' => 'PGW-...', 'transaction_id' => 12345]
     * @return array{success:bool, amount?:float, currency?:string, gateway_reference?:string, message?:string, customer?:array, raw_response?:array}
     */
    public function verify($reference, array $options = []);

    /**
     * Verify webhook authenticity using raw request body (do not pre-parse).
     */
    public function verifyWebhookSignature($rawBody, array $serverHeaders);

    public function extractWebhookReference(array $payload);

    public function isTestWebhook(array $payload);

    public function shouldProcessWebhookEvent(array $payload);
}
