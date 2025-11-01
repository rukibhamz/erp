<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Invoice: <?= htmlspecialchars($invoice['invoice_number'] ?? 'N/A') ?></h1>
</div>

<!-- Accounting Navigation -->
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('accounting') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="<?= base_url('accounts') ?>">
            <i class="bi bi-diagram-3"></i> Chart of Accounts
        </a>
        <a class="nav-link" href="<?= base_url('cash') ?>">
            <i class="bi bi-wallet2"></i> Cash Management
        </a>
        <a class="nav-link active" href="<?= base_url('receivables') ?>">
            <i class="bi bi-receipt"></i> Receivables
        </a>
        <a class="nav-link" href="<?= base_url('payables') ?>">
            <i class="bi bi-file-earmark-medical"></i> Payables
        </a>
        <a class="nav-link" href="<?= base_url('ledger') ?>">
            <i class="bi bi-journal-text"></i> General Ledger
        </a>
    </nav>
</div>

<style>
.accounting-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.accounting-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.accounting-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.accounting-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.accounting-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

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

