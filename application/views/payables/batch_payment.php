<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Batch Bill Payment</h1>
        <a href="<?= base_url('payables/bills') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="batchPaymentForm">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Payment Date *</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Method *</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cash Account *</label>
                        <select name="cash_account_id" class="form-select" required>
                            <option value="">Select Account</option>
                            <?php foreach ($cash_accounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= htmlspecialchars($account['account_name']) ?> 
                                    (<?= format_currency($account['current_balance'], $account['currency'] ?? 'USD') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control" placeholder="Payment reference number">
                </div>

                <h5 class="mb-3">Select Bills to Pay</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleAll()"></th>
                                <th>Bill #</th>
                                <th>Vendor</th>
                                <th>Due Date</th>
                                <th>Balance</th>
                                <th>Payment Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($bills)): ?>
                                <?php foreach ($bills as $bill): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="bill_ids[]" value="<?= $bill['id'] ?>" 
                                                   onchange="toggleRow(<?= $bill['id'] ?>)" class="bill-checkbox">
                                        </td>
                                        <td><?= htmlspecialchars($bill['bill_number']) ?></td>
                                        <td><?= htmlspecialchars($bill['company_name']) ?></td>
                                        <td class="<?= strtotime($bill['due_date']) < time() ? 'text-danger' : '' ?>">
                                            <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                                        </td>
                                        <td><?= format_currency($bill['balance_amount'], $bill['currency'] ?? 'USD') ?></td>
                                        <td>
                                            <input type="number" name="amount_<?= $bill['id'] ?>" 
                                                   class="form-control form-control-sm payment-amount" 
                                                   step="0.01" 
                                                   value="<?= $bill['balance_amount'] ?>"
                                                   max="<?= $bill['balance_amount'] ?>"
                                                   disabled
                                                   onchange="calculateTotal()">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No unpaid bills found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Total Payment:</strong></td>
                                <td><strong id="totalPayment">0.00</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= base_url('payables/bills') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Process Batch Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.bill-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
        toggleRow(cb.value);
    });
}

function toggleRow(billId) {
    const checkbox = document.querySelector(`input[name="bill_ids[]"][value="${billId}"]`);
    const amountInput = document.querySelector(`input[name="amount_${billId}"]`);
    if (checkbox && amountInput) {
        amountInput.disabled = !checkbox.checked;
        if (!checkbox.checked) {
            amountInput.value = '';
        }
        calculateTotal();
    }
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.payment-amount:not([disabled])').forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
    });
    document.getElementById('totalPayment').textContent = total.toFixed(2);
}

// Initialize total calculation
calculateTotal();
</script>

<?php $this->load->view('layouts/footer'); ?>

