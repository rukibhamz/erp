<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Invoice: <?= htmlspecialchars($invoice['invoice_number'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if ($pdf_exists && $pdf_url): ?>
                <a href="<?= $pdf_url ?>" target="_blank" class="btn btn-primary">
                    <i class="bi bi-file-pdf"></i> View PDF
                </a>
            <?php endif; ?>
            <a href="<?= base_url('receivables/invoices/download/' . $invoice['id']) ?>" class="btn btn-primary">
                <i class="bi bi-download"></i> Download PDF
            </a>
            <?php if (hasPermission('receivables', 'update')): ?>
                <?php if (!empty($invoice['email'])): ?>
                    <a href="<?= base_url('receivables/invoices/send/' . $invoice['id']) ?>" 
                       class="btn btn-success" 
                       onclick="return confirm('Send invoice to <?= htmlspecialchars($invoice['email']) ?>?')">
                        <i class="bi bi-envelope"></i> Send Email
                    </a>
                <?php endif; ?>
                <?php if (($invoice['balance_amount'] ?? 0) > 0): ?>
                    <a href="<?= base_url('receivables/invoices/payment/' . $invoice['id']) ?>" class="btn btn-warning">
                        <i class="bi bi-wallet2"></i> Record Payment
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('receivables/invoices/edit/' . $invoice['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('receivables/invoices') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Invoice Details</h5>
                <div>
                    <span class="badge bg-<?= $invoice['status'] === 'paid' ? 'success' : ($invoice['status'] === 'overdue' ? 'danger' : 'info') ?>">
                        <?= ucfirst(str_replace('_', ' ', $invoice['status'])) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Invoice Number:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?><br>
                        <strong>Customer:</strong> <?= htmlspecialchars($invoice['company_name'] ?? '-') ?><br>
                        <strong>Invoice Date:</strong> <?= format_date($invoice['invoice_date']) ?><br>
                        <strong>Due Date:</strong> <?= format_date($invoice['due_date']) ?>
                        <?php if (!empty($invoice['reference'])): ?>
                            <br><strong>Reference:</strong> <?= htmlspecialchars($invoice['reference']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Total Amount:</strong> <span class="fs-5"><?= format_currency($invoice['total_amount'], $invoice['currency']) ?></span><br>
                        <strong>Paid:</strong> <?= format_currency($invoice['paid_amount'] ?? 0, $invoice['currency']) ?><br>
                        <strong>Balance:</strong> <span class="fs-5 <?= ($invoice['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($invoice['balance_amount'], $invoice['currency']) ?>
                        </span>
                        <?php if ($invoice['tax_rate'] > 0): ?>
                            <br><small class="text-muted">Tax Rate: <?= number_format($invoice['tax_rate'], 2) ?>%</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($items)): ?>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_description'] ?? '') ?></td>
                                    <td class="text-end"><?= number_format($item['quantity'] ?? 0, 2) ?></td>
                                    <td class="text-end"><?= format_currency($item['unit_price'] ?? 0, $invoice['currency']) ?></td>
                                    <td class="text-end"><?= format_currency($item['line_total'] ?? 0, $invoice['currency']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end fw-bold"><?= format_currency($invoice['subtotal'] ?? 0, $invoice['currency']) ?></td>
                            </tr>
                            <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end">Tax:</td>
                                <td class="text-end"><?= format_currency($invoice['tax_amount'], $invoice['currency']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end">Discount:</td>
                                <td class="text-end"><?= format_currency($invoice['discount_amount'], $invoice['currency']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="3" class="text-end fw-bold fs-5">Total:</td>
                                <td class="text-end fw-bold fs-5"><?= format_currency($invoice['total_amount'], $invoice['currency']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> No items found for this invoice. 
                    <a href="<?= base_url('receivables/invoices/edit/' . $invoice['id']) ?>" class="alert-link">Add items</a> to generate PDF.
                </div>
                <?php endif; ?>
                
                <?php if (!empty($invoice['notes'])): ?>
                <div class="mb-3">
                    <strong>Notes:</strong>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($invoice['terms'])): ?>
                <div class="mb-3">
                    <strong>Payment Terms:</strong>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($invoice['terms'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- PDF Viewer Sidebar -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">PDF Viewer</h5>
                <div class="btn-group btn-group-sm">
                    <?php if ($pdf_exists && $pdf_url): ?>
                        <a href="<?= $pdf_url ?>" target="_blank" class="btn btn-primary" title="Open in new tab">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <a href="<?= base_url('receivables/invoices/download/' . $invoice['id']) ?>" class="btn btn-primary" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0" style="min-height: 600px;">
                <?php if ($pdf_exists && $pdf_url): ?>
                    <iframe src="<?= $pdf_url ?>" style="width: 100%; height: 600px; border: none;" title="Invoice PDF" id="pdfViewer"></iframe>
                <?php else: ?>
                    <div class="p-4 text-center">
                        <i class="bi bi-file-pdf" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">PDF not yet generated</p>
                        <a href="<?= base_url('receivables/invoices/pdf/' . $invoice['id']) ?>" target="_blank" class="btn btn-primary" onclick="generatePdf(this)">
                            <i class="bi bi-file-earmark-pdf"></i> Generate PDF
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function generatePdf(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating...';
    
    // After PDF is generated, reload the page to show it
    setTimeout(function() {
        window.location.reload();
    }, 2000);
}
</script>
