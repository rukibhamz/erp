<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Tenant</h1>
        <a href="<?= base_url('tenants') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
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
        <a class="nav-link active" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link" href="<?= base_url('rent-invoices') ?>">
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

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('tenants/create') ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="tenant_code" class="form-label">Tenant Code</label>
                    <input type="text" class="form-control" id="tenant_code" name="tenant_code">
                    <small class="text-muted">Leave blank to auto-generate</small>
                </div>
                
                <div class="col-md-6">
                    <label for="tenant_type" class="form-label">Tenant Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="tenant_type" name="tenant_type" required>
                        <option value="commercial">Commercial</option>
                        <option value="residential">Residential</option>
                        <option value="short_term">Short Term</option>
                        <option value="owner_operated">Owner Operated</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="business_name" class="form-label">Business Name</label>
                    <input type="text" class="form-control" id="business_name" name="business_name">
                </div>
                
                <div class="col-md-6">
                    <label for="business_registration" class="form-label">Business Registration</label>
                    <input type="text" class="form-control" id="business_registration" name="business_registration" placeholder="CAC/RC Number">
                </div>
                
                <div class="col-md-6">
                    <label for="business_type" class="form-label">Business Type</label>
                    <input type="text" class="form-control" id="business_type" name="business_type" placeholder="e.g., Retail, Restaurant, Office">
                </div>
                
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>
                
                <div class="col-md-6">
                    <label for="alternate_phone" class="form-label">Alternate Phone</label>
                    <input type="text" class="form-control" id="alternate_phone" name="alternate_phone">
                </div>
                
                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                </div>
                
                <div class="col-md-4">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city">
                </div>
                
                <div class="col-md-4">
                    <label for="state" class="form-label">State</label>
                    <input type="text" class="form-control" id="state" name="state">
                </div>
                
                <div class="col-md-4">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country">
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('tenants') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Tenant
                </button>
            </div>
        </form>
    </div>
</div>

