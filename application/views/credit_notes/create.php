<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Credit Note</h1>
        <a href="<?= base_url('credit-notes') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($invoice): ?>
        <div class="alert alert-info">
            <strong>Related Invoice:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?> - 
            <?= format_currency($invoice['total_amount'], $invoice['currency']) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-select" required id="customer_id">
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>" <?= ($invoice && $invoice['customer_id'] == $customer['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($customer['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($invoice_id): ?>
                        <input type="hidden" name="invoice_id" value="<?= $invoice_id ?>">
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label class="form-label">Credit Note Date *</label>
                        <input type="date" name="credit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-select">
                            <?php foreach (get_all_currencies() as $code => $currency): ?>
                                <option value="<?= $code ?>" <?= ($invoice && $invoice['currency'] === $code) ? 'selected' : ($code === 'USD' ? 'selected' : '') ?>>
                                    <?= $code ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Reason for Credit Note *</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>

                <!-- Items Table -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Product/Service</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Amount</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr id="row_0">
                                    <td>
                                        <select name="items[0][product_id]" class="form-select form-select-sm">
                                            <option value="">Select Product</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][description]" class="form-control form-control-sm"></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm" step="0.01" value="1" onchange="calculateRow(0)"></td>
                                    <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm" step="0.01" value="0" onchange="calculateRow(0)"></td>
                                    <td><span class="line-total">0.00</span></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(0)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td><strong id="subtotal">0.00</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                                    <td>
                                        <input type="number" name="tax_amount" class="form-control form-control-sm" step="0.01" value="0" onchange="calculateTotals()">
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td><strong id="totalAmount">0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('credit-notes') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Credit Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let itemIndex = 1;

function addItem() {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.id = 'row_' + itemIndex;
    row.innerHTML = `
        <td>
            <select name="items[${itemIndex}][product_id]" class="form-select form-select-sm">
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm"></td>
        <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm" step="0.01" value="1" onchange="calculateRow(${itemIndex})"></td>
        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm" step="0.01" value="0" onchange="calculateRow(${itemIndex})"></td>
        <td><span class="line-total">0.00</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${itemIndex})"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(row);
    itemIndex++;
}

function removeRow(index) {
    const row = document.getElementById('row_' + index);
    if (row) {
        row.remove();
        calculateTotals();
    }
}

function calculateRow(index) {
    const row = document.getElementById('row_' + index);
    if (!row) return;
    
    const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    const rate = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
    const total = quantity * rate;
    
    row.querySelector('.line-total').textContent = total.toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
        const rate = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
        subtotal += quantity * rate;
    });
    
    const taxAmount = parseFloat(document.querySelector('input[name="tax_amount"]').value) || 0;
    const total = subtotal + taxAmount;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('totalAmount').textContent = total.toFixed(2);
}
</script>


