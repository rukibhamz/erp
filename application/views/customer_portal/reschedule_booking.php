<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Reschedule Booking</h1>
    <a href="<?= base_url('customer-portal/booking/' . $booking['id']) ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <!-- Current schedule -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">Current Schedule</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>Booking:</strong> <?= htmlspecialchars($booking['booking_number']) ?></p>
                <p class="mb-1"><strong>Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?></p>
                <p class="mb-1"><strong>Time:</strong> <?= date('g:i A', strtotime($booking['start_time'])) ?> – <?= date('g:i A', strtotime($booking['end_time'])) ?></p>
                <p class="mb-0"><strong>Space:</strong> <?= htmlspecialchars($booking['facility_name'] ?? '—') ?></p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h6 class="mb-0">Choose New Date & Time</h6></div>
            <div class="card-body">
                <form method="POST" id="rescheduleForm" action="<?= base_url('customer-portal/reschedule-booking/' . $booking['id']) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="start_time" id="hidden_start_time">
                    <input type="hidden" name="end_time"   id="hidden_end_time">

                    <div class="mb-3">
                        <label class="form-label fw-bold">New Date <span class="text-danger">*</span></label>
                        <input type="date" id="reschedule_date" name="booking_date" class="form-control"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        <small class="text-muted">Select a date to see available slots</small>
                    </div>

                    <div class="mb-3" id="slot-section" style="display:none;">
                        <label class="form-label fw-bold">Available Time Slots</label>
                        <div id="slot-container"></div>
                        <div id="selected-slot-display" class="alert alert-success mt-2" style="display:none;">
                            <i class="bi bi-check-circle"></i> Selected: <strong id="selected-slot-text"></strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason for Rescheduling</label>
                        <textarea name="reason" class="form-control" rows="2"
                                  placeholder="Optional — let us know why you're rescheduling"></textarea>
                    </div>

                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i>
                        Rescheduling is subject to availability. Your booking details will be updated once confirmed.
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('customer-portal/booking/' . $booking['id']) ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning" id="submitBtn" disabled>
                            <i class="bi bi-calendar-check"></i> Confirm Reschedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    const facilityId = <?= intval($booking['facility_id'] ?? $booking['space_id'] ?? 0) ?>;
    const bookingId  = <?= intval($booking['id']) ?>;
    const duration   = <?= floatval($booking['duration_hours'] ?? 1) ?>;

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
                if (!data.success || !data.slots || data.slots.length === 0) {
                    slotContainer.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-circle"></i> No available slots on this date. Please try another.</div>';
                    return;
                }
                let html = '<div class="row g-2">';
                data.slots.forEach(slot => {
                    const [sh, sm] = slot.start.split(':').map(Number);
                    const endMins = sh * 60 + sm + Math.round(duration * 60);
                    const endTime = String(Math.floor(endMins/60)).padStart(2,'0') + ':' + String(endMins%60).padStart(2,'0');
                    html += `<div class="col-6">
                        <button type="button" class="btn btn-outline-primary w-100 slot-btn"
                                data-start="${slot.start}" data-end="${endTime}">
                            <div class="fw-bold">${fmt(slot.start)}</div>
                            <small class="text-muted">to ${fmt(endTime)}</small>
                        </button>
                    </div>`;
                });
                html += '</div>';
                slotContainer.innerHTML = html;
                slotContainer.querySelectorAll('.slot-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        slotContainer.querySelectorAll('.slot-btn').forEach(b => { b.classList.remove('btn-primary','active'); b.classList.add('btn-outline-primary'); });
                        this.classList.remove('btn-outline-primary'); this.classList.add('btn-primary','active');
                        hiddenStart.value = this.dataset.start;
                        hiddenEnd.value   = this.dataset.end;
                        selectedText.textContent = fmt(this.dataset.start) + ' – ' + fmt(this.dataset.end);
                        selectedDisplay.style.display = 'block';
                        submitBtn.disabled = false;
                    });
                });
            })
            .catch(() => { slotContainer.innerHTML = '<div class="alert alert-danger">Failed to load slots. Please try again.</div>'; });
    }

    function fmt(t) {
        const [h,m] = t.split(':').map(Number);
        return `${h%12||12}:${String(m).padStart(2,'0')} ${h>=12?'PM':'AM'}`;
    }

    dateInput.addEventListener('change', function() { if (this.value) loadSlots(this.value); });
    dateInput.addEventListener('input',  function() { if (/^\d{4}-\d{2}-\d{2}$/.test(this.value)) loadSlots(this.value); });
    dateInput.addEventListener('click',  function() { if (typeof this.showPicker==='function') this.showPicker(); });

    document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
        if (!hiddenStart.value || !hiddenEnd.value) { e.preventDefault(); alert('Please select a time slot.'); }
    });
})();
</script>
