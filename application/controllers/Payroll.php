<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends Base_Controller {
    private $employeeModel;
    private $payrollModel;
    private $accountModel;
    private $journalModel;
    private $cashAccountModel;
    private $activityModel;
    private $transactionService;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('payroll', 'read');
        $this->employeeModel = $this->loadModel('Employee_model');
        $this->payrollModel = $this->loadModel('Payroll_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->activityModel = $this->loadModel('Activity_model');
        
        // Load Transaction Service
        require_once BASEPATH . 'services/Transaction_service.php';
        $this->transactionService = new Transaction_service();
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
            check_csrf(); // CSRF Protection
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
                redirect('payroll/employees/create');
            }
            
            // Validate phone
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
                redirect('payroll/employees/create');
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Validate names
            if (!empty($data['first_name']) && !validate_name($data['first_name'])) {
                $this->setFlashMessage('danger', 'Invalid first name.');
                redirect('payroll/employees/create');
            }
            
            if (!empty($data['last_name']) && !validate_name($data['last_name'])) {
                $this->setFlashMessage('danger', 'Invalid last name.');
                redirect('payroll/employees/create');
            }
            
            // Auto-generate employee code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['employee_code'])) {
                $data['employee_code'] = $this->employeeModel->getNextEmployeeCode();
            }

            // Salary structure - Use safe JSON decoding
            $salaryStructure = [
                'basic_salary' => floatval($_POST['basic_salary'] ?? 0),
                'allowances' => safe_json_decode($_POST['allowances_json'] ?? '[]', true, []),
                'deductions' => safe_json_decode($_POST['deductions_json'] ?? '[]', true, [])
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
            check_csrf(); // CSRF Protection
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

                    // Calculate Nigerian statutory deductions automatically
                    require_once BASEPATH . 'helpers/nigerian_tax_helper.php';
                    
                    // Extract housing and transport allowances if they exist
                    $housingAllowance = 0;
                    $transportAllowance = 0;
                    foreach ($allowances as $allowance) {
                        $name = strtolower($allowance['name'] ?? $allowance['type'] ?? '');
                        if (strpos($name, 'housing') !== false) {
                            $housingAllowance = floatval($allowance['amount'] ?? 0);
                        } elseif (strpos($name, 'transport') !== false) {
                            $transportAllowance = floatval($allowance['amount'] ?? 0);
                        }
                    }
                    
                    // Calculate Nigerian deductions
                    $nigerianDeductions = calculate_monthly_deductions(
                        $basicSalary,
                        $housingAllowance,
                        $transportAllowance
                    );
                    
                    // Add statutory deductions to the deductions array
                    $statutoryDeductions = [
                        ['name' => 'PAYE', 'type' => 'tax', 'amount' => $nigerianDeductions['deductions']['paye']],
                        ['name' => 'Pension (8%)', 'type' => 'pension', 'amount' => $nigerianDeductions['deductions']['pension']],
                        ['name' => 'NHF (2.5%)', 'type' => 'nhf', 'amount' => $nigerianDeductions['deductions']['nhf']]
                    ];
                    
                    // Merge with existing deductions
                    $allDeductions = array_merge($deductions, $statutoryDeductions);

                    $totalDeductions = 0;
                    foreach ($allDeductions as $deduction) {
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
                        'deductions_json' => json_encode($allDeductions),
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
        } catch (Exception $e) {
            error_log('Payroll processPayroll - Failed to load employees: ' . $e->getMessage());
            error_log('Payroll processPayroll - Stack trace: ' . $e->getTraceAsString());
            $employees = [];
            $this->setFlashMessage('warning', 'Could not load employees. Please check if any active employees exist.');
        }
        
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            error_log('Payroll processPayroll - Failed to load cash accounts: ' . $e->getMessage());
            error_log('Payroll processPayroll - Stack trace: ' . $e->getTraceAsString());
            $cashAccounts = [];
            $this->setFlashMessage('warning', 'Could not load cash accounts. Please check if any active cash accounts exist.');
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

            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);

            if (!$cashAccountId) {
                $this->setFlashMessage('danger', 'Please select a cash account.');
                redirect('payroll/view/' . $payrollRunId);
            }

            // Get expense account for payroll
            $expenseAccount = $this->accountModel->getByCode('7000'); // Payroll Expense
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
            
            // Get payslips to calculate total deductions
            $payslips = $this->payrollModel->getPayslips($payrollRunId);
            $totalPAYE = 0;
            $totalPension = 0;
            $totalNHF = 0;
            $totalGrossPay = 0;
            
            foreach ($payslips as $payslip) {
                $deductions = json_decode($payslip['deductions_json'] ?? '[]', true);
                $totalGrossPay += floatval($payslip['gross_pay']);
                
                foreach ($deductions as $deduction) {
                    $type = strtolower($deduction['type'] ?? $deduction['name'] ?? '');
                    $amount = floatval($deduction['amount'] ?? 0);
                    
                    if (strpos($type, 'paye') !== false || strpos($type, 'tax') !== false) {
                        $totalPAYE += $amount;
                    } elseif (strpos($type, 'pension') !== false) {
                        $totalPension += $amount;
                    } elseif (strpos($type, 'nhf') !== false) {
                        $totalNHF += $amount;
                    }
                }
            }
            
            // Get liability accounts (create if they don't exist)
            $payeAccount = $this->accountModel->getByCode('2210'); // PAYE Payable
            $pensionAccount = $this->accountModel->getByCode('2220'); // Pension Payable
            $nhfAccount = $this->accountModel->getByCode('2230'); // NHF Payable
            
            // Build journal entry lines
            $journalEntries = [
                // Debit: Payroll Expense (gross pay)
                [
                    'account_id' => $expenseAccount['id'],
                    'debit' => $totalGrossPay,
                    'credit' => 0.00,
                    'description' => 'Payroll Expense - Gross Pay'
                ]
            ];
            
            // Credit: PAYE Liability
            if ($totalPAYE > 0 && $payeAccount) {
                $journalEntries[] = [
                    'account_id' => $payeAccount['id'],
                    'debit' => 0.00,
                    'credit' => $totalPAYE,
                    'description' => 'PAYE Withholding Tax'
                ];
            }
            
            // Credit: Pension Liability
            if ($totalPension > 0 && $pensionAccount) {
                $journalEntries[] = [
                    'account_id' => $pensionAccount['id'],
                    'debit' => 0.00,
                    'credit' => $totalPension,
                    'description' => 'Pension Contribution (8%)'
                ];
            }
            
            // Credit: NHF Liability
            if ($totalNHF > 0 && $nhfAccount) {
                $journalEntries[] = [
                    'account_id' => $nhfAccount['id'],
                    'debit' => 0.00,
                    'credit' => $totalNHF,
                    'description' => 'NHF Contribution (2.5%)'
                ];
            }
            
            // Credit: Cash (net pay)
            $netPay = floatval($payrollRun['total_amount']);
            $journalEntries[] = [
                'account_id' => $cashAccount['account_id'],
                'debit' => 0.00,
                'credit' => $netPay,
                'description' => 'Net Payroll Payment'
            ];

            // Use Transaction Service to post journal entry
            $journalData = [
                'date' => date('Y-m-d'),
                'reference_type' => 'payroll_run',
                'reference_id' => $payrollRunId,
                'description' => 'Payroll for ' . $payrollRun['period'],
                'journal_type' => 'payroll',
                'entries' => $journalEntries,
                'created_by' => $this->session['user_id'],
                'auto_post' => true // Auto-approve and post
            ];

            $journalId = $this->transactionService->postJournalEntry($journalData);

            if (!$journalId) {
                throw new Exception('Failed to create journal entry');
            }

            // Update payroll run status
            $updateData = ['status' => 'posted'];
            
            // Only add posted_date and posted_by if columns exist
            try {
                $columns = $this->db->query("SHOW COLUMNS FROM `" . $this->db->getPrefix() . "payroll_runs` LIKE 'posted_date'")->fetchAll();
                if (!empty($columns)) {
                    $updateData['posted_date'] = date('Y-m-d H:i:s');
                    $updateData['posted_by'] = $this->session['user_id'];
                }
            } catch (Exception $e) {
                error_log("Payroll postPayroll: Could not check for posted_date column: " . $e->getMessage());
            }
            
            $updated = $this->payrollModel->updateRun($payrollRunId, $updateData);
            
            if (!$updated) {
                throw new Exception('Failed to update payroll run status');
            }

            // Update all payslips to posted status
            $payslips = $this->payrollModel->getPayslips($payrollRunId);
            foreach ($payslips as $payslip) {
                $this->db->query(
                    "UPDATE `" . $this->db->getPrefix() . "payslips` 
                     SET status = 'posted' 
                     WHERE id = ?",
                    [$payslip['id']]
                );
            }
            
            error_log("Payroll postPayroll: Updated " . count($payslips) . " payslips to posted status");

            // Update cash account balance
            $balanceUpdated = $this->cashAccountModel->updateBalance(
                $cashAccountId, 
                floatval($payrollRun['total_amount']), 
                'withdrawal'
            );
            
            if (!$balanceUpdated) {
                error_log("Payroll postPayroll: WARNING - Failed to update cash account balance");
            } else {
                error_log("Payroll postPayroll: Successfully updated cash account #{$cashAccountId} balance by -" . floatval($payrollRun['total_amount']));
            }

            $this->setFlashMessage('success', 'Payroll posted successfully.');
            redirect('payroll/view/' . $payrollRunId);
        } catch (Exception $e) {
            error_log('Payroll postPayroll error: ' . $e->getMessage());
            error_log('Payroll postPayroll trace: ' . $e->getTraceAsString());
            $this->setFlashMessage('danger', 'Failed to post payroll: ' . $e->getMessage());
            redirect('payroll/view/' . $payrollRunId);
        }
    }
    
    /**
     * Alias for postPayroll to support /payroll/post/{id} URL
     */
    public function post($payrollRunId) {
        return $this->postPayroll($payrollRunId);
    }
    
    /**
     * View individual payslip
     */
    public function payslip($payslipId) {
        try {
            // Get payslip details
            $payslip = $this->payrollModel->getPayslipById($payslipId);
            
            if (!$payslip) {
                $this->setFlashMessage('danger', 'Payslip not found.');
                redirect('payroll');
                return;
            }
            
            // Get employee details
            $employee = $this->employeeModel->getById($payslip['employee_id']);
            
            // Get payroll run details
            $payrollRun = $this->payrollModel->getRunById($payslip['payroll_run_id']);
            
            // Decode earnings and deductions
            $earnings = json_decode($payslip['earnings_json'] ?? '[]', true);
            $deductions = json_decode($payslip['deductions_json'] ?? '[]', true);
            
            $data = [
                'page_title' => 'Payslip - ' . ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''),
                'payslip' => $payslip,
                'employee' => $employee,
                'payroll_run' => $payrollRun,
                'earnings' => $earnings,
                'deductions' => $deductions,
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('payroll/payslip', $data);
        } catch (Exception $e) {
            error_log('Payroll payslip error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading payslip: ' . $e->getMessage());
            redirect('payroll');
        }
    }
    
    /**
     * Diagnostic method to check why payroll data isn't loading
     * Access via: /payroll/diagnostic
     */
    public function diagnostic() {
        // Only allow admin access
        if (!isset($this->session['role']) || !in_array($this->session['role'], ['admin', 'super_admin'])) {
            $this->setFlashMessage('danger', 'Access denied');
            redirect('dashboard');
            return;
        }
        
        $diagnostics = [];
        
        // Check employees
        try {
            $allEmployees = $this->db->fetchAll("SELECT * FROM `" . $this->db->getPrefix() . "employees`");
            $activeEmployees = $this->employeeModel->getActiveEmployees();
            $diagnostics['employees'] = [
                'total' => count($allEmployees),
                'active' => count($activeEmployees),
                'sample' => array_slice($activeEmployees, 0, 3),
                'status' => count($activeEmployees) > 0 ? 'success' : 'warning'
            ];
        } catch (Exception $e) {
            $diagnostics['employees'] = [
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
        
        // Check cash accounts
        try {
            $allCashAccounts = $this->db->fetchAll("SELECT * FROM `" . $this->db->getPrefix() . "cash_accounts`");
            $activeCashAccounts = $this->cashAccountModel->getActive();
            $diagnostics['cash_accounts'] = [
                'total' => count($allCashAccounts),
                'active' => count($activeCashAccounts),
                'sample' => array_slice($activeCashAccounts, 0, 3),
                'status' => count($activeCashAccounts) > 0 ? 'success' : 'warning'
            ];
        } catch (Exception $e) {
            $diagnostics['cash_accounts'] = [
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
        
        // Check accounts table
        try {
            $allAccounts = $this->db->fetchAll("SELECT * FROM `" . $this->db->getPrefix() . "accounts` WHERE status = 'active'");
            $diagnostics['accounts'] = [
                'total' => count($allAccounts),
                'status' => 'success'
            ];
        } catch (Exception $e) {
            $diagnostics['accounts'] = [
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
        
        $data = [
            'page_title' => 'Payroll Diagnostic',
            'diagnostics' => $diagnostics
        ];
        
        $this->loadView('payroll/diagnostic', $data);
    }
}

