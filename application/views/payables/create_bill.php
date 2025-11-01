<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Create Bill</h1>
</div>

<!-- Accounting Navigation -->
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('accounting') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="<?= base_url('accounts') ?>">
            <i class="bi bi-diagram-3"></i> Chart of Accounts
        </a>
        <a class="nav-link" href="<?= base_url('cash') ?>">
            <i class="bi bi-wallet2"></i> Cash Management
        </a>
        <a class="nav-link" href="<?= base_url('receivables') ?>">
            <i class="bi bi-receipt"></i> Receivables
        </a>
        <a class="nav-link active" href="<?= base_url('payables') ?>">
            <i class="bi bi-file-earmark-medical"></i> Payables
        </a>
        <a class="nav-link" href="<?= base_url('ledger') ?>">
            <i class="bi bi-journal-text"></i> General Ledger
        </a>
    </nav>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Bill Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('payables/bills/create') ?>" id="billForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                            <select class="form-select" id="vendor_id" name="vendor_id" required>
                                <option value="">Select Vendor</option>
                                <?php if (!empty($vendors)): ?>
                                    <?php foreach ($vendors as $vendor): ?>
                                        <option value="<?= $vendor['id'] ?>">
                                            <?= htmlspecialchars($vendor['company_name']) ?> (<?= htmlspecialchars($vendor['vendor_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="bill_date" class="form-label">Bill Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="bill_date" name="bill_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php if (!empty($currencies)): ?>
                                    <?php foreach ($currencies as $code => $name): ?>
                                        <option value="<?= $code ?>" <?= $code === 'USD' ? 'selected' : '' ?>>
                                            <?= $code ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="received">Received</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" placeholder="PO Number, etc.">
                        </div>
                        <div class="col-md-3">
                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" value="0.00" onchange="calculateTotals()">
                        </div>
                        <div class="col-md-3">
                            <label for="discount_amount" class="form-label">Discount Amount</label>
                            <input type="number" step="0.01" class="form-control" id="discount_amount" name="discount_amount" value="0.00" onchange="calculateTotals()">
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered" id="billItemsTable">
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
                            <tbody id="billItemsBody">
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
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end" name="items[0][tax_rate]" value="0.00" onchange="calculateLineTotal(0)">
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
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                                    <td class="text-end fw-bold" id="subtotal">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end">Tax:</td>
                                    <td class="text-end" id="taxAmount">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end">Discount:</td>
                                    <td class="text-end" id="discountDisplay">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold fs-5">Total:</td>
                                    <td class="text-end fw-bold fs-5" id="totalAmount">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="terms" class="form-label">Terms & Conditions</label>
                            <textarea class="form-control" id="terms" name="terms" rows="3" placeholder="Payment terms, delivery terms, etc."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Internal notes"></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="addItem()">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Bill
                        </button>
                        <a href="<?= base_url('payables/bills') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let itemIndex = 1;

function addItem() {
    const tbody = document.getElementById('billItemsBody');
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
            <input type="number" step="0.01" class="form-control form-control-sm text-end" name="items[${itemIndex}][tax_rate]" value="0.00" onchange="calculateLineTotal(${itemIndex})">
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
    if (document.getElementById('billItemsBody').rows.length > 1) {
        btn.closest('tr').remove();
        updateRowNumbers();
        calculateTotals();
    }
}

function updateRowNumbers() {
    const rows = document.getElementById('billItemsBody').rows;
    for (let i = 0; i < rows.length; i++) {
        rows[i].cells[0].textContent = i + 1;
    }
}

function calculateLineTotal(index) {
    const row = document.querySelector(`[data-index="${index}"]`)?.closest('tr');
    if (!row) return;
    
    const quantity = parseFloat(row.querySelector(`input[name="items[${index}][quantity]"]`).value) || 0;
    const unitPrice = parseFloat(row.querySelector(`input[name="items[${index}][unit_price]"]`).value) || 0;
    const lineTotal = quantity * unitPrice;
    
    row.querySelector(`[data-index="${index}"]`).textContent = lineTotal.toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    
    document.querySelectorAll('.line-total').forEach(span => {
        subtotal += parseFloat(span.textContent) || 0;
    });
    
    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const taxAmount = subtotal * (taxRate / 100);
    const totalAmount = subtotal + taxAmount - discountAmount;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('taxAmount').textContent = taxAmount.toFixed(2);
    document.getElementById('discountDisplay').textContent = discountAmount.toFixed(2);
    document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
}

document.getElementById('billForm').addEventListener('input', function(e) {
    if (e.target.classList.contains('line-input')) {
        calculateTotals();
    }
});
</script>

<style>
.accounting-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.accounting-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.accounting-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.accounting-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.accounting-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

