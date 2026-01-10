<?php include BASEPATH . '../application/views/inventory/_nav.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-trash-alt me-2"></i>Dispose Asset</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($flash) && $flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action will permanently dispose of the asset and create accounting entries. This cannot be undone.
                    </div>

                    <!-- Asset Summary -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title">Asset Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Asset Tag:</strong> <?= htmlspecialchars($asset['asset_tag'] ?? '') ?></p>
                                    <p class="mb-1"><strong>Asset Name:</strong> <?= htmlspecialchars($asset['asset_name'] ?? '') ?></p>
                                    <p class="mb-1"><strong>Category:</strong> <?= ucfirst(htmlspecialchars($asset['asset_category'] ?? '')) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Purchase Cost:</strong> <?= format_currency($asset['purchase_cost'] ?? 0) ?></p>
                                    <p class="mb-1"><strong>Current Value:</strong> <?= format_currency($asset['current_value'] ?? 0) ?></p>
                                    <p class="mb-1"><strong>Accumulated Depreciation:</strong> <?= format_currency(($asset['purchase_cost'] ?? 0) - ($asset['current_value'] ?? 0)) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="disposal_date" class="form-label">Disposal Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="disposal_date" name="disposal_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="disposal_method" class="form-label">Disposal Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="disposal_method" name="disposal_method" required>
                                    <option value="sold">Sold</option>
                                    <option value="scrapped">Scrapped</option>
                                    <option value="donated">Donated</option>
                                    <option value="retired">Retired</option>
                                    <option value="lost">Lost/Stolen</option>
                                    <option value="trade_in">Trade-In</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sale_proceeds" class="form-label">Sale Proceeds</label>
                                <div class="input-group">
                                    <span class="input-group-text">₦</span>
                                    <input type="number" class="form-control" id="sale_proceeds" name="sale_proceeds" 
                                           step="0.01" min="0" value="0" onchange="calculateGainLoss()">
                                </div>
                                <small class="text-muted">Enter 0 if asset was scrapped or donated</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gain/Loss on Disposal</label>
                                <div class="input-group">
                                    <span class="input-group-text">₦</span>
                                    <input type="text" class="form-control" id="gain_loss" readonly>
                                </div>
                                <small class="text-muted" id="gain_loss_label">Based on current book value</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Disposal Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Enter any notes about the disposal..."></textarea>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between flex-column flex-sm-row gap-2">
                            <a href="<?= site_url('inventory/assets/view/' . ($asset['id'] ?? '')) ?>" class="btn btn-secondary order-2 order-sm-1">
                                <i class="fas fa-arrow-left me-1"></i> Cancel
                            </a>
                            <div class="order-1 order-sm-2">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to dispose of this asset? This action cannot be undone.')">
                                    <i class="fas fa-trash-alt me-1"></i> Dispose Asset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const bookValue = <?= floatval($asset['current_value'] ?? 0) ?>;

function calculateGainLoss() {
    const saleProceeds = parseFloat(document.getElementById('sale_proceeds').value) || 0;
    const gainLoss = saleProceeds - bookValue;
    
    document.getElementById('gain_loss').value = gainLoss.toFixed(2);
    
    const label = document.getElementById('gain_loss_label');
    if (gainLoss > 0) {
        label.textContent = 'Gain on disposal';
        label.className = 'text-success';
    } else if (gainLoss < 0) {
        label.textContent = 'Loss on disposal';
        label.className = 'text-danger';
    } else {
        label.textContent = 'No gain or loss';
        label.className = 'text-muted';
    }
}

// Calculate on page load
calculateGainLoss();
</script>
