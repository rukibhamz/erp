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
                        <a class="nav-link active" href="#">
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
        </div>
    </div>

    <div class="text-center mb-4">
        <h1 class="display-6 fw-bold mb-3">Your Information</h1>
        <p class="lead text-muted">Please provide your contact details</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card">
                <div class="card-body p-4">
                    <form id="customer-info-form">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" class="form-control" required
                                       value="<?= htmlspecialchars($booking_data['customer_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="customer_email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="customer_email" id="customer_email" class="form-control" required
                                       autocomplete="email" inputmode="email" spellcheck="false"
                                       placeholder="name@domain.com"
                                       aria-describedby="customer_email_help customer_email_feedback"
                                       value="<?= htmlspecialchars($booking_data['customer_email'] ?? '') ?>">
                                <div id="customer_email_help" class="form-text">Format: <strong>name@domain.extension</strong> (e.g. you@gmail.com)</div>
                                <div id="customer_email_feedback" class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="customer_phone" class="form-control" required
                                       value="<?= htmlspecialchars($booking_data['customer_phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Number of Guests</label>
                                <input type="number" name="guests" class="form-control" min="1"
                                       value="<?= htmlspecialchars($booking_data['guests'] ?? '1') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="customer_address" class="form-control" rows="2"><?= htmlspecialchars($booking_data['customer_address'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Special Requests or Notes</label>
                            <textarea name="special_requests" class="form-control" rows="3" 
                                      placeholder="Any special requirements or notes for your booking..."><?= htmlspecialchars($booking_data['special_requests'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Booking Notes (Internal)</label>
                            <textarea name="notes" class="form-control" rows="2" 
                                      placeholder="Optional notes for your reference..."><?= htmlspecialchars($booking_data['notes'] ?? '') ?></textarea>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="<?= base_url('booking-wizard/step3/' . ($booking_data['resource_id'] ?? '')) ?>" class="btn btn-outline-dark">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <button type="submit" id="step4_continue_btn" class="btn btn-primary" disabled>
                                Continue to Review <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('customer-info-form');
    const emailInput = document.getElementById('customer_email');
    const emailFeedback = document.getElementById('customer_email_feedback');
    const continueBtn = document.getElementById('step4_continue_btn');
    const EMAIL_FORMAT_MSG = 'Enter a valid email as name@domain.extension (e.g. you@gmail.com).';

    /** Matches server validate_email(): name@domain.extension */
    function isValidCustomerEmail(email) {
        const e = String(email || '').trim();
        if (!e || e.length > 254) return false;
        if ((e.match(/@/g) || []).length !== 1) return false;
        const parts = e.split('@');
        const local = parts[0];
        const domain = parts[1];
        if (!local || !domain || local.length > 64) return false;
        if (domain.indexOf('.') === -1) return false;
        if (domain !== domain.replace(/^\.+|\.+$/g, '') || domain.indexOf('..') !== -1 || local.indexOf('..') !== -1) {
            return false;
        }
        const labels = domain.split('.');
        const tld = labels[labels.length - 1];
        if (!tld || tld.length < 2) return false;
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(e);
    }

    function setEmailValidity(valid, message) {
        if (!emailInput) return;
        emailInput.classList.remove('is-valid', 'is-invalid');
        if (valid) {
            emailInput.classList.add('is-valid');
            if (emailFeedback) {
                emailFeedback.textContent = '';
                emailFeedback.classList.remove('d-block');
            }
        } else {
            emailInput.classList.add('is-invalid');
            if (emailFeedback) {
                emailFeedback.textContent = message || EMAIL_FORMAT_MSG;
                emailFeedback.classList.add('d-block');
            }
        }
    }

    function updateContinueButton() {
        if (!continueBtn || !form) return;
        const emailOk = isValidCustomerEmail(emailInput ? emailInput.value : '');
        const formOk = form.checkValidity() && emailOk;
        continueBtn.disabled = !formOk;
    }

    function validateEmailLive(showEmptyHint) {
        const raw = emailInput ? emailInput.value : '';
        const trimmed = raw.trim();
        if (trimmed === '') {
            if (emailInput) {
                emailInput.classList.remove('is-valid', 'is-invalid');
            }
            if (emailFeedback) {
                emailFeedback.textContent = showEmptyHint ? 'Email is required.' : '';
                emailFeedback.classList.toggle('d-block', !!showEmptyHint);
            }
            updateContinueButton();
            return false;
        }
        const ok = isValidCustomerEmail(trimmed);
        setEmailValidity(ok, ok ? '' : EMAIL_FORMAT_MSG);
        updateContinueButton();
        return ok;
    }

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            validateEmailLive(false);
        });
        emailInput.addEventListener('blur', function() {
            validateEmailLive(true);
        });
    }

    form.querySelectorAll('input, textarea, select').forEach(function(el) {
        if (el === emailInput) return;
        el.addEventListener('input', updateContinueButton);
        el.addEventListener('change', updateContinueButton);
    });

    validateEmailLive(false);
    updateContinueButton();

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateEmailLive(true)) {
            emailInput.focus();
            return;
        }

        const formData = new FormData(form);
        const data = {};
        formData.forEach(function(value, key) {
            data[key] = value;
        });
        data.customer_email = String(data.customer_email || '').trim();

        continueBtn.disabled = true;

        fetch('<?= base_url('booking-wizard/save-step') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: wizardCsrfPrefix('step=4&data=' + encodeURIComponent(JSON.stringify(data)))
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                window.location.href = '<?= base_url('booking-wizard/step5') ?>';
                return;
            }
            const msg = result.message || 'Error saving data. Please try again.';
            if (result.message && emailInput) {
                setEmailValidity(false, msg);
                emailInput.focus();
            } else {
                alert(msg);
            }
            updateContinueButton();
        })
        .catch(function(error) {
            console.error('Error:', error);
            alert('Error saving data. Please try again.');
            updateContinueButton();
        });
    });
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
</style>

