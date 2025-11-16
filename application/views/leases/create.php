<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Lease</h1>
        <a href="<?= base_url('leases') ?>" class="btn btn-outline-secondary">
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
        <a class="nav-link" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link active" href="<?= base_url('leases') ?>">
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
        <form action="<?= base_url('leases/create') ?>" >
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="space_id" class="form-label">Space <span class="text-danger">*</span></label>
                    <select class="form-select" id="space_id" name="space_id" required>
                        <option value="">Select Space</option>
                        <?php foreach ($spaces as $space): ?>
                            <option value="<?= $space['id'] ?>" <?= ($preselected_space_id ?? null) == $space['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($space['space_name']) ?> - 
                                <?= htmlspecialchars($space['property_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="tenant_id" class="form-label">Tenant <span class="text-danger">*</span></label>
                    <select class="form-select" id="tenant_id" name="tenant_id" required>
                        <option value="">Select Tenant</option>
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?= $tenant['id'] ?>" <?= ($preselected_tenant_id ?? null) == $tenant['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tenant['business_name'] ?: $tenant['contact_person']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="lease_number" class="form-label">Lease Number</label>
                    <input type="text" class="form-control" id="lease_number" name="lease_number">
                    <small class="text-muted">Leave blank to auto-generate</small>
                </div>
                
                <div class="col-md-6">
                    <label for="lease_type" class="form-label">Lease Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="lease_type" name="lease_type" required>
                        <option value="commercial">Commercial</option>
                        <option value="residential">Residential</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="lease_term" class="form-label">Lease Term <span class="text-danger">*</span></label>
                    <select class="form-select" id="lease_term" name="lease_term" required>
                        <option value="fixed_term">Fixed Term</option>
                        <option value="periodic">Periodic</option>
                        <option value="month_to_month">Month-to-Month</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>
                
                <div class="col-md-6">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date">
                    <small class="text-muted">Leave blank for periodic/month-to-month</small>
                </div>
                
                <div class="col-md-6">
                    <label for="rent_amount" class="form-label">Rent Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="rent_amount" name="rent_amount" required>
                </div>
                
                <div class="col-md-6">
                    <label for="payment_frequency" class="form-label">Payment Frequency</label>
                    <select class="form-select" id="payment_frequency" name="payment_frequency">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="annually">Annually</option>
                        <option value="daily">Daily</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="rent_due_date" class="form-label">Rent Due Date (Day of Month)</label>
                    <input type="number" min="1" max="31" class="form-control" id="rent_due_date" name="rent_due_date" value="5">
                </div>
                
                <div class="col-md-6">
                    <label for="security_deposit" class="form-label">Security Deposit</label>
                    <input type="number" step="0.01" class="form-control" id="security_deposit" name="security_deposit" value="0">
                </div>
                
                <div class="col-md-6">
                    <label for="service_charge" class="form-label">Service Charge</label>
                    <input type="number" step="0.01" class="form-control" id="service_charge" name="service_charge" value="0">
                </div>
                
                <div class="col-md-6">
                    <label for="rent_escalation_rate" class="form-label">Annual Escalation Rate (%)</label>
                    <input type="number" step="0.01" class="form-control" id="rent_escalation_rate" name="rent_escalation_rate" value="0">
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('leases') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Lease
                </button>
            </div>
        </form>
    </div>
</div>

