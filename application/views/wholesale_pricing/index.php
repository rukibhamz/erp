<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Wholesale Pricing</h5>
                    <a href="<?= site_url('wholesale-pricing/create') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Add Pricing Rule
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($flash) && $flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Wholesale pricing allows you to offer discounted prices for bulk purchases. Configure pricing tiers based on quantity thresholds.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Customer Type</th>
                                    <th>Min Quantity</th>
                                    <th>Discount Type</th>
                                    <th>Discount Value</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pricing_rules)): ?>
                                    <?php foreach ($pricing_rules as $rule): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($rule['item_name'])): ?>
                                                    <?= htmlspecialchars($rule['item_name']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">All Items</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($rule['customer_type_name'])): ?>
                                                    <?= htmlspecialchars($rule['customer_type_name']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">All Customers</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= intval($rule['min_quantity'] ?? 0) ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($rule['discount_type'] ?? '') === 'percentage' ? 'info' : 'secondary' ?>">
                                                    <?= ucfirst($rule['discount_type'] ?? 'fixed') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (($rule['discount_type'] ?? '') === 'percentage'): ?>
                                                    <?= floatval($rule['discount_value'] ?? 0) ?>%
                                                <?php else: ?>
                                                    ₦<?= number_format(floatval($rule['discount_value'] ?? 0), 2) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= ($rule['is_active'] ?? 0) ? 'success' : 'secondary' ?>">
                                                    <?= ($rule['is_active'] ?? 0) ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= site_url('wholesale-pricing/edit/' . ($rule['id'] ?? '')) ?>" 
                                                       class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= site_url('wholesale-pricing/delete/' . ($rule['id'] ?? '')) ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Delete this pricing rule?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-tags fa-3x mb-3 d-block opacity-50"></i>
                                            No wholesale pricing rules configured
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Discount Tiers Section -->
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="fas fa-layer-group me-2"></i>Discount Tiers</h6>
                        <a href="<?= site_url('discount-tiers') ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-cog me-1"></i> Manage Tiers
                        </a>
                    </div>
                    
                    <div class="row">
                        <?php 
                        $defaultTiers = [
                            ['name' => 'Bronze', 'min_quantity' => 10, 'discount' => 5],
                            ['name' => 'Silver', 'min_quantity' => 50, 'discount' => 10],
                            ['name' => 'Gold', 'min_quantity' => 100, 'discount' => 15],
                            ['name' => 'Platinum', 'min_quantity' => 500, 'discount' => 20],
                        ];
                        $tiers = $discount_tiers ?? $defaultTiers;
                        foreach ($tiers as $tier): 
                        ?>
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title"><?= htmlspecialchars($tier['name'] ?? '') ?></h6>
                                        <p class="display-6 text-primary mb-1"><?= intval($tier['discount'] ?? 0) ?>%</p>
                                        <small class="text-muted">Min: <?= intval($tier['min_quantity'] ?? 0) ?> units</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
