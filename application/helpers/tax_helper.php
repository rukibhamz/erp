<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tax Helper Functions
 */

/**
 * Calculate VAT amount from gross amount (VAT-inclusive)
 */
function calculateVATFromInclusive($grossAmount, $vatRate = 7.5) {
    $vatAmount = ($grossAmount * $vatRate) / (100 + $vatRate);
    return round($vatAmount, 2);
}

/**
 * Calculate VAT amount from net amount (VAT-exclusive)
 */
function calculateVATFromExclusive($netAmount, $vatRate = 7.5) {
    $vatAmount = $netAmount * ($vatRate / 100);
    return round($vatAmount, 2);
}

/**
 * Calculate WHT amount
 */
function calculateWHT($grossAmount, $whtRate) {
    $whtAmount = $grossAmount * ($whtRate / 100);
    return round($whtAmount, 2);
}

/**
 * Record VAT transaction from invoice/bill
 */
function recordVATTransaction($transactionType, $transactionId, $customerId, $vendorId, $grossAmount, $vatAmount, $vatRate, $date, $transactionReference = null) {
    try {
        $db = Database::getInstance();
        $prefix = $db->getPrefix();
        
        $data = [
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'transaction_reference' => $transactionReference,
            'customer_id' => $customerId,
            'vendor_id' => $vendorId,
            'vat_amount' => $vatAmount,
            'vat_rate' => $vatRate,
            'gross_amount' => $grossAmount,
            'net_amount' => $grossAmount - $vatAmount,
            'vat_type' => 'standard',
            'date' => $date
        ];
        
        return $db->insert('vat_transactions', $data);
    } catch (Exception $e) {
        error_log('recordVATTransaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Record WHT transaction from payment
 */
function recordWHTTransaction($paymentId, $whtType, $grossAmount, $whtRate, $beneficiaryId, $beneficiaryName, $beneficiaryTin, $date, $transactionReference = null) {
    try {
        $db = Database::getInstance();
        $prefix = $db->getPrefix();
        
        $whtAmount = calculateWHT($grossAmount, $whtRate);
        
        $data = [
            'payment_id' => $paymentId,
            'wht_type' => $whtType,
            'wht_rate' => $whtRate,
            'gross_amount' => $grossAmount,
            'wht_amount' => $whtAmount,
            'net_amount' => $grossAmount - $whtAmount,
            'date' => $date,
            'beneficiary_id' => $beneficiaryId,
            'beneficiary_name' => $beneficiaryName,
            'beneficiary_tin' => $beneficiaryTin,
            'transaction_reference' => $transactionReference
        ];
        
        return $db->insert('wht_transactions', $data);
    } catch (Exception $e) {
        error_log('recordWHTTransaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get VAT rate from settings or default
 */
function getVATRate() {
    try {
        $db = Database::getInstance();
        $taxType = $db->fetchOne(
            "SELECT rate FROM `" . $db->getPrefix() . "tax_types` 
             WHERE code = 'VAT' AND is_active = 1 LIMIT 1"
        );
        return floatval($taxType['rate'] ?? 7.5);
    } catch (Exception $e) {
        return 7.5; // Default Nigerian VAT rate
    }
}

/**
 * Get WHT rate by type
 */
function getWHTRate($whtType) {
    $rates = [
        'dividends' => 10,
        'interest' => 10,
        'rent' => 10,
        'royalties' => 10,
        'professional_fees' => 10,
        'directors_fees' => 10,
        'consultancy' => 5,
        'construction' => 5,
        'commission' => 5,
        'technical_services' => 10
    ];
    
    return floatval($rates[$whtType] ?? 10);
}

