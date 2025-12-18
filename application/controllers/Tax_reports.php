<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_reports extends Base_Controller {
    private $vatReturnModel;
    private $whtReturnModel;
    private $citCalculationModel;
    private $taxPaymentModel;
    private $taxFilingModel;
    private $taxTypeModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->vatReturnModel = $this->loadModel('Vat_return_model');
        $this->whtReturnModel = $this->loadModel('Wht_return_model');
        $this->citCalculationModel = $this->loadModel('Cit_calculation_model');
        $this->taxPaymentModel = $this->loadModel('Tax_payment_model');
        $this->taxFilingModel = $this->loadModel('Tax_filing_model');
        // Tax deadline model removed - compliance module deleted
        $this->taxTypeModel = $this->loadModel('Tax_type_model');
    }
    
    public function index() {
        $reportType = $_GET['type'] ?? 'summary';
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-12-31');
        
        $data = [
            'page_title' => 'Tax Reports',
            'report_type' => $reportType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'flash' => $this->getFlashMessage()
        ];
        
        // Get report data based on type
        try {
            switch ($reportType) {
                case 'summary':
                    $data = array_merge($data, $this->getSummaryReport($startDate, $endDate));
                    break;
                case 'vat':
                    $data = array_merge($data, $this->getVATReport($startDate, $endDate));
                    break;
                case 'wht':
                    $data = array_merge($data, $this->getWHTReport($startDate, $endDate));
                    break;
                case 'cit':
                    $data = array_merge($data, $this->getCITReport());
                    break;
                case 'education_tax':
                    $data = array_merge($data, $this->getEducationTaxReport($startDate, $endDate));
                    break;
                case 'payments':
                    $data = array_merge($data, $this->getPaymentsReport($startDate, $endDate));
                    break;
                case 'compliance':
                    // Compliance module removed - return empty data
                    $data['compliance_rate'] = 100;
                    $data['upcoming_deadlines'] = [];
                    $data['overdue_deadlines'] = [];
                    $data['overdue_filings'] = [];
                    break;
                default:
                    $data = array_merge($data, $this->getSummaryReport($startDate, $endDate));
            }
        } catch (Exception $e) {
            error_log('Tax_reports index error: ' . $e->getMessage());
        }
        
        $this->loadView('tax/reports/index', $data);
    }
    
    private function getSummaryReport($startDate, $endDate) {
        try {
            // VAT Summary
            $vatReturns = $this->vatReturnModel->getRecentReturns(100);
            $vatReturns = array_filter($vatReturns, function($r) use ($startDate, $endDate) {
                return $r['period_start'] >= $startDate && $r['period_end'] <= $endDate;
            });
            
            $totalVATPayable = 0;
            $totalVATPaid = 0;
            foreach ($vatReturns as $return) {
                $totalVATPayable += floatval($return['vat_payable'] ?? 0);
                if ($return['status'] === 'paid') {
                    $totalVATPaid += floatval($return['vat_payable'] ?? 0);
                }
            }
            
            // WHT Summary
            $whtReturns = $this->whtReturnModel->getRecentReturns(100);
            $totalWHTPayable = 0;
            $totalWHTPaid = 0;
            foreach ($whtReturns as $return) {
                $totalWHTPayable += floatval($return['total_wht'] ?? 0);
                if ($return['status'] === 'paid') {
                    $totalWHTPaid += floatval($return['total_wht'] ?? 0);
                }
            }
            
            // CIT Summary
            $citCalculations = $this->citCalculationModel->getAll();
            $totalCITPayable = 0;
            foreach ($citCalculations as $calc) {
                $totalCITPayable += floatval($calc['final_tax_liability'] ?? 0);
            }
            
            // Payments Summary
            $payments = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "tax_payments` 
                 WHERE payment_date >= ? AND payment_date <= ?
                 ORDER BY payment_date DESC",
                [$startDate, $endDate]
            );
            $totalPayments = array_sum(array_column($payments, 'amount'));
            
            // Compliance module removed - no deadlines
            $upcomingDeadlines = [];
            $overdueDeadlines = [];
            
            // Education Tax Summary
            $this->loadModel('Education_tax_model');
            $edTaxReturns = $this->loadModel('Education_tax_model')->getReturns();
            $totalEdTaxPayable = 0;
            $totalEdTaxPaid = 0;
            foreach ($edTaxReturns as $return) {
                if ($return['tax_year'] >= date('Y', strtotime($startDate)) && $return['tax_year'] <= date('Y', strtotime($endDate))) {
                    $totalEdTaxPayable += floatval($return['tax_amount'] ?? 0);
                    $totalEdTaxPaid += floatval($return['paid_amount'] ?? 0);
                }
            }
            
            return [
                'total_vat_payable' => $totalVATPayable,
                'total_vat_paid' => $totalVATPaid,
                'total_wht_payable' => $totalWHTPayable,
                'total_wht_paid' => $totalWHTPaid,
                'total_cit_payable' => $totalCITPayable,
                'total_ed_tax_payable' => $totalEdTaxPayable,
                'total_ed_tax_paid' => $totalEdTaxPaid,
                'total_payments' => $totalPayments,
                'upcoming_deadlines_count' => count($upcomingDeadlines),
                'overdue_deadlines_count' => count($overdueDeadlines),
                'vat_returns' => array_slice($vatReturns, 0, 10),
                'wht_returns' => array_slice($whtReturns, 0, 10),
                'ed_tax_returns' => array_slice($edTaxReturns, 0, 10),
                'payments' => array_slice($payments, 0, 10)
            ];
        } catch (Exception $e) {
            error_log('Tax_reports getSummaryReport error: ' . $e->getMessage());
            return [
                'total_vat_payable' => 0,
                'total_vat_paid' => 0,
                'total_wht_payable' => 0,
                'total_wht_paid' => 0,
                'total_cit_payable' => 0,
                'total_payments' => 0,
                'upcoming_deadlines_count' => 0,
                'overdue_deadlines_count' => 0,
                'vat_returns' => [],
                'wht_returns' => [],
                'payments' => []
            ];
        }
    }
    
    private function getVATReport($startDate, $endDate) {
        try {
            $vatReturns = $this->vatReturnModel->getRecentReturns(100);
            $vatReturns = array_filter($vatReturns, function($r) use ($startDate, $endDate) {
                return $r['period_start'] >= $startDate && $r['period_end'] <= $endDate;
            });
            
            return [
                'vat_returns' => $vatReturns,
                'total_output_vat' => array_sum(array_column($vatReturns, 'output_vat')),
                'total_input_vat' => array_sum(array_column($vatReturns, 'input_vat')),
                'total_net_vat' => array_sum(array_column($vatReturns, 'net_vat')),
                'total_payable' => array_sum(array_column($vatReturns, 'vat_payable'))
            ];
        } catch (Exception $e) {
            error_log('Tax_reports getVATReport error: ' . $e->getMessage());
            return ['vat_returns' => [], 'total_output_vat' => 0, 'total_input_vat' => 0, 'total_net_vat' => 0, 'total_payable' => 0];
        }
    }
    
    private function getWHTReport($startDate, $endDate) {
        try {
            $whtReturns = $this->whtReturnModel->getRecentReturns(100);
            $whtTransactions = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "wht_transactions` 
                 WHERE date >= ? AND date <= ?
                 ORDER BY date DESC",
                [$startDate, $endDate]
            );
            
            return [
                'wht_returns' => $whtReturns,
                'wht_transactions' => $whtTransactions,
                'total_wht' => array_sum(array_column($whtReturns, 'total_wht')),
                'transaction_count' => count($whtTransactions)
            ];
        } catch (Exception $e) {
            error_log('Tax_reports getWHTReport error: ' . $e->getMessage());
            return ['wht_returns' => [], 'wht_transactions' => [], 'total_wht' => 0, 'transaction_count' => 0];
        }
    }
    
    private function getCITReport() {
        try {
            $citCalculations = $this->citCalculationModel->getAll();
            
            return [
                'cit_calculations' => $citCalculations,
                'total_liability' => array_sum(array_column($citCalculations, 'final_tax_liability'))
            ];
        } catch (Exception $e) {
            error_log('Tax_reports getCITReport error: ' . $e->getMessage());
            return ['cit_calculations' => [], 'total_liability' => 0];
        }
    }
    
    private function getEducationTaxReport($startDate, $endDate) {
        try {
            $this->loadModel('Education_tax_model');
            $returns = $this->loadModel('Education_tax_model')->getReturns();
            $filtered = [];
            $totalPayable = 0;
            foreach ($returns as $return) {
                if ($return['tax_year'] >= date('Y', strtotime($startDate)) && $return['tax_year'] <= date('Y', strtotime($endDate))) {
                    $filtered[] = $return;
                    $totalPayable += floatval($return['tax_amount'] ?? 0);
                }
            }
            return [
                'ed_tax_returns' => $filtered,
                'total_payable' => $totalPayable
            ];
        } catch (Exception $e) {
            error_log('Tax_reports getEducationTaxReport error: ' . $e->getMessage());
            return ['ed_tax_returns' => [], 'total_payable' => 0];
        }
    }
    
    private function getPaymentsReport($startDate, $endDate) {
        try {
            $payments = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "tax_payments` 
                 WHERE payment_date >= ? AND payment_date <= ?
                 ORDER BY payment_date DESC",
                [$startDate, $endDate]
            );
            
            // Group by tax type
            $byType = [];
            foreach ($payments as $payment) {
                $type = $payment['tax_type'] ?? 'other';
                if (!isset($byType[$type])) {
                    $byType[$type] = 0;
                }
                $byType[$type] += floatval($payment['amount'] ?? 0);
            }
            
            return [
                'payments' => $payments,
                'total_payments' => array_sum(array_column($payments, 'amount')),
                'by_type' => $byType
            ];
        } catch (Exception $e) {
            error_log('Tax_reports getPaymentsReport error: ' . $e->getMessage());
            return ['payments' => [], 'total_payments' => 0, 'by_type' => []];
        }
    }
    
    private function getComplianceReport() {
        // Compliance module removed - return empty data
        return [
            'upcoming_deadlines' => [],
            'overdue_deadlines' => [],
            'overdue_filings' => [],
            'compliance_rate' => 100
        ];
    }
    
    public function export() {
        $this->requirePermission('tax', 'read');
        
        $reportType = $_GET['type'] ?? 'summary';
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-12-31');
        $format = $_GET['format'] ?? 'csv';
        
        try {
            $filename = 'tax_' . $reportType . '_' . date('Y-m-d') . '.' . $format;
            
            if ($format === 'csv') {
                $this->exportCSV($reportType, $startDate, $endDate, $filename);
            } else {
                $this->setFlashMessage('info', 'PDF/Excel export requires additional libraries. CSV export is available.');
                redirect('tax/reports?type=' . $reportType . '&start_date=' . $startDate . '&end_date=' . $endDate);
            }
        } catch (Exception $e) {
            error_log('Tax_reports export error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error exporting report: ' . $e->getMessage());
            redirect('tax/reports?type=' . $reportType);
        }
    }
    
    private function exportCSV($reportType, $startDate, $endDate, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['TAX REPORT: ' . strtoupper($reportType)]);
        fputcsv($output, ['Period: ' . $startDate . ' to ' . $endDate]);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        try {
            switch ($reportType) {
                case 'summary':
                    $summary = $this->getSummaryReport($startDate, $endDate);
                    fputcsv($output, ['Tax Summary']);
                    fputcsv($output, ['VAT Payable', format_currency($summary['total_vat_payable'] ?? 0)]);
                    fputcsv($output, ['WHT Remitted', format_currency($summary['total_wht_payable'] ?? 0)]);
                    fputcsv($output, ['CIT Liability', format_currency($summary['total_cit_payable'] ?? 0)]);
                    fputcsv($output, ['Total Payments', format_currency($summary['total_payments'] ?? 0)]);
                    break;
                    
                case 'vat':
                    $report = $this->getVATReport($startDate, $endDate);
                    fputcsv($output, ['Return #', 'Period Start', 'Period End', 'Output VAT', 'Input VAT', 'VAT Payable', 'Status']);
                    foreach ($report['vat_returns'] as $return) {
                        fputcsv($output, [
                            $return['return_number'] ?? '',
                            $return['period_start'] ?? '',
                            $return['period_end'] ?? '',
                            number_format($return['output_vat'] ?? 0, 2),
                            number_format($return['input_vat'] ?? 0, 2),
                            number_format($return['vat_payable'] ?? 0, 2),
                            ucfirst($return['status'] ?? '')
                        ]);
                    }
                    break;
                    
                case 'wht':
                    $report = $this->getWHTReport($startDate, $endDate);
                    fputcsv($output, ['Return #', 'Period Start', 'Period End', 'Total WHT', 'Transactions', 'Status']);
                    foreach ($report['wht_returns'] as $return) {
                        fputcsv($output, [
                            $return['return_number'] ?? '',
                            $return['period_start'] ?? '',
                            $return['period_end'] ?? '',
                            number_format($return['total_wht'] ?? 0, 2),
                            $return['transaction_count'] ?? 0,
                            ucfirst($return['status'] ?? '')
                        ]);
                    }
                    break;
                    
                case 'cit':
                    $report = $this->getCITReport();
                    fputcsv($output, ['Year', 'Profit Before Tax', 'Adjustments', 'Assessable Profit', 'CIT Amount', 'Status']);
                    foreach ($report['cit_calculations'] as $calc) {
                        fputcsv($output, [
                            $calc['year'] ?? '',
                            number_format($calc['profit_before_tax'] ?? 0, 2),
                            number_format($calc['total_adjustments'] ?? 0, 2),
                            number_format($calc['assessable_profit'] ?? 0, 2),
                            number_format($calc['cit_amount'] ?? 0, 2),
                            ucfirst($calc['status'] ?? '')
                        ]);
                    }
                    break;
                    
                case 'payments':
                    $report = $this->getPaymentsReport($startDate, $endDate);
                    fputcsv($output, ['Date', 'Tax Type', 'Amount', 'Payment Method', 'Reference', 'Status']);
                    foreach ($report['payments'] as $payment) {
                        fputcsv($output, [
                            $payment['payment_date'] ?? '',
                            $payment['tax_type'] ?? '',
                            number_format($payment['amount'] ?? 0, 2),
                            ucfirst($payment['payment_method'] ?? ''),
                            $payment['reference'] ?? '',
                            ucfirst($payment['status'] ?? '')
                        ]);
                    }
                    break;
                    
                case 'compliance':
                    // Compliance module removed
                    fputcsv($output, ['Compliance Report - Module Removed']);
                    fputcsv($output, []);
                    fputcsv($output, ['Overdue Deadlines']);
                    fputcsv($output, ['This module has been removed from the system.']);
                    break;
                    
                default:
                    fputcsv($output, ['Report type not supported']);
            }
        } catch (Exception $e) {
            fputcsv($output, ['Error generating report: ' . $e->getMessage()]);
        }
        
        fclose($output);
        exit;
    }
}
