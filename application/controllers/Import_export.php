<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Import_export extends Base_Controller {
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Import / Export Data',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('import_export/index', $data);
    }
    
    public function import() {
        $this->requirePermission('settings', 'create');
        $type = $_GET['type'] ?? 'customers';
        
        $data = [
            'page_title' => 'Import ' . ucfirst($type),
            'type' => $type,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('import_export/import', $data);
    }
    
    public function processImport() {
        $this->requirePermission('settings', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['import_file'])) {
            $this->setFlashMessage('danger', 'No file uploaded.');
            redirect('import-export/import?type=' . ($_POST['type'] ?? 'customers'));
        }
        
        $type = sanitize_input($_POST['type'] ?? 'customers');
        $file = $_FILES['import_file'];
        
        // Validate file
        $validation = $this->validateImportFile($file);
        if (!$validation['valid']) {
            $this->setFlashMessage('danger', $validation['error']);
            redirect('import-export/import?type=' . $type);
        }
        
        // Process import
        try {
            $result = $this->processFileImport($type, $file);
            
            if ($result['success']) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Import', "Imported {$result['imported']} {$type} records");
                $this->setFlashMessage('success', "Successfully imported {$result['imported']} records. " . ($result['errors'] > 0 ? "{$result['errors']} errors encountered." : ""));
            } else {
                $this->setFlashMessage('danger', $result['message'] ?? 'Import failed.');
            }
        } catch (Exception $e) {
            error_log('Import error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error processing import: ' . $e->getMessage());
        }
        
        redirect('import-export/import?type=' . $type);
    }
    
    public function export() {
        $this->requirePermission('settings', 'read');
        $type = sanitize_input($_GET['type'] ?? 'customers');
        
        try {
            $this->exportData($type);
        } catch (Exception $e) {
            error_log('Export error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error exporting data: ' . $e->getMessage());
            redirect('import-export');
        }
    }
    
    public function downloadTemplate() {
        $this->requirePermission('settings', 'read');
        $type = sanitize_input($_GET['type'] ?? 'customers');
        
        $this->generateTemplate($type);
    }
    
    private function validateImportFile($file) {
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $allowedMimes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'Invalid file type. Please upload CSV or Excel file.'];
        }
        
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['valid' => false, 'error' => 'File size exceeds 10MB limit.'];
        }
        
        return ['valid' => true];
    }
    
    private function processFileImport($type, $file) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $imported = 0;
        $errors = 0;
        $errorMessages = [];
        
        if ($extension === 'csv') {
            $result = $this->importCSV($type, $file['tmp_name']);
        } else {
            // Excel import would require PhpSpreadsheet library
            return ['success' => false, 'message' => 'Excel import requires PhpSpreadsheet library. Please use CSV format.'];
        }
        
        return [
            'success' => true,
            'imported' => $result['imported'],
            'errors' => $result['errors'],
            'messages' => $result['messages'] ?? []
        ];
    }
    
    private function importCSV($type, $filePath) {
        $imported = 0;
        $errors = 0;
        $messages = [];
        
        if (($handle = fopen($filePath, 'r')) === false) {
            throw new Exception('Could not open file for reading');
        }
        
        // Get header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception('Invalid CSV file format');
        }
        
        // Normalize headers
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        
        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $data = array_combine($headers, $row);
            
            try {
                switch ($type) {
                    case 'customers':
                        $this->importCustomer($data);
                        $imported++;
                        break;
                    case 'items':
                        $this->importItem($data);
                        $imported++;
                        break;
                    case 'vendors':
                        $this->importVendor($data);
                        $imported++;
                        break;
                    default:
                        $errors++;
                        $messages[] = "Row {$rowNum}: Unknown import type";
                }
            } catch (Exception $e) {
                $errors++;
                $messages[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
        
        fclose($handle);
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'messages' => $messages
        ];
    }
    
    private function importCustomer($data) {
        $customerModel = $this->loadModel('Customer_model');
        
        // Map CSV fields to database fields
        $customerData = [
            'company_name' => sanitize_input($data['name'] ?? $data['company_name'] ?? ''),
            'customer_code' => sanitize_input($data['customer_code'] ?? ($customerModel->getNextCustomerCode() ?? '')),
            'email' => sanitize_input($data['email'] ?? ''),
            'phone' => sanitize_input($data['phone'] ?? ''),
            'address' => sanitize_input($data['address'] ?? ''),
            'city' => sanitize_input($data['city'] ?? ''),
            'state' => sanitize_input($data['state'] ?? ''),
            'country' => sanitize_input($data['country'] ?? 'Nigeria'),
            'postal_code' => sanitize_input($data['postal_code'] ?? ''),
            'credit_limit' => floatval($data['credit_limit'] ?? 0),
            'payment_terms' => sanitize_input($data['payment_terms'] ?? 'net_30'),
            'status' => sanitize_input($data['status'] ?? 'active'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (empty($customerData['company_name'])) {
            throw new Exception('Customer name is required');
        }
        
        return $customerModel->create($customerData);
    }
    
    private function importItem($data) {
        $itemModel = $this->loadModel('Item_model');
        
        // Map CSV fields to database fields
        $itemData = [
            'item_name' => sanitize_input($data['name'] ?? $data['item_name'] ?? ''),
            'sku' => sanitize_input($data['item_code'] ?? $data['sku'] ?? ($itemModel->getNextSKU() ?? '')),
            'description' => sanitize_input($data['description'] ?? ''),
            'category' => sanitize_input($data['category'] ?? ''),
            'unit' => sanitize_input($data['unit'] ?? 'pcs'),
            'unit_cost' => floatval($data['unit_cost'] ?? 0),
            'unit_price' => floatval($data['unit_price'] ?? 0),
            'item_type' => sanitize_input($data['item_type'] ?? 'product'),
            'taxable' => !empty($data['taxable']) || (isset($data['taxable']) && strtolower($data['taxable']) === 'yes') ? 1 : 0,
            'item_status' => sanitize_input($data['status'] ?? $data['item_status'] ?? 'active'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (empty($itemData['item_name'])) {
            throw new Exception('Item name is required');
        }
        
        return $itemModel->create($itemData);
    }
    
    private function importVendor($data) {
        $vendorModel = $this->loadModel('Vendor_model');
        
        $vendorData = [
            'name' => sanitize_input($data['name'] ?? ''),
            'vendor_code' => sanitize_input($data['vendor_code'] ?? $this->generateVendorCode()),
            'email' => sanitize_input($data['email'] ?? ''),
            'phone' => sanitize_input($data['phone'] ?? ''),
            'address' => sanitize_input($data['address'] ?? ''),
            'city' => sanitize_input($data['city'] ?? ''),
            'state' => sanitize_input($data['state'] ?? ''),
            'country' => sanitize_input($data['country'] ?? 'Nigeria'),
            'payment_terms' => sanitize_input($data['payment_terms'] ?? 'net_30'),
            'status' => sanitize_input($data['status'] ?? 'active'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (empty($vendorData['name'])) {
            throw new Exception('Vendor name is required');
        }
        
        return $vendorModel->create($vendorData);
    }
    
    private function exportData($type) {
        $filename = $type . '_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($type) {
            case 'customers':
                $this->exportCustomers($output);
                break;
            case 'items':
                $this->exportItems($output);
                break;
            case 'vendors':
                $this->exportVendors($output);
                break;
            case 'invoices':
                $this->exportInvoices($output);
                break;
            default:
                fclose($output);
                throw new Exception('Unknown export type');
        }
        
        fclose($output);
        exit;
    }
    
    private function exportCustomers($output) {
        $customerModel = $this->loadModel('Customer_model');
        $customers = $customerModel->getAll();
        
        // Headers
        fputcsv($output, [
            'Name', 'Customer Code', 'Email', 'Phone', 'Address', 
            'City', 'State', 'Country', 'Postal Code', 
            'Credit Limit', 'Payment Terms', 'Status'
        ]);
        
        // Data rows
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['company_name'] ?? $customer['name'] ?? '',
                $customer['customer_code'] ?? '',
                $customer['email'] ?? '',
                $customer['phone'] ?? '',
                $customer['address'] ?? '',
                $customer['city'] ?? '',
                $customer['state'] ?? '',
                $customer['country'] ?? '',
                $customer['postal_code'] ?? '',
                $customer['credit_limit'] ?? 0,
                $customer['payment_terms'] ?? '',
                $customer['status'] ?? ''
            ]);
        }
    }
    
    private function exportItems($output) {
        $itemModel = $this->loadModel('Item_model');
        $items = $itemModel->getAll();
        
        fputcsv($output, [
            'Name', 'Item Code', 'Description', 'Category', 'Unit',
            'Unit Cost', 'Unit Price', 'Item Type', 'Taxable', 'Status'
        ]);
        
        foreach ($items as $item) {
            fputcsv($output, [
                $item['item_name'] ?? $item['name'] ?? '',
                $item['sku'] ?? $item['item_code'] ?? '',
                $item['description'] ?? '',
                $item['category'] ?? '',
                $item['unit'] ?? '',
                $item['unit_cost'] ?? 0,
                $item['unit_price'] ?? 0,
                $item['item_type'] ?? '',
                ($item['taxable'] ?? 0) ? 'Yes' : 'No',
                $item['item_status'] ?? $item['status'] ?? ''
            ]);
        }
    }
    
    private function exportVendors($output) {
        $vendorModel = $this->loadModel('Vendor_model');
        $vendors = $vendorModel->getAll();
        
        fputcsv($output, [
            'Name', 'Vendor Code', 'Email', 'Phone', 'Address',
            'City', 'State', 'Country', 'Payment Terms', 'Status'
        ]);
        
        foreach ($vendors as $vendor) {
            fputcsv($output, [
                $vendor['name'] ?? '',
                $vendor['vendor_code'] ?? '',
                $vendor['email'] ?? '',
                $vendor['phone'] ?? '',
                $vendor['address'] ?? '',
                $vendor['city'] ?? '',
                $vendor['state'] ?? '',
                $vendor['country'] ?? '',
                $vendor['payment_terms'] ?? '',
                $vendor['status'] ?? ''
            ]);
        }
    }
    
    private function exportInvoices($output) {
        $invoiceModel = $this->loadModel('Invoice_model');
        $invoices = $invoiceModel->getAll();
        
        fputcsv($output, [
            'Invoice Number', 'Invoice Date', 'Due Date', 'Customer', 'Subtotal',
            'Tax Amount', 'Total Amount', 'Status', 'Balance Amount'
        ]);
        
        foreach ($invoices as $invoice) {
            fputcsv($output, [
                $invoice['invoice_number'] ?? '',
                $invoice['invoice_date'] ?? '',
                $invoice['due_date'] ?? '',
                $invoice['customer_name'] ?? '',
                $invoice['subtotal'] ?? 0,
                $invoice['tax_amount'] ?? 0,
                $invoice['total_amount'] ?? 0,
                $invoice['status'] ?? '',
                $invoice['balance_amount'] ?? 0
            ]);
        }
    }
    
    private function generateTemplate($type) {
        $filename = $type . '_import_template.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($type) {
            case 'customers':
                fputcsv($output, [
                    'Name', 'Customer Code', 'Email', 'Phone', 'Address',
                    'City', 'State', 'Country', 'Postal Code',
                    'Credit Limit', 'Payment Terms', 'Status'
                ]);
                // Add sample row
                fputcsv($output, [
                    'Sample Customer Ltd', 'CUST001', 'customer@example.com', '08012345678',
                    '123 Sample Street', 'Lagos', 'Lagos State', 'Nigeria', '100001',
                    '1000000', 'net_30', 'active'
                ]);
                break;
                
            case 'items':
                fputcsv($output, [
                    'Name', 'Item Code', 'Description', 'Category', 'Unit',
                    'Unit Cost', 'Unit Price', 'Item Type', 'Taxable', 'Status'
                ]);
                fputcsv($output, [
                    'Sample Item', 'ITEM001', 'Sample description', 'Category A', 'pcs',
                    '1000', '1500', 'product', 'Yes', 'active'
                ]);
                break;
                
            case 'vendors':
                fputcsv($output, [
                    'Name', 'Vendor Code', 'Email', 'Phone', 'Address',
                    'City', 'State', 'Country', 'Payment Terms', 'Status'
                ]);
                fputcsv($output, [
                    'Sample Vendor Ltd', 'VEND001', 'vendor@example.com', '08012345678',
                    '456 Vendor Street', 'Abuja', 'FCT', 'Nigeria', 'net_30', 'active'
                ]);
                break;
        }
        
        fclose($output);
        exit;
    }
    
}

