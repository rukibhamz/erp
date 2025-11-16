<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Rent Invoice: <?= htmlspecialchars($invoice['invoice_number']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('rent-invoices') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<!-- Property Management Navigation -->
<div class="property-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('properties') ?>">
            <i class="bi bi-building"></i> Properties
        </a>
        <a class="nav-link" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link active" href="<?= base_url('rent-invoices') ?>">
            <i class="bi bi-receipt"></i> Rent Invoices
        </a>
    </nav>
</div>

<style>
.property-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.property-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.property-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.property-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.property-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

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
                <h5 class="card-title mb-0">Invoice Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Invoice Number:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Period:</dt>
                    <dd class="col-sm-8">
                        <?= date('M d, Y', strtotime($invoice['period_start'])) ?> - 
                        <?= date('M d, Y', strtotime($invoice['period_end'])) ?>
                    </dd>
                    
                    <dt class="col-sm-4">Tenant:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($lease['business_name'] ?? $lease['contact_person']) ?></dd>
                    
                    <dt class="col-sm-4">Space:</dt>
                    <dd class="col-sm-8">
                        <?= htmlspecialchars($lease['space_name']) ?> - 
                        <?= htmlspecialchars($lease['property_name']) ?>
                    </dd>
                    
                    <dt class="col-sm-4">Rent Amount:</dt>
                    <dd class="col-sm-8"><?= format_currency($invoice['rent_amount']) ?></dd>
                    
                    <?php if ($invoice['service_charge'] > 0): ?>
                        <dt class="col-sm-4">Service Charge:</dt>
                        <dd class="col-sm-8"><?= format_currency($invoice['service_charge']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($invoice['utility_charge'] > 0): ?>
                        <dt class="col-sm-4">Utility Charge:</dt>
                        <dd class="col-sm-8"><?= format_currency($invoice['utility_charge']) ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Total Amount:</dt>
                    <dd class="col-sm-8"><strong><?= format_currency($invoice['total_amount']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Paid Amount:</dt>
                    <dd class="col-sm-8"><?= format_currency($invoice['paid_amount']) ?></dd>
                    
                    <dt class="col-sm-4">Balance:</dt>
                    <dd class="col-sm-8">
                        <strong class="<?= floatval($invoice['balance_amount']) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($invoice['balance_amount']) ?>
                        </strong>
                    </dd>
                    
                    <dt class="col-sm-4">Due Date:</dt>
                    <dd class="col-sm-8">
                        <?= date('M d, Y', strtotime($invoice['due_date'])) ?>
                        <?php if ($invoice['due_date'] < date('Y-m-d') && $invoice['balance_amount'] > 0): ?>
                            <span class="badge bg-danger ms-2">Overdue</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= 
                            $invoice['status'] === 'paid' ? 'success' : 
                            ($invoice['status'] === 'overdue' ? 'danger' : 
                            ($invoice['status'] === 'partial' ? 'warning' : 'secondary')) 
                        ?>">
                            <?= ucfirst($invoice['status']) ?>
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
                    <p class="text-muted mb-0">No payments received for this invoice.</p>
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
                
                <?php if ($invoice['balance_amount'] > 0): ?>
                    <hr>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                        <i class="bi bi-plus-circle"></i> Record Payment
                    </button>
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
                <?php if ($invoice['balance_amount'] > 0): ?>
                    <button type="button" class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                        <i class="bi bi-cash"></i> Record Payment
                    </button>
                <?php endif; ?>
                
                <a href="<?= base_url('leases/view/' . $invoice['lease_id']) ?>" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-file-earmark-text"></i> View Lease
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('rent-invoices/record-payment') ?>
            <?php echo csrf_field(); ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                               value="<?= $invoice['balance_amount'] ?>" max="<?= $invoice['balance_amount'] ?>" required>
                        <small class="text-muted">Balance: <?= format_currency($invoice['balance_amount']) ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number" 
                               placeholder="Transaction/Cheque number">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

