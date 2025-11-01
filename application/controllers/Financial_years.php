<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Financial_years extends Base_Controller {
    private $financialYearModel;
    private $periodLockModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
        $this->financialYearModel = $this->loadModel('Financial_year_model');
        $this->periodLockModel = $this->loadModel('Period_lock_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        try {
            $financialYears = $this->financialYearModel->getAll();
            $currentYear = $this->financialYearModel->getCurrent();
        } catch (Exception $e) {
            $financialYears = [];
            $currentYear = null;
        }

        $data = [
            'page_title' => 'Financial Years',
            'financial_years' => $financialYears,
            'current_year' => $currentYear,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('financial_years/index', $data);
    }

    public function create() {
        $this->requirePermission('settings', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $yearName = sanitize_input($_POST['year_name'] ?? '');
            $startDate = sanitize_input($_POST['start_date'] ?? '');
            $endDate = sanitize_input($_POST['end_date'] ?? '');

            if (!$startDate || !$endDate) {
                $this->setFlashMessage('danger', 'Please provide start and end dates.');
                redirect('financial-years/create');
            }

            // Validate date range
            if (strtotime($startDate) >= strtotime($endDate)) {
                $this->setFlashMessage('danger', 'End date must be after start date.');
                redirect('financial-years/create');
            }

            $data = [
                'year_name' => $yearName ?: date('Y', strtotime($startDate)),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => sanitize_input($_POST['status'] ?? 'open')
            ];

            if ($this->financialYearModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Financial Years', 'Created financial year: ' . $data['year_name']);
                $this->setFlashMessage('success', 'Financial year created successfully.');
                redirect('financial-years');
            } else {
                $this->setFlashMessage('danger', 'Failed to create financial year.');
            }
        }

        $data = [
            'page_title' => 'Create Financial Year',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('financial_years/create', $data);
    }

    public function close($id) {
        $this->requirePermission('settings', 'update');

        try {
            $year = $this->financialYearModel->getById($id);
            if (!$year) {
                $this->setFlashMessage('danger', 'Financial year not found.');
                redirect('financial-years');
            }

            if ($year['status'] === 'closed') {
                $this->setFlashMessage('danger', 'Financial year already closed.');
                redirect('financial-years');
            }

            if ($this->financialYearModel->close($id, $this->session['user_id'])) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Financial Years', 'Closed financial year: ' . $year['year_name']);
                $this->setFlashMessage('success', 'Financial year closed successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to close financial year.');
            }
        } catch (Exception $e) {
            error_log('Financial_years close error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error closing financial year: ' . $e->getMessage());
        }

        redirect('financial-years');
    }

    public function periods($financialYearId) {
        try {
            $year = $this->financialYearModel->getById($financialYearId);
            if (!$year) {
                $this->setFlashMessage('danger', 'Financial year not found.');
                redirect('financial-years');
            }

            // Get locked periods
            $lockedPeriods = $this->periodLockModel->getLockedPeriods($financialYearId);

            // Build period list
            $periods = [];
            $startDate = new DateTime($year['start_date']);
            $endDate = new DateTime($year['end_date']);
            $current = clone $startDate;

            while ($current <= $endDate) {
                $month = (int)$current->format('n');
                $yearNum = (int)$current->format('Y');
                
                $isLocked = false;
                foreach ($lockedPeriods as $lock) {
                    if ($lock['period_month'] == $month && $lock['period_year'] == $yearNum) {
                        $isLocked = true;
                        break;
                    }
                }

                $periods[] = [
                    'month' => $month,
                    'year' => $yearNum,
                    'month_name' => $current->format('F Y'),
                    'is_locked' => $isLocked
                ];

                $current->modify('+1 month');
            }
        } catch (Exception $e) {
            $year = null;
            $periods = [];
        }

        $data = [
            'page_title' => 'Period Management',
            'financial_year' => $year,
            'periods' => $periods,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('financial_years/periods', $data);
    }

    public function lockPeriod() {
        $this->requirePermission('settings', 'update');

        $financialYearId = intval($_POST['financial_year_id'] ?? 0);
        $month = intval($_POST['month'] ?? 0);
        $year = intval($_POST['year'] ?? 0);

        if (!$financialYearId || !$month || !$year) {
            $this->setFlashMessage('danger', 'Invalid parameters.');
            redirect('financial-years');
        }

        if ($this->periodLockModel->lockPeriod($financialYearId, $month, $year, $this->session['user_id'])) {
            $this->activityModel->log($this->session['user_id'], 'create', 'Financial Years', "Locked period: {$month}/{$year}");
            $this->setFlashMessage('success', 'Period locked successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to lock period.');
        }

        redirect('financial-years/periods/' . $financialYearId);
    }

    public function unlockPeriod() {
        $this->requirePermission('settings', 'update');

        $financialYearId = intval($_POST['financial_year_id'] ?? 0);
        $month = intval($_POST['month'] ?? 0);
        $year = intval($_POST['year'] ?? 0);

        if (!$financialYearId || !$month || !$year) {
            $this->setFlashMessage('danger', 'Invalid parameters.');
            redirect('financial-years');
        }

        if ($this->periodLockModel->unlockPeriod($financialYearId, $month, $year)) {
            $this->activityModel->log($this->session['user_id'], 'update', 'Financial Years', "Unlocked period: {$month}/{$year}");
            $this->setFlashMessage('success', 'Period unlocked successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to unlock period.');
        }

        redirect('financial-years/periods/' . $financialYearId);
    }
}

