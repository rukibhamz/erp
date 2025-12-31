<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <!-- Progress Steps -->
            <div class="mb-5">
                <ul class="nav nav-pills nav-justified">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard') ?>"><strong>Step 1:</strong> Location & Space</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard/step2/' . $resource['id']) ?>"><strong>Step 2:</strong> Date & Time</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><strong>Step 3:</strong> Extras</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 4:</strong> Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 5:</strong> Review & Pay</a>
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
                                                    <div class="input-group" style="width: 120px;">
                                                        <button class="btn btn-outline-secondary btn-sm minus-btn" type="button" data-addon-id="<?= $addon['id'] ?>">-</button>
                                                        <input type="number" class="form-control form-control-sm text-center qty-input" 
                                                               data-addon-id="<?= $addon['id'] ?>" 
                                                               data-price="<?= $addon['price'] ?>"
                                                               data-name="<?= htmlspecialchars($addon['name']) ?>"
                                                               value="0" min="0" max="99">
                                                        <button class="btn btn-outline-secondary btn-sm plus-btn" type="button" data-addon-id="<?= $addon['id'] ?>">+</button>
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
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Date:</span>
                                    <span id="summary-date"><?= $booking_data['date'] ?? 'Select date' ?></span>
                                </div>
                            </div>
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
                                    <span id="resource-cost">--</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Add-ons:</span>
                                    <span id="addons-total">₦0.00</span>
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
                    <a href="<?= base_url('booking-wizard/step2/' . $resource['id']) ?>" class="btn btn-outline-secondary">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInputs = document.querySelectorAll('.qty-input');
    const plusBtns = document.querySelectorAll('.plus-btn');
    const minusBtns = document.querySelectorAll('.minus-btn');
    const addonsTotalEl = document.getElementById('addons-total');
    const subtotalEl = document.getElementById('subtotal');
    
    let selectedAddons = {};

    // Plus button handler
    plusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const addonId = this.dataset.addonId;
            const input = document.querySelector(`.qty-input[data-addon-id="${addonId}"]`);
            const currentValue = parseInt(input.value) || 0;
            input.value = currentValue + 1;
            updateAddon(addonId, input);
        });
    });

    // Minus button handler
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

    // Quantity input handler
    qtyInputs.forEach(input => {
        input.addEventListener('change', function() {
            const addonId = this.dataset.addonId;
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

    function updateSummary() {
        let addonsTotal = 0;
        Object.values(selectedAddons).forEach(addon => {
            addonsTotal += addon.price * addon.quantity;
        });
        
        addonsTotalEl.textContent = '₦' + addonsTotal.toFixed(2);
        // Resource cost would be calculated from backend, for now show placeholder
        const resourceCost = 0; // This will be calculated
        subtotalEl.textContent = '₦' + (resourceCost + addonsTotal).toFixed(2);
    }

    // Continue button handler
    document.getElementById('continue-btn').addEventListener('click', function() {
        // Save addons to session
        fetch('<?= base_url('booking-wizard/save-step') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `step=3&data[addons]=${JSON.stringify(selectedAddons)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '<?= base_url('booking-wizard/step4') ?>';
            } else {
                alert('Error saving data. Please try again.');
            }
        });
    });

    // Initial summary update
    updateSummary();
});
</script>

<style>
.nav-pills .nav-link {
    background-color: #f8f9fa;
    color: #000;
    border: 1px solid #dee2e6;
}
.nav-pills .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}
</style>

