<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Reschedule: <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h1>
    <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5">
        <!-- Current schedule -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">Current Schedule</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?></p>
                <p class="mb-1"><strong>Time:</strong> <?= date('g:i A', strtotime($booking['start_time'])) ?> – <?= date('g:i A', strtotime($booking['end_time'])) ?></p>
                <p class="mb-0"><strong>Space:</strong> <?= htmlspecialchars($booking['facility_name'] ?? $booking['space_name'] ?? '—') ?></p>
            </div>
        </div>

        <!-- Reschedule form -->
        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h6 class="mb-0">New Schedule</h6></div>
            <!-- Debug: facility_id=<?= $booking['facility_id'] ?? 'NULL' ?>, space_id=<?= $booking['space_id'] ?? 'NULL' ?> -->
            <div class="card-body">
                <form method="POST" id="rescheduleForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="start_time" id="hidden_start_time">
                    <input type="hidden" name="end_time"   id="hidden_end_time">

                    <div class="mb-3">
                        <label class="form-label fw-bold">New Date <span class="text-danger">*</span></label>
                        <input type="date" id="reschedule_date" name="booking_date" class="form-control"
                               min="<?= date('Y-m-d') ?>" required>
                        <small class="text-muted">Select a date to see available slots</small>
                    </div>

                    <!-- Time slot picker -->
                    <div class="mb-3" id="slot-section" style="display:none;">
                        <label class="form-label fw-bold">Available Time Slots</label>
                        <div id="slot-container">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                <span class="ms-2 text-muted">Loading slots…</span>
                            </div>
                        </div>
                        <div id="selected-slot-display" class="alert alert-success mt-2" style="display:none;">
                            <i class="bi bi-check-circle"></i> Selected: <strong id="selected-slot-text"></strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason for Rescheduling</label>
                        <textarea name="reason" class="form-control" rows="2"
                                  placeholder="Optional — enter reason…"></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning" id="submitBtn" disabled>
                            <i class="bi bi-calendar-check"></i> Confirm Reschedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Use facility_id directly — most reliable for internal bookings
    const facilityId  = <?= intval($booking['facility_id'] ?? $booking['space_id'] ?? 0) ?>;
    const bookingId   = <?= intval($booking['id']) ?>;
    const duration    = <?= floatval($booking['duration_hours'] ?? 1) ?>;

    // Debug: log what we have
    console.log('Reschedule debug — facilityId:', facilityId, 'bookingId:', bookingId, 'duration:', duration);
    console.log('Booking data:', <?= json_encode(['facility_id' => $booking['facility_id'] ?? null, 'space_id' => $booking['space_id'] ?? null, 'booking_date' => $booking['booking_date'] ?? null]) ?>);

    if (!facilityId) {
        document.getElementById('slot-section').innerHTML = '<div class="alert alert-danger">Configuration error: no facility linked to this booking (facility_id=0). Please contact support.</div>';
        document.getElementById('slot-section').style.display = 'block';
    }

    const dateInput      = document.getElementById('reschedule_date');
    const slotSection    = document.getElementById('slot-section');
    const slotContainer  = document.getElementById('slot-container');
    const selectedDisplay = document.getElementById('selected-slot-display');
    const selectedText   = document.getElementById('selected-slot-text');
    const hiddenStart    = document.getElementById('hidden_start_time');
    const hiddenEnd      = document.getElementById('hidden_end_time');
    const submitBtn      = document.getElementById('submitBtn');

    function loadSlots(date) {
        slotSection.style.display = 'block';
        selectedDisplay.style.display = 'none';
        hiddenStart.value = '';
        hiddenEnd.value   = '';
        submitBtn.disabled = true;
        slotContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div><span class="ms-2 text-muted">Loading slots…</span></div>';

        fetch(`<?= base_url('bookings/get-slots') ?>?facility_id=${facilityId}&date=${date}&exclude_booking_id=${bookingId}`)
            .then(r => r.json())
            .then(data => {
                console.log('Slot response:', data);
                if (!data.success || !data.slots || data.slots.length === 0) {
                    slotContainer.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-circle"></i> No available time slots on this date. Please try another date.</div>';
                    return;
                }

                let html = '<div class="row g-2">';
                data.slots.forEach(slot => {
                    const [sh, sm] = slot.start.split(':').map(Number);
                    const endMins = sh * 60 + sm + Math.round(duration * 60);
                    const endTime = String(Math.floor(endMins/60)).padStart(2,'0') + ':' + String(endMins%60).padStart(2,'0');
                    html += `<div class="col-6 col-md-4">
                        <button type="button" class="btn btn-outline-primary w-100 slot-btn"
                                data-start="${slot.start}" data-end="${endTime}">
                            <div class="fw-bold">${formatTime(slot.start)}</div>
                            <small class="text-muted">to ${formatTime(endTime)}</small>
                        </button>
                    </div>`;
                });
                html += '</div>';
                slotContainer.innerHTML = html;

                slotContainer.querySelectorAll('.slot-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        slotContainer.querySelectorAll('.slot-btn').forEach(b => {
                            b.classList.remove('btn-primary', 'active');
                            b.classList.add('btn-outline-primary');
                        });
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-primary', 'active');
                        hiddenStart.value = this.dataset.start;
                        hiddenEnd.value   = this.dataset.end;
                        selectedText.textContent = formatTime(this.dataset.start) + ' – ' + formatTime(this.dataset.end);
                        selectedDisplay.style.display = 'block';
                        submitBtn.disabled = false;
                    });
                });
            })
            .catch(err => {
                slotContainer.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Failed to load time slots. Please try again.</div>';
                console.error('Slot load error:', err);
            });
    }

    function formatTime(t) {
        const [h, m] = t.split(':').map(Number);
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12  = h % 12 || 12;
        return `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
    }

    dateInput.addEventListener('change', function() {
        if (this.value) loadSlots(this.value);
    });
    dateInput.addEventListener('input', function() {
        if (/^\d{4}-\d{2}-\d{2}$/.test(this.value)) loadSlots(this.value);
    });
    dateInput.addEventListener('click', function() {
        if (typeof this.showPicker === 'function') this.showPicker();
    });

    document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
        if (!hiddenStart.value || !hiddenEnd.value) {
            e.preventDefault();
            alert('Please select a time slot before confirming.');
        }
    });
})();
</script>
