<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Bill: <?= htmlspecialchars($bill['bill_number'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (hasPermission('payables', 'update')): ?>
                <a href="<?= base_url('payables/bills/edit/' . $bill['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('payables/bills') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Bill Details</h5>
                <div>
                    <span class="badge bg-<?= $bill['status'] === 'paid' ? 'success' : ($bill['status'] === 'overdue' ? 'danger' : 'info') ?>">
                        <?= ucfirst(str_replace('_', ' ', $bill['status'])) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Bill Number:</strong> <?= htmlspecialchars($bill['bill_number']) ?><br>
                        <strong>Vendor:</strong> <?= htmlspecialchars($vendor['company_name'] ?? '-') ?><br>
                        <strong>Bill Date:</strong> <?= format_date($bill['bill_date']) ?><br>
                        <strong>Due Date:</strong> <?= format_date($bill['due_date']) ?>
                        <?php if (!empty($bill['reference'])): ?>
                            <br><strong>Reference:</strong> <?= htmlspecialchars($bill['reference']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Total Amount:</strong> <span class="fs-5"><?= format_currency($bill['total_amount'], $bill['currency']) ?></span><br>
                        <strong>Paid:</strong> <?= format_currency($bill['paid_amount'] ?? 0, $bill['currency']) ?><br>
                        <strong>Balance:</strong> <span class="fs-5 <?= ($bill['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($bill['balance_amount'], $bill['currency']) ?>
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
                                    <td class="text-end"><?= format_currency($item['unit_price'], $bill['currency']) ?></td>
                                    <td class="text-end"><?= format_currency($item['line_total'], $bill['currency']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end fw-bold"><?= format_currency($bill['subtotal'], $bill['currency']) ?></td>
                            </tr>
                            <?php if ($bill['tax_amount'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end">Tax (<?= $bill['tax_rate'] ?>%):</td>
                                <td class="text-end"><?= format_currency($bill['tax_amount'], $bill['currency']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($bill['discount_amount'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-end">Discount:</td>
                                <td class="text-end"><?= format_currency($bill['discount_amount'], $bill['currency']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="3" class="text-end fw-bold fs-5">Total:</td>
                                <td class="text-end fw-bold fs-5"><?= format_currency($bill['total_amount'], $bill['currency']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($payments)): ?>
                <div class="mb-3">
                    <h6>Payments</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Payment Number</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= format_date($payment['payment_date']) ?></td>
                                        <td><?= htmlspecialchars($payment['payment_number']) ?></td>
                                        <td class="text-end"><?= format_currency($payment['allocated_amount'] ?? $payment['amount'], $bill['currency']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($bill['notes'])): ?>
                <div class="mb-3">
                    <strong>Notes:</strong>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($bill['notes'])) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($bill['terms'])): ?>
                <div class="mb-3">
                    <strong>Payment Terms:</strong>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($bill['terms'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

