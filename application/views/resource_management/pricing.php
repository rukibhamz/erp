<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Resource Pricing</h5>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPricingModal">
                            <i class="fas fa-plus me-1"></i> Add Pricing Rule
                        </button>
                        <a href="<?= site_url('facilities') ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($flash) && $flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($facility) && $facility): ?>
                        <div class="alert alert-info">
                            <strong>Facility:</strong> <?= htmlspecialchars($facility['facility_name'] ?? '') ?>
                        </div>
                    <?php endif; ?>

                    <!-- Base Pricing -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Base Pricing</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_base_pricing">
                                
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Hourly Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="hourly_rate" 
                                                   value="<?= floatval($pricing['hourly_rate'] ?? 0) ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Half-Day Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="half_day_rate" 
                                                   value="<?= floatval($pricing['half_day_rate'] ?? 0) ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Daily Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="daily_rate" 
                                                   value="<?= floatval($pricing['daily_rate'] ?? 0) ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Weekly Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="weekly_rate" 
                                                   value="<?= floatval($pricing['weekly_rate'] ?? 0) ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Security Deposit</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="security_deposit" 
                                                   value="<?= floatval($pricing['security_deposit'] ?? 0) ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Cleaning Fee</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" class="form-control" name="cleaning_fee" 
                                                   value="<?= floatval($pricing['cleaning_fee'] ?? 0) ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Tax Rate (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="tax_rate" 
                                                   value="<?= floatval($pricing['tax_rate'] ?? 7.5) ?>" step="0.01" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Base Pricing
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Special Pricing Rules -->
                    <h6>Special Pricing Rules</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rule Name</th>
                                    <th>Type</th>
                                    <th>Adjustment</th>
                                    <th>Valid Period</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pricing_rules)): ?>
                                    <?php foreach ($pricing_rules as $rule): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($rule['name'] ?? '') ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($rule['type'] ?? '') === 'discount' ? 'success' : 'warning' ?>">
                                                    <?= ucfirst($rule['type'] ?? 'adjustment') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (($rule['adjustment_type'] ?? '') === 'percentage'): ?>
                                                    <?= floatval($rule['adjustment_value'] ?? 0) ?>%
                                                <?php else: ?>
                                                    ₦<?= number_format(floatval($rule['adjustment_value'] ?? 0), 2) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= date('M d', strtotime($rule['start_date'] ?? '')) ?> - 
                                                <?= date('M d, Y', strtotime($rule['end_date'] ?? '')) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= ($rule['is_active'] ?? 0) ? 'success' : 'secondary' ?>">
                                                    <?= ($rule['is_active'] ?? 0) ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= site_url('resource-management/delete-pricing/' . ($rule['id'] ?? '')) ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Delete this pricing rule?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No special pricing rules defined
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Pricing Rule Modal -->
<div class="modal fade" id="addPricingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= site_url('resource-management/add-pricing') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="facility_id" value="<?= htmlspecialchars($facility['id'] ?? '') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Pricing Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rule Name</label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., Weekend Premium, Holiday Discount" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" required>
                                <option value="discount">Discount</option>
                                <option value="surcharge">Surcharge</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Adjustment Type</label>
                            <select class="form-select" name="adjustment_type" required>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Adjustment Value</label>
                        <input type="number" class="form-control" name="adjustment_value" step="0.01" min="0" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
