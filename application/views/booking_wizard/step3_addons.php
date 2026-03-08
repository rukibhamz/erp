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

<script nonce="<?= csp_nonce() ?>">
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
        // Resource cost from PHP
        const resourceCost = <?= isset($resource_cost) ? floatval($resource_cost) : 0 ?>;
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

