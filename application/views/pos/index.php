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
            <a href="<?= base_url('pos/reports') ?>" class="btn btn-primary btn-sm">
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
        <script nonce="<?= csp_nonce() ?>">
            // Store items globally for JS access
            const itemsList = <?= json_encode($items) ?>;
            const bookingsList = <?= json_encode($recent_bookings ?? []) ?>;
            const csrfToken = '<?= csrf_token() ?>';
        </script>
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
                                <div class="card item-card" data-item-id="<?= $item['id'] ?>" data-item-name="<?= htmlspecialchars($item['item_name'] ?? $item['name'] ?? '') ?>" data-item-price="<?= $item['selling_price'] ?? 0 ?>">
                                    <div class="card-body text-center p-2">
                                        <div class="fw-bold small"><?= htmlspecialchars(substr($item['item_name'] ?? $item['name'] ?? '', 0, 20)) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($item['sku'] ?? $item['item_code'] ?? '') ?></div>
                                        <div class="text-primary fw-bold mt-1"><?= format_currency($item['selling_price'] ?? 0) ?></div>
                                        <?php if (($item['is_wholesale_enabled'] ?? 0) == 1): ?>
                                            <div class="badge bg-info p-1 mt-1" style="font-size: 0.65rem;">
                                                WS: <?= format_currency($item['wholesale_price']) ?> (min <?= $item['wholesale_moq'] ?>)
                                            </div>
                                        <?php endif; ?>
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
                    <button class="btn btn-sm btn-primary" id="clearCartBtn">Clear</button>
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
                        <label class="form-label">Linked Booking (Add-on Sale)</label>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <input type="text" class="form-control form-control-sm" id="bookingSearch" placeholder="Filter code/customer...">
                            </div>
                            <div class="col-6">
                                <select class="form-select form-select-sm" id="bookingSort">
                                    <option value="date_desc">Date: Newest</option>
                                    <option value="date_asc">Date: Oldest</option>
                                    <option value="code_asc">Code: A-Z</option>
                                    <option value="code_desc">Code: Z-A</option>
                                    <option value="customer_asc">Customer: A-Z</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <select class="form-select form-select-sm" id="bookingPaymentFilter">
                                    <option value="">All payment statuses</option>
                                    <option value="paid">Paid</option>
                                    <option value="partial">Partial</option>
                                    <option value="unpaid">Unpaid</option>
                                    <option value="overpaid">Overpaid</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control form-control-sm" id="bookingDateFrom" title="From date">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control form-control-sm" id="bookingDateTo" title="To date">
                            </div>
                        </div>
                        <select class="form-select" id="bookingSelect">
                            <option value="">Walk-in / No booking</option>
                        </select>
                        <small class="text-muted">Filter and sort by booking code, date, customer, or payment status.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount Paid</label>
                        <input type="number" id="amountPaid" class="form-control form-control-lg" value="0" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3" id="changeDisplay" style="display: none;">
                        <label class="form-label">Change</label>
                        <div class="h5 mb-0 text-success" id="changeAmount"><?= format_currency(0) ?></div>
                    </div>
                    
                    <button class="btn btn-dark btn-lg w-100" id="processBtn" disabled>
                        <i class="bi bi-check-circle"></i> Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
let cart = [];

// Event delegation for item clicks
document.addEventListener('DOMContentLoaded', function() {
    const itemsGrid = document.getElementById('itemsGrid');
    if (itemsGrid) {
        itemsGrid.addEventListener('click', function(e) {
            const itemCard = e.target.closest('.item-card');
            if (itemCard) {
                const itemId = parseInt(itemCard.dataset.itemId);
                const itemName = itemCard.dataset.itemName;
                const itemPrice = parseFloat(itemCard.dataset.itemPrice);
                if (itemId && itemName !== undefined) {
                    addToCart(itemId, itemName, itemPrice);
                }
            }
        });
    }
});

