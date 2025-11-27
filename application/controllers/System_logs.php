<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System Logs Controller
 * 
 * Admin dashboard for viewing and managing system logs
 * 
 * @package    ERP
 * @subpackage Controllers
 * @category   Admin
 */
class System_logs extends Base_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read'); // Only admins can view logs
        $this->load->library('error_logger');
        $this->load->model('System_log_model');
    }
    
    /**
     * Display logs dashboard
     */
    public function index() {
        $data['page_title'] = 'System Logs';
        
        // Get filter parameters
        $level = $this->input->get('level');
        $module = $this->input->get('module');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $search = $this->input->get('search');
        
        // Pagination
        $page = $this->input->get('page') ?? 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        // Get logs with filters
        $filters = [
            'level' => $level,
            'module' => $module,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'search' => $search
        ];
        
        $data['logs'] = $this->System_log_model->getLogs($filters, $per_page, $offset);
        $data['total'] = $this->System_log_model->countLogs($filters);
        $data['pages'] = ceil($data['total'] / $per_page);
        $data['current_page'] = $page;
        
        // Get statistics
        $data['stats'] = $this->error_logger->getStatistics(24);
        
        // Get unique modules for filter
        $data['modules'] = $this->System_log_model->getUniqueModules();
        
        // Current filters
        $data['filters'] = $filters;
        
        $this->load->view('templates/header', $data);
        $this->load->view('system/logs', $data);
        $this->load->view('templates/footer');
    }
    
    /**
     * View single log entry
     */
    public function view($id) {
        $data['page_title'] = 'Log Details';
        $data['log'] = $this->System_log_model->getLog($id);
        
        if (!$data['log']) {
            $this->session->set_flashdata('error', 'Log entry not found');
            redirect('system_logs');
        }
        
        $this->load->view('templates/header', $data);
        $this->load->view('system/log_detail', $data);
        $this->load->view('templates/footer');
    }
    
    /**
     * Export logs to CSV
     */
    public function export() {
        // Get filter parameters
        $level = $this->input->get('level');
        $module = $this->input->get('module');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        
        $filters = [
            'level' => $level,
            'module' => $module,
            'date_from' => $date_from,
            'date_to' => $date_to
        ];
        
        $logs = $this->System_log_model->getLogs($filters, 10000, 0); // Max 10k records
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['ID', 'Level', 'Message', 'Module', 'User', 'IP Address', 'Created At']);
        
        // CSV data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['level'],
                $log['message'],
                $log['module'],
                $log['username'] ?? 'System',
                $log['ip_address'],
                $log['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Clean old logs
     */
    public function clean() {
        $this->requirePermission('settings', 'delete');
        
        $days = $this->input->post('days') ?? 90;
        $deleted = $this->error_logger->cleanOldLogs($days);
        
        $this->session->set_flashdata('success', "Deleted {$deleted} old log entries");
        redirect('system_logs');
    }
    
    /**
     * Get logs via AJAX for real-time updates
     */
    public function ajax_get_logs() {
        $level = $this->input->get('level');
        $limit = $this->input->get('limit') ?? 20;
        
        $logs = $this->error_logger->getRecentLogs($limit, $level);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'logs' => $logs]);
    }
    
    /**
     * Get log statistics via AJAX
     */
    public function ajax_get_stats() {
        $hours = $this->input->get('hours') ?? 24;
        $stats = $this->error_logger->getStatistics($hours);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'stats' => $stats]);
    }
}
