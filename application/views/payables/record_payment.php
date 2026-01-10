<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>Record Payment for Bill #<?= htmlspecialchars($bill['bill_number'] ?? '') ?>
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
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Vendor:</strong> <?= htmlspecialchars($bill['vendor_name'] ?? '') ?></p>
                                <p class="mb-1"><strong>Bill Date:</strong> <?= htmlspecialchars($bill['bill_date'] ?? '') ?></p>
                                <p class="mb-0"><strong>Due Date:</strong> <?= htmlspecialchars($bill['due_date'] ?? '') ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-1"><strong>Bill Total:</strong> ₦<?= number_format(floatval($bill['total'] ?? 0), 2) ?></p>
                                <p class="mb-1"><strong>Amount Paid:</strong> ₦<?= number_format(floatval($bill['amount_paid'] ?? 0), 2) ?></p>
                                <p class="mb-0 text-danger"><strong>Balance Due:</strong> ₦<?= number_format(floatval($bill['balance'] ?? $bill['total'] ?? 0), 2) ?></p>
                            </div>
                        </div>
                    </div>

                    <form action="<?= site_url('payables/process-payment/' . ($bill['id'] ?? '')) ?>" method="POST">
                        <?= csrf_field() ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" name="payment_date" id="payment_date" class="form-control" 
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₦</span>
                                        <input type="number" name="amount" id="amount" class="form-control" 
                                               value="<?= floatval($bill['balance'] ?? $bill['total'] ?? 0) ?>" 
                                               min="0.01" max="<?= floatval($bill['balance'] ?? $bill['total'] ?? 0) ?>"
                                               step="0.01" required>
                                    </div>
                                    <small class="text-muted">Maximum: ₦<?= number_format(floatval($bill['balance'] ?? $bill['total'] ?? 0), 2) ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-select" required>
                                        <option value="">Select Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="card">Card Payment</option>
                                        <option value="mobile_money">Mobile Money</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bank_account_id" class="form-label">Pay From Account <span class="text-danger">*</span></label>
                                    <select name="bank_account_id" id="bank_account_id" class="form-select" required>
                                        <option value="">Select Account</option>
                                        <?php foreach ($bank_accounts ?? [] as $account): ?>
                                            <option value="<?= $account['id'] ?>">
                                                <?= htmlspecialchars($account['name'] ?? '') ?> 
                                                (₦<?= number_format(floatval($account['balance'] ?? 0), 2) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reference_number" class="form-label">Reference Number</label>
                                    <input type="text" name="reference_number" id="reference_number" class="form-control" 
                                           placeholder="e.g., Cheque number, Transfer reference">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="cheque_date_group" style="display: none;">
                                    <label for="cheque_date" class="form-label">Cheque Date</label>
                                    <input type="date" name="cheque_date" id="cheque_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" 
                                      placeholder="Optional payment notes"></textarea>
                        </div>

                        <!-- WHT Deduction -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="apply_wht" id="apply_wht" value="1">
                                    <label class="form-check-label" for="apply_wht">
                                        <strong>Apply Withholding Tax (WHT)</strong>
                                    </label>
                                </div>
                                <div id="wht_fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="wht_rate" class="form-label">WHT Rate (%)</label>
                                                <select name="wht_rate" id="wht_rate" class="form-select">
                                                    <option value="5">5% - Professional Services</option>
                                                    <option value="10">10% - Rent</option>
                                                    <option value="2.5">2.5% - Contracts</option>
                                                    <option value="5">5% - Dividends</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">WHT Amount</label>
                                                <input type="text" id="wht_amount_display" class="form-control" readonly>
                                                <input type="hidden" name="wht_amount" id="wht_amount">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= site_url('payables/view/' . ($bill['id'] ?? '')) ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Bill
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i> Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment History Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Payment History</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($payments)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($payments as $payment): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="mb-1"><strong>₦<?= number_format(floatval($payment['amount'] ?? 0), 2) ?></strong></p>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($payment['payment_date'] ?? '') ?> • 
                                                <?= ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? '')) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-success">Paid</span>
                                    </div>
                                    <?php if (!empty($payment['reference_number'])): ?>
                                        <small class="text-muted d-block mt-1">
                                            Ref: <?= htmlspecialchars($payment['reference_number']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">
                            <i class="fas fa-info-circle me-1"></i> No payments recorded yet
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethod = document.getElementById('payment_method');
    const chequeDateGroup = document.getElementById('cheque_date_group');
    const applyWht = document.getElementById('apply_wht');
    const whtFields = document.getElementById('wht_fields');
    const whtRate = document.getElementById('wht_rate');
    const amountInput = document.getElementById('amount');
    const whtAmountDisplay = document.getElementById('wht_amount_display');
    const whtAmountHidden = document.getElementById('wht_amount');
    
    // Show/hide cheque date field
    paymentMethod.addEventListener('change', function() {
        chequeDateGroup.style.display = this.value === 'cheque' ? 'block' : 'none';
    });
    
    // Show/hide WHT fields
    applyWht.addEventListener('change', function() {
        whtFields.style.display = this.checked ? 'block' : 'none';
        if (this.checked) {
            calculateWht();
        }
    });
    
    // Calculate WHT
    function calculateWht() {
        const amount = parseFloat(amountInput.value) || 0;
        const rate = parseFloat(whtRate.value) || 0;
        const whtAmount = amount * (rate / 100);
        whtAmountDisplay.value = '₦' + whtAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        whtAmountHidden.value = whtAmount.toFixed(2);
    }
    
    whtRate.addEventListener('change', calculateWht);
    amountInput.addEventListener('input', function() {
        if (applyWht.checked) {
            calculateWht();
        }
    });
});
</script>
