<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Lease</h1>
        <a href="<?= base_url('leases/view/' . $lease['id']) ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<!-- Location Management Navigation -->
<div class="Location-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('locations') ?>">
            <i class="bi bi-building"></i> Locations
        </a>
        <a class="nav-link" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link active" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link" href="<?= base_url('rent-invoices') ?>">
            <i class="bi bi-receipt"></i> Rent Invoices
        </a>
    </nav>
</div>

<style>
.Location-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.Location-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.Location-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.Location-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.Location-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="bi bi-file-earmark-plus"></i> Lease Information: <?= htmlspecialchars($lease['lease_number']) ?></h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('leases/edit/' . $lease['id']) ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="space_id" class="form-label">Space</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($lease['space_name'] . ' - ' . $lease['property_name']) ?>" readonly disabled>
                    <small class="text-muted">Space cannot be changed after lease creation</small>
                </div>
                
                <div class="col-md-6">
                    <label for="tenant_id" class="form-label">Tenant</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($lease['business_name'] ?: ($lease['tenant_name'] ?? 'N/A')) ?>" readonly disabled>
                    <small class="text-muted">Tenant cannot be changed after lease creation</small>
                </div>
                
                <div class="col-md-6">
                    <label for="lease_number" class="form-label">Lease Number</label>
                    <input type="text" class="form-control" id="lease_number" value="<?= htmlspecialchars($lease['lease_number']) ?>" readonly disabled>
                </div>
                
                <div class="col-md-6">
                    <label for="lease_type" class="form-label">Lease Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="lease_type" name="lease_type" required>
                        <option value="commercial" <?= $lease['lease_type'] === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                        <option value="residential" <?= $lease['lease_type'] === 'residential' ? 'selected' : '' ?>>Residential</option>
                        <option value="mixed" <?= $lease['lease_type'] === 'mixed' ? 'selected' : '' ?>>Mixed</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="lease_term" class="form-label">Lease Term <span class="text-danger">*</span></label>
                    <select class="form-select" id="lease_term" name="lease_term" required>
                        <option value="fixed_term" <?= $lease['lease_term'] === 'fixed_term' ? 'selected' : '' ?>>Fixed Term</option>
                        <option value="periodic" <?= $lease['lease_term'] === 'periodic' ? 'selected' : '' ?>>Periodic</option>
                        <option value="month_to_month" <?= $lease['lease_term'] === 'month_to_month' ? 'selected' : '' ?>>Month-to-Month</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $lease['start_date'] ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $lease['end_date'] ?>">
                    <small class="text-muted">Leave blank for periodic/month-to-month</small>
                </div>
                
                <div class="col-md-6">
                    <label for="rent_amount" class="form-label">Rent Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" id="rent_amount" name="rent_amount" value="<?= $lease['rent_amount'] ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="payment_frequency" class="form-label">Payment Frequency</label>
                    <select class="form-select" id="payment_frequency" name="payment_frequency">
                        <option value="monthly" <?= $lease['payment_frequency'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="weekly" <?= $lease['payment_frequency'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                        <option value="quarterly" <?= $lease['payment_frequency'] === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                        <option value="annually" <?= $lease['payment_frequency'] === 'annually' ? 'selected' : '' ?>>Annually</option>
                        <option value="daily" <?= $lease['payment_frequency'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="rent_due_date" class="form-label">Rent Due Date (Day of Month)</label>
                    <input type="number" min="1" max="31" class="form-control" id="rent_due_date" name="rent_due_date" value="<?= $lease['rent_due_date'] ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="security_deposit" class="form-label">Security Deposit</label>
                    <input type="number" step="0.01" class="form-control" id="security_deposit" name="security_deposit" value="<?= $lease['security_deposit'] ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="service_charge" class="form-label">Service Charge</label>
                    <input type="number" step="0.01" class="form-control" id="service_charge" name="service_charge" value="<?= $lease['service_charge'] ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="rent_escalation_rate" class="form-label">Annual Escalation Rate (%)</label>
                    <input type="number" step="0.01" class="form-control" id="rent_escalation_rate" name="rent_escalation_rate" value="<?= $lease['rent_escalation_rate'] ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= $lease['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="expired" <?= $lease['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                        <option value="terminated" <?= $lease['status'] === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                        <option value="draft" <?= $lease['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('leases/view/' . $lease['id']) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
