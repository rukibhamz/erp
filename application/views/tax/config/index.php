<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Configuration</h1>
        <a href="<?= base_url('tax/config/create') ?>" class="btn btn-dark">
            <i class="bi bi-plus-circle"></i> Create Tax Type
        </a>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Tax Types</h6>
                <h4 class="mb-0"><?= count($tax_types ?? []) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Active Taxes</h6>
                <h4 class="mb-0 text-success">
                    <?= count(array_filter($tax_types ?? [], function($t) { return $t['is_active'] == 1; })) ?>
                </h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">FIRS Taxes</h6>
                <h4 class="mb-0">
                    <?= count(array_filter($tax_types ?? [], function($t) { return ($t['authority'] ?? '') === 'FIRS'; })) ?>
                </h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">State Taxes</h6>
                <h4 class="mb-0">
                    <?= count(array_filter($tax_types ?? [], function($t) { return ($t['authority'] ?? '') === 'State'; })) ?>
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- Tax Types by Authority -->
<?php if (!empty($grouped_taxes ?? [])): ?>
    <?php foreach ($grouped_taxes as $authority => $taxes): ?>
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="bi bi-building"></i> <?= htmlspecialchars($authority) ?> 
                    <span class="badge bg-light text-dark ms-2"><?= count($taxes) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Rate</th>
                                <th>Calculation Method</th>
                                <th>Filing Frequency</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($taxes as $tax): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($tax['name']) ?></strong>
                                        <?php if (!empty($tax['description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($tax['description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($tax['code']) ?></code></td>
                                    <td>
                                        <?php if (($tax['calculation_method'] ?? '') === 'progressive'): ?>
                                            <span class="text-muted">Progressive</span>
                                        <?php else: ?>
                                            <strong><?= number_format($tax['rate'] ?? 0, 2) ?>%</strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst(str_replace('_', ' ', $tax['calculation_method'] ?? 'percentage')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= ucfirst($tax['filing_frequency'] ?? 'monthly') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm <?= $tax['is_active'] ? 'btn-success' : 'btn-secondary' ?>" 
                                                onclick="toggleStatus(<?= $tax['id'] ?>)">
                                            <?= $tax['is_active'] ? 'Active' : 'Inactive' ?>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('tax/config/edit/' . $tax['id']) ?>" 
                                               class="btn btn-outline-dark" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteTax(<?= $tax['id'] ?>, '<?= htmlspecialchars(addslashes($tax['name'])) ?>')" 
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-sliders" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No tax types configured.</p>
            <a href="<?= base_url('tax/config/create') ?>" class="btn btn-dark mt-2">
                <i class="bi bi-plus-circle"></i> Create First Tax Type
            </a>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleStatus(id) {
    fetch('<?= base_url('tax/config/toggle') ?>/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update status'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating status');
    });
}

function deleteTax(id, name) {
    if (confirm('Deactivate tax type "' + name + '"?\n\nNote: Tax types in use cannot be deleted and will be deactivated instead.')) {
        window.location.href = '<?= base_url('tax/config/delete') ?>/' + id;
    }
}
</script>



