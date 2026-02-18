<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Invoice: <?= htmlspecialchars($invoice['invoice_number'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <a href="<?= base_url('receivables/invoices/view/' . $invoice['id']) ?>" class="btn btn-primary">
                <i class="bi bi-eye"></i> View
            </a>
            <a href="<?= base_url('receivables/invoices/pdf/' . $invoice['id']) ?>" target="_blank" class="btn btn-primary">
                <i class="bi bi-file-pdf"></i> PDF
            </a>
            <a href="<?= base_url('receivables/invoices') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Invoice Details</h5>
                <div>
                    <span class="badge bg-<?= $invoice['status'] === 'paid' ? 'success' : ($invoice['status'] === 'overdue' ? 'danger' : 'info') ?>">
                        <?= ucfirst(str_replace('_', ' ', $invoice['status'])) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('receivables/invoices/edit/' . $invoice['id']) ?>" id="invoiceForm">
                    <?php echo csrf_field(); ?>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select" id="customer_id" name="customer_id" required disabled>
                                <option value="<?= $invoice['customer_id'] ?>">
                                    <?= htmlspecialchars($invoice['company_name'] ?? '-') ?>
                                </option>
                            </select>
                            <small class="text-muted">Customer cannot be changed after invoice creation</small>
                        </div>
                        <div class="col-md-2">
                            <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="<?= $invoice['invoice_date'] ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="<?= $invoice['due_date'] ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php if (!empty($currencies)): ?>
                                    <?php foreach ($currencies as $code => $name): ?>
                                        <option value="<?= $code ?>" <?= $code === ($invoice['currency'] ?? 'USD') ? 'selected' : '' ?>>
                                            <?= $code ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?= $invoice['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="sent" <?= $invoice['status'] === 'sent' ? 'selected' : '' ?>>Sent</option>
                                <option value="paid" <?= $invoice['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="partially_paid" <?= $invoice['status'] === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                                <option value="overdue" <?= $invoice['status'] === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" value="<?= htmlspecialchars($invoice['reference'] ?? '') ?>" placeholder="PO Number, etc.">
                        </div>
                        <div class="col-md-6">
                            <label for="discount_amount" class="form-label">Discount Amount</label>
                            <input type="number" step="0.01" class="form-control" id="discount_amount" name="discount_amount" value="<?= $invoice['discount_amount'] ?? 0 ?>" onchange="calculateTotals()">
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered" id="invoiceItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 35%;">Description</th>
                                    <th style="width: 10%;">Qty</th>
                                    <th style="width: 15%;" class="text-end">Unit Price</th>
                                    <th style="width: 15%;" class="text-end">Tax Rate %</th>
                                    <th style="width: 15%;" class="text-end">Line Total</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="invoiceItemsBody">
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $index => $item): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= $item['product_id'] ?? '' ?>">
                                                <input type="hidden" name="items[<?= $index ?>][account_id]" value="<?= $item['account_id'] ?? '' ?>">
                                                <input type="text" class="form-control form-control-sm" name="items[<?= $index ?>][description]" value="<?= htmlspecialchars($item['item_description']) ?>" placeholder="Item description" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm" name="items[<?= $index ?>][quantity]" value="<?= $item['quantity'] ?>" onchange="calculateLineTotal(<?= $index ?>)" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control form-control-sm text-end" name="items[<?= $index ?>][unit_price]" value="<?= $item['unit_price'] ?>" onchange="calculateLineTotal(<?= $index ?>)" required>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm text-end tax-select" name="items[<?= $index ?>][tax_rate]" onchange="calculateLineTotal(<?= $index ?>)">
                                                    <option value="0.00">None (0%)</option>
                                                    <?php if (!empty($tax_types)): ?>
                                                        <?php foreach ($tax_types as $name => $rate): ?>
                                                            <?php 
                                                                // Check if this rate matches the item's rate
                                                                $isSelected = (abs(($item['tax_rate'] ?? 0) - $rate) < 0.01);
                                                            ?>
                                                            <option value="<?= $rate ?>" <?= $isSelected ? 'selected' : '' ?>>
                                                                <?= $name ?> (<?= $rate ?>%)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </td>
                                            <td class="text-end">
                                                <span class="line-total" data-index="<?= $index ?>"><?= number_format($item['line_total'], 2) ?></span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td>1</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="items[0][description]" placeholder="Item description" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control form-control-sm" name="items[0][quantity]" value="1.00" onchange="calculateLineTotal(0)" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control form-control-sm text-end" name="items[0][unit_price]" value="0.00" onchange="calculateLineTotal(0)" required>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm text-end tax-select" name="items[0][tax_rate]" onchange="calculateLineTotal(0)">
                                                 <option value="0.00">None (0%)</option>
                                                <?php if (!empty($tax_types)): ?>
                                                    <?php foreach ($tax_types as $name => $rate): ?>
                                                        <option value="<?= $rate ?>"><?= $name ?> (<?= $rate ?>%)</option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </td>
                                        <td class="text-end">
                                            <span class="line-total" data-index="0">0.00</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                                    <td class="text-end fw-bold" id="subtotal"><?= number_format($invoice['subtotal'] ?? 0, 2) ?></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end">Tax:</td>
                                    <td class="text-end" id="taxAmount"><?= number_format($invoice['tax_amount'] ?? 0, 2) ?></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end">Discount:</td>
                                    <td class="text-end" id="discountDisplay"><?= number_format($invoice['discount_amount'] ?? 0, 2) ?></td>
                                    <td></td>
                                </tr>
                                <tr class="table-primary">
                                    <td colspan="5" class="text-end fw-bold fs-5">Total:</td>
                                    <td class="text-end fw-bold fs-5" id="totalAmount"><?= number_format($invoice['total_amount'] ?? 0, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="terms" class="form-label">Terms & Conditions</label>
                            <textarea class="form-control" id="terms" name="terms" rows="3" placeholder="Payment terms, delivery terms, etc."><?= htmlspecialchars($invoice['terms'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Internal notes"><?= htmlspecialchars($invoice['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="addItem()">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Invoice
                        </button>
                        <a href="<?= base_url('receivables/invoices/view/' . $invoice['id']) ?>" class="btn btn-secondary">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <a href="<?= base_url('receivables/invoices') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let itemIndex = <?= !empty($items) ? count($items) : 1 ?>;

// Define tax options from PHP
const taxOptions = `
    <option value="0.00">None (0%)</option>
    <?php if (!empty($tax_types)): ?>
        <?php foreach ($tax_types as $name => $rate): ?>
            <option value="<?= $rate ?>"><?= $name ?> (<?= $rate ?>%)</option>
        <?php endforeach; ?>
    <?php endif; ?>
`;

function addItem() {
    const tbody = document.getElementById('invoiceItemsBody');
    const rowCount = tbody.rows.length + 1;
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>${rowCount}</td>
        <td>
            <input type="text" class="form-control form-control-sm" name="items[${itemIndex}][description]" placeholder="Item description" required>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm" name="items[${itemIndex}][quantity]" value="1.00" onchange="calculateLineTotal(${itemIndex})" required>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm text-end" name="items[${itemIndex}][unit_price]" value="0.00" onchange="calculateLineTotal(${itemIndex})" required>
        </td>
        <td>
            <select class="form-select form-select-sm text-end tax-select" name="items[${itemIndex}][tax_rate]" onchange="calculateLineTotal(${itemIndex})">
                ${taxOptions}
            </select>
        </td>
        <td class="text-end">
            <span class="line-total" data-index="${itemIndex}">0.00</span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    itemIndex++;
    updateRowNumbers();
}

function removeItem(btn) {
    if (document.getElementById('invoiceItemsBody').rows.length > 1) {
        btn.closest('tr').remove();
        updateRowNumbers();
        calculateTotals();
    }
}

function updateRowNumbers() {
    const rows = document.getElementById('invoiceItemsBody').rows;
    for (let i = 0; i < rows.length; i++) {
        rows[i].cells[0].textContent = i + 1;
    }
}

function calculateLineTotal(index) {
    const row = document.querySelector(`[data-index="${index}"]`)?.closest('tr');
    if (!row) return;
    
    // Find inputs within the row
    const qtyInput = row.querySelector(`input[name*="[quantity]"]`);
    const priceInput = row.querySelector(`input[name*="[unit_price]"]`);
    const taxSelect = row.querySelector(`select[name*="[tax_rate]"]`);
    
    const quantity = parseFloat(qtyInput.value) || 0;
    const unitPrice = parseFloat(priceInput.value) || 0;
    const taxRate = parseFloat(taxSelect.value) || 0;
    
    const lineSubtotal = quantity * unitPrice;
    const lineTax = lineSubtotal * (taxRate / 100);
    const lineTotal = lineSubtotal + lineTax;
    
    // Update display
    row.querySelector(`.line-total`).textContent = lineTotal.toFixed(2);
    
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let totalTax = 0;
    
    // Iterate rows to sum up precise values (calculate from inputs)
    const rows = document.getElementById('invoiceItemsBody').rows;
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const qtyInput = row.querySelector(`input[name*="[quantity]"]`);
        const priceInput = row.querySelector(`input[name*="[unit_price]"]`);
        const taxSelect = row.querySelector(`select[name*="[tax_rate]"]`);
        
        if (qtyInput && priceInput && taxSelect) {
            const quantity = parseFloat(qtyInput.value) || 0;
            const unitPrice = parseFloat(priceInput.value) || 0;
            const taxRate = parseFloat(taxSelect.value) || 0;
            
            const lineSubtotal = quantity * unitPrice;
            const lineTax = lineSubtotal * (taxRate / 100);
            
            subtotal += lineSubtotal;
            totalTax += lineTax;
        }
    }
    
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const totalAmount = subtotal + totalTax - discountAmount;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('taxAmount').textContent = totalTax.toFixed(2);
    document.getElementById('discountDisplay').textContent = discountAmount.toFixed(2);
    document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
}

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
    
    // Recalculate when inputs change
    document.getElementById('invoiceForm').addEventListener('input', function(e) {
        if (e.target.classList.contains('form-control-sm') || e.target.classList.contains('form-select')) {
             const row = e.target.closest('tr');
            if (row) {
                const indexSpan = row.querySelector('.line-total');
                if (indexSpan) {
                    const index = indexSpan.getAttribute('data-index');
                    calculateLineTotal(index);
                }
            }
        }
    });
});
</script>
