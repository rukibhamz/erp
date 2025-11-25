<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employees extends Base_Controller {
    private $employeeModel;
    private $payrollModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('employees', 'read'); // Changed from payroll to employees
        $this->employeeModel = $this->loadModel('Employee_model');
        $this->payrollModel = $this->loadModel('Payroll_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $employees = $this->employeeModel->getActiveEmployees();
        } catch (Exception $e) {
            error_log('Employees index error: ' . $e->getMessage());
            $employees = [];
        }
        
        $data = [
            'page_title' => 'Employees',
            'employees' => $employees,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('employees/index', $data);
    }
    
    public function create() {
        $this->requirePermission('employees', 'create');
        
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
            
            // Validate email
            if (!empty($data['email']) && !validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
                redirect('employees/create');
            }
            
            // Validate phone
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
                redirect('employees/create');
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Validate names
            if (!empty($data['first_name']) && !validate_name($data['first_name'])) {
                $this->setFlashMessage('danger', 'Invalid first name.');
                redirect('employees/create');
            }
            
            if (!empty($data['last_name']) && !validate_name($data['last_name'])) {
                $this->setFlashMessage('danger', 'Invalid last name.');
                redirect('employees/create');
            }
            
            // Auto-generate employee code if empty (leave blank to auto-generate)
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
                $this->activityModel->log($this->session['user_id'], 'create', 'Employees', 'Created employee: ' . $data['first_name'] . ' ' . $data['last_name']);
                $this->setFlashMessage('success', 'Employee created successfully.');
                redirect('employees');
            } else {
                $this->setFlashMessage('danger', 'Failed to create employee.');
            }
        }
        
        $data = [
            'page_title' => 'Create Employee',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('employees/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('payroll', 'update');
        
        $employee = $this->employeeModel->getById($id);
        if (!$employee) {
            $this->setFlashMessage('danger', 'Employee not found.');
            redirect('employees');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                redirect('employees/edit/' . $id);
            }
            
            // Validate phone
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number.');
                redirect('employees/edit/' . $id);
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Validate names
            if (!empty($data['first_name']) && !validate_name($data['first_name'])) {
                $this->setFlashMessage('danger', 'Invalid first name.');
                redirect('employees/edit/' . $id);
            }
            
            if (!empty($data['last_name']) && !validate_name($data['last_name'])) {
                $this->setFlashMessage('danger', 'Invalid last name.');
                redirect('employees/edit/' . $id);
            }
            
            // Salary structure
            $salaryStructure = [
                'basic_salary' => floatval($_POST['basic_salary'] ?? 0),
                'allowances' => [],
                'deductions' => []
            ];
            $data['salary_structure'] = json_encode($salaryStructure);
            
            if ($this->employeeModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Employees', 'Updated employee: ' . $data['first_name'] . ' ' . $data['last_name']);
                $this->setFlashMessage('success', 'Employee updated successfully.');
                redirect('employees');
            } else {
                $this->setFlashMessage('danger', 'Failed to update employee.');
            }
        }
        
        $data = [
            'page_title' => 'Edit Employee',
            'employee' => $employee,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('employees/edit', $data);
    }
    
    public function view($id) {
        $employee = $this->employeeModel->getById($id);
        if (!$employee) {
            $this->setFlashMessage('danger', 'Employee not found.');
            redirect('employees');
        }
        
        // Get payroll history for this employee
        try {
            $payrollHistory = $this->payrollModel->getByEmployee($id);
        } catch (Exception $e) {
            error_log('Employees view payroll history error: ' . $e->getMessage());
            $payrollHistory = [];
        }
        
        $data = [
            'page_title' => 'Employee Details',
            'employee' => $employee,
            'payroll_history' => $payrollHistory,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('employees/view', $data);
    }
}

