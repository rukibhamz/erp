<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Utility Reports</h1>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-graph-up" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Consumption Report</h5>
                <p class="text-muted">View consumption trends and analysis by meter, period, and utility type.</p>
                <a href="<?= base_url('utilities/reports/consumption') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-cash-stack" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Cost Report</h5>
                <p class="text-muted">Analyze utility costs, payments, and outstanding balances.</p>
                <a href="<?= base_url('utilities/reports/cost') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-receipt" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Billing Report</h5>
                <p class="text-muted">Generate detailed billing reports by date range and status.</p>
                <a href="<?= base_url('utilities/reports/billing') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
</div>

