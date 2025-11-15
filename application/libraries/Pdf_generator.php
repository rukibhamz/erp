<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDF Generator Library
 * Professional PDF generator for invoices and documents
 * Supports Dompdf (preferred), TCPDF, or HTML fallback
 */
class Pdf_generator {
    private $pdf;
    private $companyInfo;
    private $pdfLibrary = null; // 'dompdf', 'tcpdf', or 'html'
    
    public function __construct($companyInfo = []) {
        $this->companyInfo = $companyInfo;
        
        // Try Dompdf first (preferred - easier to use)
        // Check if autoloader exists and load it
        $autoloadPath = BASEPATH . '../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
        
        if (class_exists('Dompdf\Dompdf')) {
            try {
                $options = new \Dompdf\Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                $options->set('defaultFont', 'DejaVu Sans');
                
                $this->pdf = new \Dompdf\Dompdf($options);
                $this->pdfLibrary = 'dompdf';
            } catch (Exception $e) {
                error_log('Dompdf initialization error: ' . $e->getMessage());
                $this->pdf = null;
                $this->pdfLibrary = 'html';
            }
        }
        // Try TCPDF if available
        elseif (class_exists('TCPDF')) {
            $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $this->pdfLibrary = 'tcpdf';
        } else {
            // Fallback: HTML output
            $this->pdf = null;
            $this->pdfLibrary = 'html';
        }
    }
    
