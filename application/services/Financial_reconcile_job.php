<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Detect + auto-repair booking/invoice/GL mismatches, stale gateway payments,
 * and chart-of-accounts balance drift. Used by Cron::financialReconcile.
 */
class Financial_reconcile_job {
    /** @var Booking_financial_sync_service */
    private $bookingSync;
    /** @var Balance_calculator */
    private $balanceCalculator;
    /** @var Account_model */
    private $accountModel;

    public function __construct() {
        require_once BASEPATH . 'services/Booking_financial_sync_service.php';
        $this->bookingSync = new Booking_financial_sync_service();

        require_once BASEPATH . 'services/Balance_calculator.php';
        $this->balanceCalculator = new Balance_calculator();

        require_once BASEPATH . 'models/Account_model.php';
        $this->accountModel = new Account_model();
    }

    /**
     * Run full suite. Caps from env (or defaults) avoid PHP timeouts.
     *
     * @return array Structured summary for logging / email / HTTP response
     */
    public function run(array $options = []): array {
        $bookingLimit = intval($options['booking_limit'] ?? env('CRON_FINANCIAL_BOOKING_LIMIT', 2000));
        $gatewayLimit = intval($options['gateway_limit'] ?? env('CRON_GATEWAY_LIMIT', 200));
        $bookingLimit = max(1, min(20000, $bookingLimit > 0 ? $bookingLimit : 2000));
        $gatewayLimit = max(1, min(1000, $gatewayLimit > 0 ? $gatewayLimit : 200));

        $startedAt = date('c');
        $summary = [
            'started_at' => $startedAt,
            'finished_at' => null,
            'ok' => true,
            'bookings' => [],
            'gateway' => [],
            'accounts' => [],
            'had_work' => false,
            'had_errors' => false,
        ];

        $summary['bookings'] = $this->reconcileBookings($bookingLimit);
        $summary['gateway'] = $this->reconcileGateway($gatewayLimit);
        $summary['accounts'] = $this->reconcileAccountBalances();

        $summary['finished_at'] = date('c');
        $summary['had_work'] = $this->sectionHadWork($summary);
        $summary['had_errors'] = $this->sectionHadErrors($summary);
        $summary['ok'] = !$summary['had_errors'];

        return $summary;
    }

    private function reconcileBookings(int $limit): array {
        $section = [
            'mismatched_found' => 0,
            'repaired' => 0,
            'failed' => 0,
            'skipped' => 0,
            'truncated' => false,
            'errors' => [],
            'sample' => [],
        ];

        try {
            $mismatched = $this->bookingSync->findMismatchedBookings([
                'full_scan' => true,
                'status' => 'active',
                'limit' => $limit,
            ]);
            $section['mismatched_found'] = count($mismatched);
            $section['truncated'] = count($mismatched) >= $limit;

            foreach (array_slice($mismatched, 0, 20) as $row) {
                $section['sample'][] = [
                    'id' => $row['id'] ?? null,
                    'booking_number' => $row['booking_number'] ?? '',
                    'issues' => $row['issues'] ?? [],
                    'booking_total' => $row['booking_total'] ?? 0,
                    'invoice_total' => $row['invoice_total'] ?? 0,
                    'posted_revenue' => $row['posted_revenue'] ?? 0,
                ];
            }

            if (empty($mismatched)) {
                return $section;
            }

            $ids = array_map(function ($row) {
                return intval($row['id'] ?? 0);
            }, $mismatched);

            $bulk = $this->bookingSync->reconcileMany($ids, null);
            $section['repaired'] = intval($bulk['ok'] ?? 0);
            $section['failed'] = intval($bulk['failed'] ?? 0);
            $section['skipped'] = intval($bulk['skipped'] ?? 0);
            $section['errors'] = array_slice($bulk['errors'] ?? [], 0, 25);
        } catch (Exception $e) {
            $section['errors'][] = $e->getMessage();
            error_log('Financial_reconcile_job bookings: ' . $e->getMessage());
        }

        return $section;
    }

    private function reconcileGateway(int $limit): array {
        $section = [
            'pending_checked' => 0,
            'pending_verified' => 0,
            'fulfillment_retried' => 0,
            'errors' => [],
        ];

        try {
            if (!class_exists('Payment', false)) {
                require_once BASEPATH . 'controllers/Payment.php';
            }
            // Nested controller construct is allowed while CRON_JOB_RUNNING is set.
            $payment = new Payment();
            $stats = $payment->reconcileStaleGatewayPayments($limit);
            $section['pending_checked'] = intval($stats['pending_checked'] ?? 0);
            $section['pending_verified'] = intval($stats['pending_verified'] ?? 0);
            $section['fulfillment_retried'] = intval($stats['fulfillment_retried'] ?? 0);
            $section['errors'] = array_slice($stats['errors'] ?? [], 0, 25);
        } catch (Exception $e) {
            $section['errors'][] = $e->getMessage();
            error_log('Financial_reconcile_job gateway: ' . $e->getMessage());
        }

        return $section;
    }

