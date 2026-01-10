<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-balance-scale me-2"></i>Allocate Utility Bill
                        <span class="badge bg-primary ms-2"><?= htmlspecialchars($bill['bill_number'] ?? '') ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Bill Summary -->
                    <div class="alert alert-light border mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Provider:</strong> <?= htmlspecialchars($bill['provider_name'] ?? '') ?></p>
                                <p class="mb-1"><strong>Type:</strong> <?= htmlspecialchars($bill['utility_type'] ?? '') ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Bill Period:</strong> <?= htmlspecialchars($bill['billing_period'] ?? '') ?></p>
                                <p class="mb-1"><strong>Due Date:</strong> <?= htmlspecialchars($bill['due_date'] ?? '') ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Total Amount:</strong> <span class="text-primary fs-5">₦<?= number_format(floatval($bill['total_amount'] ?? 0), 2) ?></span></p>
                                <p class="mb-1"><strong>Already Allocated:</strong> ₦<?= number_format(floatval($bill['allocated_amount'] ?? 0), 2) ?></p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Remaining:</strong> <span class="text-success fs-5">₦<?= number_format(floatval($bill['remaining_amount'] ?? 0), 2) ?></span></p>
                                <div class="progress mt-2" style="height: 20px;">
                                    <?php 
                                    $allocatedPercent = ($bill['total_amount'] ?? 1) > 0 
                                        ? (($bill['allocated_amount'] ?? 0) / ($bill['total_amount'] ?? 1)) * 100 
                                        : 0;
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?= $allocatedPercent ?>%" 
                                         aria-valuenow="<?= $allocatedPercent ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?= number_format($allocatedPercent, 1) ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="<?= site_url('utility-allocations/process-allocate/' . ($bill['id'] ?? '')) ?>" method="POST">
                        <?= csrf_field() ?>
                        
                        <h6 class="mb-3"><i class="fas fa-list me-2"></i>Allocations</h6>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="allocation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%">Property/Unit</th>
                                        <th style="width: 15%">Previous Reading</th>
                                        <th style="width: 15%">Current Reading</th>
                                        <th style="width: 12%">Usage</th>
                                        <th style="width: 15%">Share %</th>
                                        <th style="width: 18%">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="allocations">
                                    <?php 
                                    $allocations = $allocations ?? [['property_id' => '', 'previous_reading' => 0, 'current_reading' => 0, 'share_percent' => 0]];
                                    foreach ($allocations as $index => $allocation): 
                                        $usage = floatval($allocation['current_reading'] ?? 0) - floatval($allocation['previous_reading'] ?? 0);
                                        $amount = floatval($allocation['amount'] ?? 0);
                                    ?>
                                        <tr class="allocation-row">
                                            <td>
                                                <select name="allocations[<?= $index ?>][property_id]" class="form-select form-select-sm" required>
                                                    <option value="">Select Property</option>
                                                    <?php foreach ($properties ?? [] as $property): ?>
                                                        <option value="<?= $property['id'] ?>" 
                                                                <?= ($allocation['property_id'] ?? '') == $property['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($property['name'] ?? '') ?>
                                                            (<?= htmlspecialchars($property['unit_number'] ?? '') ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="allocations[<?= $index ?>][previous_reading]" 
                                                       class="form-control form-control-sm prev-reading"
                                                       value="<?= floatval($allocation['previous_reading'] ?? 0) ?>" 
                                                       min="0" step="0.01">
                                            </td>
                                            <td>
                                                <input type="number" name="allocations[<?= $index ?>][current_reading]" 
                                                       class="form-control form-control-sm curr-reading"
                                                       value="<?= floatval($allocation['current_reading'] ?? 0) ?>" 
                                                       min="0" step="0.01">
                                            </td>
                                            <td class="usage-display text-center">
                                                <?= $usage > 0 ? number_format($usage, 2) : '-' ?>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="allocations[<?= $index ?>][share_percent]" 
                                                           class="form-control share-percent"
                                                           value="<?= floatval($allocation['share_percent'] ?? 0) ?>" 
                                                           min="0" max="100" step="0.1">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">₦</span>
                                                    <input type="number" name="allocations[<?= $index ?>][amount]" 
                                                           class="form-control allocation-amount"
                                                           value="<?= $amount ?>" 
                                                           min="0" step="0.01">
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-allocation">
                                                <i class="fas fa-plus me-1"></i> Add Property
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm ms-2" id="auto-allocate">
                                                <i class="fas fa-magic me-1"></i> Auto-Allocate
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="equal-split">
                                                <i class="fas fa-divide me-1"></i> Equal Split
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="5" class="text-end"><strong>Total Allocated:</strong></td>
                                        <td class="text-end" id="total-allocated">
                                            <strong>₦<?= number_format(array_sum(array_column($allocations, 'amount')), 2) ?></strong>
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="5" class="text-end"><strong>Remaining:</strong></td>
                                        <td class="text-end">
                                            <strong id="remaining-amount" class="<?= ($bill['remaining_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                                ₦<?= number_format(floatval($bill['remaining_amount'] ?? 0), 2) ?>
                                            </strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="allocation_method" class="form-label">Allocation Method</label>
                                    <select name="allocation_method" id="allocation_method" class="form-select">
                                        <option value="usage" <?= ($bill['allocation_method'] ?? '') === 'usage' ? 'selected' : '' ?>>By Meter Usage</option>
                                        <option value="equal" <?= ($bill['allocation_method'] ?? '') === 'equal' ? 'selected' : '' ?>>Equal Split</option>
                                        <option value="percentage" <?= ($bill['allocation_method'] ?? '') === 'percentage' ? 'selected' : '' ?>>Manual Percentage</option>
                                        <option value="sqft" <?= ($bill['allocation_method'] ?? '') === 'sqft' ? 'selected' : '' ?>>By Square Footage</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="2"><?= htmlspecialchars($bill['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= site_url('utility-bills/view/' . ($bill['id'] ?? '')) ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Bill
                            </a>
                            <button type="submit" class="btn btn-primary" id="save-btn" disabled>
                                <i class="fas fa-save me-1"></i> Save Allocations
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let allocationIndex = <?= count($allocations) ?>;
    const totalAmount = <?= floatval($bill['total_amount'] ?? 0) ?>;
    const remainingAmount = <?= floatval($bill['remaining_amount'] ?? 0) ?>;
    
    // Add allocation row
    document.getElementById('add-allocation').addEventListener('click', function() {
        const tbody = document.getElementById('allocations');
        const row = document.createElement('tr');
        row.className = 'allocation-row';
        row.innerHTML = `
            <td>
                <select name="allocations[${allocationIndex}][property_id]" class="form-select form-select-sm" required>
                    <option value="">Select Property</option>
                    <?php foreach ($properties ?? [] as $property): ?>
                        <option value="<?= $property['id'] ?>"><?= htmlspecialchars($property['name'] . ' (' . $property['unit_number'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" name="allocations[${allocationIndex}][previous_reading]" class="form-control form-control-sm prev-reading" value="0" min="0" step="0.01"></td>
            <td><input type="number" name="allocations[${allocationIndex}][current_reading]" class="form-control form-control-sm curr-reading" value="0" min="0" step="0.01"></td>
            <td class="usage-display text-center">-</td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" name="allocations[${allocationIndex}][share_percent]" class="form-control share-percent" value="0" min="0" max="100" step="0.1">
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">₦</span>
                    <input type="number" name="allocations[${allocationIndex}][amount]" class="form-control allocation-amount" value="0" min="0" step="0.01">
                </div>
            </td>
        `;
        tbody.appendChild(row);
        allocationIndex++;
        attachEventListeners(row);
    });
    
    function attachEventListeners(row) {
        row.querySelectorAll('.prev-reading, .curr-reading, .share-percent, .allocation-amount').forEach(input => {
            input.addEventListener('input', calculateTotals);
        });
    }
    
    document.querySelectorAll('.allocation-row').forEach(attachEventListeners);
    
    // Auto-allocate based on usage
    document.getElementById('auto-allocate').addEventListener('click', function() {
        let totalUsage = 0;
        const rows = document.querySelectorAll('.allocation-row');
        
        rows.forEach(row => {
            const curr = parseFloat(row.querySelector('.curr-reading').value) || 0;
            const prev = parseFloat(row.querySelector('.prev-reading').value) || 0;
            const usage = Math.max(0, curr - prev);
            row.querySelector('.usage-display').textContent = usage > 0 ? usage.toFixed(2) : '-';
            totalUsage += usage;
        });
        
        if (totalUsage > 0) {
            rows.forEach(row => {
                const curr = parseFloat(row.querySelector('.curr-reading').value) || 0;
                const prev = parseFloat(row.querySelector('.prev-reading').value) || 0;
                const usage = Math.max(0, curr - prev);
                const share = (usage / totalUsage) * 100;
                row.querySelector('.share-percent').value = share.toFixed(1);
                row.querySelector('.allocation-amount').value = (share / 100 * totalAmount).toFixed(2);
            });
        }
        calculateTotals();
    });
    
    // Equal split
    document.getElementById('equal-split').addEventListener('click', function() {
        const rows = document.querySelectorAll('.allocation-row');
        if (rows.length === 0) return;
        
        const share = 100 / rows.length;
        rows.forEach(row => {
            row.querySelector('.share-percent').value = share.toFixed(1);
            row.querySelector('.allocation-amount').value = (share / 100 * totalAmount).toFixed(2);
        });
        calculateTotals();
    });
    
    function calculateTotals() {
        let totalAllocated = 0;
        document.querySelectorAll('.allocation-row').forEach(row => {
            const amount = parseFloat(row.querySelector('.allocation-amount').value) || 0;
            totalAllocated += amount;
        });
        
        const remaining = totalAmount - totalAllocated;
        document.getElementById('total-allocated').innerHTML = '<strong>₦' + totalAllocated.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong>';
        
        const remainingEl = document.getElementById('remaining-amount');
        remainingEl.textContent = '₦' + remaining.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        remainingEl.className = remaining > 0 ? 'text-danger' : 'text-success';
        
        // Enable/disable save button
        document.getElementById('save-btn').disabled = remaining > 0.01;
    }
});
</script>
