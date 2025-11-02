<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Utility Bill: <?= htmlspecialchars($bill['bill_number']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('utilities/bills') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bill Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Bill Number:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($bill['bill_number']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Billing Period:</dt>
                    <dd class="col-sm-8">
                        <?= date('M d, Y', strtotime($bill['billing_period_start'])) ?> - 
                        <?= date('M d, Y', strtotime($bill['billing_period_end'])) ?>
                    </dd>
                    
                    <dt class="col-sm-4">Meter:</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($bill['meter_id'])): ?>
                            <a href="<?= base_url('utilities/meters/view/' . $bill['meter_id']) ?>">
                                <?= htmlspecialchars($bill['meter_number'] ?? 'Meter #' . $bill['meter_id']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Utility Type:</dt>
                    <dd class="col-sm-8">
                        <?= htmlspecialchars($bill['utility_type_name'] ?? 'N/A') ?>
                        <?php if (!empty($bill['unit_of_measure'])): ?>
                            <small class="text-muted">(<?= htmlspecialchars($bill['unit_of_measure']) ?>)</small>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Consumption:</dt>
                    <dd class="col-sm-8">
                        <strong><?= number_format($bill['consumption'] ?? 0, 2) ?></strong>
                        <small class="text-muted"><?= htmlspecialchars($bill['consumption_unit'] ?? 'units') ?></small>
                    </dd>
                    
                    <dt class="col-sm-4">Fixed Charge:</dt>
                    <dd class="col-sm-8"><?= format_currency($bill['fixed_charge'] ?? 0) ?></dd>
                    
                    <dt class="col-sm-4">Variable Charge:</dt>
                    <dd class="col-sm-8"><?= format_currency($bill['variable_charge'] ?? 0) ?></dd>
                    
                    <?php if (($bill['demand_charge'] ?? 0) > 0): ?>
                        <dt class="col-sm-4">Demand Charge:</dt>
                        <dd class="col-sm-8"><?= format_currency($bill['demand_charge']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (($bill['tax_rate'] ?? 0) > 0): ?>
                        <dt class="col-sm-4">Tax (<?= $bill['tax_rate'] ?>%):</dt>
                        <dd class="col-sm-8"><?= format_currency($bill['tax_amount'] ?? 0) ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Total Amount:</dt>
                    <dd class="col-sm-8"><strong><?= format_currency($bill['total_amount']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Paid Amount:</dt>
                    <dd class="col-sm-8"><?= format_currency($bill['paid_amount']) ?></dd>
                    
                    <dt class="col-sm-4">Balance:</dt>
                    <dd class="col-sm-8">
                        <strong class="<?= floatval($bill['balance_amount']) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($bill['balance_amount']) ?>
                        </strong>
                    </dd>
                    
                    <dt class="col-sm-4">Due Date:</dt>
                    <dd class="col-sm-8">
                        <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                        <?php if ($bill['due_date'] < date('Y-m-d') && $bill['balance_amount'] > 0): ?>
                            <span class="badge bg-danger ms-2">Overdue</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= 
                            $bill['status'] === 'paid' ? 'success' : 
                            ($bill['status'] === 'overdue' ? 'danger' : 
                            ($bill['status'] === 'partial' ? 'warning' : 'secondary')) 
                        ?>">
                            <?= ucfirst($bill['status']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Bill Type:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $bill['bill_type'] === 'actual' ? 'success' : 'warning' ?>">
                            <?= ucfirst($bill['bill_type']) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Payments Section -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Payments Received</h5>
            </div>
            <div class="card-body">
                <?php if (empty($payments)): ?>
                    <p class="text-muted mb-0">No payments received for this bill.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Payment #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['payment_number']) ?></td>
                                        <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                        <td><?= format_currency($payment['amount']) ?></td>
                                        <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                                        <td><?= htmlspecialchars($payment['reference_number'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <?php if (($bill['balance_amount'] ?? 0) > 0): ?>
                    <a href="<?= base_url('utilities/payments/record/' . $bill['id']) ?>" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-cash"></i> Record Payment
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($bill['meter_id'])): ?>
                    <a href="<?= base_url('utilities/meters/view/' . $bill['meter_id']) ?>" class="btn btn-outline-dark w-100 mb-2">
                        <i class="bi bi-speedometer2"></i> View Meter
                    </a>
                <?php endif; ?>
                
                <?php if ($bill['pdf_url']): ?>
                    <a href="<?= base_url($bill['pdf_url']) ?>" target="_blank" class="btn btn-outline-dark w-100 mb-2">
                        <i class="bi bi-file-pdf"></i> Download PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

