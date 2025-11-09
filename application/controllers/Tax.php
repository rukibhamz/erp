<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax extends Base_Controller {
    private $taxTypeModel;
    private $taxSettingsModel;
    private $vatReturnModel;
    private $whtReturnModel;
    private $citCalculationModel;
    private $taxPaymentModel;
    private $taxFilingModel;
    private $taxDeadlineModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->taxTypeModel = $this->loadModel('Tax_type_model');
        $this->taxSettingsModel = $this->loadModel('Tax_settings_model');
        $this->vatReturnModel = $this->loadModel('Vat_return_model');
        $this->whtReturnModel = $this->loadModel('Wht_return_model');
        $this->citCalculationModel = $this->loadModel('Cit_calculation_model');
        $this->taxPaymentModel = $this->loadModel('Tax_payment_model');
        $this->taxFilingModel = $this->loadModel('Tax_filing_model');
        $this->taxDeadlineModel = $this->loadModel('Tax_deadline_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $settings = $this->taxSettingsModel->getSettings();
            
            // Get recent VAT returns
            $vatReturns = $this->vatReturnModel->getRecentReturns(5);
            
            // Get upcoming deadlines
            $upcomingDeadlines = $this->taxDeadlineModel->getUpcoming(30);
            $overdueDeadlines = $this->taxDeadlineModel->getOverdue();
            
            // Calculate tax liabilities
            $totalVATPayable = 0;
            $totalWHTPayable = 0;
            $totalCITPayable = 0;
            $totalPAYEPayable = 0;
            
            foreach ($vatReturns as $return) {
                if ($return['status'] === 'filed' && $return['payment_date'] === null) {
                    $totalVATPayable += floatval($return['vat_payable'] ?? 0);
                }
            }
            
            // Get overdue filings
            $overdueFilings = $this->taxFilingModel->getOverdueFilings();
            
            // Calculate compliance score
            $totalDeadlines = count($upcomingDeadlines) + count($overdueDeadlines);
            $completedDeadlines = count($upcomingDeadlines) + count($overdueDeadlines) - count($overdueDeadlines);
            $complianceScore = $totalDeadlines > 0 ? round(($completedDeadlines / $totalDeadlines) * 100) : 100;
            
        } catch (Exception $e) {
            error_log('Tax index error: ' . $e->getMessage());
            $settings = $this->taxSettingsModel->getSettings();
            $vatReturns = [];
            $upcomingDeadlines = [];
            $overdueDeadlines = [];
            $overdueFilings = [];
            $totalVATPayable = 0;
            $totalWHTPayable = 0;
            $totalCITPayable = 0;
            $totalPAYEPayable = 0;
            $complianceScore = 100;
        }
        
        $data = [
            'page_title' => 'Tax Management',
            'settings' => $settings,
            'vat_returns' => $vatReturns,
            'upcoming_deadlines' => $upcomingDeadlines,
            'overdue_deadlines' => $overdueDeadlines,
            'overdue_filings' => $overdueFilings,
            'total_vat_payable' => $totalVATPayable,
            'total_wht_payable' => $totalWHTPayable,
            'total_cit_payable' => $totalCITPayable,
            'total_paye_payable' => $totalPAYEPayable,
            'compliance_score' => $complianceScore,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/index', $data);
    }
    
    public function settings() {
        $this->requirePermission('tax', 'update');
        
        // Handle tax rate updates
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tax_rates'])) {
            $updated = 0;
            if (isset($_POST['tax_rates']) && is_array($_POST['tax_rates'])) {
                foreach ($_POST['tax_rates'] as $taxId => $rate) {
                    $taxId = intval($taxId);
                    $rate = floatval($rate);
                    try {
                        $this->taxTypeModel->update($taxId, ['rate' => $rate]);
                        $updated++;
                    } catch (Exception $e) {
                        error_log('Tax rate update error: ' . $e->getMessage());
                    }
                }
            }
            if ($updated > 0) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Tax', 'Updated tax rates');
                $this->setFlashMessage('success', "Updated {$updated} tax rate(s) successfully.");
                redirect('tax/settings');
            }
        }
        
        // Handle general settings update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_tax_rates'])) {
            $data = [
                'company_tin' => sanitize_input($_POST['company_tin'] ?? ''),
                'company_registration_number' => sanitize_input($_POST['company_registration_number'] ?? ''),
                'tax_office' => sanitize_input($_POST['tax_office'] ?? ''),
                'tax_office_code' => sanitize_input($_POST['tax_office_code'] ?? ''),
                'industry_sector_code' => sanitize_input($_POST['industry_sector_code'] ?? ''),
                'accounting_year_end_month' => intval($_POST['accounting_year_end_month'] ?? 12),
                'tax_year' => intval($_POST['tax_year'] ?? date('Y')),
                'small_company_relief' => isset($_POST['small_company_relief']) ? 1 : 0,
                'pioneer_status' => isset($_POST['pioneer_status']) ? 1 : 0,
                'pioneer_expiry_date' => !empty($_POST['pioneer_expiry_date']) ? $_POST['pioneer_expiry_date'] : null,
                'vat_registration_number' => sanitize_input($_POST['vat_registration_number'] ?? ''),
                'vat_registration_date' => !empty($_POST['vat_registration_date']) ? $_POST['vat_registration_date'] : null,
                'vat_scheme' => sanitize_input($_POST['vat_scheme'] ?? 'standard'),
                'vat_threshold' => floatval($_POST['vat_threshold'] ?? 0),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->taxSettingsModel->updateSettings($data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Tax', 'Updated tax settings');
                $this->setFlashMessage('success', 'Tax settings updated successfully.');
                redirect('tax/settings');
            } else {
                $this->setFlashMessage('danger', 'Failed to update tax settings.');
            }
        }
        
        try {
            $taxTypes = $this->taxTypeModel->getAll();
        } catch (Exception $e) {
            error_log('Tax settings tax types error: ' . $e->getMessage());
            $taxTypes = [];
        }
        
        // Nigerian tax recommended rates
        $recommendedRates = [
            'VAT' => 7.5,
            'WHT_DIV' => 10,
            'WHT_INT' => 10,
            'WHT_RENT' => 10,
            'WHT_PROF' => 10,
            'WHT_DIR' => 10,
            'WHT_CONS' => 5,
            'WHT_CONST' => 5,
            'WHT_COMM' => 5,
            'WHT_TECH' => 10,
            'CIT' => 30,
            'EDT' => 2.5,
            'PAYE' => 0, // Progressive
            'CGT' => 10,
            'NITDA' => 1
        ];
        
        $data = [
            'page_title' => 'Tax Settings',
            'settings' => $this->taxSettingsModel->getSettings(),
            'tax_types' => $taxTypes,
            'recommended_rates' => $recommendedRates,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/settings', $data);
    }
}

