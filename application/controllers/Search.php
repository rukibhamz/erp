<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends Base_Controller {
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        $query = sanitize_input($_GET['q'] ?? '');
        $module = sanitize_input($_GET['module'] ?? 'all');
        
        if (empty($query)) {
            $data = [
                'page_title' => 'Search',
                'query' => '',
                'results' => [],
                'flash' => $this->getFlashMessage()
            ];
            $this->loadView('search/index', $data);
            return;
        }
        
        $results = $this->performSearch($query, $module);
        
        $data = [
            'page_title' => 'Search Results',
            'query' => $query,
            'module' => $module,
            'results' => $results,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('search/index', $data);
    }
    
    public function ajax() {
        $query = sanitize_input($_GET['q'] ?? '');
        $module = sanitize_input($_GET['module'] ?? 'all');
        
        if (empty($query) || strlen($query) < 2) {
            echo json_encode(['success' => false, 'results' => []]);
            exit;
        }
        
        $results = $this->performSearch($query, $module, 5);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }
    
    private function performSearch($query, $module = 'all', $limit = 20) {
        $results = [];
        $queryLike = '%' . $query . '%';
        
        try {
            if ($module === 'all' || $module === 'customers') {
                $customers = $this->db->fetchAll(
                    "SELECT id, name, customer_code, email, phone, 'customer' as type 
                     FROM `" . $this->db->getPrefix() . "customers` 
                     WHERE (name LIKE ? OR customer_code LIKE ? OR email LIKE ? OR phone LIKE ?)
                     AND status = 'active'
                     LIMIT " . intval($limit),
                    [$queryLike, $queryLike, $queryLike, $queryLike]
                );
                $results = array_merge($results, $customers);
            }
            
            if ($module === 'all' || $module === 'invoices') {
                $invoices = $this->db->fetchAll(
                    "SELECT id, invoice_number, total_amount, invoice_date, 'invoice' as type 
                     FROM `" . $this->db->getPrefix() . "invoices` 
                     WHERE invoice_number LIKE ? 
                     LIMIT " . intval($limit),
                    [$queryLike]
                );
                $results = array_merge($results, $invoices);
            }
            
            if ($module === 'all' || $module === 'bookings') {
                $bookings = $this->db->fetchAll(
                    "SELECT id, booking_number, booking_date, total_amount, 'booking' as type 
                     FROM `" . $this->db->getPrefix() . "bookings` 
                     WHERE booking_number LIKE ? OR customer_name LIKE ?
                     LIMIT " . intval($limit),
                    [$queryLike, $queryLike]
                );
                $results = array_merge($results, $bookings);
            }
            
            if ($module === 'all' || $module === 'items') {
                $items = $this->db->fetchAll(
                    "SELECT id, name, item_code, 'item' as type 
                     FROM `" . $this->db->getPrefix() . "items` 
                     WHERE (name LIKE ? OR item_code LIKE ?) AND status = 'active'
                     LIMIT " . intval($limit),
                    [$queryLike, $queryLike]
                );
                $results = array_merge($results, $items);
            }
            
            if ($module === 'all' || $module === 'vendors') {
                $vendors = $this->db->fetchAll(
                    "SELECT id, name, vendor_code, email, 'vendor' as type 
                     FROM `" . $this->db->getPrefix() . "vendors` 
                     WHERE (name LIKE ? OR vendor_code LIKE ? OR email LIKE ?)
                     AND status = 'active'
                     LIMIT " . intval($limit),
                    [$queryLike, $queryLike, $queryLike]
                );
                $results = array_merge($results, $vendors);
            }
            
            if ($module === 'all' || $module === 'transactions') {
                $transactions = $this->db->fetchAll(
                    "SELECT t.id, t.reference, t.amount, t.date, 'transaction' as type 
                     FROM `" . $this->db->getPrefix() . "transactions` t
                     WHERE t.reference LIKE ? OR t.description LIKE ?
                     LIMIT " . intval($limit),
                    [$queryLike, $queryLike]
                );
                $results = array_merge($results, $transactions);
            }
            
            if ($module === 'all' || $module === 'properties') {
                $properties = $this->db->fetchAll(
                    "SELECT id, name, property_code, 'property' as type 
                     FROM `" . $this->db->getPrefix() . "properties` 
                     WHERE (name LIKE ? OR property_code LIKE ?) AND status = 'active'
                     LIMIT " . intval($limit),
                    [$queryLike, $queryLike]
                );
                $results = array_merge($results, $properties);
            }
            
        } catch (Exception $e) {
            error_log('Search error: ' . $e->getMessage());
        }
        
        return $results;
    }
}