function addToCart(itemId, itemName, price) {
    const existing = cart.find(i => i.item_id === itemId);
    if (existing) {
        existing.quantity += 1;
    } else {
        // Find item details to check wholesale
        const item = itemsList.find(i => i.id === itemId);
        let currentPrice = price;
        
        cart.push({
            item_id: itemId,
            item_name: itemName,
            quantity: 1,
            price: currentPrice,
            retail_price: price,
            wholesale_price: item ? item.wholesale_price : price,
            wholesale_moq: item ? item.wholesale_moq : 0,
            is_wholesale_enabled: item ? item.is_wholesale_enabled : 0,
            tax_rate: <?= $default_vat_rate ?? 7.5 ?>
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
            // Apply wholesale price in client side if MOQ met
            if (item.is_wholesale_enabled == 1 && item.quantity >= item.wholesale_moq) {
                item.price = item.wholesale_price;
            } else {
                item.price = item.retail_price;
            }
            
            const lineTotal = item.price * item.quantity;
            subtotal += lineTotal;
            html += `
                <tr>
                    <td>${item.item_name}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm cart-qty-input" 
                               value="${item.quantity}" min="1" 
                               data-index="${index}"
                               style="width: 70px;">
                    </td>
                    <td>${formatCurrency(item.price)}</td>
                    <td>${formatCurrency(lineTotal)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger cart-remove-btn" data-index="${index}">
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
        
        document.getElementById('processBtn').disabled = cart.length === 0;
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
    formData.append('booking_id', document.getElementById('bookingSelect')?.value || '');
    formData.append('ajax', '1');
    formData.append('csrf_token', csrfToken);
    
    fetch('<?= base_url('pos/process') ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
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

// Clear cart button
document.getElementById('clearCartBtn')?.addEventListener('click', function() {
    clearCart();
});

// Process sale button
document.getElementById('processBtn')?.addEventListener('click', function() {
    processSale();
});

// Event delegation for cart remove buttons and qty inputs
document.getElementById('cartTable')?.addEventListener('click', function(e) {
    const removeBtn = e.target.closest('.cart-remove-btn');
    if (removeBtn) {
        removeFromCart(parseInt(removeBtn.dataset.index));
    }
});

document.getElementById('cartTable')?.addEventListener('change', function(e) {
    const qtyInput = e.target.closest('.cart-qty-input');
    if (qtyInput) {
        updateQuantity(parseInt(qtyInput.dataset.index), parseInt(qtyInput.value));
    }
});

function switchTerminal(terminalId) {
    window.location.href = '<?= base_url('pos') ?>?terminal=' + terminalId;
}

function normalizeBookingDate(dateValue) {
    if (!dateValue) return null;
    const parsed = new Date(dateValue);
    if (!isNaN(parsed.getTime())) return parsed;
    return null;
}

function normalizeDateOnly(dateValue) {
    if (!dateValue) return null;
    const parsed = new Date(dateValue + 'T00:00:00');
    if (isNaN(parsed.getTime())) return null;
    parsed.setHours(0, 0, 0, 0);
    return parsed;
}

function formatBookingDate(dateValue) {
    const parsed = normalizeBookingDate(dateValue);
    if (!parsed) return 'No date';
    return parsed.toLocaleDateString('en-GB');
}

function renderBookingOptions() {
    const select = document.getElementById('bookingSelect');
    if (!select) return;

    const searchValue = (document.getElementById('bookingSearch')?.value || '').trim().toLowerCase();
    const sortValue = document.getElementById('bookingSort')?.value || 'date_desc';
    const paymentFilter = (document.getElementById('bookingPaymentFilter')?.value || '').trim().toLowerCase();
    const fromDateValue = document.getElementById('bookingDateFrom')?.value || '';
    const toDateValue = document.getElementById('bookingDateTo')?.value || '';
    const currentValue = select.value || '';
    const fromDate = normalizeDateOnly(fromDateValue);
    const toDate = normalizeDateOnly(toDateValue);

    let filtered = (bookingsList || []).filter(booking => {
        const code = String(booking.booking_number || '').toLowerCase();
        const customer = String(booking.customer_name || '').toLowerCase();
        const paymentStatus = String(booking.payment_status || 'unpaid').toLowerCase();
        const bookingDate = String(booking.booking_date || '').toLowerCase();
        const bookingDateObj = normalizeDateOnly(String(booking.booking_date || ''));

        const matchesSearch = !searchValue || code.includes(searchValue) || customer.includes(searchValue) || bookingDate.includes(searchValue);
        const matchesPayment = !paymentFilter || paymentStatus === paymentFilter;
        const matchesFromDate = !fromDate || (bookingDateObj && bookingDateObj >= fromDate);
        const matchesToDate = !toDate || (bookingDateObj && bookingDateObj <= toDate);
        return matchesSearch && matchesPayment && matchesFromDate && matchesToDate;
    });

    filtered.sort((a, b) => {
        const codeA = String(a.booking_number || '');
        const codeB = String(b.booking_number || '');
        const customerA = String(a.customer_name || '');
        const customerB = String(b.customer_name || '');
        const dateA = normalizeBookingDate(a.booking_date);
        const dateB = normalizeBookingDate(b.booking_date);
        const timeA = dateA ? dateA.getTime() : 0;
        const timeB = dateB ? dateB.getTime() : 0;

        switch (sortValue) {
            case 'date_asc':
                return timeA - timeB;
            case 'code_asc':
                return codeA.localeCompare(codeB);
            case 'code_desc':
                return codeB.localeCompare(codeA);
            case 'customer_asc':
                return customerA.localeCompare(customerB);
            case 'date_desc':
            default:
                return timeB - timeA;
        }
    });

    let optionsHtml = '<option value="">Walk-in / No booking</option>';
    filtered.forEach(booking => {
        const id = Number(booking.id || 0);
        const code = String(booking.booking_number || ('Booking #' + id));
        const customer = String(booking.customer_name || 'Customer');
        const dateLabel = formatBookingDate(booking.booking_date);
        const paymentStatus = String(booking.payment_status || 'unpaid');
        optionsHtml += `<option value="${id}">${escapeHtml(code)} - ${escapeHtml(customer)} | ${escapeHtml(dateLabel)} (${escapeHtml(paymentStatus)})</option>`;
    });

    select.innerHTML = optionsHtml;
    if (currentValue) {
        select.value = currentValue;
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.getElementById('bookingSearch')?.addEventListener('input', renderBookingOptions);
document.getElementById('bookingSort')?.addEventListener('change', renderBookingOptions);
document.getElementById('bookingPaymentFilter')?.addEventListener('change', renderBookingOptions);
document.getElementById('bookingDateFrom')?.addEventListener('change', renderBookingOptions);
document.getElementById('bookingDateTo')?.addEventListener('change', renderBookingOptions);
renderBookingOptions();
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



