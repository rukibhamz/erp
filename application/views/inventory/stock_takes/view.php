<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include BASEPATH . 'views/layouts/header.php';
include BASEPATH . 'views/inventory/_nav.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><?= htmlspecialchars($page_title) ?></h3>
            <div>
                <?php if ($stock_take['status'] === 'scheduled' && hasPermission('inventory', 'update')): ?>
                    <a href="<?= base_url('inventory/stock-takes/start/' . $stock_take['id']) ?>" class="btn btn-info" onclick="return confirm('Start this stock take?')">
                        <i class="bi bi-play-circle"></i> Start
                    </a>
                <?php endif; ?>
                <?php if ($stock_take['status'] === 'in_progress' && hasPermission('inventory', 'update')): ?>
                    <a href="<?= base_url('inventory/stock-takes/complete/' . $stock_take['id']) ?>" class="btn btn-success" onclick="return confirm('Complete this stock take? Adjustments will be created for variances.')">
                        <i class="bi bi-check-circle"></i> Complete
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('inventory/stock-takes') ?>" class="btn btn-outline-dark">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <?php if (isset($flash) && $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Stock Take Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Stock Take Number:</strong><br>
                                <?= htmlspecialchars($stock_take['stock_take_number']) ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Location:</strong><br>
                                <?= htmlspecialchars($stock_take['location_name'] ?? 'N/A') ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Type:</strong><br>
                                <?= ucfirst($stock_take['type']) ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Status:</strong><br>
                                <?php
                                $statusClass = [
                                    'scheduled' => 'secondary',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $class = $statusClass[$stock_take['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $class ?>"><?= ucfirst(str_replace('_', ' ', $stock_take['status'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Count Sheet</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="countSheet">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>SKU</th>
                                <th>Unit</th>
                                <th>Expected Qty</th>
                                <th>Counted Qty</th>
                                <th>Variance</th>
                                <?php if ($stock_take['status'] === 'in_progress'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="<?= $stock_take['status'] === 'in_progress' ? '7' : '6' ?>" class="text-center text-muted">No items in count sheet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr data-item-id="<?= $item['id'] ?>">
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td><?= htmlspecialchars($item['unit_of_measure'] ?? 'each') ?></td>
                                        <td><?= number_format($item['expected_qty'], 2) ?></td>
                                        <td>
                                            <?php if ($stock_take['status'] === 'in_progress'): ?>
                                                <input type="number" 
                                                       class="form-control form-control-sm counted-qty" 
                                                       value="<?= number_format($item['counted_qty'], 2) ?>" 
                                                       step="0.01" 
                                                       data-item-id="<?= $item['id'] ?>"
                                                       style="width: 100px;">
                                            <?php else: ?>
                                                <?= number_format($item['counted_qty'], 2) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $variance = floatval($item['variance']);
                                            $varianceClass = $variance == 0 ? '' : ($variance > 0 ? 'text-success' : 'text-danger');
                                            ?>
                                            <span class="<?= $varianceClass ?>">
                                                <?= number_format($variance, 2) ?>
                                            </span>
                                        </td>
                                        <?php if ($stock_take['status'] === 'in_progress'): ?>
                                            <td>
                                                <button class="btn btn-sm btn-success save-count" data-item-id="<?= $item['id'] ?>">
                                                    <i class="bi bi-check"></i> Save
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($stock_take['status'] === 'in_progress'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveButtons = document.querySelectorAll('.save-count');
    const stockTakeId = <?= $stock_take['id'] ?>;

    saveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            const row = this.closest('tr');
            const qtyInput = row.querySelector('.counted-qty');
            const countedQty = parseFloat(qtyInput.value) || 0;

            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('stock_take_id', stockTakeId);
            formData.append('counted_qty', countedQty);

            fetch('<?= base_url('inventory/stock-takes/update-count') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to update count'));
                }
            })
            .catch(error => {
                alert('Error updating count');
                console.error('Error:', error);
            });
        });
    });
});
</script>
<?php endif; ?>

<?php include BASEPATH . 'views/layouts/footer.php'; ?>

