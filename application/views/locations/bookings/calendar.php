<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Booking Calendar</h1>
        <a href="<?= base_url('locations/bookings') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Bookings
        </a>
    </div>
</div>

<!-- Location Management Navigation -->
<div class="Location-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('locations') ?>">
            <i class="bi bi-building"></i> Locations
        </a>
        <a class="nav-link" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link active" href="<?= base_url('locations/bookings') ?>">
            <i class="bi bi-calendar-check"></i> Bookings
        </a>
        <a class="nav-link" href="<?= base_url('rent-invoices') ?>">
            <i class="bi bi-receipt"></i> Rent Invoices
        </a>
    </nav>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="location_filter" class="form-label">Select Location</label>
                <select name="location_id" id="location_filter" class="form-select" onchange="loadSpaces(this.value)">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['id'] ?>" <?= ($selected_location && $selected_location['id'] == $loc['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc['Location_name'] ?? $loc['property_name'] ?? 'N/A') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="space_filter" class="form-label">Select Space</label>
                <select name="space_id" id="space_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">All Spaces</option>
                    <?php foreach ($spaces as $space): ?>
                        <option value="<?= $space['id'] ?>" <?= ($selected_space && $selected_space['id'] == $space['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($space['space_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <?php if ($selected_space): ?>
                    <a href="<?= base_url('locations/create-booking' . ($selected_location ? '/' . $selected_location['id'] : '') . '/' . $selected_space['id']) ?>" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> Book This Space
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_space): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-calendar-month"></i> 
                Availability Calendar: <?= htmlspecialchars($selected_space['space_name']) ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($bookings)): ?>
                <div class="mb-4">
                    <h6>Upcoming Bookings (Next 30 Days)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                        <td>
                                            <?= date('g:i A', strtotime($booking['start_time'])) ?> - 
                                            <?= date('g:i A', strtotime($booking['end_time'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($booking['customer_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($booking['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($booking['id'])): ?>
                                                <a href="<?= base_url('locations/view-booking/' . $booking['id']) ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted mt-3">No bookings found for the next 30 days.</p>
            <?php endif; ?>
            
            <!-- Calendar visualization would go here -->
            <div class="mt-4">
                <p class="text-muted"><small>Full calendar view with time slots coming soon. Use the booking form to check specific dates.</small></p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-calendar-month" style="font-size: 4rem; color: #dee2e6;"></i>
            <h5 class="mt-3 text-muted">Select a Space</h5>
            <p class="text-muted">Please select a location and space from the dropdowns above to view its booking calendar.</p>
        </div>
    </div>
<?php endif; ?>

<script>
function loadSpaces(locationId) {
    if (!locationId) {
        document.getElementById('space_filter').innerHTML = '<option value="">All Spaces</option>';
        return;
    }
    
    fetch('<?= base_url() ?>locations/get-spaces-for-booking?location_id=' + locationId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const spaceSelect = document.getElementById('space_filter');
                spaceSelect.innerHTML = '<option value="">All Spaces</option>';
                data.spaces.forEach(space => {
                    const option = document.createElement('option');
                    option.value = space.id;
                    option.textContent = space.space_name;
                    spaceSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
}
</script>

