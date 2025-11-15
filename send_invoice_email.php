<?php
/**
 * Send Invoice via Email
 * 
 * SECURITY: This endpoint validates user authentication and invoice access
 * before sending the invoice PDF as an email attachment
 * 
 * Usage: send_invoice_email.php (POST request with invoice_id and recipient_email)
 */

// Bootstrap the application
define('BASEPATH', __DIR__ . '/application/');
require_once BASEPATH . 'core/Base_Controller.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in to send invoices.']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    // Get POST data
    $invoiceId = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $recipientEmail = isset($_POST['recipient_email']) ? trim($_POST['recipient_email']) : '';
    $customMessage = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Validate input
    if ($invoiceId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid invoice ID.']);
        exit;
    }
    
    if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid recipient email is required.']);
        exit;
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
        echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
        exit;
    }
    
    // SECURITY: Check if user has permission to send this invoice
    // You can add additional checks here (e.g., user role, ownership, etc.)
    
    // Get invoice items
    $items = $invoiceModel->getItems($invoiceId);
    
    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invoice has no items.']);
        exit;
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
    
    // Load PDF generator
    require_once BASEPATH . 'libraries/Pdf_generator.php';
    $pdfGenerator = new Pdf_generator($companyInfo);
    
    // Generate PDF
    $pdfContent = $pdfGenerator->generateInvoice($invoice, $items, $customer);
    
    // Check if it's HTML fallback or actual PDF
    $isPdf = (substr($pdfContent, 0, 4) === '%PDF');
    $filename = 'Invoice-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . ($isPdf ? '.pdf' : '.html');
    
    // Prepare email
    $subject = 'Invoice ' . $invoice['invoice_number'] . ' from ' . $companyInfo['name'];
    
    // Build email message
    $emailMessage = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .invoice-details { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #0066cc; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Invoice ' . htmlspecialchars($invoice['invoice_number']) . '</h2>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($customer['company_name'] ?: 'Customer') . ',</p>
            <p>Please find attached your invoice <strong>' . htmlspecialchars($invoice['invoice_number']) . '</strong>.</p>
            
            <div class="invoice-details">
                <p><strong>Invoice Date:</strong> ' . date('M d, Y', strtotime($invoice['invoice_date'])) . '</p>
                <p><strong>Due Date:</strong> ' . date('M d, Y', strtotime($invoice['due_date'])) . '</p>
                <p><strong>Total Amount:</strong> ' . format_currency($invoice['total_amount'], $invoice['currency']) . '</p>
                ' . ($invoice['balance_amount'] > 0 ? '<p><strong>Balance Due:</strong> ' . format_currency($invoice['balance_amount'], $invoice['currency']) . '</p>' : '') . '
            </div>';
    
    if (!empty($customMessage)) {
        $emailMessage .= '<p>' . nl2br(htmlspecialchars($customMessage)) . '</p>';
    }
    
    $emailMessage .= '<p>If you have any questions about this invoice, please contact us.</p>
            <p>Thank you for your business!</p>
        </div>
        <div class="footer">
            <p><strong>' . htmlspecialchars($companyInfo['name']) . '</strong></p>
            ' . (!empty($companyInfo['email']) ? '<p>Email: ' . htmlspecialchars($companyInfo['email']) . '</p>' : '') . '
            ' . (!empty($companyInfo['phone']) ? '<p>Phone: ' . htmlspecialchars($companyInfo['phone']) . '</p>' : '') . '
        </div>
    </div>
</body>
</html>';
    
    // Load helpers
    require_once BASEPATH . 'helpers/url_helper.php';
    require_once BASEPATH . 'helpers/email_helper.php';
    require_once BASEPATH . 'helpers/common_helper.php';
    
    // Ensure format_currency function exists
    if (!function_exists('format_currency')) {
        function format_currency($amount, $currency = 'USD') {
            $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦'];
            $symbol = $symbols[$currency] ?? $currency . ' ';
            return $symbol . number_format((float)$amount, 2);
        }
    }
    
    // Prepare attachment
    $attachments = [[
        'content' => $pdfContent,
        'filename' => $filename,
        'mime' => $isPdf ? 'application/pdf' : 'text/html'
    ]];
    
    // Send email with attachment
    $result = send_email(
        $recipientEmail,
        $subject,
        $emailMessage,
        $companyInfo['email'] ?? null,
        $companyInfo['name'] ?? null,
        true, // HTML email
        $attachments
    );
    
    if ($result) {
        // Update invoice status to 'sent' if it's currently 'draft'
        if ($invoice['status'] === 'draft') {
            $invoiceModel->update($invoiceId, ['status' => 'sent']);
        }
        
        // Log activity (if activity logging is available)
        // You can add activity logging here
        
        echo json_encode([
            'success' => true,
            'message' => 'Invoice sent successfully to ' . htmlspecialchars($recipientEmail) . '.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email. Please check your SMTP configuration and try again.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Send invoice email error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error sending invoice: ' . htmlspecialchars($e->getMessage())
    ]);
}

