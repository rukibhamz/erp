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
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/calendar-timeslots.css') ?>">
<style>
    .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
    .view-controls .btn { margin-right: 5px; }
    .date-navigation { display: flex; align-items: center; gap: 10px; }
    .date-navigation input { width: 150px; }
    .calendar-legend { display: flex; gap: 20px; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
    .legend-item { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
    .color-box { width: 16px; height: 16px; border-radius: 3px; }
    .color-box.available { background-color: #d1e7dd; border: 1px solid #0f5132; }
    .color-box.booked { background-color: #f8d7da; border: 1px solid #842029; }
    .color-box.pending { background-color: #fff3cd; border: 1px solid #664d03; }
</style>

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
            <div class="calendar-header">
                <div class="view-controls btn-group">
                    <a href="<?= base_url('locations/booking-calendar/' . ($selected_location['id'] ?? '') . '/' . $selected_space['id'] . '?view=month&date=' . $selected_date) ?>" 
                       class="btn btn-sm <?= $view_type === 'month' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-calendar-month"></i> Month
                    </a>
                    <a href="<?= base_url('locations/booking-calendar/' . ($selected_location['id'] ?? '') . '/' . $selected_space['id'] . '?view=day&date=' . $selected_date) ?>" 
                       class="btn btn-sm <?= $view_type === 'day' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-clock"></i> Day
                    </a>
                </div>
                
                <div class="date-navigation">
                    <button class="btn btn-sm btn-secondary" onclick="navigateDate(-1)">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <input type="date" class="form-control form-control-sm" id="selected-date" value="<?= $selected_date ?>" onchange="reloadCalendar()">
                    <button class="btn btn-sm btn-secondary" onclick="navigateDate(1)">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="goToToday()">Today</button>
                </div>
            </div>

            <?php if ($view_type === 'day'): ?>
                <!-- Day View: Time Slot Grid -->
                <div class="time-grid-container mt-4">
                    <div class="time-grid">
                        <div class="time-labels">
                            <?php foreach ($time_slots as $slot): ?>
                                <div class="time-label">
                                    <?= htmlspecialchars(explode(' - ', $slot['label'])[0]) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="time-slots">
                            <?php foreach ($time_slots as $slot): ?>
                                <?php 
                                    $statusClass = $slot['available'] ? 'available' : 'booked';
                                    if ($slot['booking'] && ($slot['booking']['status'] ?? '') === 'pending') {
                                        $statusClass = 'pending';
                                    }
                                ?>
                                <div class="time-slot <?= $statusClass ?>"
                                     data-start="<?= $slot['start_time'] ?>"
                                     data-end="<?= $slot['end_time'] ?>"
                                     data-date="<?= $selected_date ?>"
                                     data-space="<?= $selected_space['id'] ?>"
                                     <?= $slot['available'] ? 'onclick="openQuickBookingModal(this)"' : '' ?>>
                                    
                                    <?php if (!$slot['available']): ?>
                                        <div class="booking-info">
                                            <strong><?= htmlspecialchars($slot['booking']['customer_name'] ?? 'Booked') ?></strong>
                                            <?php if (!empty($slot['booking']['id'])): ?>
                                                <small class="d-block text-muted"><?= $slot['start_time'] ?> - <?= $slot['end_time'] ?></small>
                                                <a href="<?= base_url('locations/view-booking/' . $slot['booking']['id']) ?>" 
                                                   class="btn btn-xs btn-outline-light mt-1 py-0 px-1" style="font-size: 0.7rem;"
                                                   onclick="event.stopPropagation()">
                                                    Details
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="slot-status">
                                            <i class="bi bi-check-circle"></i> Available
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="calendar-legend mt-4">
                        <div class="legend-item">
                            <span class="color-box available"></span> Available
                        </div>
                        <div class="legend-item">
                            <span class="color-box booked"></span> Booked
                        </div>
                        <div class="legend-item">
                            <span class="color-box pending"></span> Pending
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Month View: List of Upcoming Bookings -->
                <?php if (!empty($bookings)): ?>
                    <div class="mt-4">
                        <h6>Upcoming Bookings for <?= date('F Y', strtotime($selected_date)) ?></h6>
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
                                                <span class="badge bg-<?= ($booking['status'] ?? 'pending') === 'confirmed' ? 'success' : 'warning' ?>">
                                                    <?= ucfirst($booking['status'] ?? 'pending') ?>
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
                    <p class="text-muted mt-4">No bookings found for the selected month.</p>
                <?php endif; ?>
            <?php endif; ?>
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

<!-- Quick Booking Modal -->
<div class="modal fade" id="quick-booking-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Quick Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('locations/create-booking') ?>" method="POST" id="quick-booking-form">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <input type="hidden" name="space_id" id="modal-space-id">
                    <input type="hidden" name="booking_date" id="modal-date">
                    <input type="hidden" name="start_time" id="modal-start-time">
                    <input type="hidden" name="end_time" id="modal-end-time">
                    <input type="hidden" name="booking_type" value="hourly">
                    
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Time Slot</label>
                        <input type="text" class="form-control bg-light" id="modal-time-display" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" class="form-control" name="customer_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Phone *</label>
                            <input type="tel" class="form-control" name="customer_phone" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" class="form-control" name="customer_email">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Number of Guests</label>
                        <input type="number" class="form-control" name="number_of_guests" value="1" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="booking_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function reloadCalendar() {
    const spaceId = document.getElementById('space_filter').value;
    const locationId = document.getElementById('location_filter').value;
    const date = document.getElementById('selected-date').value;
    const view = '<?= $view_type ?>';
    
    if (spaceId) {
        window.location.href = '<?= base_url('locations/booking-calendar') ?>/' + locationId + '/' + spaceId + '?view=' + view + '&date=' + date;
    } else {
        document.querySelector('form').submit();
    }
}

function navigateDate(days) {
    const dateInput = document.getElementById('selected-date');
    const currentDate = new Date(dateInput.value);
    currentDate.setDate(currentDate.getDate() + days);
    dateInput.value = currentDate.toISOString().split('T')[0];
    reloadCalendar();
}

function goToToday() {
    const dateInput = document.getElementById('selected-date');
    dateInput.value = new Date().toISOString().split('T')[0];
    reloadCalendar();
}

function openQuickBookingModal(element) {
    const startTime = element.dataset.start;
    const endTime = element.dataset.end;
    const date = element.dataset.date;
    const spaceId = element.dataset.space;
    
    document.getElementById('modal-space-id').value = spaceId;
    document.getElementById('modal-date').value = date;
    document.getElementById('modal-start-time').value = startTime;
    document.getElementById('modal-end-time').value = endTime;
    
    // Format display
    const startObj = new Date(date + 'T' + startTime);
    const endObj = new Date(date + 'T' + endTime);
    const options = { hour: 'numeric', minute: '2-digit', hour12: true };
    const dateOptions = { weekday: 'short', month: 'short', day: 'numeric' };
    
    const timeStr = startObj.toLocaleTimeString('en-US', options) + ' - ' + endObj.toLocaleTimeString('en-US', options);
    const dateStr = startObj.toLocaleDateString('en-US', dateOptions);
    
    document.getElementById('modal-time-display').value = timeStr + ' on ' + dateStr;
    
    const modal = new bootstrap.Modal(document.getElementById('quick-booking-modal'));
    modal.show();
}

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

