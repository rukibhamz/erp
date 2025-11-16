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
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Meters</h6>
                <h2 class="mb-0"><?= number_format($stats['total_meters']) ?></h2>
                <a href="<?= base_url('utilities/meters') ?>" class="btn btn-sm btn-primary mt-2">
                    View All
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Bills</h6>
                <h2 class="mb-0"><?= number_format($stats['total_bills']) ?></h2>
                <a href="<?= base_url('utilities/bills') ?>" class="btn btn-sm btn-primary mt-2">
                    View All
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Overdue Bills</h6>
                <h2 class="mb-0 text-danger"><?= number_format($stats['overdue_bills']) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">This Month Consumption</h6>
                <h2 class="mb-0"><?= number_format($stats['total_consumption'], 2) ?></h2>
                <small class="text-muted">Units</small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header">
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
<div class="card">
    <div class="card-header">
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

