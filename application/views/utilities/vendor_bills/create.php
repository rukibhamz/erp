<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Vendor Bill</h1>
        <a href="<?= base_url('utilities/vendor-bills') ?>" class="btn btn-outline-secondary">
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

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('utilities/vendor-bills/create') ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="vendor_bill_number" class="form-label">Vendor Bill Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="vendor_bill_number" name="vendor_bill_number" required placeholder="Enter vendor's bill number">
                </div>
                
                <div class="col-md-6">
                    <label for="provider_id" class="form-label">Provider <span class="text-danger">*</span></label>
                    <select class="form-select" id="provider_id" name="provider_id" required>
                        <option value="">Select Provider</option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?= $provider['id'] ?>">
                                <?= htmlspecialchars($provider['provider_name']) ?> - <?= htmlspecialchars($provider['utility_type_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="bill_date" class="form-label">Bill Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="bill_date" name="bill_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="period_start" class="form-label">Period Start</label>
                    <input type="date" class="form-control" id="period_start" name="period_start">
                </div>
                
                <div class="col-md-6">
                    <label for="period_end" class="form-label">Period End</label>
                    <input type="date" class="form-control" id="period_end" name="period_end">
                </div>
                
                <div class="col-md-6">
                    <label for="consumption" class="form-label">Consumption</label>
                    <input type="number" step="0.01" class="form-control" id="consumption" name="consumption" placeholder="Total consumption">
                </div>
                
                <div class="col-md-6">
                    <label for="amount" class="form-label">Amount (Before Tax) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" required oninput="calculateTotal()">
                </div>
                
                <div class="col-md-6">
                    <label for="tax_amount" class="form-label">Tax Amount</label>
                    <input type="number" step="0.01" class="form-control" id="tax_amount" name="tax_amount" value="0" oninput="calculateTotal()">
                </div>
                
                <div class="col-md-6">
                    <label for="total_amount" class="form-label">Total Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" required readonly>
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional notes about this vendor bill"></textarea>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('utilities/vendor-bills') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Vendor Bill
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function calculateTotal() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const taxAmount = parseFloat(document.getElementById('tax_amount').value) || 0;
    const total = amount + taxAmount;
    document.getElementById('total_amount').value = total.toFixed(2);
}
</script>

