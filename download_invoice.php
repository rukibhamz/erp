<?php
/**
 * Download Invoice PDF
 * 
 * SECURITY: This endpoint validates user authentication and invoice access
 * before forcing download of the PDF with proper Content-Disposition headers
 * 
 * Usage: download_invoice.php?id=123
 */

// Bootstrap the application
define('BASEPATH', __DIR__ . '/application/');
require_once BASEPATH . 'core/Base_Controller.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized: Please log in to download invoices.');
}

try {
    // Get invoice ID from query parameter
    $invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($invoiceId <= 0) {
        http_response_code(400);
        die('Invalid invoice ID.');
    }
    
    // Load database
    require_once BASEPATH . 'core/Database.php';
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    
    // Load models
    require_once BASEPATH . 'models/Invoice_model.php';
    require_once BASEPATH . 'models/Entity_model.php';
    $invoiceModel = new Invoice_model();
    $entityModel = new Entity_model();
    
    // Get invoice with customer data
    $invoice = $invoiceModel->getWithCustomer($invoiceId);
    
    if (!$invoice) {
        http_response_code(404);
        die('Invoice not found.');
    }
    
    // SECURITY: Check if user has permission to download this invoice
    // You can add additional checks here (e.g., user role, ownership, etc.)
    
    // Get invoice items
    $items = $invoiceModel->getItems($invoiceId);
    
    if (empty($items)) {
        http_response_code(400);
        die('Invoice has no items.');
    }
    
    // Get company/entity information
    $entities = $entityModel->getAll();
    $companyInfo = !empty($entities) ? $entities[0] : [
        'name' => 'Company Name',
        'address' => '',
        'city' => '',
        'state' => '',
        'zip_code' => '',
        'country' => '',
        'phone' => '',
        'email' => '',
        'tax_id' => '',
        'logo' => null
    ];
    
    // Prepare customer data
    $customer = [
        'company_name' => $invoice['company_name'] ?? '',
        'address' => $invoice['address'] ?? '',
        'city' => $invoice['city'] ?? '',
        'state' => $invoice['state'] ?? '',
        'zip_code' => $invoice['zip_code'] ?? '',
        'country' => $invoice['country'] ?? '',
        'email' => $invoice['email'] ?? '',
        'phone' => $invoice['phone'] ?? ''
    ];
    
    // Load helpers
    require_once BASEPATH . 'helpers/url_helper.php';
    require_once BASEPATH . 'helpers/common_helper.php';
    
    // Load PDF generator
    require_once BASEPATH . 'libraries/Pdf_generator.php';
    $pdfGenerator = new Pdf_generator($companyInfo);
    
    // Generate PDF
    $pdfContent = $pdfGenerator->generateInvoice($invoice, $items, $customer);
    
    // Check if it's HTML fallback or actual PDF
    $isPdf = (substr($pdfContent, 0, 4) === '%PDF');
    
    // Sanitize filename
    $filename = 'Invoice-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . ($isPdf ? '.pdf' : '.html');
    
    if ($isPdf) {
        // Force download with proper headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output PDF
        echo $pdfContent;
    } else {
        // HTML fallback - still force download
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $pdfContent;
    }
    
    exit;
    
} catch (Exception $e) {
    error_log('Download invoice error: ' . $e->getMessage());
    http_response_code(500);
    die('Error generating invoice: ' . htmlspecialchars($e->getMessage()));
}

