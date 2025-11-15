<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Invoice: <?= htmlspecialchars($invoice['invoice_number'] ?? 'N/A') ?></h1>
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
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Total Amount:</strong> <span class="fs-5"><?= format_currency($invoice['total_amount'], $invoice['currency']) ?></span><br>
                        <strong>Paid:</strong> <?= format_currency($invoice['paid_amount'] ?? 0, $invoice['currency']) ?><br>
                        <strong>Balance:</strong> <span class="fs-5 <?= ($invoice['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($invoice['balance_amount'], $invoice['currency']) ?>
                        </span>
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
                                    <td><?= htmlspecialchars($item['item_description']) ?></td>
                                    <td class="text-end"><?= number_format($item['quantity'], 2) ?></td>
                                    <td class="text-end"><?= format_currency($item['unit_price'], $invoice['currency']) ?></td>
                                    <td class="text-end"><?= format_currency($item['line_total'], $invoice['currency']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end fw-bold"><?= format_currency($invoice['subtotal'], $invoice['currency']) ?></td>
                            </tr>
                            <?php if ($invoice['tax_amount'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end">Tax (<?= $invoice['tax_rate'] ?>%):</td>
                                <td class="text-end"><?= format_currency($invoice['tax_amount'], $invoice['currency']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($invoice['discount_amount'] > 0): ?>
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
                <?php endif; ?>
                
                <?php if (!empty($invoice['notes'])): ?>
                <div class="mb-3">
                    <strong>Notes:</strong>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <a href="<?= base_url('receivables/invoices/pdf/' . $invoice['id']) ?>" target="_blank" class="btn btn-primary">
                        <i class="bi bi-file-pdf"></i> View/Download PDF
                    </a>
                    <?php if (($invoice['balance_amount'] ?? 0) > 0): ?>
                        <a href="<?= base_url('receivables/invoices/payment/' . $invoice['id']) ?>" class="btn btn-success">
                            <i class="bi bi-cash-coin"></i> Record Payment
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('receivables/invoices') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Invoices
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

