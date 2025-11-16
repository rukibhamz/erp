<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Estimate / Quote</h1>
        <a href="<?= base_url('estimates') ?>" class="btn btn-secondary">
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
            <form method="POST" id="estimateForm">
                <?php echo csrf_field(); ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-select" required id="customer_id">
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estimate Date *</label>
                        <input type="date" name="estimate_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-select">
                            <?php foreach (get_all_currencies() as $code => $currency): ?>
                                <option value="<?= $code ?>" <?= $code === 'USD' ? 'selected' : '' ?>><?= $code ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control">
                </div>

                <!-- Items Table -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Items</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 25%">Product/Service</th>
                                    <th style="width: 25%">Description</th>
                                    <th style="width: 10%">Quantity</th>
                                    <th style="width: 10%">Rate</th>
                                    <th style="width: 10%">Tax %</th>
                                    <th style="width: 10%">Amount</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr id="row_0">
                                    <td>1</td>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select form-select-sm" onchange="loadProduct(0)">
                                            <option value="">Select Product</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][description]" class="form-control form-control-sm"></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm" step="0.01" value="1" onchange="calculateRow(0)"></td>
                                    <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm" step="0.01" value="0" onchange="calculateRow(0)"></td>
                                    <td><input type="number" name="items[0][tax_rate]" class="form-control form-control-sm" step="0.01" value="0" onchange="calculateRow(0)"></td>
                                    <td><span class="line-total">0.00</span></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(0)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end"><strong>Subtotal:</strong></td>
                                    <td><strong id="subtotal">0.00</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end"><strong>Tax:</strong></td>
                                    <td><strong id="taxAmount">0.00</strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-end"><strong>Total:</strong></td>
                                    <td><strong id="totalAmount">0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Terms & Conditions</label>
                            <textarea name="terms" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('estimates') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Estimate</button>
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
        <td>${itemIndex + 1}</td>
        <td>
            <select name="items[${itemIndex}][product_id]" class="form-select form-select-sm" onchange="loadProduct(${itemIndex})">
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['product_code'] . ' - ' . $product['product_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm"></td>
        <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm" step="0.01" value="1" onchange="calculateRow(${itemIndex})"></td>
        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm" step="0.01" value="0" onchange="calculateRow(${itemIndex})"></td>
        <td><input type="number" name="items[${itemIndex}][tax_rate]" class="form-control form-control-sm" step="0.01" value="0" onchange="calculateRow(${itemIndex})"></td>
        <td><span class="line-total">0.00</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${itemIndex})"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(row);
    itemIndex++;
    updateRowNumbers();
}

function removeRow(index) {
    const row = document.getElementById('row_' + index);
    if (row) {
        row.remove();
        updateRowNumbers();
        calculateTotals();
    }
}

function updateRowNumbers() {
    const rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach((row, index) => {
        row.querySelector('td:first-child').textContent = index + 1;
    });
}

function loadProduct(index) {
    const select = document.querySelector(`#row_${index} select[name^="items[${index}][product_id]"]`);
    const productId = select.value;
    // TODO: Load product details via AJAX and populate fields
}

function calculateRow(index) {
    const row = document.getElementById('row_' + index);
    if (!row) return;
    
    const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    const rate = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
    const taxRate = parseFloat(row.querySelector('input[name*="[tax_rate]"]').value) || 0;
    
    const subtotal = quantity * rate;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;
    
    row.querySelector('.line-total').textContent = total.toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let totalTax = 0;
    
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
        const rate = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
        const taxRate = parseFloat(row.querySelector('input[name*="[tax_rate]"]').value) || 0;
        
        const lineSubtotal = quantity * rate;
        const lineTax = lineSubtotal * (taxRate / 100);
        
        subtotal += lineSubtotal;
        totalTax += lineTax;
    });
    
    const total = subtotal + totalTax;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('taxAmount').textContent = totalTax.toFixed(2);
    document.getElementById('totalAmount').textContent = total.toFixed(2);
}

// Initialize calculation
calculateTotals();
</script>


