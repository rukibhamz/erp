<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends Base_Controller {
    private $employeeModel;
    private $payrollModel;
    private $accountModel;
    private $journalModel;
    private $cashAccountModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('payroll', 'read');
        $this->employeeModel = $this->loadModel('Employee_model');
        $this->payrollModel = $this->loadModel('Payroll_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        $period = $_GET['period'] ?? date('Y-m');
        
        try {
            $payrollRuns = $this->payrollModel->getByPeriod($period);
        } catch (Exception $e) {
            $payrollRuns = [];
        }

        $data = [
            'page_title' => 'Payroll',
            'payroll_runs' => $payrollRuns,
            'selected_period' => $period,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('payroll/index', $data);
    }

    public function employees() {
        try {
            $employees = $this->employeeModel->getActiveEmployees();
        } catch (Exception $e) {
            $employees = [];
        }

        $data = [
            'page_title' => 'Employees',
            'employees' => $employees,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('payroll/employees', $data);
    }

    public function createEmployee() {
        $this->requirePermission('payroll', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'employee_code' => sanitize_input($_POST['employee_code'] ?? ''),
                'first_name' => sanitize_input($_POST['first_name'] ?? ''),
                'last_name' => sanitize_input($_POST['last_name'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'department' => sanitize_input($_POST['department'] ?? ''),
                'position' => sanitize_input($_POST['position'] ?? ''),
                'employment_type' => sanitize_input($_POST['employment_type'] ?? 'full-time'),
                'hire_date' => sanitize_input($_POST['hire_date'] ?? date('Y-m-d')),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if (empty($data['employee_code'])) {
                $data['employee_code'] = $this->employeeModel->getNextEmployeeCode();
            }

            // Salary structure
            $salaryStructure = [
                'basic_salary' => floatval($_POST['basic_salary'] ?? 0),
                'allowances' => json_decode($_POST['allowances_json'] ?? '[]', true),
                'deductions' => json_decode($_POST['deductions_json'] ?? '[]', true)
            ];
            $data['salary_structure'] = json_encode($salaryStructure);

            if ($this->employeeModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Payroll', 'Created employee: ' . $data['first_name'] . ' ' . $data['last_name']);
                $this->setFlashMessage('success', 'Employee created successfully.');
                redirect('payroll/employees');
            } else {
                $this->setFlashMessage('danger', 'Failed to create employee.');
            }
        }

        $data = [
            'page_title' => 'Create Employee',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('payroll/create_employee', $data);
    }

    public function processPayroll() {
        $this->requirePermission('payroll', 'create');

        $period = $_GET['period'] ?? date('Y-m');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $period = sanitize_input($_POST['period'] ?? date('Y-m'));
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $employeeIds = $_POST['employee_ids'] ?? [];

            if (empty($employeeIds)) {
                $this->setFlashMessage('danger', 'Please select at least one employee.');
                redirect('payroll/process?period=' . $period);
            }

            try {
                $this->db->beginTransaction();

                // Create payroll run
                $payrollRunData = [
                    'period' => $period,
                    'status' => 'draft',
                    'processed_date' => date('Y-m-d'),
                    'created_by' => $this->session['user_id']
                ];

                $payrollRunId = $this->payrollModel->createRun($payrollRunData);

                if (!$payrollRunId) {
                    throw new Exception('Failed to create payroll run');
                }

                // Process each employee
                $totalPayroll = 0;
                foreach ($employeeIds as $employeeId) {
                    $employee = $this->employeeModel->getById($employeeId);
                    if (!$employee) continue;

                    $salaryStructure = json_decode($employee['salary_structure'] ?? '{}', true);
                    $basicSalary = floatval($salaryStructure['basic_salary'] ?? 0);
                    $allowances = $salaryStructure['allowances'] ?? [];
                    $deductions = $salaryStructure['deductions'] ?? [];

                    // Calculate totals
                    $totalAllowances = 0;
                    foreach ($allowances as $allowance) {
                        $totalAllowances += floatval($allowance['amount'] ?? 0);
                    }

                    $totalDeductions = 0;
                    foreach ($deductions as $deduction) {
                        $totalDeductions += floatval($deduction['amount'] ?? 0);
                    }

                    $grossPay = $basicSalary + $totalAllowances;
                    $netPay = $grossPay - $totalDeductions;
                    $totalPayroll += $netPay;

                    // Create payslip
                    $payslipData = [
                        'payroll_run_id' => $payrollRunId,
                        'employee_id' => $employeeId,
                        'period' => $period,
                        'basic_salary' => $basicSalary,
                        'gross_pay' => $grossPay,
                        'total_deductions' => $totalDeductions,
                        'net_pay' => $netPay,
                        'earnings_json' => json_encode($allowances),
                        'deductions_json' => json_encode($deductions),
                        'status' => 'draft'
                    ];

                    $this->payrollModel->createPayslip($payslipData);
                }

                // Update payroll run total
                $this->payrollModel->updateRun($payrollRunId, [
                    'total_amount' => $totalPayroll,
                    'status' => 'processed'
                ]);

                $this->db->commit();
                $this->activityModel->log($this->session['user_id'], 'create', 'Payroll', 'Processed payroll for period: ' . $period);
                $this->setFlashMessage('success', 'Payroll processed successfully.');
                redirect('payroll/view/' . $payrollRunId);
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log('Payroll processPayroll error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to process payroll: ' . $e->getMessage());
                redirect('payroll/process?period=' . $period);
            }
        }

        try {
            $employees = $this->employeeModel->getActiveEmployees();
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            $employees = [];
            $cashAccounts = [];
        }

        $data = [
            'page_title' => 'Process Payroll',
            'employees' => $employees,
            'cash_accounts' => $cashAccounts,
            'period' => $period,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('payroll/process', $data);
    }

    public function view($payrollRunId) {
        try {
            $payrollRun = $this->payrollModel->getRunById($payrollRunId);
            if (!$payrollRun) {
                $this->setFlashMessage('danger', 'Payroll run not found.');
                redirect('payroll');
            }

            $payslips = $this->payrollModel->getPayslips($payrollRunId);
            
            // Add employee info to payslips
            foreach ($payslips as &$payslip) {
                $employee = $this->employeeModel->getById($payslip['employee_id']);
                $payslip['employee_name'] = $employee ? ($employee['first_name'] . ' ' . $employee['last_name']) : '-';
            }
        } catch (Exception $e) {
            $payrollRun = null;
            $payslips = [];
        }

        // Get cash accounts for posting
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            $cashAccounts = [];
        }
        
        $data = [
            'page_title' => 'Payroll Run: ' . ($payrollRun['period'] ?? ''),
            'payroll_run' => $payrollRun,
            'payslips' => $payslips,
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('payroll/view', $data);
    }

    public function postPayroll($payrollRunId) {
        $this->requirePermission('payroll', 'update');

        try {
            $payrollRun = $this->payrollModel->getRunById($payrollRunId);
            if (!$payrollRun) {
                $this->setFlashMessage('danger', 'Payroll run not found.');
                redirect('payroll');
            }

            if ($payrollRun['status'] === 'posted') {
                $this->setFlashMessage('danger', 'Payroll already posted.');
                redirect('payroll/view/' . $payrollRunId);
            }

            $payslips = $this->payrollModel->getPayslips($payrollRunId);
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);

            if (!$cashAccountId) {
                $this->setFlashMessage('danger', 'Please select a cash account.');
                redirect('payroll/view/' . $payrollRunId);
            }

            $this->db->beginTransaction();

            // Create journal entry for payroll
            $journalNumber = $this->journalModel->getNextEntryNumber();
            $journalData = [
                'entry_number' => $journalNumber,
                'entry_date' => date('Y-m-d'),
                'reference' => 'PAYROLL-' . $payrollRun['period'],
                'description' => 'Payroll for ' . $payrollRun['period'],
                'amount' => floatval($payrollRun['total_amount']),
                'status' => 'draft',
                'journal_type' => 'payroll',
                'created_by' => $this->session['user_id']
            ];

            $journalId = $this->journalModel->create($journalData);

            // Get expense account for payroll
            $expenseAccount = $this->accountModel->getDefaultAccount('Expenses', 'Payroll Expense');
            if (!$expenseAccount) {
                $expenseAccounts = $this->accountModel->getByType('Expenses');
                $expenseAccount = !empty($expenseAccounts) ? $expenseAccounts[0] : null;
            }

            if (!$expenseAccount) {
                throw new Exception('Payroll expense account not found');
            }

            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            if (!$cashAccount) {
                throw new Exception('Cash account not found');
            }

            // Debit Payroll Expense
            $this->db->query(
                "INSERT INTO `" . $this->db->getPrefix() . "journal_entry_lines` 
                 (journal_entry_id, account_id, description, debit, credit, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$journalId, $expenseAccount['id'], 'Payroll Expense', floatval($payrollRun['total_amount']), 0]
            );

            // Credit Cash Account
            $this->db->query(
                "INSERT INTO `" . $this->db->getPrefix() . "journal_entry_lines` 
                 (journal_entry_id, account_id, description, debit, credit, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$journalId, $cashAccount['account_id'], 'Payroll Payment', 0, floatval($payrollRun['total_amount'])]
            );

            // Post journal entry
            $this->journalModel->approve($journalId, $this->session['user_id']);
            $this->journalModel->post($journalId, $this->session['user_id']);

            // Update payroll run status
            $this->payrollModel->updateRun($payrollRunId, ['status' => 'posted']);

            // Update cash account balance
            $this->cashAccountModel->updateBalance($cashAccountId, floatval($payrollRun['total_amount']), 'withdrawal');

            $this->db->commit();
            $this->activityModel->log($this->session['user_id'], 'update', 'Payroll', 'Posted payroll: ' . $payrollRun['period']);
            $this->setFlashMessage('success', 'Payroll posted successfully.');
            redirect('payroll/view/' . $payrollRunId);
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Payroll postPayroll error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to post payroll: ' . $e->getMessage());
            redirect('payroll/view/' . $payrollRunId);
        }
    }
}

