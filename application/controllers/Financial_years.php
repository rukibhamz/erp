<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Financial_years extends Base_Controller {
    private $financialYearModel;
    private $periodLockModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('accounting', 'read');
        $this->financialYearModel = $this->loadModel('Financial_year_model');
        $this->periodLockModel = $this->loadModel('Period_lock_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    /**
     * List all financial years
     */
    public function index() {
        try {
            $financialYears = $this->financialYearModel->getAll('start_date DESC');
        } catch (Exception $e) {
            $financialYears = [];
        }

        $data = [
            'page_title' => 'Financial Years',
            'financial_years' => $financialYears,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('financial_years/index', $data);
    }

    /**
     * Create a new financial year
     */
    public function create() {
        $this->requirePermission('accounting', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $startDate = sanitize_input($_POST['start_date'] ?? '');
            $endDate = sanitize_input($_POST['end_date'] ?? '');
            $name = sanitize_input($_POST['name'] ?? '');

            if (empty($startDate) || empty($endDate)) {
                $this->setFlashMessage('danger', 'Start date and end date are required.');
                redirect('financial-years/create');
            }

            if (strtotime($endDate) <= strtotime($startDate)) {
                $this->setFlashMessage('danger', 'End date must be after start date.');
                redirect('financial-years/create');
            }

            // Auto-generate name if empty
            if (empty($name)) {
                $name = 'FY ' . date('Y', strtotime($startDate)) . '/' . date('Y', strtotime($endDate));
            }

            $data = [
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $this->session['user_id']
            ];

            try {
                $id = $this->financialYearModel->create($data);
                if ($id) {
                    $this->activityModel->log($this->session['user_id'], 'create', 'Financial Years', 'Created financial year: ' . $name);
                    $this->setFlashMessage('success', 'Financial year created successfully.');
                    redirect('financial-years');
                } else {
                    $this->setFlashMessage('danger', 'Failed to create financial year.');
                }
            } catch (Exception $e) {
                error_log('Financial year create error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'An error occurred while creating the financial year.');
            }
        }

        $data = [
            'page_title' => 'Create Financial Year',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('financial_years/create', $data);
    }

    /**
     * Close a financial year
     */
    public function close($id) {
        $this->requirePermission('accounting', 'update');

        try {
            $financialYear = $this->financialYearModel->getById($id);
            if (!$financialYear) {
                $this->setFlashMessage('danger', 'Financial year not found.');
                redirect('financial-years');
            }

            if ($financialYear['status'] === 'closed') {
                $this->setFlashMessage('warning', 'This financial year is already closed.');
                redirect('financial-years');
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                check_csrf();
                
                $retainedEarnings = $this->financialYearModel->calculateRetainedEarnings($id);
                
                if ($this->financialYearModel->close($id, $this->session['user_id'])) {
                    $this->activityModel->log(
                        $this->session['user_id'], 
                        'update', 
                        'Financial Years', 
                        'Closed financial year: ' . $financialYear['name'] . ' (Retained Earnings: ' . number_format($retainedEarnings, 2) . ')'
                    );
                    $this->setFlashMessage('success', 'Financial year closed successfully. Retained earnings: ' . number_format($retainedEarnings, 2));
                } else {
                    $this->setFlashMessage('danger', 'Failed to close financial year.');
                }
                redirect('financial-years');
            }

            // Show confirmation page
            $retainedEarnings = $this->financialYearModel->calculateRetainedEarnings($id);

            $data = [
                'page_title' => 'Close Financial Year',
                'financial_year' => $financialYear,
                'retained_earnings' => $retainedEarnings,
                'flash' => $this->getFlashMessage()
            ];

            $this->loadView('financial_years/close', $data);
        } catch (Exception $e) {
            error_log('Financial year close error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'An error occurred.');
            redirect('financial-years');
        }
    }

    /**
     * View/manage periods for a financial year
     */
    public function periods($id) {
        try {
            $financialYear = $this->financialYearModel->getById($id);
            if (!$financialYear) {
                $this->setFlashMessage('danger', 'Financial year not found.');
                redirect('financial-years');
            }

            $lockedPeriods = $this->periodLockModel->getLockedPeriods($id);
            
            // Build months list for this financial year
            $months = [];
            $start = new DateTime($financialYear['start_date']);
            $end = new DateTime($financialYear['end_date']);
            $current = clone $start;
            
            while ($current <= $end) {
                $month = intval($current->format('n'));
                $year = intval($current->format('Y'));
                $isLocked = false;
                
                foreach ($lockedPeriods as $lp) {
                    if ($lp['period_month'] == $month && $lp['period_year'] == $year) {
                        $isLocked = true;
                        break;
                    }
                }
                
                $months[] = [
                    'month' => $month,
                    'year' => $year,
                    'label' => $current->format('F Y'),
                    'locked' => $isLocked
                ];
                
                $current->modify('+1 month');
            }

            $data = [
                'page_title' => 'Periods - ' . $financialYear['name'],
                'financial_year' => $financialYear,
                'months' => $months,
                'flash' => $this->getFlashMessage()
            ];

            $this->loadView('financial_years/periods', $data);
        } catch (Exception $e) {
            error_log('Financial year periods error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'An error occurred.');
            redirect('financial-years');
        }
    }

    /**
     * Lock a period (AJAX/POST)
     */
    public function lockPeriod() {
        $this->requirePermission('accounting', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('financial-years');
        }
        
        check_csrf();

        $financialYearId = intval($_POST['financial_year_id'] ?? 0);
        $month = intval($_POST['month'] ?? 0);
        $year = intval($_POST['year'] ?? 0);

        if (!$financialYearId || !$month || !$year) {
            $this->setFlashMessage('danger', 'Invalid period data.');
            redirect('financial-years');
        }

        try {
            if ($this->periodLockModel->lockPeriod($financialYearId, $month, $year, $this->session['user_id'])) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Period Locks', "Locked period: $month/$year");
                $this->setFlashMessage('success', 'Period locked successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to lock period.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error locking period.');
        }

        redirect('financial-years/periods/' . $financialYearId);
    }

    /**
     * Unlock a period (AJAX/POST)
     */
    public function unlockPeriod() {
        $this->requirePermission('accounting', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('financial-years');
        }
        
        check_csrf();

        $financialYearId = intval($_POST['financial_year_id'] ?? 0);
        $month = intval($_POST['month'] ?? 0);
        $year = intval($_POST['year'] ?? 0);

        if (!$financialYearId || !$month || !$year) {
            $this->setFlashMessage('danger', 'Invalid period data.');
            redirect('financial-years');
        }

        try {
            if ($this->periodLockModel->unlockPeriod($financialYearId, $month, $year)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Period Locks', "Unlocked period: $month/$year");
                $this->setFlashMessage('success', 'Period unlocked successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to unlock period.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error unlocking period.');
        }

        redirect('financial-years/periods/' . $financialYearId);
    }
}
