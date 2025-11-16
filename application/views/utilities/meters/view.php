<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Meter: <?= htmlspecialchars($meter['meter_number']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('utilities/readings/create?meter_id=' . $meter['id']) ?>" class="btn btn-success">
                <i class="bi bi-clipboard-data"></i> Record Reading
            </a>
            <a href="<?= base_url('utilities/meters/edit/' . $meter['id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?= base_url('utilities/meters') ?>" class="btn btn-outline-secondary">
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
                <h5 class="card-title mb-0">Meter Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Meter Number:</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars($meter['meter_number']) ?></strong></dd>
                    
                    <dt class="col-sm-4">Utility Type:</dt>
                    <dd class="col-sm-8">
                        <i class="bi bi-lightning"></i> <?= htmlspecialchars($meter['utility_type_name']) ?>
                        <small class="text-muted">(<?= htmlspecialchars($meter['unit_of_measure']) ?>)</small>
                    </dd>
                    
                    <dt class="col-sm-4">Meter Type:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $meter['meter_type'])) ?></dd>
                    
                    <dt class="col-sm-4">Location:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($meter['meter_location'] ?: 'N/A') ?></dd>
                    
                    <?php if ($meter['property_name']): ?>
                        <dt class="col-sm-4">Property:</dt>
                        <dd class="col-sm-8">
                            <?php if ($meter['property_id']): ?>
                                <a href="<?= base_url('properties/view/' . $meter['property_id']) ?>">
                                    <?= htmlspecialchars($meter['property_name']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($meter['property_name']) ?>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if ($meter['space_name']): ?>
                        <dt class="col-sm-4">Space:</dt>
                        <dd class="col-sm-8">
                            <?php if ($meter['space_id']): ?>
                                <a href="<?= base_url('spaces/view/' . $meter['space_id']) ?>">
                                    <?= htmlspecialchars($meter['space_name']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($meter['space_name']) ?>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $meter['status'] === 'active' ? 'success' : ($meter['status'] === 'faulty' ? 'danger' : 'secondary') ?>">
                            <?= ucfirst($meter['status']) ?>
                        </span>
                    </dd>
                    
                    <?php if ($meter['last_reading']): ?>
                        <dt class="col-sm-4">Last Reading:</dt>
                        <dd class="col-sm-8">
                            <strong><?= number_format($meter['last_reading'], 2) ?></strong>
                            <small class="text-muted"><?= htmlspecialchars($meter['unit_of_measure']) ?></small>
                            <?php if ($meter['last_reading_date']): ?>
                                <br><small class="text-muted">Date: <?= date('M d, Y', strtotime($meter['last_reading_date'])) ?></small>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- Recent Readings -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Readings</h5>
                <a href="<?= base_url('utilities/readings?meter_id=' . $meter['id']) ?>" class="btn btn-sm btn-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($readings)): ?>
                    <p class="text-muted mb-0">No readings recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reading</th>
                                    <th>Consumption</th>
                                    <th>Type</th>
                                    <th>Reader</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($readings as $reading): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($reading['reading_date'])) ?></td>
                                        <td><?= number_format($reading['reading_value'], 2) ?></td>
                                        <td><?= number_format($reading['consumption'] ?? 0, 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $reading['reading_type'] === 'actual' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($reading['reading_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($reading['reader_name'] ?: 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Bills -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Bills</h5>
                <a href="<?= base_url('utilities/bills?meter_id=' . $meter['id']) ?>" class="btn btn-sm btn-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($bills)): ?>
                    <p class="text-muted mb-3">No bills generated yet.</p>
                    <a href="<?= base_url('utilities/bills/generate/' . $meter['id']) ?>" class="btn btn-primary">
                        <i class="bi bi-receipt"></i> Generate Bill
                    </a>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Bill #</th>
                                    <th>Period</th>
                                    <th>Consumption</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bills as $bill): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($bill['bill_number']) ?></td>
                                        <td>
                                            <?= date('M d', strtotime($bill['billing_period_start'])) ?> - 
                                            <?= date('M d, Y', strtotime($bill['billing_period_end'])) ?>
                                        </td>
                                        <td><?= number_format($bill['consumption'], 2) ?></td>
                                        <td><?= format_currency($bill['total_amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $bill['status'] === 'paid' ? 'success' : ($bill['status'] === 'overdue' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($bill['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('utilities/bills/view/' . $bill['id']) ?>" class="btn btn-sm btn-primary">
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
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="<?= base_url('utilities/readings/create?meter_id=' . $meter['id']) ?>" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-clipboard-data"></i> Record Reading
                </a>
                <a href="<?= base_url('utilities/bills/generate/' . $meter['id']) ?>" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-receipt"></i> Generate Bill
                </a>
                <a href="<?= base_url('utilities/readings?meter_id=' . $meter['id']) ?>" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-list"></i> View All Readings
                </a>
            </div>
        </div>
    </div>
</div>

