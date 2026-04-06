<div class="container py-5 mt-5">
    <div class="row justify-content-center text-center">
        <div class="col-md-8">
            <div class="maintenance-icon mb-4">
                <i class="fas fa-tools fa-5x text-primary animate-bounce"></i>
            </div>
            <h1 class="display-4 fw-bold mb-3">System Under Maintenance</h1>
            <p class="lead text-muted mb-5">
                We're currently performing some important updates to improve your experience. 
                We'll be back shortly!
            </p>
            
            <div class="card border-0 shadow-sm mb-5 p-4 bg-light text-start">
                <h5 class="fw-bold"><i class="fas fa-info-circle me-2"></i> What does this mean?</h5>
                <ul class="mb-0 mt-3">
                    <li class="mb-2">Admin functions and dashboards are temporarily restricted.</li>
                    <li class="mb-2 text-success fw-bold">The Booking Portal is still available for browsing spaces and availability!</li>
                    <li>Online bookings are temporarily disabled while we update our payment and accounting systems.</li>
                </ul>
            </div>
            
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="<?= base_url('booking-wizard/step1') ?>" class="btn btn-primary btn-lg px-4 gap-3 shadow">
                    <i class="fas fa-calendar-alt me-2"></i> Browse Booking Portal
                </a>
                <a href="<?= base_url('login') ?>" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="fas fa-user-lock me-2"></i> Administrator Login
                </a>
            </div>
            
            <div class="mt-5 pt-4 border-top">
                <p class="text-muted small">
                    &copy; <?= date('Y') ?> <?= $config['company_name'] ?? 'ERP System' ?>. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.animate-bounce {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
    40% {transform: translateY(-20px);}
    60% {transform: translateY(-10px);}
}

.maintenance-icon {
    color: #4e73df;
}

h1 {
    color: #2e59d9;
}
</style>
