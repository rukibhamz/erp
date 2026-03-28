<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Identity Resolver Service
 *
 * Lookup-or-create a customer record by email address.
 * This is the single deduplication gateway for bookings, tenants, and lease invoices.
 *
 * Guarantees:
 *  - Idempotent: calling resolve() twice with the same email returns the same customer_id.
 *  - Case-insensitive: email is normalised with strtolower() before lookup and insert.
 *  - Non-fatal: all exceptions are caught and logged; null is returned on DB error.
 */
class Identity_resolver {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Look up or create a customer by email.
     *
     * @param string $email       Required. Matched case-insensitively.
     * @param string $displayName Used as company_name on new records.
     * @param string $phone       Used as phone on new records.
     * @param string $source      One of 'booking', 'tenant', 'invoice'.
     * @return int|null           Customer ID, or null on DB error / empty email.
     */
    public function resolve(string $email, string $displayName, string $phone, string $source): ?int {
        try {
            $email = strtolower(trim($email));

            if ($email === '') {
                return null;
            }

            $prefix = $this->db->getPrefix();

            // 1. Lookup by normalised email
            $existing = $this->db->fetchOne(
                "SELECT id FROM `{$prefix}customers` WHERE LOWER(email) = ? AND status = 'active' LIMIT 1",
                [$email]
            );

            if ($existing) {
                return (int) $existing['id'];
            }

            // 2. Insert new customer
            $customerCode = $this->generateCustomerCode();

            $this->db->insert('customers', [
                'customer_code'   => $customerCode,
                'company_name'    => $displayName ?: $email,
                'phone'           => $phone ?: null,
                'email'           => $email,
                'customer_source' => $source,
                'status'          => 'active',
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            // Fetch the newly inserted ID
            $newRecord = $this->db->fetchOne(
                "SELECT id FROM `{$prefix}customers` WHERE LOWER(email) = ? AND status = 'active' LIMIT 1",
                [$email]
            );

            if ($newRecord) {
                error_log("Identity_resolver: Created new customer [{$customerCode}] for email [{$email}] source [{$source}]");
                return (int) $newRecord['id'];
            }

            return null;

        } catch (Exception $e) {
            error_log('Identity_resolver resolve() error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a sequential CUST-##### customer code.
     */
    private function generateCustomerCode(): string {
        try {
            $prefix = $this->db->getPrefix();
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(customer_code, 6) AS UNSIGNED)) as max_code
                 FROM `{$prefix}customers`
                 WHERE customer_code REGEXP '^CUST-[0-9]+$'"
            );
            $nextNum = intval($result['max_code'] ?? 0) + 1;
            return 'CUST-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            return 'CUST-' . date('YmdHis');
        }
    }
}
