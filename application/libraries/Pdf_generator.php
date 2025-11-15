<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDF Generator Library
 * Generates invoice PDFs using Dompdf or HTML fallback
 */
class Pdf_generator {
    private $companyInfo;
    private $useDompdf = false;
    
    public function __construct($companyInfo = []) {
        $this->companyInfo = $companyInfo;
        
        // Check if Dompdf is available
        if (file_exists(BASEPATH . '../vendor/autoload.php')) {
            require_once BASEPATH . '../vendor/autoload.php';
            if (class_exists('Dompdf\Dompdf')) {
                $this->useDompdf = true;
            }
        }
    }
    
    /**
     * Generate invoice PDF
     * 
     * @param array $invoice Invoice data
     * @param array $items Invoice items
     * @param array $customer Customer data
     * @return string PDF content (binary) or HTML fallback
     */
    public function generateInvoice($invoice, $items, $customer) {
        // Load the HTML template
        $html = $this->loadInvoiceTemplate($invoice, $items, $customer);
        
        // Try Dompdf if available
        if ($this->useDompdf) {
            try {
                $options = new \Dompdf\Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                
                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                return $dompdf->output();
            } catch (Exception $e) {
                error_log('Dompdf error: ' . $e->getMessage());
            }
        }
        
        // Fallback to HTML
        return $html;
    }
    
    /**
     * Load invoice template from views/receivables/invoice_pdf.php
     */
    private function loadInvoiceTemplate($invoice, $items, $customer) {
        ob_start();
        $company = $this->companyInfo;
        
        // Helper functions for template
        if (!function_exists('format_currency')) {
            function format_currency($amount, $currency = 'NGN') {
                $symbols = ['NGN' => '₦', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
                return ($symbols[$currency] ?? $currency . ' ') . number_format($amount, 2);
            }
        }
        
        if (!function_exists('format_date')) {
            function format_date($date) {
                return $date ? date('M d, Y', strtotime($date)) : '';
            }
        }
        
        // Include the template
        $templateFile = BASEPATH . 'views/receivables/invoice_pdf.php';
        if (file_exists($templateFile)) {
            include $templateFile;
        } else {
            // Fallback template if file doesn't exist
            echo '<html><body>';
            echo '<h1>Invoice ' . htmlspecialchars($invoice['invoice_number']) . '</h1>';
            echo '<p>Template file not found: ' . htmlspecialchars($templateFile) . '</p>';
            echo '</body></html>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Save PDF content to file
     * 
     * @param string $content PDF or HTML content
     * @param string $filename Filename to save
     * @return array ['success' => bool, 'file_path' => string, 'filename' => string, 'error' => string]
     */
    public function savePdf($content, $filename) {
        try {
            $uploadDir = BASEPATH . '../uploads/invoices/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filePath = $uploadDir . $filename;
            file_put_contents($filePath, $content);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'filename' => $filename
            ];
        } catch (Exception $e) {
            error_log('PDF save error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
