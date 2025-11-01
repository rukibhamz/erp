<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Budgets extends Base_Controller {
    private $budgetModel;
    private $accountModel;
    private $financialYearModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('budgets', 'read');
        $this->budgetModel = $this->loadModel('Budget_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->financialYearModel = $this->loadModel('Financial_year_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        $financialYearId = $_GET['financial_year_id'] ?? null;

        try {
            if ($financialYearId) {
                $budgets = $this->budgetModel->getByFinancialYear($financialYearId);
            } else {
                $currentYear = $this->financialYearModel->getCurrent();
                if ($currentYear) {
                    $budgets = $this->budgetModel->getByFinancialYear($currentYear['id']);
                    $financialYearId = $currentYear['id'];
                } else {
                    $budgets = [];
                }
            }

            $financialYears = $this->financialYearModel->getOpen();
        } catch (Exception $e) {
            $budgets = [];
            $financialYears = [];
        }

        $data = [
            'page_title' => 'Budgets',
            'budgets' => $budgets,
            'financial_years' => $financialYears,
            'selected_year_id' => $financialYearId,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('budgets/index', $data);
    }

    public function create() {
        $this->requirePermission('budgets', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $financialYearId = intval($_POST['financial_year_id'] ?? 0);
            $accountId = intval($_POST['account_id'] ?? 0);
            $budgetName = sanitize_input($_POST['budget_name'] ?? '');

            if (!$financialYearId || !$accountId) {
                $this->setFlashMessage('danger', 'Please select financial year and account.');
                redirect('budgets/create');
            }

            $data = [
                'budget_name' => $budgetName,
                'financial_year_id' => $financialYearId,
                'account_id' => $accountId,
                'january' => floatval($_POST['january'] ?? 0),
                'february' => floatval($_POST['february'] ?? 0),
                'march' => floatval($_POST['march'] ?? 0),
                'april' => floatval($_POST['april'] ?? 0),
                'may' => floatval($_POST['may'] ?? 0),
                'june' => floatval($_POST['june'] ?? 0),
                'july' => floatval($_POST['july'] ?? 0),
                'august' => floatval($_POST['august'] ?? 0),
                'september' => floatval($_POST['september'] ?? 0),
                'october' => floatval($_POST['october'] ?? 0),
                'november' => floatval($_POST['november'] ?? 0),
                'december' => floatval($_POST['december'] ?? 0),
                'status' => sanitize_input($_POST['status'] ?? 'draft'),
                'created_by' => $this->session['user_id']
            ];

            // Calculate total
            $data['total'] = $data['january'] + $data['february'] + $data['march'] + 
                            $data['april'] + $data['may'] + $data['june'] +
                            $data['july'] + $data['august'] + $data['september'] +
                            $data['october'] + $data['november'] + $data['december'];

            if ($this->budgetModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Budgets', 'Created budget: ' . $budgetName);
                $this->setFlashMessage('success', 'Budget created successfully.');
                redirect('budgets');
            } else {
                $this->setFlashMessage('danger', 'Failed to create budget.');
            }
        }

        try {
            $accounts = $this->accountModel->getAll();
            $financialYears = $this->financialYearModel->getOpen();
        } catch (Exception $e) {
            $accounts = [];
            $financialYears = [];
        }

        $data = [
            'page_title' => 'Create Budget',
            'accounts' => $accounts,
            'financial_years' => $financialYears,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('budgets/create', $data);
    }

    public function edit($id) {
        $this->requirePermission('budgets', 'update');

        $budget = $this->budgetModel->getById($id);
        if (!$budget) {
            $this->setFlashMessage('danger', 'Budget not found.');
            redirect('budgets');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'budget_name' => sanitize_input($_POST['budget_name'] ?? ''),
                'january' => floatval($_POST['january'] ?? 0),
                'february' => floatval($_POST['february'] ?? 0),
                'march' => floatval($_POST['march'] ?? 0),
                'april' => floatval($_POST['april'] ?? 0),
                'may' => floatval($_POST['may'] ?? 0),
                'june' => floatval($_POST['june'] ?? 0),
                'july' => floatval($_POST['july'] ?? 0),
                'august' => floatval($_POST['august'] ?? 0),
                'september' => floatval($_POST['september'] ?? 0),
                'october' => floatval($_POST['october'] ?? 0),
                'november' => floatval($_POST['november'] ?? 0),
                'december' => floatval($_POST['december'] ?? 0),
                'status' => sanitize_input($_POST['status'] ?? 'draft')
            ];

            // Calculate total
            $data['total'] = $data['january'] + $data['february'] + $data['march'] + 
                            $data['april'] + $data['may'] + $data['june'] +
                            $data['july'] + $data['august'] + $data['september'] +
                            $data['october'] + $data['november'] + $data['december'];

            if ($this->budgetModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Budgets', 'Updated budget: ' . $data['budget_name']);
                $this->setFlashMessage('success', 'Budget updated successfully.');
                redirect('budgets');
            } else {
                $this->setFlashMessage('danger', 'Failed to update budget.');
            }
        }

        try {
            $accounts = $this->accountModel->getAll();
            $financialYears = $this->financialYearModel->getOpen();
        } catch (Exception $e) {
            $accounts = [];
            $financialYears = [];
        }

        $data = [
            'page_title' => 'Edit Budget',
            'budget' => $budget,
            'accounts' => $accounts,
            'financial_years' => $financialYears,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('budgets/edit', $data);
    }

    public function report() {
        $this->requirePermission('budgets', 'read');

        $financialYearId = $_GET['financial_year_id'] ?? null;
        $month = $_GET['month'] ?? date('n');
        $year = $_GET['year'] ?? date('Y');

        try {
            if ($financialYearId) {
                $budgets = $this->budgetModel->getByFinancialYear($financialYearId);
                
                // Add variance data
                foreach ($budgets as &$budget) {
                    $variance = $this->budgetModel->getBudgetVariance($budget['id'], $month, $year);
                    $budget['variance'] = $variance;
                }
            } else {
                $budgets = [];
            }

            $financialYears = $this->financialYearModel->getOpen();
        } catch (Exception $e) {
            $budgets = [];
            $financialYears = [];
        }

        $data = [
            'page_title' => 'Budget vs Actual Report',
            'budgets' => $budgets,
            'financial_years' => $financialYears,
            'selected_year_id' => $financialYearId,
            'selected_month' => $month,
            'selected_year' => $year,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('budgets/report', $data);
    }
}

