<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Purchase Order</h1>
        <a href="<?= base_url('inventory/purchase-orders') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/inventory/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('inventory/purchase-orders/create') ?>
            <?php echo csrf_field(); ?>"  id="poForm">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select class="form-select" id="supplier_id" name="supplier_id" required>
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>">
                                <?= htmlspecialchars($supplier['supplier_name']) ?> (<?= htmlspecialchars($supplier['supplier_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="order_date" name="order_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label for="expected_date" class="form-label">Expected Date</label>
                    <input type="date" class="form-control" id="expected_date" name="expected_date">
                </div>
                
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                </div>
            </div>
            
            <!-- Items Table -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Items</h5>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow()">
                        <i class="bi bi-plus-circle"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Line Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr class="item-row">
                                    <td>
                                        <select class="form-select form-select-sm item-select" name="items[0][item_id]" required onchange="updateItemInfo(this)">
                                            <option value="">Select Item</option>
                                            <?php foreach ($items as $item): ?>
                                                <option value="<?= $item['id'] ?>" data-sku="<?= htmlspecialchars($item['sku']) ?>" <?= ($selected_item_id ?? null) == $item['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($item['sku']) ?> - <?= htmlspecialchars($item['item_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="items[0][quantity]" required min="0.01" oninput="calculateLineTotal(this)" value="1">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="items[0][unit_price]" required min="0" oninput="calculateLineTotal(this)" value="0">
                                    </td>
                                    <td>
                                        <span class="line-total">₦0.00</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)" style="display: none;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th id="grandTotal">₦0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('inventory/purchase-orders') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let itemRowIndex = 1;

function addItemRow() {
    const tbody = document.getElementById('itemsTableBody');
    const row = document.createElement('tr');
    row.className = 'item-row';
    
    const itemsOptions = <?= json_encode(array_map(function($item) {
        return ['id' => $item['id'], 'sku' => $item['sku'], 'name' => $item['item_name']];
    }, $items)) ?>;
    
    let optionsHtml = '<option value="">Select Item</option>';
    itemsOptions.forEach(item => {
        optionsHtml += `<option value="${item.id}" data-sku="${item.sku}">${item.sku} - ${item.name}</option>`;
    });
    
    row.innerHTML = `
        <td>
            <select class="form-select form-select-sm item-select" name="items[${itemRowIndex}][item_id]" required onchange="updateItemInfo(this)">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm" name="items[${itemRowIndex}][quantity]" required min="0.01" oninput="calculateLineTotal(this)" value="1">
        </td>
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm" name="items[${itemRowIndex}][unit_price]" required min="0" oninput="calculateLineTotal(this)" value="0">
        </td>
        <td>
            <span class="line-total">₦0.00</span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    
    // Show remove buttons if more than one row
    if (tbody.children.length > 1) {
        Array.from(tbody.querySelectorAll('.btn-outline-danger')).forEach(btn => {
            btn.style.display = '';
        });
    }
    
    itemRowIndex++;
}

function removeItemRow(btn) {
    const row = btn.closest('tr');
    row.remove();
    calculateGrandTotal();
    
    // Hide remove buttons if only one row left
    const tbody = document.getElementById('itemsTableBody');
    if (tbody.children.length === 1) {
        Array.from(tbody.querySelectorAll('.btn-outline-danger')).forEach(b => {
            b.style.display = 'none';
        });
    }
}

function calculateLineTotal(input) {
    const row = input.closest('tr');
    const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
    const total = quantity * unitPrice;
    row.querySelector('.line-total').textContent = '₦' + total.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
        const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
        total += quantity * unitPrice;
    });
    document.getElementById('grandTotal').textContent = '₦' + total.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function updateItemInfo(select) {
    // Can be enhanced to load item details
}
</script>

