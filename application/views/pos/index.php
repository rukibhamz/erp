<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="pos-container" style="background: #f8f9fa; min-height: 100vh; padding: 1rem;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Point of Sale</h2>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="terminalSelect" onchange="switchTerminal(this.value)" style="width: 200px;">
                <?php foreach ($terminals as $term): ?>
                    <option value="<?= $term['id'] ?>" <?= $term['id'] == $terminal_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($term['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (in_array($current_user['role'] ?? '', ['super_admin', 'admin'])): ?>
                <a href="<?= base_url('pos/terminals') ?>" class="btn btn-dark btn-sm">
                    <i class="bi bi-gear"></i> Manage Terminals
                </a>
            <?php endif; ?>
            <a href="<?= base_url('pos/reports') ?>" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-graph-up"></i> Reports
            </a>
        </div>
    </div>

    <?php if (isset($flash) && $flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Left Panel - Items & Cart -->
        <div class="col-md-8">
            <!-- Quick Item Search -->
            <div class="card mb-3">
                <div class="card-body">
                    <input type="text" id="itemSearch" class="form-control form-control-lg" 
                           placeholder="Search items by name or code..." autofocus>
                </div>
            </div>
            
            <!-- Item Grid -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">Items</h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <div class="row g-2" id="itemsGrid">
                        <?php foreach ($items as $item): ?>
                            <div class="col-md-3 col-sm-4 col-6">
                                <div class="card item-card" onclick="addToCart(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['selling_price'] ?? 0 ?>)">
                                    <div class="card-body text-center p-2">
                                        <div class="fw-bold small"><?= htmlspecialchars(substr($item['name'], 0, 20)) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($item['item_code'] ?? '') ?></div>
                                        <div class="text-primary fw-bold mt-1"><?= format_currency($item['selling_price'] ?? 0) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Cart -->
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <h6 class="mb-0">Cart</h6>
                    <button class="btn btn-sm btn-light" onclick="clearCart()">Clear</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cartTable">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Cart is empty</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Payment -->
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 90px;">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">Payment</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Subtotal</label>
                        <div class="h4 mb-0" id="cartSubtotal"><?= format_currency(0) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount</label>
                        <div class="input-group">
                            <input type="number" id="discountAmount" class="form-control" value="0" step="0.01" min="0">
                            <select class="form-select" id="discountType" style="max-width: 100px;">
                                <option value="fixed">Amount</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">VAT (<?= number_format($default_vat_rate ?? 7.5, 2) ?>%)</label>
                        <div class="h5 mb-0 text-muted" id="cartTax"><?= format_currency(0) ?></div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label"><strong>Total</strong></label>
                        <div class="h3 mb-0 text-primary" id="cartTotal"><?= format_currency(0) ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="transfer">Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount Paid</label>
                        <input type="number" id="amountPaid" class="form-control form-control-lg" value="0" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3" id="changeDisplay" style="display: none;">
                        <label class="form-label">Change</label>
                        <div class="h5 mb-0 text-success" id="changeAmount"><?= format_currency(0) ?></div>
                    </div>
                    
                    <button class="btn btn-dark btn-lg w-100" onclick="processSale()" id="processBtn" disabled>
                        <i class="bi bi-check-circle"></i> Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

function addToCart(itemId, itemName, price) {
    const existing = cart.find(i => i.item_id === itemId);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            item_id: itemId,
            item_name: itemName,
            quantity: 1,
            price: price,
            tax_rate: <?= $default_vat_rate ?? 7.5 ?> // Auto VAT rate
        });
    }
    updateCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
}

function updateQuantity(index, quantity) {
    if (quantity <= 0) {
        removeFromCart(index);
    } else {
        cart[index].quantity = quantity;
        updateCart();
    }
}

function clearCart() {
    if (cart.length > 0 && confirm('Clear cart?')) {
        cart = [];
        updateCart();
    }
}

function updateCart() {
    const tbody = document.getElementById('cartTable');
    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Cart is empty</td></tr>';
        document.getElementById('processBtn').disabled = true;
    } else {
        let html = '';
        let subtotal = 0;
        
        cart.forEach((item, index) => {
            const lineTotal = item.price * item.quantity;
            subtotal += lineTotal;
            html += `
                <tr>
                    <td>${item.item_name}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm" 
                               value="${item.quantity}" min="1" 
                               onchange="updateQuantity(${index}, parseInt(this.value))" 
                               style="width: 70px;">
                    </td>
                    <td>${formatCurrency(item.price)}</td>
                    <td>${formatCurrency(lineTotal)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
        
        const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
        const discountType = document.getElementById('discountType').value;
        let discountAmount = discount;
        if (discountType === 'percentage') {
            discountAmount = subtotal * (discount / 100);
        }
        
        // Calculate VAT automatically from cart items (before discount)
        let tax = 0;
        cart.forEach(item => {
            const lineTotal = item.price * item.quantity;
            const lineTax = lineTotal * ((item.tax_rate || <?= $default_vat_rate ?? 7.5 ?>) / 100);
            tax += lineTax;
        });
        
        // Apply discount to subtotal, then recalculate VAT on discounted amount
        const discountedSubtotal = subtotal - discountAmount;
        // Recalculate VAT on discounted amount (standard practice)
        const vatRate = <?= $default_vat_rate ?? 7.5 ?> / 100;
        tax = discountedSubtotal * vatRate;
        
        const total = discountedSubtotal + tax;
        
        document.getElementById('cartSubtotal').textContent = formatCurrency(subtotal);
        document.getElementById('cartTax').textContent = formatCurrency(tax);
        document.getElementById('cartTotal').textContent = formatCurrency(total);
        
        // Update change
        const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
        if (amountPaid > 0) {
            const change = amountPaid - total;
            document.getElementById('changeAmount').textContent = formatCurrency(change);
            document.getElementById('changeDisplay').style.display = change > 0 ? 'block' : 'none';
        }
        
        document.getElementById('processBtn').disabled = total <= 0;
    }
}

function processSale() {
    if (cart.length === 0) {
        alert('Cart is empty');
        return;
    }
    
    const formData = new FormData();
    formData.append('terminal_id', <?= $terminal_id ?>);
    formData.append('items', JSON.stringify(cart));
    formData.append('payment_method', document.getElementById('paymentMethod').value);
    formData.append('amount_paid', document.getElementById('amountPaid').value);
    formData.append('discount_amount', document.getElementById('discountAmount').value);
    formData.append('discount_type', document.getElementById('discountType').value);
    formData.append('ajax', '1');
    
    fetch('<?= base_url('pos/process') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?= base_url('pos/receipt') ?>/' + data.sale_id;
        } else {
            alert('Error: ' + (data.message || 'Failed to process sale'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error processing sale');
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2
    }).format(amount);
}

// Search functionality
document.getElementById('itemSearch')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.item-card');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.closest('.col-md-3').style.display = text.includes(search) ? '' : 'none';
    });
});

// Calculate change on amount paid change
document.getElementById('amountPaid')?.addEventListener('input', function() {
    updateCart();
});

document.getElementById('discountAmount')?.addEventListener('input', function() {
    updateCart();
});

document.getElementById('discountType')?.addEventListener('change', function() {
    updateCart();
});

function switchTerminal(terminalId) {
    window.location.href = '<?= base_url('pos') ?>?terminal=' + terminalId;
}
</script>

<style>
.item-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #dee2e6;
}

.item-card:hover {
    border-color: #000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pos-container {
    font-family: 'Poppins', sans-serif;
}
</style>