    /**
     * Generate invoice PDF
     * 
     * @param array $invoice Invoice data
     * @param array $items Invoice items
     * @param array $customer Customer data
     * @return array ['success' => bool, 'file_path' => string, 'filename' => string, 'pdf_content' => string, 'error' => string]
     */
    public function generateInvoice($invoice, $items, $customer) {
        try {
            // Generate PDF content
            if ($this->pdfLibrary === 'dompdf') {
                $pdfContent = $this->generateInvoiceDompdf($invoice, $items, $customer);
            } elseif ($this->pdfLibrary === 'tcpdf') {
                $pdfContent = $this->generateInvoiceTCPDF($invoice, $items, $customer);
            } else {
                $pdfContent = $this->generateInvoiceHTML($invoice, $items, $customer);
            }
            
            // Determine if it's actual PDF or HTML
            $isPdf = (substr($pdfContent, 0, 4) === '%PDF');
            $extension = $isPdf ? '.pdf' : '.html';
            
            // Create uploads/invoices directory if it doesn't exist
            $uploadDir = BASEPATH . '../uploads/invoices/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate filename
            $filename = 'invoice_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice['invoice_number']) . '_' . time() . $extension;
            $filePath = $uploadDir . $filename;
            
            // Save to file
            file_put_contents($filePath, $pdfContent);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'filename' => $filename,
                'pdf_content' => $pdfContent,
                'error' => null
            ];
            
        } catch (Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'file_path' => null,
                'filename' => null,
                'pdf_content' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate invoice PDF content only (without saving)
     * 
     * @param array $invoice Invoice data
     * @param array $items Invoice items
     * @param array $customer Customer data
     * @return string PDF content (binary string) or HTML fallback
     */
    public function generateInvoiceContent($invoice, $items, $customer) {
        if ($this->pdfLibrary === 'dompdf') {
            return $this->generateInvoiceDompdf($invoice, $items, $customer);
        } elseif ($this->pdfLibrary === 'tcpdf') {
            return $this->generateInvoiceTCPDF($invoice, $items, $customer);
        } else {
            return $this->generateInvoiceHTML($invoice, $items, $customer);
        }
    }
    
    /**
     * Generate invoice using Dompdf (preferred method)
     * 
     * @param array $invoice Invoice data
     * @param array $items Invoice items
     * @param array $customer Customer data
     * @return string PDF content as binary string
     */
    private function generateInvoiceDompdf($invoice, $items, $customer) {
        try {
            // Generate HTML content (uses template file if available)
            $html = $this->generateInvoiceHTML($invoice, $items, $customer);
            
            // Load HTML into Dompdf
            $this->pdf->loadHtml($html);
            
            // Set paper size and orientation
            $this->pdf->setPaper('A4', 'portrait');
            
            // Render PDF
            $this->pdf->render();
            
            // Return PDF as string
            return $this->pdf->output();
        } catch (\Exception $e) {
            error_log('Dompdf error: ' . $e->getMessage());
            error_log('Dompdf trace: ' . $e->getTraceAsString());
            // Fallback to HTML
            return $this->generateInvoiceHTML($invoice, $items, $customer);
        }
    }
    
    /**
     * Generate invoice using TCPDF
     */
    private function generateInvoiceTCPDF($invoice, $items, $customer) {
        $pdf = $this->pdf;
        
        // Set document information
        $pdf->SetCreator('ERP System');
        $pdf->SetAuthor($this->companyInfo['name'] ?? 'Company');
        $pdf->SetTitle('Invoice ' . $invoice['invoice_number']);
        $pdf->SetSubject('Invoice');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Company header
        $companyName = $this->companyInfo['name'] ?? 'Company Name';
        $companyAddress = $this->getCompanyAddress();
        
        $html = '<table cellpadding="5" cellspacing="0" style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="width: 50%;">
                    <h2 style="margin: 0; color: #333;">' . htmlspecialchars($companyName) . '</h2>
                    <p style="margin: 5px 0; color: #666; font-size: 10px;">' . nl2br(htmlspecialchars($companyAddress)) . '</p>
                </td>
                <td style="width: 50%; text-align: right;">
                    <h1 style="margin: 0; color: #0066cc; font-size: 24px;">INVOICE</h1>
                    <p style="margin: 5px 0; color: #666; font-size: 10px;">Invoice #: <strong>' . htmlspecialchars($invoice['invoice_number']) . '</strong></p>
                    <p style="margin: 5px 0; color: #666; font-size: 10px;">Date: ' . format_date($invoice['invoice_date']) . '</p>
                    <p style="margin: 5px 0; color: #666; font-size: 10px;">Due Date: ' . format_date($invoice['due_date']) . '</p>
                </td>
            </tr>
        </table>';
        
        // Customer and company info
        $html .= '<table cellpadding="5" cellspacing="0" style="width: 100%; margin-bottom: 20px; border-top: 2px solid #ddd; padding-top: 10px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <h3 style="margin: 0 0 10px 0; font-size: 12px; color: #333;">Bill To:</h3>
                    <p style="margin: 0; font-size: 10px; color: #666;">
                        <strong>' . htmlspecialchars($customer['company_name'] ?? '') . '</strong><br>
                        ' . ($customer['address'] ? htmlspecialchars($customer['address']) . '<br>' : '') . '
                        ' . ($customer['city'] ? htmlspecialchars($customer['city']) . ', ' : '') . 
                        ($customer['state'] ? htmlspecialchars($customer['state']) . ' ' : '') . 
                        ($customer['zip_code'] ? htmlspecialchars($customer['zip_code']) : '') . '<br>
                        ' . ($customer['country'] ? htmlspecialchars($customer['country']) : '') . '<br>
                        ' . ($customer['email'] ? 'Email: ' . htmlspecialchars($customer['email']) . '<br>' : '') . '
                        ' . ($customer['phone'] ? 'Phone: ' . htmlspecialchars($customer['phone']) : '') . '
                    </p>
                </td>
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <h3 style="margin: 0 0 10px 0; font-size: 12px; color: #333;">From:</h3>
                    <p style="margin: 0; font-size: 10px; color: #666; text-align: right;">
                        <strong>' . htmlspecialchars($companyName) . '</strong><br>
                        ' . nl2br(htmlspecialchars($companyAddress)) . '
                    </p>
                </td>
            </tr>
        </table>';
        
        // Items table
        $html .= '<table cellpadding="8" cellspacing="0" style="width: 100%; border: 1px solid #ddd; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th style="text-align: left; border-bottom: 2px solid #ddd; font-size: 10px; font-weight: bold;">Description</th>
                    <th style="text-align: center; border-bottom: 2px solid #ddd; font-size: 10px; font-weight: bold;">Qty</th>
                    <th style="text-align: right; border-bottom: 2px solid #ddd; font-size: 10px; font-weight: bold;">Unit Price</th>
                    <th style="text-align: right; border-bottom: 2px solid #ddd; font-size: 10px; font-weight: bold;">Total</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($items as $item) {
            $html .= '<tr>
                <td style="font-size: 10px;">' . htmlspecialchars($item['item_description']) . '</td>
                <td style="text-align: center; font-size: 10px;">' . number_format($item['quantity'], 2) . '</td>
                <td style="text-align: right; font-size: 10px;">' . format_currency($item['unit_price'], $invoice['currency']) . '</td>
                <td style="text-align: right; font-size: 10px;">' . format_currency($item['line_total'], $invoice['currency']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-size: 10px; font-weight: bold; padding-top: 10px; border-top: 1px solid #ddd;">Subtotal:</td>
                    <td style="text-align: right; font-size: 10px; font-weight: bold; padding-top: 10px; border-top: 1px solid #ddd;">' . format_currency($invoice['subtotal'], $invoice['currency']) . '</td>
                </tr>';
        
        if ($invoice['tax_amount'] > 0) {
            $html .= '<tr>
                <td colspan="3" style="text-align: right; font-size: 10px; padding-top: 5px;">Tax (' . $invoice['tax_rate'] . '%):</td>
                <td style="text-align: right; font-size: 10px; padding-top: 5px;">' . format_currency($invoice['tax_amount'], $invoice['currency']) . '</td>
            </tr>';
        }
        
        if ($invoice['discount_amount'] > 0) {
            $html .= '<tr>
                <td colspan="3" style="text-align: right; font-size: 10px; padding-top: 5px;">Discount:</td>
                <td style="text-align: right; font-size: 10px; padding-top: 5px;">' . format_currency($invoice['discount_amount'], $invoice['currency']) . '</td>
            </tr>';
        }
        
        $html .= '<tr style="background-color: #f5f5f5;">
                    <td colspan="3" style="text-align: right; font-size: 12px; font-weight: bold; padding: 10px; border-top: 2px solid #333;">Total Amount:</td>
                    <td style="text-align: right; font-size: 12px; font-weight: bold; padding: 10px; border-top: 2px solid #333;">' . format_currency($invoice['total_amount'], $invoice['currency']) . '</td>
                </tr>
            </tfoot>
        </table>';
        
        // Payment info and notes
        if (!empty($invoice['notes']) || !empty($invoice['terms'])) {
            $html .= '<table cellpadding="5" cellspacing="0" style="width: 100%; margin-top: 20px;">
                <tr>';
            
            if (!empty($invoice['notes'])) {
                $html .= '<td style="width: 50%; vertical-align: top;">
                    <h3 style="margin: 0 0 10px 0; font-size: 11px; color: #333;">Notes:</h3>
                    <p style="margin: 0; font-size: 10px; color: #666;">' . nl2br(htmlspecialchars($invoice['notes'])) . '</p>
                </td>';
            }
            
            if (!empty($invoice['terms'])) {
                $html .= '<td style="width: 50%; vertical-align: top;">
                    <h3 style="margin: 0 0 10px 0; font-size: 11px; color: #333;">Payment Terms:</h3>
                    <p style="margin: 0; font-size: 10px; color: #666;">' . nl2br(htmlspecialchars($invoice['terms'])) . '</p>
                </td>';
            }
            
            $html .= '</tr>
            </table>';
        }
        
        // Footer
        $html .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #999;">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice.</p>
        </div>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf->Output('', 'S'); // Return as string
    }
    
    /**
     * Generate invoice as HTML (used by Dompdf and as fallback)
     * Uses the invoice_pdf.php template file if available, otherwise generates inline HTML
     * 
     * @param array $invoice Invoice data
     * @param array $items Invoice items
     * @param array $customer Customer data
     * @return string HTML content
     */
    private function generateInvoiceHTML($invoice, $items, $customer) {
        // Try to use the invoice_pdf.php template file first
        $templateFile = BASEPATH . 'views/receivables/invoice_pdf.php';
        
        if (file_exists($templateFile)) {
            // Use template file
            ob_start();
            
            // Extract variables for template
            $company = $this->companyInfo;
            
            // Include the template file
            include $templateFile;
            
            // Get buffer contents
            $html = ob_get_clean();
            
            return $html;
        }
        
        // Fallback: Generate HTML inline
        $companyName = $this->companyInfo['name'] ?? 'Company Name';
        $companyAddress = $this->getCompanyAddress();
        $companyLogo = $this->companyInfo['logo'] ?? null;
        
        // Get logo path if available
        $logoPath = '';
        if ($companyLogo && file_exists(BASEPATH . '../uploads/' . $companyLogo)) {
            $logoPath = base_url('uploads/' . $companyLogo);
        }
        
        // Format currency helper (fallback if not available)
        if (!function_exists('format_currency')) {
            function format_currency($amount, $currency = 'USD') {
                $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦'];
                $symbol = $symbols[$currency] ?? $currency . ' ';
                return $symbol . number_format((float)$amount, 2);
            }
        }
        
        // Format date helper (fallback if not available)
        if (!function_exists('format_date')) {
            function format_date($date, $format = 'M d, Y') {
                if (empty($date) || $date === '0000-00-00') return '';
                $timestamp = strtotime($date);
                return $timestamp ? date($format, $timestamp) : '';
            }
        }
        
        // Start HTML output
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice ' . htmlspecialchars($invoice['invoice_number']) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #333; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #0066cc; }
        .company-info { flex: 1; }
        .company-logo { max-width: 150px; max-height: 80px; margin-bottom: 10px; }
        .company-name { font-size: 24px; font-weight: bold; color: #0066cc; margin-bottom: 10px; }
        .company-address { font-size: 10px; color: #666; line-height: 1.8; }
        .invoice-info { text-align: right; }
        .invoice-title { font-size: 32px; font-weight: bold; color: #0066cc; margin-bottom: 15px; }
        .invoice-details { font-size: 11px; }
        .invoice-details p { margin: 3px 0; }
        .billing-section { display: flex; justify-content: space-between; margin-bottom: 30px; padding: 15px; background-color: #f9f9f9; }
        .bill-to, .bill-from { flex: 1; }
        .bill-to h3, .bill-from h3 { font-size: 12px; margin-bottom: 8px; color: #333; }
        .bill-to p, .bill-from p { font-size: 10px; color: #666; line-height: 1.8; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background-color: #0066cc; color: white; padding: 10px; text-align: left; font-size: 11px; font-weight: bold; }
        .items-table td { padding: 10px; border-bottom: 1px solid #ddd; font-size: 10px; }
        .items-table tr:nth-child(even) { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-section { margin-top: 20px; }
        .totals-table { width: 100%; max-width: 300px; margin-left: auto; }
        .totals-table td { padding: 8px 10px; border-bottom: 1px solid #ddd; }
        .totals-table td:first-child { text-align: right; font-weight: bold; }
        .totals-table .total-row { background-color: #0066cc; color: white; font-weight: bold; font-size: 14px; }
        .totals-table .total-row td { border: none; }
        .notes-section { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
        .notes-section h3 { font-size: 12px; margin-bottom: 10px; color: #333; }
        .notes-section p { font-size: 10px; color: #666; line-height: 1.8; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #999; }
        @media print {
            .container { padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="company-info">
                ' . ($logoPath ? '<img src="' . htmlspecialchars($logoPath) . '" alt="Company Logo" class="company-logo">' : '') . '
                <div class="company-name">' . htmlspecialchars($companyName) . '</div>
                <div class="company-address">' . nl2br(htmlspecialchars($companyAddress)) . '</div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-details">
                    <p><strong>Invoice #:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</p>
                    <p><strong>Date:</strong> ' . format_date($invoice['invoice_date']) . '</p>
                    <p><strong>Due Date:</strong> ' . format_date($invoice['due_date']) . '</p>
                    ' . (!empty($invoice['reference']) ? '<p><strong>Reference:</strong> ' . htmlspecialchars($invoice['reference']) . '</p>' : '') . '
                </div>
            </div>
        </div>
        
        <!-- Billing Section -->
        <div class="billing-section">
            <div class="bill-to">
                <h3>Bill To:</h3>
                <p>
                    <strong>' . htmlspecialchars($customer['company_name'] ?? '') . '</strong><br>
                    ' . (!empty($customer['address']) ? htmlspecialchars($customer['address']) . '<br>' : '') . '
                    ' . (!empty($customer['city']) ? htmlspecialchars($customer['city']) . ', ' : '') . 
                    (!empty($customer['state']) ? htmlspecialchars($customer['state']) . ' ' : '') . 
                    (!empty($customer['zip_code']) ? htmlspecialchars($customer['zip_code']) : '') . '<br>
                    ' . (!empty($customer['country']) ? htmlspecialchars($customer['country']) . '<br>' : '') . '
                    ' . (!empty($customer['email']) ? 'Email: ' . htmlspecialchars($customer['email']) . '<br>' : '') . '
                    ' . (!empty($customer['phone']) ? 'Phone: ' . htmlspecialchars($customer['phone']) : '') . '
                </p>
            </div>
            <div class="bill-from">
                <h3>From:</h3>
                <p>
                    <strong>' . htmlspecialchars($companyName) . '</strong><br>
                    ' . nl2br(htmlspecialchars($companyAddress)) . '
                </p>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($items as $item) {
            $html .= '<tr>
                <td>' . htmlspecialchars($item['item_description']) . '</td>
                <td class="text-center">' . number_format($item['quantity'], 2) . '</td>
                <td class="text-right">' . format_currency($item['unit_price'], $invoice['currency']) . '</td>
                <td class="text-right">' . format_currency($item['line_total'], $invoice['currency']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>
        
        <!-- Totals Section -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">' . format_currency($invoice['subtotal'], $invoice['currency']) . '</td>
                </tr>';
        
        if (!empty($invoice['discount_amount']) && $invoice['discount_amount'] > 0) {
            $html .= '<tr>
                    <td>Discount:</td>
                    <td class="text-right">-' . format_currency($invoice['discount_amount'], $invoice['currency']) . '</td>
                </tr>';
        }
        
        if (!empty($invoice['tax_amount']) && $invoice['tax_amount'] > 0) {
            $html .= '<tr>
                    <td>Tax (' . number_format($invoice['tax_rate'], 2) . '%):</td>
                    <td class="text-right">' . format_currency($invoice['tax_amount'], $invoice['currency']) . '</td>
                </tr>';
        }
        
        $html .= '<tr class="total-row">
                    <td>Total Amount:</td>
                    <td class="text-right">' . format_currency($invoice['total_amount'], $invoice['currency']) . '</td>
                </tr>';
        
        if (!empty($invoice['paid_amount']) && $invoice['paid_amount'] > 0) {
            $html .= '<tr>
                    <td>Paid Amount:</td>
                    <td class="text-right">' . format_currency($invoice['paid_amount'], $invoice['currency']) . '</td>
                </tr>
                <tr>
                    <td><strong>Balance Due:</strong></td>
                    <td class="text-right"><strong>' . format_currency($invoice['balance_amount'], $invoice['currency']) . '</strong></td>
                </tr>';
        }
        
        $html .= '</table>
        </div>
        
        <!-- Notes and Terms Section -->
        ' . ((!empty($invoice['notes']) || !empty($invoice['terms'])) ? '<div class="notes-section">
            ' . (!empty($invoice['notes']) ? '<div style="margin-bottom: 15px;">
                <h3>Notes:</h3>
                <p>' . nl2br(htmlspecialchars($invoice['notes'])) . '</p>
            </div>' : '') . '
            ' . (!empty($invoice['terms']) ? '<div>
                <h3>Payment Terms:</h3>
                <p>' . nl2br(htmlspecialchars($invoice['terms'])) . '</p>
            </div>' : '') . '
        </div>' : '') . '
        
        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice.</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Get formatted company address
     */
    private function getCompanyAddress() {
        $parts = [];
        if (!empty($this->companyInfo['address'])) $parts[] = $this->companyInfo['address'];
        if (!empty($this->companyInfo['city'])) $parts[] = $this->companyInfo['city'];
        if (!empty($this->companyInfo['state'])) $parts[] = $this->companyInfo['state'];
        if (!empty($this->companyInfo['zip_code'])) $parts[] = $this->companyInfo['zip_code'];
        if (!empty($this->companyInfo['country'])) $parts[] = $this->companyInfo['country'];
        if (!empty($this->companyInfo['phone'])) $parts[] = 'Phone: ' . $this->companyInfo['phone'];
        if (!empty($this->companyInfo['email'])) $parts[] = 'Email: ' . $this->companyInfo['email'];
        
        return implode("\n", $parts);
    }
}

