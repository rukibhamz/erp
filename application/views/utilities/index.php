<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Utilities Dashboard</h1>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon primary me-3">
                    <i class="bi bi-speedometer2"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_number($stats['total_meters'], 0) ?></div>
                    <div class="stat-label">Total Meters</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon info me-3">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_number($stats['total_bills'], 0) ?></div>
                    <div class="stat-label">Total Bills</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon danger me-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_number($stats['overdue_bills'], 0) ?></div>
                    <div class="stat-label">Overdue Bills</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon success me-3">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_number($stats['total_consumption'], 1) ?></div>
                    <div class="stat-label">This Month Consumption</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('utilities/meters/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Meter
            </a>
            <a href="<?= base_url('utilities/readings/create') ?>" class="btn btn-success">
                <i class="bi bi-clipboard-data"></i> Record Reading
            </a>
            <a href="<?= base_url('utilities/bills/generate') ?>" class="btn btn-info">
                <i class="bi bi-receipt"></i> Generate Bill
            </a>
        </div>
    </div>
</div>

<!-- Utility Types -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">Utility Types</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($utility_types as $type): ?>
                <div class="col-md-3">
                    <div class="card border">
                        <div class="card-body text-center">
                            <i class="bi <?= $type['icon'] ?> fs-1 text-primary mb-2"></i>
                            <h6><?= htmlspecialchars($type['name']) ?></h6>
                            <small class="text-muted"><?= htmlspecialchars($type['unit_of_measure']) ?></small>
                            <br>
                            <a href="<?= base_url('utilities/meters?utility_type_id=' . $type['id']) ?>" class="btn btn-sm btn-primary mt-2">
                                View Meters
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

