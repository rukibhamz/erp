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
                        <a class="nav-link" href="<?= base_url('booking-wizard/step2/' . ($booking_data['resource_id'] ?? '')) ?>">
                            <span class="step-num">2</span>
                            <span class="step-text">DateTime</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard/step3/' . ($booking_data['resource_id'] ?? '')) ?>">
                            <span class="step-num">3</span>
                            <span class="step-text">Extras</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard/step4') ?>">
                            <span class="step-num">4</span>
                            <span class="step-text">Info</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <span class="step-num">5</span>
                            <span class="step-text">Review</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="text-center mb-4">
                <h1 class="display-6 fw-bold mb-3">Review & Payment</h1>
                <p class="lead text-muted">Please review your booking and select payment method</p>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Booking Summary -->
                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Booking Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Resource:</strong><br>
                                    <?= htmlspecialchars($resource['facility_name'] ?? '') ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Date & Time:</strong><br>
                                    <?php $isMultiDay5 = !empty($booking_data['end_date']) && ($booking_data['end_date'] ?? '') !== ($booking_data['date'] ?? ''); ?>
                                    <?php if ($isMultiDay5): ?>
                                        <strong>Start:</strong> <?= date('F j, Y', strtotime($booking_data['date'] ?? '')) ?><br>
                                        <strong>End:</strong> <?= date('F j, Y', strtotime($booking_data['end_date'])) ?><br>
                                    <?php else: ?>
                                        <?= date('F j, Y', strtotime($booking_data['date'] ?? '')) ?><br>
                                    <?php endif; ?>
                                    <?= date('g:i A', strtotime($booking_data['start_time'] ?? '')) ?> - 
                                    <?= date('g:i A', strtotime($booking_data['end_time'] ?? '')) ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Customer:</strong><br>
                                    <?= htmlspecialchars($booking_data['customer_name'] ?? '') ?><br>
                                    <?= htmlspecialchars($booking_data['customer_email'] ?? '') ?><br>
                                    <?= htmlspecialchars($booking_data['customer_phone'] ?? '') ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Guests:</strong> <?= $booking_data['guests'] ?? 1 ?>
                                </div>
                            </div>
                            <?php if (!empty($booking_data['equipment_tier'])): ?>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <strong>Equipment Tier:</strong> <?= ucfirst($booking_data['equipment_tier']) ?> Equipment
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($booking_data['special_requests'])): ?>
                                <div class="mb-3">
                                    <strong>Special Requests:</strong><br>
                                    <?= nl2br(htmlspecialchars($booking_data['special_requests'] ?? '')) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment Plan Selection -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Plan</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_plan" id="plan_full" value="full" checked>
                                <label class="form-check-label" for="plan_full">
                                    <strong>Full Payment</strong><br>
                                    <small class="text-muted">Pay the full amount now</small>
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_plan" id="plan_deposit" value="deposit">
                                <label class="form-check-label" for="plan_deposit">
                                    <strong>Deposit Payment</strong><br>
                                    <small class="text-muted">Pay 50% now, balance 7 days before booking</small>
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_plan" id="plan_installment" value="installment">
                                <label class="form-check-label" for="plan_installment">
                                    <strong>Installment Plan</strong><br>
                                    <small class="text-muted">Pay in 3 equal installments</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_plan" id="plan_pay_later" value="pay_later">
                                <label class="form-check-label" for="plan_pay_later">
                                    <strong>Pay Later</strong><br>
                                    <small class="text-muted">Pay on booking date</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Promo Code</h5>
                            <div class="input-group">
                                <input type="text" id="promo_code" class="form-control" placeholder="Enter promo code">
                                <button class="btn btn-outline-dark" type="button" id="apply-promo-btn">Apply</button>
                            </div>
                            <div id="promo-message" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Payment Summary -->
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Payment Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Resource Cost:</span>
                                <strong id="summary-resource-cost"><?= format_currency($booking_data['base_amount'] ?? 0) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Add-ons:</span>
                                <strong id="summary-addons"><?= format_currency($booking_data['addons_total'] ?? 0) ?></strong>
                            </div>
                            <?php if (!empty($booking_data['rentals_total']) && $booking_data['rentals_total'] > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Equipment Rentals:</span>
                                <strong id="summary-rentals"><?= format_currency($booking_data['rentals_total']) ?></strong>
                            </div>
                            <?php if (!empty($booking_data['rental_items_list'])): ?>
                                <div class="ps-3 mb-2 small text-muted">
                                    <?php foreach ($booking_data['rental_items_list'] as $rItem): ?>
                                        <div class="d-flex justify-content-between">
                                            <span><?= $rItem['quantity'] ?>x <?= htmlspecialchars($rItem['name']) ?></span>
                                            <span><?= format_currency($rItem['subtotal']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if (($booking_data['discount_amount'] ?? 0) > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Discount:</span>
                                    <strong>-<?= format_currency($booking_data['discount_amount'] ?? 0) ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (($booking_data['tax_amount'] ?? 0) > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (<?= number_format($booking_data['tax_rate'] ?? 0, 1) ?>%):</span>
                                    <strong><?= format_currency($booking_data['tax_amount'] ?? 0) ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (($booking_data['security_deposit'] ?? 0) > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Security Deposit:</span>
                                    <strong><?= format_currency($booking_data['security_deposit'] ?? 0) ?></strong>
                                </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong class="h5">Total:</strong>
                                <strong class="h5 text-primary" id="summary-total"><?= format_currency($booking_data['total_amount'] ?? 0) ?></strong>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <?php if (!empty($gateways)): ?>
                                        <?php foreach ($gateways as $index => $gateway): ?>
                                            <option value="gateway" data-gateway-code="<?= $gateway['gateway_code'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
                                                Pay Online - <?= htmlspecialchars($gateway['gateway_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <option value="cash" <?= empty($gateways) ? 'selected' : '' ?>>Cash (Pay on Site)</option>
                                    
                                    <?php if ($has_bank_details ?? false): ?>
                                        <option value="bank">Bank Transfer</option>
                                    <?php else: ?>
                                        <option value="bank" disabled class="text-muted">Bank Transfer (Unavailable)</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <button type="button" id="complete-booking-btn" class="btn btn-primary w-100 btn-lg">
                                <i class="bi bi-check-circle"></i> Complete Booking
                            </button>
                            
                            <small class="text-muted d-block mt-2 text-center">
                                By completing this booking, you agree to our terms and conditions.
                                <br><strong class="text-danger">Cancellation Policy: <?= htmlspecialchars(get_policy_notice('cancellation_policy_notice', '70% refund if cancelled before the booking date. No refund on the day of or after the event.')) ?></strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const promoCodeInput = document.getElementById('promo_code');
    const applyPromoBtn = document.getElementById('apply-promo-btn');
    const promoMessage = document.getElementById('promo-message');
    const completeBookingBtn = document.getElementById('complete-booking-btn');

    // Apply promo code
    applyPromoBtn.addEventListener('click', function() {
        const code = promoCodeInput.value.trim();
        if (!code) {
            promoMessage.innerHTML = '<div class="alert alert-warning">Please enter a promo code</div>';
            return;
        }

        fetch('<?= base_url('booking-wizard/validate-promo') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `csrf_token=<?= csrf_token() ?>&code=${code}&amount=<?= $booking_data['subtotal'] ?? 0 ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                promoMessage.innerHTML = '<div class="alert alert-success">Promo code applied! Discount: <?= format_currency(0) ?></div>';
                // Update totals would happen here
            } else {
                promoMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        });
    });

    // Complete booking
    completeBookingBtn.addEventListener('click', function() {
        const paymentPlan = document.querySelector('input[name="payment_plan"]:checked').value;
        const paymentMethod = document.getElementById('payment_method').value;
        const selectedOption = document.getElementById('payment_method').selectedOptions[0];
        const gatewayCode = selectedOption.dataset.gatewayCode || '';

        // Disable button to prevent double submission
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        // Submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('booking-wizard/finalize') ?>';
        
        // Add CSRF token
        form.appendChild(createInput('csrf_token', '<?= csrf_token() ?>'));
        form.appendChild(createInput('payment_plan', paymentPlan));
        form.appendChild(createInput('payment_method', paymentMethod));
        if (gatewayCode) {
            form.appendChild(createInput('gateway_code', gatewayCode));
        }
        
        document.body.appendChild(form);
        form.submit();
    });

    function createInput(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        return input;
    }
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

