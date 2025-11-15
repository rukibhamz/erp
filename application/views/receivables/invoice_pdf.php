<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            background: #fff;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #0066cc;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.8;
        }
        
        .invoice-title {
            text-align: right;
            flex: 1;
        }
        
        .invoice-title h1 {
            font-size: 32px;
            color: #0066cc;
            margin-bottom: 10px;
        }
        
        .invoice-meta {
            font-size: 11px;
            color: #666;
            text-align: right;
        }
        
        .invoice-meta strong {
            color: #333;
        }
        
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .bill-to, .bill-from {
            flex: 1;
        }
        
        .bill-to h3, .bill-from h3 {
            font-size: 12px;
            margin-bottom: 10px;
            color: #333;
            text-transform: uppercase;
        }
        
        .bill-to p, .bill-from p {
            font-size: 11px;
            color: #666;
            line-height: 1.8;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table thead {
            background-color: #0066cc;
            color: #fff;
        }
        
        .items-table th {
            padding: 12px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .items-table th.text-right {
            text-align: right;
        }
        
        .items-table th.text-center {
            text-align: center;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
        }
        
        .items-table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .items-table td {
            padding: 10px 12px;
            font-size: 11px;
        }
        
        .items-table tfoot {
            background-color: #f5f5f5;
        }
        
        .items-table tfoot td {
            padding: 12px;
            font-weight: bold;
            border-top: 2px solid #333;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .total-row {
            background-color: #0066cc !important;
            color: #fff !important;
            font-size: 14px;
        }
        
        .total-row td {
            color: #fff !important;
        }
        
        .notes-section {
            margin-top: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .notes-section h3 {
            font-size: 12px;
            margin-bottom: 10px;
            color: #333;
            text-transform: uppercase;
        }
        
        .notes-section p {
            font-size: 11px;
            color: #666;
            line-height: 1.8;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .invoice-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <div class="company-name"><?= htmlspecialchars($company['name'] ?? 'Company Name') ?></div>
                <div class="company-details">
                    <?php if (!empty($company['address'])): ?>
                        <?= htmlspecialchars($company['address']) ?><br>
                    <?php endif; ?>
                    <?php 
                    $addressParts = array_filter([
                        $company['city'] ?? '',
                        $company['state'] ?? '',
                        $company['zip_code'] ?? '',
                        $company['country'] ?? ''
                    ]);
                    if (!empty($addressParts)): 
                    ?>
                        <?= htmlspecialchars(implode(', ', $addressParts)) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['phone'])): ?>
                        Phone: <?= htmlspecialchars($company['phone']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['email'])): ?>
                        Email: <?= htmlspecialchars($company['email']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['tax_id'])): ?>
                        Tax ID: <?= htmlspecialchars($company['tax_id']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-meta">
                    <div><strong>Invoice #:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
                    <div><strong>Date:</strong> <?= format_date($invoice['invoice_date']) ?></div>
                    <div><strong>Due Date:</strong> <?= format_date($invoice['due_date']) ?></div>
                    <?php if (!empty($invoice['reference'])): ?>
                        <div><strong>Reference:</strong> <?= htmlspecialchars($invoice['reference']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Billing Information -->
        <div class="billing-section">
            <div class="bill-to">
                <h3>Bill To:</h3>
                <p>
                    <strong><?= htmlspecialchars($customer['company_name']) ?></strong><br>
                    <?php if (!empty($customer['address'])): ?>
                        <?= htmlspecialchars($customer['address']) ?><br>
                    <?php endif; ?>
                    <?php 
                    $customerAddressParts = array_filter([
                        $customer['city'] ?? '',
                        $customer['state'] ?? '',
                        $customer['zip_code'] ?? '',
                        $customer['country'] ?? ''
                    ]);
                    if (!empty($customerAddressParts)): 
                    ?>
                        <?= htmlspecialchars(implode(', ', $customerAddressParts)) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($customer['email'])): ?>
                        Email: <?= htmlspecialchars($customer['email']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($customer['phone'])): ?>
                        Phone: <?= htmlspecialchars($customer['phone']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="bill-from">
                <h3>From:</h3>
                <p>
                    <strong><?= htmlspecialchars($company['name'] ?? 'Company Name') ?></strong><br>
                    <?php if (!empty($company['address'])): ?>
                        <?= htmlspecialchars($company['address']) ?><br>
                    <?php endif; ?>
                    <?php 
                    $companyAddressParts = array_filter([
                        $company['city'] ?? '',
                        $company['state'] ?? '',
                        $company['zip_code'] ?? '',
                        $company['country'] ?? ''
                    ]);
                    if (!empty($companyAddressParts)): 
                    ?>
                        <?= htmlspecialchars(implode(', ', $companyAddressParts)) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['phone'])): ?>
                        Phone: <?= htmlspecialchars($company['phone']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($company['email'])): ?>
                        Email: <?= htmlspecialchars($company['email']) ?>
                    <?php endif; ?>
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
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['item_description']) ?></td>
                            <td class="text-center"><?= number_format($item['quantity'], 2) ?></td>
                            <td class="text-right"><?= format_currency($item['unit_price'], $invoice['currency']) ?></td>
                            <td class="text-right"><?= format_currency($item['line_total'], $invoice['currency']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px; color: #999;">No items found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                    <td class="text-right"><strong><?= format_currency($invoice['subtotal'], $invoice['currency']) ?></strong></td>
                </tr>
                <?php if ($invoice['tax_amount'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-right">Tax (<?= number_format($invoice['tax_rate'], 2) ?>%):</td>
                    <td class="text-right"><?= format_currency($invoice['tax_amount'], $invoice['currency']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($invoice['discount_amount'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-right">Discount:</td>
                    <td class="text-right"><?= format_currency($invoice['discount_amount'], $invoice['currency']) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>TOTAL AMOUNT:</strong></td>
                    <td class="text-right"><strong><?= format_currency($invoice['total_amount'], $invoice['currency']) ?></strong></td>
                </tr>
                <?php if ($invoice['paid_amount'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-right">Paid:</td>
                    <td class="text-right"><?= format_currency($invoice['paid_amount'], $invoice['currency']) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right"><strong>Balance Due:</strong></td>
                    <td class="text-right"><strong><?= format_currency($invoice['balance_amount'], $invoice['currency']) ?></strong></td>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>
        
        <!-- Notes and Terms -->
        <?php if (!empty($invoice['notes']) || !empty($invoice['terms'])): ?>
        <div class="notes-section">
            <?php if (!empty($invoice['notes'])): ?>
                <h3>Notes:</h3>
                <p><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($invoice['terms'])): ?>
                <h3 style="margin-top: 15px;">Payment Terms:</h3>
                <p><?= nl2br(htmlspecialchars($invoice['terms'])) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice.</p>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads (optional - can be removed if not desired)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>

