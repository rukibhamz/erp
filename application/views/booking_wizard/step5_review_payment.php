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
                        <a class="nav-link" href="<?= base_url('booking-wizard/step2/' . ($booking_data['resource_id'] ?? '')) ?>"><strong>Step 2:</strong> Date & Time</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard/step3/' . ($booking_data['resource_id'] ?? '')) ?>"><strong>Step 3:</strong> Extras</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard/step4') ?>"><strong>Step 4:</strong> Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><strong>Step 5:</strong> Review & Pay</a>
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
                                    <?= date('F j, Y', strtotime($booking_data['date'] ?? '')) ?><br>
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
                                <button class="btn btn-outline-secondary" type="button" id="apply-promo-btn">Apply</button>
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
                            <?php if (($booking_data['discount_amount'] ?? 0) > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Discount:</span>
                                    <strong>-<?= format_currency($booking_data['discount_amount'] ?? 0) ?></strong>
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
                                    <option value="cash">Cash (Pay on Site)</option>
                                    <option value="bank">Bank Transfer</option>
                                    <?php if (!empty($gateways)): ?>
                                        <?php foreach ($gateways as $gateway): ?>
                                            <option value="gateway" data-gateway-code="<?= $gateway['gateway_code'] ?>">
                                                Pay Online - <?= htmlspecialchars($gateway['gateway_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <button type="button" id="complete-booking-btn" class="btn btn-primary w-100 btn-lg">
                                <i class="bi bi-check-circle"></i> Complete Booking
                            </button>
                            
                            <small class="text-muted d-block mt-2 text-center">
                                By completing this booking, you agree to our terms and conditions
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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

