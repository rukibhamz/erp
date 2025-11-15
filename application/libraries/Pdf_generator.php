<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDF Generator Library
 * Simple PDF generator for invoices and documents
 * Uses TCPDF if available, otherwise falls back to HTML output
 */
class Pdf_generator {
    private $pdf;
    private $companyInfo;
    
    public function __construct($companyInfo = []) {
        $this->companyInfo = $companyInfo;
        
        // Try to use TCPDF if available
        if (class_exists('TCPDF')) {
            $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        } else {
            // Fallback: Create a simple PDF-like HTML generator
            $this->pdf = null;
        }
    }
    
    /**
     * Generate invoice PDF
     * 
     * @param array $invoice Invoice data
     * @param array $items Invoice items
     * @param array $customer Customer data
     * @return string PDF content or HTML fallback
     */
    public function generateInvoice($invoice, $items, $customer) {
        if ($this->pdf instanceof TCPDF) {
            return $this->generateInvoiceTCPDF($invoice, $items, $customer);
        } else {
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
     * Generate invoice as HTML (fallback when TCPDF not available)
     */
    private function generateInvoiceHTML($invoice, $items, $customer) {
        $companyName = $this->companyInfo['name'] ?? 'Company Name';
        $companyAddress = $this->getCompanyAddress();
        
        ob_start();
        include BASEPATH . '../application/views/receivables/invoice_pdf.php';
        return ob_get_clean();
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

