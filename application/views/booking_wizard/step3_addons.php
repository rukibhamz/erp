<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <!-- Progress Steps -->
            <div class="mb-4 mb-md-5">
                <ul class="nav nav-pills nav-wizard justify-content-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard') ?>">
                            <span class="step-num">1</span>
                            <span class="step-text">Location</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard/step2/' . $resource['id']) ?>">
                            <span class="step-num">2</span>
                            <span class="step-text">DateTime</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <span class="step-num">3</span>
                            <span class="step-text">Extras</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#">
                            <span class="step-num">4</span>
                            <span class="step-text">Info</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#">
                            <span class="step-num">5</span>
                            <span class="step-text">Review</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="text-center mb-4">
                <h1 class="display-6 fw-bold mb-3">Add Extras & Services</h1>
                <p class="lead text-muted">Enhance your booking with additional services</p>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <?php if (!empty($addons)): ?>
                        <div class="row g-4">
                            <?php foreach ($addons as $addon): ?>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($addon['name']) ?></h5>
                                            <p class="text-muted small"><?= htmlspecialchars($addon['description'] ?? '') ?></p>
                                            <div class="mb-3">
                                                <span class="badge bg-secondary"><?= ucfirst($addon['addon_type']) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong class="h5 text-primary"><?= format_currency($addon['price']) ?></strong>
                                                    <small class="text-muted">per unit</small>
                                                </div>
                                                <div>
                                                    <div class="qty-control d-flex align-items-center gap-1">
                                                        <button class="btn btn-dark btn-sm minus-btn" type="button" 
                                                                data-addon-id="<?= $addon['id'] ?>"
                                                                style="width:32px;height:32px;padding:0;font-size:1.1rem;line-height:1;">−</button>
                                                        <input type="number" class="form-control form-control-sm text-center qty-input" 
                                                               data-addon-id="<?= $addon['id'] ?>" 
                                                               data-price="<?= $addon['price'] ?>"
                                                               data-name="<?= htmlspecialchars($addon['name']) ?>"
                                                               value="0" min="0" max="99"
                                                               style="width:52px;height:32px;text-align:center;">
                                                        <button class="btn btn-dark btn-sm plus-btn" type="button" 
                                                                data-addon-id="<?= $addon['id'] ?>"
                                                                style="width:32px;height:32px;padding:0;font-size:1.1rem;line-height:1;">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No add-ons available for this resource.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($rentable_items)): ?>
                        <h4 class="mt-5 mb-4">Equipment Rentals</h4>
                        <div class="row g-4">
                            <?php foreach ($rentable_items as $item): ?>
                                <div class="col-md-6">
                                    <div class="card h-100 border-success">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                                            <p class="text-muted small"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                                            <div class="mb-3">
                                                <span class="badge bg-success">Rental Item</span>
                                                <span class="badge bg-light text-dark border"><i class="bi bi-box"></i> <?= $item['current_stock'] ?> Available</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong class="h5 text-success">₦<?= number_format($item['rental_rate'], 2) ?></strong>
                                                    <small class="text-muted">/<?= $item['rental_rate_type'] ?></small>
                                                </div>
                                                <div>
                                                    <div class="qty-control d-flex align-items-center gap-1">
                                                        <button class="btn btn-success btn-sm minus-rental-btn" type="button" 
                                                                data-item-id="<?= $item['id'] ?>"
                                                                style="width:32px;height:32px;padding:0;font-size:1.1rem;line-height:1;">−</button>
                                                        <input type="number" class="form-control form-control-sm text-center rental-qty-input" 
                                                               data-item-id="<?= $item['id'] ?>" 
                                                               data-price="<?= $item['rental_rate'] ?>"
                                                               data-name="<?= htmlspecialchars($item['name']) ?>"
                                                               value="0" min="0" max="<?= $item['current_stock'] ?>"
                                                               style="width:52px;height:32px;text-align:center;">
                                                        <button class="btn btn-success btn-sm plus-rental-btn" type="button" 
                                                                data-item-id="<?= $item['id'] ?>"
                                                                style="width:32px;height:32px;padding:0;font-size:1.1rem;line-height:1;">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <hr>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Resource:</span>
                                    <strong><?= htmlspecialchars($resource['facility_name']) ?></strong>
                                </div>
                            </div>
                            <?php
                            $isMultiDay = !empty($booking_data['end_date']) && ($booking_data['end_date'] ?? '') !== ($booking_data['date'] ?? '');
                            if ($isMultiDay): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Start Date:</span>
                                    <span><?= date('M j, Y', strtotime($booking_data['date'])) ?></span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>End Date:</span>
                                    <span><?= date('M j, Y', strtotime($booking_data['end_date'])) ?></span>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Date:</span>
                                    <span id="summary-date"><?= !empty($booking_data['date']) ? date('M j, Y', strtotime($booking_data['date'])) : 'Select date' ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Time:</span>
                                    <span id="summary-time"><?= ($booking_data['start_time'] ?? '') . ' - ' . ($booking_data['end_time'] ?? '') ?></span>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Resource Cost:</span>
                                    <span id="resource-cost"><?= isset($resource_cost) ? format_currency($resource_cost) : '--' ?></span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Add-ons:</span>
                                    <span id="addons-total">₦0.00</span>
                                </div>
                            </div>
                            <div class="mb-2" id="rentals-summary-row" style="display: none;">
                                <div class="d-flex justify-content-between text-success">
                                    <span>Rentals:</span>
                                    <span id="rentals-total">₦0.00</span>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Subtotal:</strong>
                                <strong id="subtotal">₦0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <a href="<?= base_url('booking-wizard/step2/' . $resource['id']) ?>" class="btn btn-outline-dark">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="button" id="continue-btn" class="btn btn-primary float-end">
                        Continue <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const qtyInputs = document.querySelectorAll('.qty-input');
    const plusBtns = document.querySelectorAll('.plus-btn');
    const minusBtns = document.querySelectorAll('.minus-btn');
    const addonsTotalEl = document.getElementById('addons-total');
    
    // Rentals
    const rentalQtyInputs = document.querySelectorAll('.rental-qty-input');
    const plusRentalBtns = document.querySelectorAll('.plus-rental-btn');
    const minusRentalBtns = document.querySelectorAll('.minus-rental-btn');
    const rentalsTotalEl = document.getElementById('rentals-total');
    const rentalsSummaryRow = document.getElementById('rentals-summary-row');
    
    const subtotalEl = document.getElementById('subtotal');
    
    let selectedAddons = {};
    let selectedRentals = {};

    // Plus Addon
    plusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const addonId = this.dataset.addonId;
            const input = document.querySelector(`.qty-input[data-addon-id="${addonId}"]`);
            const currentValue = parseInt(input.value) || 0;
            const max = parseInt(input.getAttribute('max')) || 99;
            if (currentValue < max) {
                input.value = currentValue + 1;
                updateAddon(addonId, input);
            }
        });
    });

    // Minus Addon
    minusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const addonId = this.dataset.addonId;
            const input = document.querySelector(`.qty-input[data-addon-id="${addonId}"]`);
            const currentValue = parseInt(input.value) || 0;
            if (currentValue > 0) {
                input.value = currentValue - 1;
                updateAddon(addonId, input);
            }
        });
    });

    qtyInputs.forEach(input => {
        input.addEventListener('change', function() {
            const addonId = this.dataset.addonId;
            const max = parseInt(this.getAttribute('max')) || 99;
            if (parseInt(this.value) > max) this.value = max;
            if (parseInt(this.value) < 0) this.value = 0;
            updateAddon(addonId, this);
        });
    });

    function updateAddon(addonId, input) {
        const qty = parseInt(input.value) || 0;
        if (qty > 0) {
            selectedAddons[addonId] = {
                quantity: qty,
                price: parseFloat(input.dataset.price),
                name: input.dataset.name
            };
        } else {
            delete selectedAddons[addonId];
        }
        updateSummary();
    }
    
    // Plus Rental
    plusRentalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const input = document.querySelector(`.rental-qty-input[data-item-id="${itemId}"]`);
            const currentValue = parseInt(input.value) || 0;
            const max = parseInt(input.getAttribute('max')) || 999;
            if (currentValue < max) {
                input.value = currentValue + 1;
                updateRental(itemId, input);
            }
        });
    });

    // Minus Rental
    minusRentalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const input = document.querySelector(`.rental-qty-input[data-item-id="${itemId}"]`);
            const currentValue = parseInt(input.value) || 0;
            if (currentValue > 0) {
                input.value = currentValue - 1;
                updateRental(itemId, input);
            }
        });
    });

    rentalQtyInputs.forEach(input => {
        input.addEventListener('change', function() {
            const itemId = this.dataset.itemId;
            const max = parseInt(this.getAttribute('max')) || 999;
            if (parseInt(this.value) > max) this.value = max;
            if (parseInt(this.value) < 0) this.value = 0;
            updateRental(itemId, this);
        });
    });

    function updateRental(itemId, input) {
        const qty = parseInt(input.value) || 0;
        if (qty > 0) {
            selectedRentals[itemId] = {
                quantity: qty,
                price: parseFloat(input.dataset.price),
                name: input.dataset.name
            };
        } else {
            delete selectedRentals[itemId];
        }
        updateSummary();
    }

    function updateSummary() {
        let addonsTotal = 0;
        Object.values(selectedAddons).forEach(addon => {
            addonsTotal += addon.price * addon.quantity;
        });
        
        let rentalsTotal = 0;
        Object.values(selectedRentals).forEach(item => {
            rentalsTotal += item.price * item.quantity;
        });
        
        addonsTotalEl.textContent = '₦' + addonsTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        if (rentalsTotal > 0) {
            rentalsTotalEl.textContent = '₦' + rentalsTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            rentalsSummaryRow.style.display = 'block';
        } else {
            rentalsSummaryRow.style.display = 'none';
        }
        
        // Resource cost from PHP
        const resourceCost = <?= isset($resource_cost) ? floatval($resource_cost) : 0 ?>;
        subtotalEl.textContent = '₦' + (resourceCost + addonsTotal + rentalsTotal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Continue button handler
    document.getElementById('continue-btn').addEventListener('click', function() {
        // Save addons and rentals to session
        let bodyPayload = `step=3&data[addons]=${encodeURIComponent(JSON.stringify(selectedAddons))}`;
        
        // Also save rentals mapping format expects direct value like: rental_items[ID]=QTY
        // But for consistency we can also pass it as a JSON string, then backend decodes it
        // Or we pass object with quantity since that's what the controller expects for addons?
        // Let's look at controller: $rentalItemsData = $bookingData['rental_items'] ?? [];
        // The wizard controller expects an array of item_id => qty, same as addons
        const rentalDataClean = {};
        for(let id in selectedRentals) {
            rentalDataClean[id] = selectedRentals[id].quantity;
        }
        bodyPayload += `&data[rental_items]=${encodeURIComponent(JSON.stringify(rentalDataClean))}`;
        
        // Also clean addons format to what backend expects if needed?
        // Wait, step 3 controller expects addons like: array key => quantity
        const addonDataClean = {};
        for(let id in selectedAddons) {
            addonDataClean[id] = selectedAddons[id].quantity;
        }
        // Actually modify payload to format backend expects
        bodyPayload = `step=3&data[addons]=${encodeURIComponent(JSON.stringify(addonDataClean))}&data[rental_items]=${encodeURIComponent(JSON.stringify(rentalDataClean))}`;
        
        fetch('<?= base_url('booking-wizard/save-step') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: bodyPayload
        })
        .then(response => {
            const status = response.status;
            return response.text().then(text => ({ text, status }));
        })
        .then(({ text, status }) => {
            if (status >= 500) {
                alert('Server error (' + status + '). Please try again later.');
                return;
            }
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    window.location.href = '<?= base_url('booking-wizard/step4') ?>';
                } else {
                    alert('Error saving data. Please try again.');
                }
            } catch (e) {
                alert('Invalid server response.');
            }
        });
    });

    // Initial summary update
    updateSummary();
});
</script>

<style>
/* Responsive Wizard Navigation */
.nav-wizard {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    -webkit-overflow-scrolling: touch;
    gap: 0.5rem;
}
.nav-wizard::-webkit-scrollbar {
    height: 4px;
}
.nav-wizard::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 4px;
}
.nav-wizard .nav-item {
    flex: 0 0 auto;
}
.nav-wizard .nav-link {
    background-color: #f8f9fa;
    color: #4b5563;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 8px;
    font-size: 0.875rem;
    white-space: nowrap;
}
.nav-wizard .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}
.nav-wizard .step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: rgba(0,0,0,0.1);
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.75rem;
}
.nav-wizard .nav-link.active .step-num {
    background: rgba(255,255,255,0.2);
}
@media (max-width: 576px) {
    .nav-wizard .step-text {
        display: none;
    }
    .nav-wizard .nav-link {
        padding: 0.5rem;
    }
    .display-6 {
        font-size: 1.5rem;
    }
}

@media (max-width: 991.98px) {
    .card.sticky-top {
        position: static !important;
        margin-top: 2rem;
    }
}
</style>

