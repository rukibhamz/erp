<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Keeps booking totals aligned with linked invoices and posted GL revenue.
 */
class Booking_financial_sync_service {
    private $db;
    private $invoiceModel;
    private $accountModel;
    private $transactionService;
    private $paymentModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->paymentModel = $this->loadModel('Booking_payment_model');

        $transactionServicePath = BASEPATH . 'services/Transaction_service.php';
        if (file_exists($transactionServicePath)) {
            require_once $transactionServicePath;
            $this->transactionService = new Transaction_service();
        } else {
            $this->transactionService = null;
        }
    }

    /**
     * Financial fields to compare before/after an edit.
     */
    public function snapshotFromBooking(array $booking): array {
        return [
            'subtotal' => floatval($booking['subtotal'] ?? 0),
            'tax_amount' => floatval($booking['tax_amount'] ?? 0),
            'discount_amount' => floatval($booking['discount_amount'] ?? 0),
            'total_amount' => floatval($booking['total_amount'] ?? 0),
            'paid_amount' => floatval($booking['paid_amount'] ?? 0),
        ];
    }

    /**
     * Sync invoices + GL after booking pricing changed (e.g. discount edit).
     */
    public function syncAfterBookingChange(int $bookingId, array $before, array $after, string $reason = 'edit', $createdBy = null): array {
        $result = [
            'ok' => true,
            'invoices_updated' => 0,
            'journal_posted' => false,
            'messages' => [],
        ];

        try {
            $this->paymentModel->syncBookingBalance($bookingId);
            $after = $this->refreshBookingRow($bookingId, $after);

            $invoiceIds = $this->resolveInvoiceIds($after, $bookingId);
            $result['invoices_updated'] = $this->syncInvoices($bookingId, $after, $invoiceIds);

            $deltaTotal = round(floatval($after['total_amount'] ?? 0) - floatval($before['total_amount'] ?? 0), 2);
            $deltaTax = round(floatval($after['tax_amount'] ?? 0) - floatval($before['tax_amount'] ?? 0), 2);

            if (abs($deltaTotal) >= 0.01) {
                $posted = $this->postRevenueAdjustment(
                    $bookingId,
                    $after,
                    $deltaTotal,
                    $deltaTax,
                    $reason,
                    $createdBy
                );
                $result['journal_posted'] = $posted;
                if ($posted) {
                    $result['messages'][] = 'Accounting adjustment posted for ' . $this->formatMoney(abs($deltaTotal)) . '.';
                }
            }

            if ($result['invoices_updated'] > 0) {
                $result['messages'][] = $result['invoices_updated'] . ' linked invoice(s) updated.';
            }
        } catch (Exception $e) {
            $result['ok'] = false;
            $result['messages'][] = $e->getMessage();
            error_log('Booking_financial_sync_service syncAfterBookingChange: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Align invoices and GL with current booking totals (manual reconcile).
     */
    public function reconcile(int $bookingId, array $booking, $createdBy = null): array {
        $result = [
            'ok' => true,
            'invoices_updated' => 0,
            'journal_posted' => false,
            'messages' => [],
        ];

        try {
            $this->paymentModel->syncBookingBalance($bookingId);
            $booking = $this->refreshBookingRow($bookingId, $booking);

            $invoiceIds = $this->resolveInvoiceIds($booking, $bookingId);
            $result['invoices_updated'] = $this->syncInvoices($bookingId, $booking, $invoiceIds);

            $bookingTotal = floatval($booking['total_amount'] ?? 0);
            $postedTotal = $this->getEffectivePostedRevenue($bookingId, $invoiceIds);
            $deltaTotal = round($bookingTotal - $postedTotal, 2);

            if (abs($deltaTotal) >= 0.01) {
                $taxAmount = floatval($booking['tax_amount'] ?? 0);
                $deltaTax = 0.0;
                if (abs($bookingTotal) >= 0.01) {
                    $deltaTax = round($deltaTotal * ($taxAmount / $bookingTotal), 2);
                }

                $posted = $this->postRevenueAdjustment(
                    $bookingId,
                    $booking,
                    $deltaTotal,
                    $deltaTax,
                    'reconcile',
                    $createdBy
                );
                $result['journal_posted'] = $posted;
                if ($posted) {
                    $result['messages'][] = 'Posted GL adjustment of ' . $this->formatMoney(abs($deltaTotal)) . ' to match booking total.';
                } else {
                    $result['messages'][] = 'GL adjustment could not be posted (check revenue/AR accounts).';
                    $result['ok'] = false;
                }
            } else {
                $result['messages'][] = 'Booking, invoice(s), and posted revenue are already aligned.';
            }

            if ($result['invoices_updated'] > 0) {
                $result['messages'][] = 'Updated ' . $result['invoices_updated'] . ' invoice(s) to match booking.';
            }
        } catch (Exception $e) {
            $result['ok'] = false;
            $result['messages'][] = $e->getMessage();
            error_log('Booking_financial_sync_service reconcile: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Find bookings whose totals do not match linked invoice(s) or posted GL.
     *
     * @param array $filters status (active|all), date_from, date_to, limit, full_scan
     */
    public function findMismatchedBookings(array $filters = []): array {
        $prefix = $this->db->getPrefix();
        $limit = min(20000, max(1, intval($filters['limit'] ?? 2000)));
        $statusFilter = $filters['status'] ?? 'active';
        $dateFrom = trim($filters['date_from'] ?? '');
        $dateTo = trim($filters['date_to'] ?? '');
        $fullScan = !empty($filters['full_scan']);

        $where = ['b.total_amount > 0'];
        $params = [];

        if ($statusFilter === 'active') {
            $where[] = "b.status NOT IN ('cancelled', 'refunded', 'no_show')";
        }
        if ($dateFrom !== '') {
            $where[] = 'b.booking_date >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[] = 'b.booking_date <= ?';
            $params[] = $dateTo;
        }

        $whereSql = implode(' AND ', $where);
        $candidateIds = [];

        if ($fullScan) {
            $rows = $this->db->fetchAll(
                "SELECT b.id FROM `{$prefix}bookings` b
                 WHERE {$whereSql}
                 ORDER BY b.booking_date DESC, b.id DESC
                 LIMIT " . intval($limit),
                $params
            );
            foreach ($rows as $row) {
                $candidateIds[] = intval($row['id']);
            }
        } else {
            $queries = [
                "SELECT DISTINCT b.id FROM `{$prefix}bookings` b
                 INNER JOIN `{$prefix}invoices` i
                    ON (i.reference = CONCAT('BKG-', b.id) OR (COALESCE(b.invoice_id, 0) > 0 AND i.id = b.invoice_id))
                 WHERE {$whereSql} AND ABS(b.total_amount - i.total_amount) >= 0.01",
                "SELECT DISTINCT b.id FROM `{$prefix}bookings` b
                 INNER JOIN `{$prefix}journal_entries` je
                    ON je.reference = CONCAT('booking_revenue:', b.id) AND je.status != 'void'
                 WHERE {$whereSql} AND ABS(b.total_amount - je.amount) >= 0.01",
                "SELECT DISTINCT b.id FROM `{$prefix}bookings` b
                 INNER JOIN `{$prefix}invoices` i ON i.reference = CONCAT('BKG-', b.id)
                 INNER JOIN `{$prefix}journal_entries` je
                    ON je.reference = CONCAT('booking_invoice:', i.id) AND je.status != 'void'
                 WHERE {$whereSql} AND ABS(b.total_amount - je.amount) >= 0.01",
            ];

            foreach ($queries as $sql) {
                $rows = $this->db->fetchAll($sql, $params);
                foreach ($rows as $row) {
                    $candidateIds[] = intval($row['id'] ?? 0);
                }
            }
            $candidateIds = array_values(array_unique(array_filter($candidateIds)));
        }

        $mismatched = [];
        foreach ($candidateIds as $bookingId) {
            if (count($mismatched) >= $limit) {
                break;
            }

            $booking = $this->db->fetchOne(
                "SELECT b.*, f.facility_name AS facility_name
                 FROM `{$prefix}bookings` b
                 LEFT JOIN `{$prefix}facilities` f ON f.id = b.facility_id
                 WHERE b.id = ?",
                [$bookingId]
            );
            if (!$booking) {
                continue;
            }

            $summary = $this->getReconciliationSummary($bookingId, $booking);
            if (empty($summary['needs_reconcile'])) {
                continue;
            }

            $issues = [];
            if (!empty($summary['invoice_mismatch'])) {
                $issues[] = 'invoice';
            }
            if (!empty($summary['gl_mismatch'])) {
                $issues[] = 'gl';
            }

            $mismatched[] = [
                'id' => $bookingId,
                'booking_number' => $booking['booking_number'] ?? ('#' . $bookingId),
                'booking_date' => $booking['booking_date'] ?? '',
                'status' => $booking['status'] ?? '',
                'customer_name' => $booking['customer_name'] ?? '',
                'facility_name' => $booking['facility_name'] ?? '',
                'booking_total' => $summary['booking_total'],
                'invoice_total' => $summary['invoice_total'],
                'posted_revenue' => $summary['posted_revenue'],
                'invoice_mismatch' => $summary['invoice_mismatch'],
                'gl_mismatch' => $summary['gl_mismatch'],
                'issues' => $issues,
            ];
        }

        usort($mismatched, function ($a, $b) {
            return strcmp($b['booking_date'] ?? '', $a['booking_date'] ?? '');
        });

        return $mismatched;
    }

    /**
     * Reconcile many bookings (skips rows already aligned).
     */
    public function reconcileMany(array $bookingIds, $createdBy = null): array {
        $result = [
            'ok' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach (array_unique(array_map('intval', $bookingIds)) as $bookingId) {
            if ($bookingId <= 0) {
                continue;
            }

            $booking = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "bookings` WHERE id = ?",
                [$bookingId]
            );
            if (!$booking) {
                $result['skipped']++;
                continue;
            }

            $summary = $this->getReconciliationSummary($bookingId, $booking);
            if (empty($summary['needs_reconcile'])) {
                $result['skipped']++;
                continue;
            }

            $one = $this->reconcile($bookingId, $booking, $createdBy);
            if (!empty($one['ok'])) {
                $result['ok']++;
            } else {
                $result['failed']++;
                $ref = $booking['booking_number'] ?? $bookingId;
                $result['errors'][] = $ref . ': ' . implode(' ', $one['messages'] ?? ['Reconciliation failed']);
            }
        }

        return $result;
    }

    /**
     * Summary for booking view (mismatch indicators).
     */
    public function getReconciliationSummary(int $bookingId, array $booking): array {
        $invoiceIds = $this->resolveInvoiceIds($booking, $bookingId);
        $bookingTotal = floatval($booking['total_amount'] ?? 0);
        $invoiceTotal = null;
        $invoiceMismatch = false;

        foreach ($invoiceIds as $invoiceId) {
            $inv = $this->invoiceModel->getById($invoiceId);
            if (!$inv) {
                continue;
            }
            $invTotal = floatval($inv['total_amount'] ?? 0);
            if ($invoiceTotal === null) {
                $invoiceTotal = $invTotal;
            } else {
                $invoiceTotal += $invTotal;
            }
            if (abs($invTotal - $bookingTotal) >= 0.01) {
                $invoiceMismatch = true;
            }
        }

        $postedTotal = $this->getEffectivePostedRevenue($bookingId, $invoiceIds);
        $glMismatch = abs($bookingTotal - $postedTotal) >= 0.01 && $bookingTotal > 0;

        return [
            'booking_total' => $bookingTotal,
            'invoice_total' => $invoiceTotal,
            'posted_revenue' => $postedTotal,
            'invoice_ids' => $invoiceIds,
            'invoice_mismatch' => $invoiceMismatch,
            'gl_mismatch' => $glMismatch,
            'needs_reconcile' => $invoiceMismatch || $glMismatch,
        ];
    }

    private function refreshBookingRow(int $bookingId, array $fallback): array {
        $row = $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . "bookings` WHERE id = ?",
            [$bookingId]
        );
        return $row ? array_merge($fallback, $row) : $fallback;
    }

    private function resolveInvoiceIds(array $booking, int $bookingId): array {
        $invoiceIds = [];
        $directId = intval($booking['invoice_id'] ?? 0);
        if ($directId > 0) {
            $invoiceIds[] = $directId;
        }

        $byReference = $this->db->fetchAll(
            "SELECT id FROM `" . $this->db->getPrefix() . "invoices` WHERE reference = ?",
            ['BKG-' . $bookingId]
        );
        foreach ($byReference as $row) {
            $rowId = intval($row['id'] ?? 0);
            if ($rowId > 0) {
                $invoiceIds[] = $rowId;
            }
        }

        return array_values(array_unique($invoiceIds));
    }

    private function syncInvoices(int $bookingId, array $booking, array $invoiceIds): int {
        if (empty($invoiceIds)) {
            return 0;
        }

        $subtotal = floatval($booking['subtotal'] ?? 0);
        $taxRate = floatval($booking['tax_rate'] ?? 0);
        $taxAmount = floatval($booking['tax_amount'] ?? 0);
        $discountAmount = floatval($booking['discount_amount'] ?? 0);
        $totalAmount = floatval($booking['total_amount'] ?? 0);
        $paidAmount = min(floatval($booking['paid_amount'] ?? 0), $totalAmount);
        $balanceAmount = max(0, $totalAmount - $paidAmount);

        $status = 'sent';
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partially_paid';
        }

        $updated = 0;
        $bookingRef = $booking['booking_number'] ?? ('#' . $bookingId);

        foreach ($invoiceIds as $invoiceId) {
            $this->invoiceModel->update($invoiceId, [
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'status' => $status,
                'notes' => 'Booking ' . $bookingRef . ' — synced from booking totals',
            ]);

            $items = $this->invoiceModel->getItems($invoiceId);
            if (count($items) === 1) {
                $this->db->query(
                    "UPDATE `" . $this->db->getPrefix() . "invoice_items`
                     SET unit_price = ?, line_total = ?, tax_rate = ?, tax_amount = ?
                     WHERE id = ?",
                    [$subtotal, $subtotal, $taxRate, $taxAmount, intval($items[0]['id'])]
                );
            }

            $updated++;
        }

        return $updated;
    }

    /**
     * Sum posted sales recognition (invoice journal preferred over booking_revenue).
     */
    private function getEffectivePostedRevenue(int $bookingId, array $invoiceIds): float {
        $total = 0.0;
        $prefix = $this->db->getPrefix();

        if (!empty($invoiceIds)) {
            foreach ($invoiceIds as $invoiceId) {
                $rows = $this->db->fetchAll(
                    "SELECT amount FROM `" . $prefix . "journal_entries` WHERE reference = ? AND status != 'void'",
                    ['booking_invoice:' . $invoiceId]
                );
                foreach ($rows as $row) {
                    $total += floatval($row['amount'] ?? 0);
                }
            }
            if ($total > 0) {
                return round($total, 2);
            }
        }

        $rows = $this->db->fetchAll(
            "SELECT amount FROM `" . $prefix . "journal_entries` WHERE reference = ? AND status != 'void'",
            ['booking_revenue:' . $bookingId]
        );
        foreach ($rows as $row) {
            $total += floatval($row['amount'] ?? 0);
        }

        $adjustRows = $this->db->fetchAll(
            "SELECT amount, description FROM `" . $prefix . "journal_entries`
             WHERE reference = ? AND status != 'void'",
            ['booking_adjustment:' . $bookingId]
        );
        foreach ($adjustRows as $row) {
            $amt = floatval($row['amount'] ?? 0);
            $desc = strtolower($row['description'] ?? '');
            if (strpos($desc, 'decrease') !== false || strpos($desc, 'reduction') !== false) {
                $total -= $amt;
            } else {
                $total += $amt;
            }
        }

        return round(max(0, $total), 2);
    }

    private function postRevenueAdjustment(
        int $bookingId,
        array $booking,
        float $deltaTotal,
        float $deltaTax,
        string $reason,
        $createdBy
    ): bool {
        if (!$this->transactionService || abs($deltaTotal) < 0.01) {
            return false;
        }

        $deltaRevenue = round($deltaTotal - $deltaTax, 2);
        $bookingRef = $booking['booking_number'] ?? ('#' . $bookingId);

        $revenueAccount = $this->accountModel->getByCode('4100') ?: $this->accountModel->getByCode('4000');
        if (!$revenueAccount) {
            $revenueAccounts = $this->accountModel->getByType('Revenue');
            $revenueAccount = !empty($revenueAccounts) ? $revenueAccounts[0] : null;
        }

        $arAccount = $this->accountModel->getByCode('1200');
        if (!$arAccount) {
            foreach ($this->accountModel->getByType('Assets') as $acc) {
                if (stripos($acc['account_name'] ?? '', 'receivable') !== false) {
                    $arAccount = $acc;
                    break;
                }
            }
        }

        if (!$revenueAccount || !$arAccount) {
            error_log('Booking_financial_sync: missing AR or revenue account');
            return false;
        }

        $entries = [];
        $isIncrease = $deltaTotal > 0;
        $absTotal = abs($deltaTotal);
        $absRev = abs($deltaRevenue);
        $absTax = abs($deltaTax);

        if ($isIncrease) {
            $entries[] = [
                'account_id' => $arAccount['id'],
                'debit' => $absTotal,
                'credit' => 0,
                'description' => 'AR adjustment (increase) - Booking ' . $bookingRef,
            ];
            if ($absRev >= 0.01) {
                $entries[] = [
                    'account_id' => $revenueAccount['id'],
                    'debit' => 0,
                    'credit' => $absRev,
                    'description' => 'Revenue adjustment (increase) - Booking ' . $bookingRef,
                ];
            }
        } else {
            if ($absRev >= 0.01) {
                $entries[] = [
                    'account_id' => $revenueAccount['id'],
                    'debit' => $absRev,
                    'credit' => 0,
                    'description' => 'Revenue adjustment (decrease) - Booking ' . $bookingRef,
                ];
            }
            $entries[] = [
                'account_id' => $arAccount['id'],
                'debit' => 0,
                'credit' => $absTotal,
                'description' => 'AR adjustment (decrease) - Booking ' . $bookingRef,
            ];
        }

        if ($absTax >= 0.01) {
            $vatAccount = $this->accountModel->getOrCreateVatAccount();
            if ($vatAccount) {
                if ($isIncrease) {
                    $entries[] = [
                        'account_id' => $vatAccount['id'],
                        'debit' => 0,
                        'credit' => $absTax,
                        'description' => 'VAT adjustment (increase) - Booking ' . $bookingRef,
                    ];
                } else {
                    $entries[] = [
                        'account_id' => $vatAccount['id'],
                        'debit' => $absTax,
                        'credit' => 0,
                        'description' => 'VAT adjustment (decrease) - Booking ' . $bookingRef,
                    ];
                }
            } elseif (!$isIncrease && $absRev >= 0.01) {
                $entries[0]['debit'] += $absTax;
            } elseif ($isIncrease && count($entries) > 1) {
                $entries[1]['credit'] += $absTax;
            }
        }

        $debitSum = 0.0;
        $creditSum = 0.0;
        foreach ($entries as $line) {
            $debitSum += floatval($line['debit'] ?? 0);
            $creditSum += floatval($line['credit'] ?? 0);
        }
        $diff = round($debitSum - $creditSum, 2);
        if (abs($diff) >= 0.01 && !empty($entries)) {
            if ($diff > 0) {
                $entries[0]['credit'] = floatval($entries[0]['credit'] ?? 0) + $diff;
            } else {
                $entries[0]['debit'] = floatval($entries[0]['debit'] ?? 0) + abs($diff);
            }
        }

        $direction = $isIncrease ? 'increase' : 'decrease';
        $description = 'Booking ' . $direction . ' (' . $reason . ') - ' . $bookingRef;

        $this->transactionService->postJournalEntry([
            'date' => date('Y-m-d'),
            'reference_type' => 'booking_adjustment',
            'reference_id' => $bookingId,
            'description' => $description,
            'journal_type' => 'sales',
            'entries' => $entries,
            'created_by' => $createdBy,
            'auto_post' => true,
        ]);

        return true;
    }

    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }

    private function formatMoney($amount) {
        return '₦' . number_format((float) $amount, 2);
    }
}
