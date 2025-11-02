<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Vendor Bill: <?= htmlspecialchars($bill['vendor_bill_number']) ?></h1>
        <div class="d-flex gap-2">
            <?php if ($bill['status'] === 'pending' || $bill['status'] === 'verified'): ?>
                <a href="<?= base_url('utilities/vendor-bills/approve/' . $bill['id']) ?>" 
                   class="btn btn-success"
                   onclick="return confirm('Approve this vendor bill and post to accounting?')">
                    <i class="bi bi-check-circle"></i> Approve
                </a>
            <?php endif; ?>
            <a href="<?= base_url('utilities/vendor-bills') ?>" class="btn btn-outline-secondary">
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
                    <dt class="col-sm-4">Vendor Bill Number:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($bill['vendor_bill_number']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Provider:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($bill['provider_name'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Utility Type:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($bill['utility_type_name'] ?? '-') ?></dd>
                    
                    <dt class="col-sm-4">Bill Date:</dt>
                    <dd class="col-sm-8"><?= date('M d, Y', strtotime($bill['bill_date'])) ?></dd>
                    
                    <dt class="col-sm-4">Due Date:</dt>
                    <dd class="col-sm-8">
                        <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                        <?php if (strtotime($bill['due_date']) < time() && $bill['status'] !== 'paid'): ?>
                            <span class="badge bg-danger ms-2">Overdue</span>
                        <?php endif; ?>
                    </dd>
                    
                    <?php if ($bill['period_start'] && $bill['period_end']): ?>
                        <dt class="col-sm-4">Billing Period:</dt>
                        <dd class="col-sm-8">
                            <?= date('M d, Y', strtotime($bill['period_start'])) ?> - 
                            <?= date('M d, Y', strtotime($bill['period_end'])) ?>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if ($bill['consumption']): ?>
                        <dt class="col-sm-4">Consumption:</dt>
                        <dd class="col-sm-8"><?= number_format($bill['consumption'], 2) ?> units</dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Amount (Before Tax):</dt>
                    <dd class="col-sm-8"><?= format_currency($bill['amount']) ?></dd>
                    
                    <dt class="col-sm-4">Tax Amount:</dt>
                    <dd class="col-sm-8"><?= format_currency($bill['tax_amount']) ?></dd>
                    
                    <dt class="col-sm-4">Total Amount:</dt>
                    <dd class="col-sm-8"><strong><?= format_currency($bill['total_amount']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Balance Amount:</dt>
                    <dd class="col-sm-8">
                        <strong class="<?= floatval($bill['balance_amount']) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($bill['balance_amount']) ?>
                        </strong>
                    </dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= 
                            $bill['status'] === 'paid' ? 'success' : 
                            ($bill['status'] === 'approved' ? 'info' : 
                            ($bill['status'] === 'verified' ? 'primary' : 'warning')) ?>">
                            <?= ucfirst($bill['status']) ?>
                        </span>
                    </dd>
                    
                    <?php if ($bill['notes']): ?>
                        <dt class="col-sm-4">Notes:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($bill['notes'])) ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($bill['approved_by']): ?>
                        <dt class="col-sm-4">Approved By:</dt>
                        <dd class="col-sm-8">User ID: <?= $bill['approved_by'] ?></dd>
                        
                        <dt class="col-sm-4">Approved At:</dt>
                        <dd class="col-sm-8"><?= $bill['approved_at'] ? date('M d, Y H:i', strtotime($bill['approved_at'])) : '-' ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <?php if ($bill['status'] === 'pending' || $bill['status'] === 'verified'): ?>
                    <a href="<?= base_url('utilities/vendor-bills/approve/' . $bill['id']) ?>" 
                       class="btn btn-success w-100 mb-2"
                       onclick="return confirm('Approve this vendor bill and post to accounting?')">
                        <i class="bi bi-check-circle"></i> Approve & Post to Accounting
                    </a>
                <?php endif; ?>
                
                <a href="<?= base_url('utilities/vendor-bills') ?>" class="btn btn-outline-dark w-100 mb-2">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

