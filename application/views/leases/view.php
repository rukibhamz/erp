<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Lease: <?= htmlspecialchars($lease['lease_number']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('rent-invoices/generate/' . $lease['id']) ?>" class="btn btn-success" onclick="return confirm('Generate rent invoice for this month?')">
                <i class="bi bi-receipt"></i> Generate Invoice
            </a>
            <a href="<?= base_url('leases') ?>" class="btn btn-outline-secondary">
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

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Lease Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Lease Number:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($lease['lease_number']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Property:</dt>
                    <dd class="col-sm-8">
                        <a href="<?= base_url('properties/view/' . $lease['property_id']) ?>">
                            <?= htmlspecialchars($lease['property_name']) ?>
                        </a>
                    </dd>
                    
                    <dt class="col-sm-4">Space:</dt>
                    <dd class="col-sm-8">
                        <a href="<?= base_url('spaces/view/' . $lease['space_id']) ?>">
                            <?= htmlspecialchars($lease['space_name']) ?>
                        </a>
                    </dd>
                    
                    <dt class="col-sm-4">Tenant:</dt>
                    <dd class="col-sm-8">
                        <?= htmlspecialchars($lease['business_name'] ?: $lease['contact_person']) ?>
                        <br>
                        <small class="text-muted"><?= htmlspecialchars($lease['email']) ?> | <?= htmlspecialchars($lease['phone']) ?></small>
                    </dd>
                    
                    <dt class="col-sm-4">Lease Type:</dt>
                    <dd class="col-sm-8"><?= ucfirst($lease['lease_type']) ?></dd>
                    
                    <dt class="col-sm-4">Lease Term:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', '-', $lease['lease_term'])) ?></dd>
                    
                    <dt class="col-sm-4">Start Date:</dt>
                    <dd class="col-sm-8"><?= date('M d, Y', strtotime($lease['start_date'])) ?></dd>
                    
                    <dt class="col-sm-4">End Date:</dt>
                    <dd class="col-sm-8">
                        <?= $lease['end_date'] ? date('M d, Y', strtotime($lease['end_date'])) : 'Ongoing' ?>
                        <?php if ($lease['end_date'] && strtotime($lease['end_date']) < strtotime('+90 days')): ?>
                            <span class="badge bg-warning ms-2">Expiring Soon</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Rent Amount:</dt>
                    <dd class="col-sm-8"><strong><?= format_currency($lease['rent_amount']) ?></strong> 
                        (<?= ucfirst($lease['payment_frequency']) ?>)</dd>
                    
                    <?php if ($lease['service_charge'] > 0): ?>
                        <dt class="col-sm-4">Service Charge:</dt>
                        <dd class="col-sm-8"><?= format_currency($lease['service_charge']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($lease['security_deposit'] > 0): ?>
                        <dt class="col-sm-4">Security Deposit:</dt>
                        <dd class="col-sm-8"><?= format_currency($lease['security_deposit']) ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Rent Due Date:</dt>
                    <dd class="col-sm-8">Day <?= $lease['rent_due_date'] ?> of each month</dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $lease['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($lease['status']) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Invoices Section -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Rent Invoices</h5>
            </div>
            <div class="card-body">
                <?php if (empty($invoices)): ?>
                    <p class="text-muted mb-3">No invoices generated yet.</p>
                    <a href="<?= base_url('rent-invoices/generate/' . $lease['id']) ?>" class="btn btn-primary">
                        <i class="bi bi-receipt"></i> Generate Invoice
                    </a>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($inv['invoice_number']) ?></td>
                                        <td>
                                            <?= date('M d', strtotime($inv['period_start'])) ?> - 
                                            <?= date('M d, Y', strtotime($inv['period_end'])) ?>
                                        </td>
                                        <td><?= format_currency($inv['total_amount']) ?></td>
                                        <td><?= format_currency($inv['paid_amount']) ?></td>
                                        <td>
                                            <strong class="<?= floatval($inv['balance_amount']) > 0 ? 'text-danger' : 'text-success' ?>">
                                                <?= format_currency($inv['balance_amount']) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $inv['status'] === 'paid' ? 'success' : 
                                                ($inv['status'] === 'overdue' ? 'danger' : 'warning') 
                                            ?>">
                                                <?= ucfirst($inv['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('rent-invoices/view/' . $inv['id']) ?>" class="btn btn-sm btn-outline-dark">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
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
                <h5 class="card-title mb-0">Financial Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Total Invoiced</h6>
                    <h4 class="mb-0"><?= format_currency($stats['total_invoiced']) ?></h4>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Total Paid</h6>
                    <h4 class="mb-0 text-success"><?= format_currency($stats['total_paid']) ?></h4>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Total Due</h6>
                    <h4 class="mb-0 <?= $stats['total_due'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= format_currency($stats['total_due']) ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>

