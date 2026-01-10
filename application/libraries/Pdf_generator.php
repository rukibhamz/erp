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
    /**
     * Generate bill PDF
     */
    public function generateBill($bill, $items, $vendor) {
        // Load the HTML template
        $html = $this->loadBillTemplate($bill, $items, $vendor);
        
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
     * Load bill template
     */
    private function loadBillTemplate($bill, $items, $vendor) {
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
        
        // Reuse invoice template structure but with Bill data
        // Ideally we should have a generic template, but creating a specific one is clearer
        // For now, let's create a temporary inline template or look for a bill view.
        // I'll check if views/payables/bill_pdf.php exists. If not, I'll use a fallback here.
        
        $templateFile = BASEPATH . 'views/payables/bill_pdf.php';
        if (file_exists($templateFile)) {
            include $templateFile;
        } else {
            // Fallback inline template
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333; }
                    .header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
                    .company-info h2 { margin: 0; color: #2c3e50; }
                    .bill-info { text-align: right; }
                    .bill-info h1 { margin: 0; color: #e74c3c; }
                    .addresses { display: flex; justify-content: space-between; margin-bottom: 40px; }
                    .vendor-to { width: 48%; }
                    .company-addr { width: 48%; text-align: right; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                    th { background: #f8f9fa; text-align: left; padding: 12px; border-bottom: 2px solid #ddd; }
                    td { padding: 12px; border-bottom: 1px solid #eee; }
                    .amounts { width: 300px; margin-left: auto; }
                    .amount-row { display: flex; justify-content: space-between; padding: 5px 0; }
                    .total { font-weight: bold; font-size: 1.2em; border-top: 2px solid #333; margin-top: 10px; padding-top: 10px; }
                    .footer { margin-top: 50px; text-align: center; color: #777; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="company-info">
                        <h2><?= htmlspecialchars($company['name']) ?></h2>
                        <div><?= htmlspecialchars($company['address']) ?></div>
                        <div><?= htmlspecialchars($company['city']) ?>, <?= htmlspecialchars($company['state']) ?></div>
                    </div>
                    <div class="bill-info">
                        <h1>BILL</h1>
                        <div>#<?= htmlspecialchars($bill['bill_number']) ?></div>
                        <div>Date: <?= format_date($bill['bill_date']) ?></div>
                        <div>Due: <?= format_date($bill['due_date']) ?></div>
                        <div style="margin-top: 10px; display: inline-block; padding: 4px 8px; background: #eee; border-radius: 4px;">
                            <?= strtoupper($bill['status']) ?>
                        </div>
                    </div>
                </div>

                <div class="addresses">
                    <div class="vendor-to">
                        <h3>Vendor</h3>
                        <strong><?= htmlspecialchars($vendor['company_name']) ?></strong><br>
                        <?= htmlspecialchars($vendor['address']) ?><br>
                        <?= htmlspecialchars($vendor['city']) ?><br>
                        <?= htmlspecialchars($vendor['email']) ?>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align: center">Qty</th>
                            <th style="text-align: right">Price</th>
                            <th style="text-align: right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['description'] ?? $item['item_description'] ?? '-') ?></td>
                            <td style="text-align: center"><?= $item['quantity'] ?></td>
                            <td style="text-align: right"><?= format_currency($item['unit_price'], $bill['currency']) ?></td>
                            <td style="text-align: right"><?= format_currency($item['line_total'], $bill['currency']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="amounts">
                    <div class="amount-row">
                        <span>Subtotal:</span>
                        <span><?= format_currency($bill['subtotal'], $bill['currency']) ?></span>
                    </div>
                    <?php if ($bill['discount_amount'] > 0): ?>
                    <div class="amount-row">
                        <span>Discount:</span>
                        <span>-<?= format_currency($bill['discount_amount'], $bill['currency']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($bill['tax_amount'] > 0): ?>
                    <div class="amount-row">
                        <span>Tax:</span>
                        <span><?= format_currency($bill['tax_amount'], $bill['currency']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="amount-row total">
                        <span>Total:</span>
                        <span><?= format_currency($bill['total_amount'], $bill['currency']) ?></span>
                    </div>
                    <div class="amount-row">
                        <span>Paid:</span>
                        <span><?= format_currency($bill['paid_amount'], $bill['currency']) ?></span>
                    </div>
                    <div class="amount-row" style="color: <?= $bill['balance_amount'] > 0 ? '#e74c3c' : '#27ae60' ?>">
                        <span>Balance Due:</span>
                        <span><?= format_currency($bill['balance_amount'], $bill['currency']) ?></span>
                    </div>
                </div>

                <div class="footer">
                    <p>Generated on <?= date('Y-m-d H:i:s') ?></p>
                </div>
            </body>
            </html>
            <?php
        }
        
        return ob_get_clean();
    }
}
