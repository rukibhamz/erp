<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lease Service
 *
 * Generates invoices for lease billing events and links them to the
 * customer record associated with the tenant.
 */
class Lease_service {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Generate an invoice for a lease billing event.
     *
     * @param int    $leaseId   Lease record ID.
     * @param float  $amount    Amount due.
     * @param string $dueDate   Due date (Y-m-d).
     * @param int    $createdBy User ID for audit trail.
     * @return int|false        Invoice ID or false on failure.
     */
    public function generateInvoice(int $leaseId, float $amount, string $dueDate, int $createdBy) {
        try {
            $prefix = $this->db->getPrefix();

            // Load lease with tenant details
            $lease = $this->db->fetchOne(
                "SELECT l.*, t.customer_id, t.business_name, l.lease_number
                 FROM `{$prefix}leases` l
                 JOIN `{$prefix}tenants` t ON l.tenant_id = t.id
                 WHERE l.id = ?",
                [$leaseId]
            );

            if (!$lease) {
                error_log("Lease_service generateInvoice: lease [{$leaseId}] not found");
                return false;
            }

            if (empty($lease['customer_id'])) {
                error_log("Lease_service generateInvoice: tenant has NULL customer_id for lease [{$leaseId}]; skipping this billing cycle");
                return false;
            }

            // Generate invoice number
            if (!class_exists('Invoice_model')) {
                require_once BASEPATH . 'models/Invoice_model.php';
            }
            $invoiceModel = new Invoice_model();

            $invoiceData = [
                'invoice_number'  => $invoiceModel->getNextInvoiceNumber(),
                'customer_id'     => (int) $lease['customer_id'],
                'invoice_date'    => date('Y-m-d'),
                'due_date'        => $dueDate,
                'reference'       => $lease['lease_number'],
                'lease_reference' => $lease['lease_number'],
                'subtotal'        => $amount,
                'tax_rate'        => 0,
                'tax_amount'      => 0,
                'discount_amount' => 0,
                'total_amount'    => $amount,
                'balance_amount'  => $amount,
                'currency'        => 'NGN',
                'notes'           => 'Auto-generated from lease ' . $lease['lease_number'],
                'status'          => 'sent',
                'created_by'      => $createdBy,
            ];

            $invoiceId = $invoiceModel->create($invoiceData);

            if (!$invoiceId) {
                error_log("Lease_service generateInvoice: failed to insert invoice for lease [{$leaseId}]");
                return false;
            }

            error_log("Lease_service generateInvoice: created invoice [{$invoiceData['invoice_number']}] for lease [{$leaseId}]");

            // Optionally post journal entry via Transaction_service
            try {
                if (!class_exists('Transaction_service')) {
                    require_once BASEPATH . 'services/Transaction_service.php';
                }
                $txnService = new Transaction_service();

                // Get AR and Revenue accounts
                if (!class_exists('Account_model')) {
                    require_once BASEPATH . 'models/Account_model.php';
                }
                $accountModel = new Account_model();
                $arAccount      = $accountModel->getByCode('1200');
                $revenueAccount = $accountModel->getByCode('4000');
                if (!$revenueAccount) {
                    $candidates = $accountModel->getByType('Revenue');
                    $revenueAccount = $candidates[0] ?? null;
                }

                if ($arAccount && $revenueAccount) {
                    $txnService->postJournalEntry([
                        'date'           => date('Y-m-d'),
                        'reference_type' => 'invoice',
                        'reference_id'   => $invoiceId,
                        'description'    => 'Lease Invoice ' . $invoiceData['invoice_number'] . ' (' . $lease['lease_number'] . ')',
                        'journal_type'   => 'sales',
                        'entries'        => [
                            ['account_id' => $arAccount['id'],      'debit' => $amount, 'credit' => 0,      'description' => 'Lease AR - ' . $lease['lease_number']],
                            ['account_id' => $revenueAccount['id'], 'debit' => 0,       'credit' => $amount, 'description' => 'Lease Revenue - ' . $lease['lease_number']],
                        ],
                        'created_by'     => $createdBy,
                        'auto_post'      => true,
                    ]);
                }
            } catch (Exception $txnEx) {
                error_log('Lease_service generateInvoice: journal entry error (non-fatal): ' . $txnEx->getMessage());
            }

            return $invoiceId;

        } catch (Exception $e) {
            error_log('Lease_service generateInvoice error: ' . $e->getMessage());
            return false;
        }
    }
}
