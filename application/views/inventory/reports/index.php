<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Inventory Reports</h1>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

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
                <i class="bi bi-box-seam" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Stock on Hand</h5>
                <p class="text-muted">View current stock levels by item, category, or location.</p>
                <a href="<?= base_url('inventory/reports/stock') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-arrow-left-right" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Stock Movements</h5>
                <p class="text-muted">Track all stock transactions and movements.</p>
                <a href="<?= base_url('inventory/reports/movements') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-cash-stack" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Stock Valuation</h5>
                <p class="text-muted">Current inventory value and valuation reports.</p>
                <a href="<?= base_url('inventory/reports/valuation') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Reorder Level Report</h5>
                <p class="text-muted">Items below reorder point requiring attention.</p>
                <a href="<?= base_url('inventory/reports/reorder') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-graph-up" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Fast/Slow Moving Items</h5>
                <p class="text-muted">Analyze item movement patterns and trends.</p>
                <a href="<?= base_url('inventory/reports/movement-analysis') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-cart-check" style="font-size: 3rem; color: #000;"></i>
                <h5 class="mt-3">Purchase Analysis</h5>
                <p class="text-muted">Purchase orders and supplier performance.</p>
                <a href="<?= base_url('inventory/reports/purchases') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> View Report
                </a>
            </div>
        </div>
    </div>
</div>

