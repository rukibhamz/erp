<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Estimate: <?= htmlspecialchars($estimate['estimate_number']) ?></h1>
        <div>
            <?php if ($estimate['status'] === 'accepted' && has_permission('estimates', 'create')): ?>
                <a href="<?= base_url('estimates/convert/' . $estimate['id']) ?>" class="btn btn-success">
                    <i class="bi bi-arrow-right-circle"></i> Convert to Invoice
                </a>
            <?php endif; ?>
            <a href="<?= base_url('estimates') ?>" class="btn btn-secondary">
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
                    <h5 class="mb-0">Estimate Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Estimate Number:</strong><br>
                            <?= htmlspecialchars($estimate['estimate_number']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Estimate Date:</strong><br>
                            <?= date('M d, Y', strtotime($estimate['estimate_date'])) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Customer:</strong><br>
                            <?= htmlspecialchars($customer['company_name'] ?? '-') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Expiry Date:</strong><br>
                            <?= $estimate['expiry_date'] ? date('M d, Y', strtotime($estimate['expiry_date'])) : '-' ?>
                        </div>
                    </div>
                    <?php if ($estimate['reference']): ?>
                        <div class="mb-3">
                            <strong>Reference:</strong><br>
                            <?= htmlspecialchars($estimate['reference']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($estimate['notes']): ?>
                        <div class="mb-3">
                            <strong>Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($estimate['notes'])) ?>
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
                                    <th>Tax</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item_description']) ?></td>
                                        <td><?= number_format($item['quantity'], 2) ?></td>
                                        <td><?= format_currency($item['unit_price'], $estimate['currency']) ?></td>
                                        <td><?= number_format($item['tax_rate'], 2) ?>%</td>
                                        <td class="text-end"><?= format_currency($item['line_total'], $estimate['currency']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><strong><?= format_currency($estimate['subtotal'], $estimate['currency']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                                    <td class="text-end"><strong><?= format_currency($estimate['tax_amount'], $estimate['currency']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong><?= format_currency($estimate['total_amount'], $estimate['currency']) ?></strong></td>
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
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'converted' => 'primary'
                    ];
                    $color = $statusColors[$estimate['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $color ?> fs-6 mb-3"><?= ucfirst($estimate['status']) ?></span>
                    
                    <div class="mt-3">
                        <strong>Amount:</strong><br>
                        <h4><?= format_currency($estimate['total_amount'], $estimate['currency']) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

