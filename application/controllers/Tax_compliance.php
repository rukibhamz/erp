<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_compliance extends Base_Controller {
    private $taxDeadlineModel;
    private $taxTypeModel;
    private $taxFilingModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->taxDeadlineModel = $this->loadModel('Tax_deadline_model');
        $this->taxTypeModel = $this->loadModel('Tax_type_model');
        $this->taxFilingModel = $this->loadModel('Tax_filing_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $view = $_GET['view'] ?? 'list'; // list, calendar
        
        try {
            $upcoming = $this->taxDeadlineModel->getUpcoming(90);
            $overdue = $this->taxDeadlineModel->getOverdue();
            $taxTypes = $this->taxTypeModel->getAllActive();
        } catch (Exception $e) {
            error_log('Tax_compliance index error: ' . $e->getMessage());
            $upcoming = [];
            $overdue = [];
            $taxTypes = [];
        }
        
        $data = [
            'page_title' => 'Tax Compliance Calendar',
            'upcoming' => $upcoming,
            'overdue' => $overdue,
            'tax_types' => $taxTypes,
            'view' => $view,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/compliance/index', $data);
    }
    
    public function list() {
        // Show list view
        $view = $_GET['view'] ?? 'list';
        
        try {
            $upcomingDeadlines = $this->taxDeadlineModel->getUpcoming(365);
            $overdueDeadlines = $this->taxDeadlineModel->getOverdue();
        } catch (Exception $e) {
            error_log('Tax_compliance list error: ' . $e->getMessage());
            $upcomingDeadlines = [];
            $overdueDeadlines = [];
        }
        
        $data = [
            'page_title' => 'Tax Compliance - List',
            'upcoming_deadlines' => $upcomingDeadlines,
            'overdue_deadlines' => $overdueDeadlines,
            'view' => $view,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/compliance/list', $data);
    }
    
    public function createDeadline() {
        $this->requirePermission('tax', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'tax_type' => sanitize_input($_POST['tax_type'] ?? ''),
                'deadline_date' => sanitize_input($_POST['deadline_date'] ?? ''),
                'deadline_type' => sanitize_input($_POST['deadline_type'] ?? 'filing'),
                'period_covered' => sanitize_input($_POST['period_covered'] ?? ''),
                'status' => 'upcoming'
            ];
            
            if ($this->taxDeadlineModel->createDeadline($data['tax_type'], $data['period_covered'], $data['deadline_date'], $data['deadline_type'])) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Tax', 'Created tax deadline: ' . $data['tax_type']);
                $this->setFlashMessage('success', 'Tax deadline created successfully.');
                redirect('tax/compliance');
            } else {
                $this->setFlashMessage('danger', 'Failed to create tax deadline.');
            }
        }
        
        try {
            $taxTypes = $this->taxTypeModel->getAllActive();
        } catch (Exception $e) {
            $taxTypes = [];
        }
        
        $data = [
            'page_title' => 'Create Tax Deadline',
            'tax_types' => $taxTypes,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/compliance/create_deadline', $data);
    }
    
    public function markCompleted() {
        $this->requirePermission('tax', 'update');
        header('Content-Type: application/json');
        
        $deadlineId = intval($_POST['deadline_id'] ?? 0);
        
        if (!$deadlineId) {
            echo json_encode(['success' => false, 'message' => 'Invalid deadline ID']);
            exit;
        }
        
        try {
            if ($this->taxDeadlineModel->markCompleted($deadlineId)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Tax', 'Marked deadline as completed: ' . $deadlineId);
                echo json_encode(['success' => true, 'message' => 'Deadline marked as completed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update deadline']);
            }
        } catch (Exception $e) {
            error_log('Tax_compliance markCompleted error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

