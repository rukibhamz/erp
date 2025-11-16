<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Record Payment</h1>
        <a href="<?= base_url('utilities/bills/view/' . $bill['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
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
                <h5 class="card-title mb-0">Payment Details</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('utilities/payments/record/' . $bill['id']) ?>
            <?php echo csrf_field(); ?>" method="POST">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>Bill Number:</strong> <?= htmlspecialchars($bill['bill_number']) ?><br>
                                <strong>Total Amount:</strong> <?= format_currency($bill['total_amount']) ?><br>
                                <strong>Paid Amount:</strong> <?= format_currency($bill['paid_amount']) ?><br>
                                <strong>Balance Amount:</strong> <span class="text-danger"><strong><?= format_currency($bill['balance_amount']) ?></strong></span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                   max="<?= $bill['balance_amount'] ?>" value="<?= $bill['balance_amount'] ?>" required>
                            <small class="text-muted">Maximum: <?= format_currency($bill['balance_amount']) ?></small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Transaction/Cheque number">
                        </div>
                        
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional notes about this payment"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="<?= base_url('utilities/bills/view/' . $bill['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bill Summary</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6">Bill #:</dt>
                    <dd class="col-6"><?= htmlspecialchars($bill['bill_number']) ?></dd>
                    
                    <dt class="col-6">Total:</dt>
                    <dd class="col-6"><strong><?= format_currency($bill['total_amount']) ?></strong></dd>
                    
                    <dt class="col-6">Paid:</dt>
                    <dd class="col-6"><?= format_currency($bill['paid_amount']) ?></dd>
                    
                    <dt class="col-6">Balance:</dt>
                    <dd class="col-6">
                        <strong class="text-danger"><?= format_currency($bill['balance_amount']) ?></strong>
                    </dd>
                </dl>
                
                <hr>
                
                <a href="<?= base_url('utilities/bills/view/' . $bill['id']) ?>" class="btn btn-outline-dark w-100">
                    <i class="bi bi-receipt"></i> View Bill
                </a>
            </div>
        </div>
    </div>
</div>

