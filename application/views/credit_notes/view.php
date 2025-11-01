<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Credit Note: <?= htmlspecialchars($credit_note['credit_note_number']) ?></h1>
        <div>
            <?php if ($credit_note['status'] === 'issued' && has_permission('receivables', 'update')): ?>
                <a href="<?= base_url('credit-notes/apply/' . $credit_note['id']) ?>" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Apply to Invoice
                </a>
            <?php endif; ?>
            <a href="<?= base_url('credit-notes') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Credit Note Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Credit Note Number:</strong><br>
                            <?= htmlspecialchars($credit_note['credit_note_number']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Credit Date:</strong><br>
                            <?= date('M d, Y', strtotime($credit_note['credit_date'])) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Customer:</strong><br>
                            <?= htmlspecialchars($customer['company_name'] ?? '-') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Related Invoice:</strong><br>
                            <?= $invoice ? htmlspecialchars($invoice['invoice_number']) : '-' ?>
                        </div>
                    </div>
                    <?php if ($credit_note['reference']): ?>
                        <div class="mb-3">
                            <strong>Reference:</strong><br>
                            <?= htmlspecialchars($credit_note['reference']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($credit_note['reason']): ?>
                        <div class="mb-3">
                            <strong>Reason:</strong><br>
                            <?= nl2br(htmlspecialchars($credit_note['reason'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_description']) ?></td>
                                        <td><?= number_format($item['quantity'], 2) ?></td>
                                        <td><?= format_currency($item['unit_price'], $credit_note['currency']) ?></td>
                                        <td class="text-end"><?= format_currency($item['line_total'], $credit_note['currency']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><strong><?= format_currency($credit_note['subtotal'], $credit_note['currency']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                    <td class="text-end"><strong><?= format_currency($credit_note['tax_amount'], $credit_note['currency']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong><?= format_currency($credit_note['total_amount'], $credit_note['currency']) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Status</h5>
                </div>
                <div class="card-body">
                    <?php
                    $statusColors = [
                        'draft' => 'secondary',
                        'issued' => 'info',
                        'applied' => 'success',
                        'void' => 'danger'
                    ];
                    $color = $statusColors[$credit_note['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $color ?> fs-6 mb-3"><?= ucfirst($credit_note['status']) ?></span>
                    
                    <div class="mt-3">
                        <strong>Amount:</strong><br>
                        <h4><?= format_currency($credit_note['total_amount'], $credit_note['currency']) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

