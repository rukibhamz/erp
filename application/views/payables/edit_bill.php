<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Bill #<?= htmlspecialchars($bill['bill_number'] ?? '') ?>
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

                    <form action="<?= site_url('payables/update-bill/' . ($bill['id'] ?? '')) ?>" method="POST">
                        <?= csrf_field() ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                                    <select name="vendor_id" id="vendor_id" class="form-select" required>
                                        <option value="">Select Vendor</option>
                                        <?php foreach ($vendors ?? [] as $vendor): ?>
                                            <option value="<?= $vendor['id'] ?>" 
                                                    <?= ($bill['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($vendor['name'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bill_number" class="form-label">Bill Number <span class="text-danger">*</span></label>
                                    <input type="text" name="bill_number" id="bill_number" class="form-control" 
                                           value="<?= htmlspecialchars($bill['bill_number'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="bill_date" class="form-label">Bill Date <span class="text-danger">*</span></label>
                                    <input type="date" name="bill_date" id="bill_date" class="form-control" 
                                           value="<?= htmlspecialchars($bill['bill_date'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" name="due_date" id="due_date" class="form-control" 
                                           value="<?= htmlspecialchars($bill['due_date'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="draft" <?= ($bill['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="pending" <?= ($bill['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="approved" <?= ($bill['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                                        <option value="partial" <?= ($bill['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Partially Paid</option>
                                        <option value="paid" <?= ($bill['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2"><?= htmlspecialchars($bill['description'] ?? '') ?></textarea>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3"><i class="fas fa-list me-2"></i>Line Items</h6>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="line-items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%">Account</th>
                                        <th style="width: 30%">Description</th>
                                        <th style="width: 15%">Quantity</th>
                                        <th style="width: 15%">Unit Price</th>
                                        <th style="width: 10%">Amount</th>
                                        <th style="width: 50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="line-items">
                                    <?php 
                                    $items = $bill['items'] ?? [['account_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0]];
                                    foreach ($items as $index => $item): 
                                    ?>
                                        <tr class="line-item">
                                            <td>
                                                <select name="items[<?= $index ?>][account_id]" class="form-select form-select-sm" required>
                                                    <option value="">Select Account</option>
                                                    <?php foreach ($expense_accounts ?? [] as $account): ?>
                                                        <option value="<?= $account['id'] ?>" 
                                                                <?= ($item['account_id'] ?? '') == $account['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($account['code'] . ' - ' . $account['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="items[<?= $index ?>][description]" 
                                                       class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars($item['description'] ?? '') ?>">
                                            </td>
                                            <td>
                                                <input type="number" name="items[<?= $index ?>][quantity]" 
                                                       class="form-control form-control-sm item-qty" 
                                                       value="<?= floatval($item['quantity'] ?? 1) ?>" 
                                                       min="0" step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="number" name="items[<?= $index ?>][unit_price]" 
                                                       class="form-control form-control-sm item-price" 
                                                       value="<?= floatval($item['unit_price'] ?? 0) ?>" 
                                                       min="0" step="0.01" required>
                                            </td>
                                            <td class="item-amount text-end">
                                                ₦<?= number_format(floatval($item['quantity'] ?? 1) * floatval($item['unit_price'] ?? 0), 2) ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-item">
                                                <i class="fas fa-plus me-1"></i> Add Line Item
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end" id="subtotal">₦<?= number_format(floatval($bill['subtotal'] ?? 0), 2) ?></td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                                        <td class="text-end">
                                            <input type="number" name="tax_amount" id="tax_amount" 
                                                   class="form-control form-control-sm" 
                                                   value="<?= floatval($bill['tax_amount'] ?? 0) ?>" 
                                                   min="0" step="0.01">
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end" id="total"><strong>₦<?= number_format(floatval($bill['total'] ?? 0), 2) ?></strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2"><?= htmlspecialchars($bill['notes'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between flex-column flex-sm-row gap-2">
                            <a href="<?= site_url('payables') ?>" class="btn btn-secondary order-2 order-sm-1">
                                <i class="fas fa-arrow-left me-1"></i> Back
                            </a>
                            <div class="order-1 order-sm-2">
                                <button type="submit" name="action" value="save" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Changes
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
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = <?= count($items) ?>;
    
    // Add line item
    document.getElementById('add-item').addEventListener('click', function() {
        const tbody = document.getElementById('line-items');
        const row = document.createElement('tr');
        row.className = 'line-item';
        row.innerHTML = `
            <td>
                <select name="items[${itemIndex}][account_id]" class="form-select form-select-sm" required>
                    <option value="">Select Account</option>
                    <?php foreach ($expense_accounts ?? [] as $account): ?>
                        <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['code'] . ' - ' . $account['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm"></td>
            <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm item-qty" value="1" min="0" step="0.01" required></td>
            <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm item-price" value="0" min="0" step="0.01" required></td>
            <td class="item-amount text-end">₦0.00</td>
            <td><button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="fas fa-times"></i></button></td>
        `;
        tbody.appendChild(row);
        itemIndex++;
        attachEventListeners(row);
    });
    
    // Remove line item
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const rows = document.querySelectorAll('.line-item');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                calculateTotals();
            }
        }
    });
    
    // Calculate amounts
    function attachEventListeners(row) {
        row.querySelectorAll('.item-qty, .item-price').forEach(input => {
            input.addEventListener('input', calculateTotals);
        });
    }
    
    document.querySelectorAll('.line-item').forEach(attachEventListeners);
    document.getElementById('tax_amount').addEventListener('input', calculateTotals);
    
    function calculateTotals() {
        let subtotal = 0;
        document.querySelectorAll('.line-item').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const amount = qty * price;
            row.querySelector('.item-amount').textContent = '₦' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            subtotal += amount;
        });
        
        const tax = parseFloat(document.getElementById('tax_amount').value) || 0;
        const total = subtotal + tax;
        
        document.getElementById('subtotal').textContent = '₦' + subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        document.getElementById('total').innerHTML = '<strong>₦' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong>';
    }
});
</script>
