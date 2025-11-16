<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Supplier</h1>
        <a href="<?= base_url('inventory/suppliers') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('inventory/suppliers/create') ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="supplier_code" class="form-label">Supplier Code</label>
                    <input type="text" class="form-control" id="supplier_code" name="supplier_code" placeholder="Leave blank for auto-generation">
                    <small class="text-muted">Auto-generated if left blank</small>
                </div>
                
                <div class="col-md-6">
                    <label for="supplier_name" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                </div>
                
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person">
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                
                <div class="col-md-6">
                    <label for="payment_terms" class="form-label">Payment Terms (Days)</label>
                    <input type="number" class="form-control" id="payment_terms" name="payment_terms" value="30" min="1">
                </div>
                
                <div class="col-md-6">
                    <label for="lead_time_days" class="form-label">Lead Time (Days)</label>
                    <input type="number" class="form-control" id="lead_time_days" name="lead_time_days" value="0" min="0">
                </div>
                
                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/suppliers') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Supplier
                </button>
            </div>
        </form>
    </div>
</div>

