<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Progress Steps -->
            <div class="mb-5">
                <ul class="nav nav-pills nav-justified">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard') ?>"><strong>Step 1:</strong> Select Resource</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><strong>Step 2:</strong> Date & Time</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><strong>Step 3:</strong> Add Extras</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><strong>Step 4:</strong> Your Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 5:</strong> Review & Pay</a>
                    </li>
                </ul>
            </div>

            <div class="text-center mb-4">
                <h1 class="display-6 fw-bold mb-3">Your Information</h1>
                <p class="lead text-muted">Please provide your contact details</p>
            </div>

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
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="customer_email" class="form-control" required
                                       value="<?= htmlspecialchars($booking_data['customer_email'] ?? $booking_data['customer_email'] ?? '') ?>">
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
                            <a href="<?= base_url('booking-wizard/step3/' . ($booking_data['resource_id'] ?? '')) ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continue to Review <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('customer-info-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Save customer info to session
        fetch('<?= base_url('booking-wizard/save-step') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `step=4&data=${encodeURIComponent(JSON.stringify(data))}`
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.location.href = '<?= base_url('booking-wizard/step5') ?>';
            } else {
                alert('Error saving data. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving data. Please try again.');
        });
    });
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

