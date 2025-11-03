<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paye extends Base_Controller {
    private $payeDeductionModel;
    private $payeReturnModel;
    private $payrollModel;
    private $employeeModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->payeDeductionModel = $this->loadModel('Paye_deduction_model');
        $this->payeReturnModel = $this->loadModel('Paye_return_model');
        $this->payrollModel = $this->loadModel('Payroll_model');
        $this->employeeModel = $this->loadModel('Employee_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $period = $_GET['period'] ?? date('Y-m');
        
        try {
            $payeDeductions = $this->payeDeductionModel->getByPeriod($period);
            $totals = $this->payeDeductionModel->getTotalByPeriod($period);
            $returns = $this->payeReturnModel->getRecentReturns(12);
        } catch (Exception $e) {
            error_log('Paye index error: ' . $e->getMessage());
            $payeDeductions = [];
            $totals = ['total_paye' => 0, 'employee_count' => 0];
            $returns = [];
        }
        
        // Add employee names to deductions
        foreach ($payeDeductions as &$deduction) {
            $employee = $this->employeeModel->getById($deduction['employee_id']);
            $deduction['employee_name'] = $employee ? ($employee['first_name'] . ' ' . $employee['last_name']) : '-';
        }
        
        $data = [
            'page_title' => 'PAYE Management',
            'paye_deductions' => $payeDeductions,
            'totals' => $totals,
            'returns' => $returns,
            'selected_period' => $period,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/paye/index', $data);
    }
    
    public function calculate($payrollRunId) {
        $this->requirePermission('tax', 'create');
        
        try {
            $payrollRun = $this->payrollModel->getRunById($payrollRunId);
            if (!$payrollRun) {
                $this->setFlashMessage('danger', 'Payroll run not found.');
                redirect('payroll');
            }
            
            $payslips = $this->payrollModel->getPayslips($payrollRunId);
            $period = $payrollRun['period'];
            
            // Check if PAYE already calculated for this period
            $existing = $this->payeReturnModel->getByPeriod($period);
            if ($existing) {
                $this->setFlashMessage('warning', 'PAYE already calculated for this period.');
                redirect('tax/paye');
            }
            
            $totalPAYE = 0;
            
            foreach ($payslips as $payslip) {
                $employee = $this->employeeModel->getById($payslip['employee_id']);
                if (!$employee) continue;
                
                $grossPay = floatval($payslip['gross_pay'] ?? 0);
                
                // Get pension and NHF from deductions
                $deductions = json_decode($payslip['deductions_json'] ?? '[]', true);
                $pensionContribution = 0;
                $nhfContribution = 0;
                
                foreach ($deductions as $deduction) {
                    $name = strtolower($deduction['name'] ?? '');
                    if (strpos($name, 'pension') !== false) {
                        $pensionContribution = floatval($deduction['amount'] ?? 0);
                    }
                    if (strpos($name, 'nhf') !== false || strpos($name, 'national housing') !== false) {
                        $nhfContribution = floatval($deduction['amount'] ?? 0);
                    }
                }
                
                // Calculate PAYE
                $payeCalculation = $this->payeDeductionModel->calculatePAYE($grossPay, $pensionContribution, $nhfContribution);
                
                // Save PAYE deduction
                $payeData = [
                    'employee_id' => $payslip['employee_id'],
                    'period' => $period,
                    'gross_income' => $payeCalculation['gross_income'],
                    'pension_contribution' => $payeCalculation['pension_contribution'],
                    'nhf_contribution' => $payeCalculation['nhf_contribution'],
                    'consolidated_relief' => $payeCalculation['consolidated_relief'],
                    'taxable_income' => $payeCalculation['taxable_income'],
                    'tax_calculated' => $payeCalculation['tax_calculated'],
                    'minimum_tax' => $payeCalculation['minimum_tax'],
                    'paye_amount' => $payeCalculation['paye_amount'],
                    'tax_bands_json' => json_encode($payeCalculation['tax_bands']),
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $this->payeDeductionModel->create($payeData);
                $totalPAYE += $payeCalculation['paye_amount'];
            }
            
            // Create PAYE return
            $returnNumber = $this->payeReturnModel->getNextReturnNumber();
            $deadlineDate = date('Y-m-21', strtotime($period . '-01 +1 month'));
            
            $returnData = [
                'return_number' => $returnNumber,
                'period' => $period,
                'total_paye' => $totalPAYE,
                'employee_count' => count($payslips),
                'filing_deadline' => $deadlineDate,
                'status' => 'draft',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->payeReturnModel->create($returnData)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Tax', 'Calculated PAYE for period: ' . $period);
                $this->setFlashMessage('success', 'PAYE calculated successfully. Total: ' . format_currency($totalPAYE));
                redirect('tax/paye');
            } else {
                $this->setFlashMessage('danger', 'Failed to create PAYE return.');
            }
        } catch (Exception $e) {
            error_log('Paye calculate error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error calculating PAYE: ' . $e->getMessage());
        }
        
        redirect('tax/paye');
    }
    
    public function view($id) {
        try {
            $return = $this->payeReturnModel->getById($id);
            if (!$return) {
                $this->setFlashMessage('danger', 'PAYE return not found.');
                redirect('tax/paye');
            }
            
            $deductions = $this->payeDeductionModel->getByPeriod($return['period']);
            
            // Add employee names
            foreach ($deductions as &$deduction) {
                $employee = $this->employeeModel->getById($deduction['employee_id']);
                $deduction['employee_name'] = $employee ? ($employee['first_name'] . ' ' . $employee['last_name']) : '-';
                $deduction['tax_bands'] = json_decode($deduction['tax_bands_json'] ?? '[]', true);
            }
            
        } catch (Exception $e) {
            error_log('Paye view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading PAYE return.');
            redirect('tax/paye');
        }
        
        $data = [
            'page_title' => 'PAYE Return: ' . ($return['return_number'] ?? ''),
            'paye_return' => $return,
            'deductions' => $deductions ?? [],
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/paye/view', $data);
    }
}
