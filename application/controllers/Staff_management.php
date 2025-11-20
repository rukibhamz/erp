<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Staff_management extends Base_Controller {
    private $employeeModel;
    private $payrollModel;
    private $accountModel;
    private $journalModel;
    private $cashAccountModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('staff_management', 'read');
        $this->employeeModel = $this->loadModel('Employee_model');
        $this->payrollModel = $this->loadModel('Payroll_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        // Get dashboard statistics
        try {
            // Count active employees
            $totalEmployeesResult = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "employees` WHERE status = 'active'"
            );
            $totalEmployees = intval($totalEmployeesResult['count'] ?? 0);
            
            // Count total payroll runs
            $totalPayrollRunsResult = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "payroll_runs`"
            );
            $totalPayrollRuns = intval($totalPayrollRunsResult['count'] ?? 0);
            
            // Get current month payroll total
            $currentPeriod = date('Y-m');
            $currentPayroll = $this->payrollModel->getByPeriod($currentPeriod);
            $monthlyPayrollTotal = 0;
            foreach ($currentPayroll as $run) {
                $monthlyPayrollTotal += floatval($run['total_amount'] ?? 0);
            }
            
            // Get pending payroll (draft status)
            $pendingPayrollResult = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "payroll_runs` WHERE status = 'draft'"
            );
            $pendingPayroll = intval($pendingPayrollResult['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Staff_management index error: ' . $e->getMessage());
            $totalEmployees = 0;
            $totalPayrollRuns = 0;
            $monthlyPayrollTotal = 0;
            $pendingPayroll = 0;
        }
        
        $data = [
            'page_title' => 'Staff Management Dashboard',
            'total_employees' => $totalEmployees,
            'total_payroll_runs' => $totalPayrollRuns,
            'monthly_payroll_total' => $monthlyPayrollTotal,
            'pending_payroll' => $pendingPayroll,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('staff_management/index', $data);
    }

    // Employee Management Methods (moved from Employees controller)
    public function employees() {
        try {
            $employees = $this->employeeModel->getActiveEmployees();
        } catch (Exception $e) {
            error_log('Staff_management employees error: ' . $e->getMessage());
            $employees = [];
        }
        
        $data = [
            'page_title' => 'Employees',
            'employees' => $employees,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('staff_management/employees/index', $data);
    }

    public function createEmployee() {
        $this->requirePermission('staff_management', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
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
            
            // Validate email
            if (!empty($data['email']) && !validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
                redirect('staff_management/employees/create');
            }
            
            // Validate phone
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number.');
                redirect('staff_management/employees/create');
            }
            
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Auto-generate employee code if empty
            if (is_empty_or_whitespace($data['employee_code'])) {
                $data['employee_code'] = $this->employeeModel->getNextEmployeeCode();
            }
            
            // Salary structure
            $salaryStructure = [
                'basic_salary' => floatval($_POST['basic_salary'] ?? 0),
                'allowances' => [],
                'deductions' => []
            ];
            $data['salary_structure'] = json_encode($salaryStructure);
            
            if ($this->employeeModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Staff_management', 'Created employee: ' . $data['first_name'] . ' ' . $data['last_name']);
                $this->setFlashMessage('success', 'Employee created successfully.');
                redirect('staff_management/employees');
            } else {
                $this->setFlashMessage('danger', 'Failed to create employee.');
            }
        }
        
        $data = [
            'page_title' => 'Create Employee',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('staff_management/employees/create', $data);
    }

    public function editEmployee($id) {
        $this->requirePermission('staff_management', 'update');
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid employee ID.');
            redirect('staff_management/employees');
            return;
        }
        
        $employee = $this->employeeModel->getById($id);
        if (!$employee) {
            $this->setFlashMessage('danger', 'Employee not found.');
            redirect('staff_management/employees');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $data = [
                'first_name' => sanitize_input($_POST['first_name'] ?? ''),
                'last_name' => sanitize_input($_POST['last_name'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'department' => sanitize_input($_POST['department'] ?? ''),
                'position' => sanitize_input($_POST['position'] ?? ''),
                'employment_type' => sanitize_input($_POST['employment_type'] ?? 'full-time'),
                'hire_date' => sanitize_input($_POST['hire_date'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];
            
            // Validate email
            if (!empty($data['email']) && !validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
                redirect('staff_management/employees/edit/' . $id);
            }
            
            // Validate phone
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number.');
                redirect('staff_management/employees/edit/' . $id);
            }
            
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Salary structure
            $salaryStructure = [
                'basic_salary' => floatval($_POST['basic_salary'] ?? 0),
                'allowances' => [],
                'deductions' => []
            ];
            $data['salary_structure'] = json_encode($salaryStructure);
            
            if ($this->employeeModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Staff_management', 'Updated employee: ' . $data['first_name'] . ' ' . $data['last_name']);
                $this->setFlashMessage('success', 'Employee updated successfully.');
                redirect('staff_management/employees');
            } else {
                $this->setFlashMessage('danger', 'Failed to update employee.');
            }
        }
        
        $data = [
            'page_title' => 'Edit Employee',
            'employee' => $employee,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('staff_management/employees/edit', $data);
    }

    public function viewEmployee($id) {
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid employee ID.');
            redirect('staff_management/employees');
            return;
        }
        
        $employee = $this->employeeModel->getById($id);
        if (!$employee) {
            $this->setFlashMessage('danger', 'Employee not found.');
            redirect('staff_management/employees');
            return;
        }
        
        // Get payroll history for this employee
        try {
            $payrollHistory = $this->payrollModel->getByEmployee($id);
        } catch (Exception $e) {
            error_log('Staff_management viewEmployee payroll history error: ' . $e->getMessage());
            $payrollHistory = [];
        }
        
        $data = [
            'page_title' => 'Employee Details',
            'employee' => $employee,
            'payroll_history' => $payrollHistory,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('staff_management/employees/view', $data);
    }

    // Payroll Management Methods (moved from Payroll controller)
    public function payroll() {
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

        $this->loadView('staff_management/payroll/index', $data);
    }

    public function processPayroll() {
        $this->requirePermission('staff_management', 'create');

        $period = $_GET['period'] ?? date('Y-m');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $period = sanitize_input($_POST['period'] ?? date('Y-m'));
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $employeeIds = $_POST['employee_ids'] ?? [];

            if (empty($employeeIds)) {
                $this->setFlashMessage('danger', 'Please select at least one employee.');
                redirect('staff_management/payroll/process?period=' . $period);
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
                $this->activityModel->log($this->session['user_id'], 'create', 'Staff_management', 'Processed payroll for period: ' . $period);
                $this->setFlashMessage('success', 'Payroll processed successfully.');
                redirect('staff_management/payroll/view/' . $payrollRunId);
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log('Staff_management processPayroll error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to process payroll: ' . $e->getMessage());
                redirect('staff_management/payroll/process?period=' . $period);
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

        $this->loadView('staff_management/payroll/process', $data);
    }

    public function viewPayroll($payrollRunId) {
        $payrollRunId = intval($payrollRunId);
        if ($payrollRunId <= 0) {
            $this->setFlashMessage('danger', 'Invalid payroll run ID.');
            redirect('staff_management/payroll');
            return;
        }
        
        try {
            $payrollRun = $this->payrollModel->getRunById($payrollRunId);
            if (!$payrollRun) {
                $this->setFlashMessage('danger', 'Payroll run not found.');
                redirect('staff_management/payroll');
                return;
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

        $this->loadView('staff_management/payroll/view', $data);
    }
}

