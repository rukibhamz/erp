<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Supplier</h1>
        <a href="<?= base_url('inventory/suppliers/view/' . $supplier['id']) ?>" class="btn btn-outline-secondary">
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
        <form method="POST" action="<?= base_url('inventory/suppliers/edit/' . $supplier['id']) ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="supplier_code" class="form-label">Supplier Code</label>
                    <input type="text" class="form-control" id="supplier_code" name="supplier_code" 
                           value="<?= htmlspecialchars($supplier['supplier_code'] ?? '') ?>" readonly>
                    <small class="text-muted">Code cannot be changed</small>
                </div>
                <div class="col-md-6">
                    <label for="supplier_name" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" 
                           value="<?= htmlspecialchars($supplier['supplier_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" 
                           value="<?= htmlspecialchars($supplier['contact_person'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($supplier['email'] ?? '') ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="payment_terms" class="form-label">Payment Terms (Days)</label>
                    <input type="number" class="form-control" id="payment_terms" name="payment_terms" 
                           value="<?= $supplier['payment_terms'] ?? 30 ?>" min="0">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="lead_time_days" class="form-label">Lead Time (Days)</label>
                    <input type="number" class="form-control" id="lead_time_days" name="lead_time_days" 
                           value="<?= $supplier['lead_time_days'] ?? 0 ?>" min="0">
                    <small class="text-muted">Average delivery time in days</small>
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($supplier['address'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           <?= ($supplier['is_active'] ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('inventory/suppliers/view/' . $supplier['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Update Supplier
                </button>
            </div>
        </form>
    </div>
</div>

