<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid py-5" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-3">Book Our Facilities</h1>
            <p class="lead text-muted">Choose from our available facilities and book your event</p>
        </div>

        <div class="row g-4">
            <?php if (!empty($facilities)): ?>
                <?php foreach ($facilities as $facility): ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($facility['facility_name']) ?></h5>
                                <p class="text-muted mb-3"><?= htmlspecialchars($facility['facility_code']) ?></p>
                                
                                <?php if ($facility['description']): ?>
                                    <p class="card-text"><?= htmlspecialchars(substr($facility['description'], 0, 120)) ?><?= strlen($facility['description']) > 120 ? '...' : '' ?></p>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-people"></i> Capacity: <?= $facility['capacity'] ?><br>
                                        <i class="bi bi-clock"></i> Min: <?= $facility['minimum_duration'] ?> hour(s)
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Rates:</strong><br>
                                    <small>Hourly: <?= format_currency($facility['hourly_rate']) ?></small>
                                    <?php if ($facility['daily_rate'] > 0): ?>
                                        <br><small>Daily: <?= format_currency($facility['daily_rate']) ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="<?= base_url('booking-portal/facility/' . $facility['id']) ?>" class="btn btn-primary w-100">
                                    <i class="bi bi-calendar-plus"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        No facilities available at the moment.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

