<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report_builder extends Base_Controller {
    private $reportModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('reports', 'read');
        $this->reportModel = $this->loadModel('Custom_report_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $userId = $this->session['user_id'];
        $role = $this->session['role'] ?? null;
        
        $reports = $this->reportModel->getUserReports($userId, $role);
        
        $data = [
            'page_title' => 'Report Builder',
            'reports' => $reports,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('report_builder/index', $data);
    }
    
    public function create() {
        $this->requirePermission('reports', 'create');
        
        $data = [
            'page_title' => 'Create Custom Report',
            'flash' => $this->getFlashMessage(),
            'modules' => $this->getAvailableModules(),
            'dataSources' => $this->getDataSources()
        ];
        
        $this->loadView('report_builder/create', $data);
    }
    
    public function save() {
        $this->requirePermission('reports', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('report-builder');
        }
        
        try {
            $reportData = [
                'report_name' => sanitize_input($_POST['report_name'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'module' => sanitize_input($_POST['module'] ?? ''),
                'report_type' => sanitize_input($_POST['report_type'] ?? 'table'),
                'data_source' => sanitize_input($_POST['data_source'] ?? ''),
                'fields' => json_decode($_POST['fields_json'] ?? '[]', true),
                'filters' => json_decode($_POST['filters_json'] ?? '[]', true),
                'grouping' => json_decode($_POST['grouping_json'] ?? '[]', true),
                'sorting' => json_decode($_POST['sorting_json'] ?? '[]', true),
                'calculated_fields' => json_decode($_POST['calculated_fields_json'] ?? '[]', true),
                'chart_config' => json_decode($_POST['chart_config_json'] ?? '[]', true),
                'format_options' => json_decode($_POST['format_options_json'] ?? '[]', true),
                'is_public' => !empty($_POST['is_public']),
                'is_scheduled' => !empty($_POST['is_scheduled']),
                'schedule_frequency' => sanitize_input($_POST['schedule_frequency'] ?? null),
                'schedule_time' => sanitize_input($_POST['schedule_time'] ?? null),
                'schedule_emails' => sanitize_input($_POST['schedule_emails'] ?? null),
                'created_by' => $this->session['user_id']
            ];
            
            $reportId = $this->reportModel->createReport($reportData);
            
            if ($reportId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Report', "Created custom report: {$reportData['report_name']}");
                $this->setFlashMessage('success', 'Report created successfully.');
                redirect('report-builder/view/' . $reportId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create report.');
            }
        } catch (Exception $e) {
            error_log('Report_builder save error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error creating report: ' . $e->getMessage());
        }
        
        redirect('report-builder/create');
    }
    
    public function view($reportId) {
        $report = $this->reportModel->getReportById($reportId);
        
        if (!$report) {
            $this->setFlashMessage('danger', 'Report not found.');
            redirect('report-builder');
        }
        
        $data = [
            'page_title' => $report['report_name'],
            'report' => $report,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('report_builder/view', $data);
    }
    
    public function execute($reportId) {
        $result = $this->reportModel->executeReport($reportId, $this->session['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    public function export($reportId, $format = 'csv') {
        $result = $this->reportModel->executeReport($reportId, $this->session['user_id']);
        
        if (!$result['success']) {
            $this->setFlashMessage('danger', $result['message'] ?? 'Failed to execute report.');
            redirect('report-builder/view/' . $reportId);
        }
        
        $report = $this->reportModel->getReportById($reportId);
        
        switch (strtolower($format)) {
            case 'csv':
                $this->exportCSV($report['report_name'], $result['data']);
                break;
            case 'json':
                $this->exportJSON($report['report_name'], $result['data']);
                break;
            default:
                $this->setFlashMessage('danger', 'Unsupported export format.');
                redirect('report-builder/view/' . $reportId);
        }
    }
    
    private function exportCSV($reportName, $data) {
        $filename = sanitize_input($reportName) . '_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($data)) {
            // Headers
            fputcsv($output, array_keys($data[0]));
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
    
    private function exportJSON($reportName, $data) {
        $filename = sanitize_input($reportName) . '_' . date('Y-m-d_His') . '.json';
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    private function getAvailableModules() {
        return [
            'accounting' => 'Accounting',
            'inventory' => 'Inventory',
            'sales' => 'Sales',
            'tax' => 'Tax',
            'payroll' => 'Payroll',
            'booking' => 'Booking',
            'property' => 'Property Management'
        ];
    }
    
    private function getDataSources() {
        return [
            'invoice' => 'Invoices',
            'customer' => 'Customers',
            'vendor' => 'Vendors',
            'item' => 'Items',
            'transaction' => 'Transactions',
            'booking' => 'Bookings',
            'payment' => 'Payments'
        ];
    }
}


