<div class="container-fluid px-4">
    <div class="mb-4">
        <?= back_button('inventory/items/view/' . $item['id']) ?>
        <h1 class="h3 mt-3"><?= $page_title ?></h1>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Tier</h6>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('discount_tiers/save/' . $item['id']) ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Minimum Quantity</label>
                            <input type="number" step="0.01" name="min_quantity" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" class="form-select">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed_price">Fixed Price (Set unit price)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount Value</label>
                            <input type="number" step="0.01" name="discount_value" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Tier</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Existing Tiers</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Min. Quantity</th>
                                    <th>Discount Rule</th>
                                    <th>Effective Unit Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tiers)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No quantity-based tiers set for this item yet.</td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($tiers as $tier): ?>
                                <tr>
                                    <td><?= number_format($tier['min_quantity'], 2) ?>+</td>
                                    <td>
                                        <?php if ($tier['discount_type'] === 'percentage'): ?>
                                            <span class="badge bg-info"><?= $tier['discount_value'] ?>% Off</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Fixed: $<?= number_format($tier['discount_value'], 2) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($tier['discount_type'] === 'percentage') {
                                                echo '$' . number_format($item['retail_price'] * (1 - $tier['discount_value']/100), 2);
                                            } else {
                                                echo '$' . number_format($tier['discount_value'], 2);
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('discount_tiers/delete/' . $tier['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this tier?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
