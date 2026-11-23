<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Secret-gated unattended jobs (Task Scheduler / curl / cron).
 * Auth is skipped via Base_Controller publicControllers; each action
 * must call assertCronSecret() first.
 */
class Cron extends Base_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Detect, report, and auto-repair booking / gateway / account mismatches.
     * GET /cron/financial-reconcile?key=CRON_SECRET
     * Optional: &format=json
     */
    public function financialReconcile() {
        $this->assertCronSecret();

        if (!defined('CRON_JOB_RUNNING')) {
            define('CRON_JOB_RUNNING', true);
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        require_once BASEPATH . 'services/Financial_reconcile_job.php';
        $job = new Financial_reconcile_job();

        try {
            $summary = $job->run();
        } catch (Exception $e) {
            error_log('Cron financialReconcile fatal: ' . $e->getMessage());
            $summary = [
                'started_at' => date('c'),
                'finished_at' => date('c'),
                'ok' => false,
                'bookings' => ['errors' => [$e->getMessage()]],
                'gateway' => ['errors' => []],
                'accounts' => ['errors' => []],
                'had_work' => false,
                'had_errors' => true,
            ];
        }

        $reportText = Financial_reconcile_job::formatReport($summary);
        $this->writeReconcileLogs($reportText, $summary);
        $this->maybeEmailReport($summary, $reportText);

        $wantJson = (isset($_GET['format']) && strtolower((string) $_GET['format']) === 'json')
            || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($wantJson) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(!empty($summary['ok']) ? 200 : 207);
            echo json_encode($summary, JSON_PRETTY_PRINT);
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(!empty($summary['ok']) ? 200 : 207);
        echo $reportText;
        exit;
    }

    /**
     * Require CRON_SECRET from env matching ?key= (constant-time).
     */
    private function assertCronSecret(): void {
        $expected = (string) env('CRON_SECRET', '');
        $provided = (string) ($_GET['key'] ?? '');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "404 Not Found";
            exit;
        }
    }

    private function writeReconcileLogs(string $reportText, array $summary): void {
        $dir = (defined('ROOTPATH') ? ROOTPATH : dirname(__DIR__, 2) . '/') . 'logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $stamp = date('Ymd_His');
        @file_put_contents($dir . '/financial_reconcile_' . $stamp . '.log', $reportText);

        $oneLiner = sprintf(
            "[%s] ok=%s bookings_found=%d repaired=%d gateway_verified=%d gateway_fulfilled=%d accounts_changed=%d errors=%s\n",
            date('Y-m-d H:i:s'),
            !empty($summary['ok']) ? '1' : '0',
            intval($summary['bookings']['mismatched_found'] ?? 0),
            intval($summary['bookings']['repaired'] ?? 0),
            intval($summary['gateway']['pending_verified'] ?? 0),
            intval($summary['gateway']['fulfillment_retried'] ?? 0),
            intval($summary['accounts']['changed'] ?? 0),
            !empty($summary['had_errors']) ? 'yes' : 'no'
        );
        @file_put_contents($dir . '/financial_reconcile.log', $oneLiner, FILE_APPEND);
    }

    private function maybeEmailReport(array $summary, string $reportText): void {
        $shouldMail = !empty($summary['had_work']) || !empty($summary['had_errors']);
        if (!$shouldMail) {
            return;
        }

        $to = trim((string) env('CRON_REPORT_EMAIL', ''));
        if ($to === '') {
            try {
                $to = trim((string) ($this->getSetting('admin_email') ?? $this->getSetting('company_email') ?? ''));
            } catch (Exception $e) {
                $to = '';
            }
        }
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (!function_exists('send_email')) {
            require_once BASEPATH . '../application/helpers/email_helper.php';
        }

        $subject = 'Financial reconcile '
            . (!empty($summary['ok']) ? 'OK' : 'WITH ERRORS')
            . ' — ' . date('Y-m-d H:i');

        try {
            send_email($to, $subject, Financial_reconcile_job::formatHtmlReport($summary));
        } catch (Exception $e) {
            error_log('Cron financialReconcile email failed: ' . $e->getMessage());
        }
    }
}