    private function reconcileAccountBalances(): array {
        $section = [
            'processed' => 0,
            'changed' => 0,
            'unchanged' => 0,
            'errors' => [],
        ];

        try {
            $accounts = $this->accountModel->getAll();
            if (empty($accounts)) {
                return $section;
            }

            foreach ($accounts as $account) {
                $accountId = intval($account['id'] ?? 0);
                if ($accountId <= 0) {
                    continue;
                }

                try {
                    $previousBalance = floatval($account['balance'] ?? 0);
                    $reconciledBalance = floatval($this->balanceCalculator->calculateBalance($accountId, null, false));

                    if (abs($reconciledBalance - $previousBalance) < 0.01) {
                        $section['unchanged']++;
                    } else {
                        $section['changed']++;
                    }

                    $this->accountModel->update($accountId, [
                        'balance' => $reconciledBalance,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $section['processed']++;
                } catch (Exception $inner) {
                    $section['errors'][] = 'Account #' . $accountId . ': ' . $inner->getMessage();
                }
            }
        } catch (Exception $e) {
            $section['errors'][] = $e->getMessage();
            error_log('Financial_reconcile_job accounts: ' . $e->getMessage());
        }

        return $section;
    }

    private function sectionHadWork(array $summary): bool {
        $b = $summary['bookings'] ?? [];
        $g = $summary['gateway'] ?? [];
        $a = $summary['accounts'] ?? [];

        return intval($b['mismatched_found'] ?? 0) > 0
            || intval($g['pending_checked'] ?? 0) > 0
            || intval($g['fulfillment_retried'] ?? 0) > 0
            || intval($a['changed'] ?? 0) > 0;
    }

    private function sectionHadErrors(array $summary): bool {
        foreach (['bookings', 'gateway', 'accounts'] as $key) {
            if (!empty($summary[$key]['errors'])) {
                return true;
            }
            if (intval($summary[$key]['failed'] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Human-readable multi-line report for logs / email / curl output.
     */
    public static function formatReport(array $summary): string {
        $b = $summary['bookings'] ?? [];
        $g = $summary['gateway'] ?? [];
        $a = $summary['accounts'] ?? [];

        $lines = [
            'Financial reconcile job',
            'Started:  ' . ($summary['started_at'] ?? ''),
            'Finished: ' . ($summary['finished_at'] ?? ''),
            'Status:   ' . (!empty($summary['ok']) ? 'OK' : 'COMPLETED_WITH_ERRORS'),
            '',
            'Bookings:',
            '  mismatched_found=' . intval($b['mismatched_found'] ?? 0),
            '  repaired=' . intval($b['repaired'] ?? 0),
            '  failed=' . intval($b['failed'] ?? 0),
            '  skipped=' . intval($b['skipped'] ?? 0),
            '  truncated=' . (!empty($b['truncated']) ? 'yes' : 'no'),
            '',
            'Gateway:',
            '  pending_checked=' . intval($g['pending_checked'] ?? 0),
            '  pending_verified=' . intval($g['pending_verified'] ?? 0),
            '  fulfillment_retried=' . intval($g['fulfillment_retried'] ?? 0),
            '',
            'Accounts:',
            '  processed=' . intval($a['processed'] ?? 0),
            '  changed=' . intval($a['changed'] ?? 0),
            '  unchanged=' . intval($a['unchanged'] ?? 0),
        ];

        foreach (['bookings', 'gateway', 'accounts'] as $key) {
            $errors = $summary[$key]['errors'] ?? [];
            if (!empty($errors)) {
                $lines[] = '';
                $lines[] = ucfirst($key) . ' errors:';
                foreach ($errors as $err) {
                    $lines[] = '  - ' . $err;
                }
            }
        }

        if (!empty($b['sample'])) {
            $lines[] = '';
            $lines[] = 'Sample mismatched bookings:';
            foreach ($b['sample'] as $row) {
                $issues = is_array($row['issues'] ?? null) ? implode(',', $row['issues']) : '';
                $lines[] = sprintf(
                    '  #%s %s issues=[%s] booking=%s invoice=%s gl=%s',
                    $row['id'] ?? '',
                    $row['booking_number'] ?? '',
                    $issues,
                    $row['booking_total'] ?? '',
                    $row['invoice_total'] ?? '',
                    $row['posted_revenue'] ?? ''
                );
            }
        }

        return implode("\n", $lines) . "\n";
    }

    public static function formatHtmlReport(array $summary): string {
        $text = htmlspecialchars(self::formatReport($summary), ENT_QUOTES, 'UTF-8');
        return '<pre style="font-family:Consolas,monospace;font-size:13px;">' . $text . '</pre>';
    }
}
