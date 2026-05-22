<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/booking-slot-picker.css') ?>">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Reschedule: <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h1>
    <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card mb-4 mb-xl-0 h-100">
            <div class="card-header"><h6 class="mb-0">Current Schedule</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?></p>
                <p class="mb-1"><strong>Time:</strong> <?= date('g:i A', strtotime($booking['start_time'])) ?> – <?= date('g:i A', strtotime($booking['end_time'])) ?></p>
                <p class="mb-0"><strong>Space:</strong> <?= htmlspecialchars($booking['facility_name'] ?? $booking['space_name'] ?? '—') ?></p>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h6 class="mb-0">New Schedule</h6></div>
            <div class="card-body">
                <form method="POST" id="rescheduleForm">
                    <?= csrf_field() ?>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Venue <span class="text-danger">*</span></label>
                            <select name="space_id" id="space_select" class="form-select" required>
                                <?php foreach (($venue_options ?? []) as $venue): ?>
                                    <option value="<?= intval($venue['space_id']) ?>"
                                            data-facility-id="<?= intval($venue['facility_id']) ?>"
                                            <?= intval($booking['space_id'] ?? 0) === intval($venue['space_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($venue['space_name']) ?><?= !empty($venue['property_name']) ? ' - ' . htmlspecialchars($venue['property_name']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">New Date <span class="text-danger">*</span></label>
                            <input type="date" id="reschedule_date" name="booking_date" class="form-control"
                                   min="<?= date('Y-m-d') ?>" required>
                            <small class="text-muted">Select a date to see available slots</small>
                        </div>
                    </div>
                    <input type="hidden" name="start_time" id="hidden_start_time">
                    <input type="hidden" name="end_time" id="hidden_end_time">

                    <div class="mb-4" id="slot-section" style="display:none;">
                        <label class="form-label fw-bold">Available Time Slots</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-success">Available block</span>
                            <span class="badge bg-danger">Occupied</span>
                            <span class="badge bg-warning text-dark">Buffer (1 hr gap)</span>
                        </div>
                        <div id="slot-container" class="reschedule-slot-area">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                <span class="ms-2 text-muted">Loading slots…</span>
                            </div>
                        </div>
                        <div id="selected-slot-display" class="alert alert-success mt-3" style="display:none;">
                            <i class="bi bi-check-circle"></i> Selected: <strong id="selected-slot-text"></strong>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card border-info h-100">
                                <div class="card-header bg-light"><strong>Updated Price Preview</strong></div>
                                <div class="card-body py-2">
                                    <div id="quote_preview_status" class="small text-muted">Select venue, date and slot to preview.</div>
                                    <div class="row mt-2">
                                        <div class="col-6 small">Base:</div><div class="col-6 small text-end" id="quote_base">-</div>
                                        <div class="col-6 small">Subtotal:</div><div class="col-6 small text-end" id="quote_subtotal">-</div>
                                        <div class="col-6 small">Tax:</div><div class="col-6 small text-end" id="quote_tax">-</div>
                                        <div class="col-6 small fw-bold">Total:</div><div class="col-6 small fw-bold text-end" id="quote_total">-</div>
                                        <div class="col-6 small">Balance:</div><div class="col-6 small text-end" id="quote_balance">-</div>
                                        <div class="col-12"><hr class="my-2"></div>
                                        <div class="col-6 small">Total vs saved booking:</div><div class="col-6 small text-end" id="quote_delta_total">-</div>
                                        <div class="col-6 small">Balance vs saved:</div><div class="col-6 small text-end" id="quote_delta_balance">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Reason for Rescheduling</label>
                            <textarea name="reason" class="form-control" rows="4"
                                      placeholder="Optional — enter reason…"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
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

<?php
$rescheduleDurationHours = intval($booking['duration_hours'] ?? 0);
if ($rescheduleDurationHours <= 0 && !empty($booking['start_time']) && !empty($booking['end_time'])) {
    $rescheduleDurationHours = max(1, (int) round((strtotime($booking['booking_date'] . ' ' . $booking['end_time']) - strtotime($booking['booking_date'] . ' ' . $booking['start_time'])) / 3600));
}
$rescheduleDurationHours = max(1, $rescheduleDurationHours);
?>
<script src="<?= base_url('assets/js/booking-slot-picker.js') ?>"></script>
<script nonce="<?= csp_nonce() ?>">
(function() {
    const bookingId   = <?= intval($booking['id']) ?>;
    const bookingDurationHours = <?= (int) $rescheduleDurationHours ?>;
    const currentDate = '<?= htmlspecialchars(!empty($booking['booking_date']) ? date('Y-m-d', strtotime($booking['booking_date'])) : '') ?>';
    const currentStart = '<?= htmlspecialchars(substr((string)($booking['start_time'] ?? ''), 0, 5)) ?>';
    const currentEnd = '<?= htmlspecialchars(substr((string)($booking['end_time'] ?? ''), 0, 5)) ?>';

    const venueSelect = document.getElementById('space_select');
    const dateInput      = document.getElementById('reschedule_date');
    const slotSection    = document.getElementById('slot-section');
    const slotContainer  = document.getElementById('slot-container');
    const selectedDisplay = document.getElementById('selected-slot-display');
    const selectedText   = document.getElementById('selected-slot-text');
    const hiddenStart    = document.getElementById('hidden_start_time');
    const hiddenEnd      = document.getElementById('hidden_end_time');
    const submitBtn      = document.getElementById('submitBtn');
    const quoteStatus = document.getElementById('quote_preview_status');
    const quoteBase = document.getElementById('quote_base');
    const quoteSubtotal = document.getElementById('quote_subtotal');
    const quoteTax = document.getElementById('quote_tax');
    const quoteTotal = document.getElementById('quote_total');
    const quoteBalance = document.getElementById('quote_balance');
    const quoteDeltaTotal = document.getElementById('quote_delta_total');
    const quoteDeltaBalance = document.getElementById('quote_delta_balance');
    const savedTotalAmount = <?= json_encode(floatval($booking['total_amount'] ?? 0)) ?>;
    const savedBalanceAmount = <?= json_encode(floatval($booking['balance_amount'] ?? 0)) ?>;

    function getFacilityId() {
        const option = venueSelect?.options?.[venueSelect.selectedIndex];
        return parseInt(option?.dataset?.facilityId || '0', 10);
    }

    function loadSlots(date) {
        const facilityId = getFacilityId();
        slotSection.style.display = 'block';
        selectedDisplay.style.display = 'none';
        hiddenStart.value = '';
        hiddenEnd.value   = '';
        submitBtn.disabled = true;
        clearQuotePreview();
        slotContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div><span class="ms-2 text-muted">Loading slots…</span></div>';

        fetch(`<?= base_url('bookings/get-slots') ?>?facility_id=${facilityId}&date=${date}&exclude_booking_id=${bookingId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load slots.');
                }

                if (!window.BookingSlotPicker) {
                    slotContainer.innerHTML = '<div class="alert alert-danger">Slot picker failed to load.</div>';
                    return;
                }

                const durationHours = data.required_duration_hours || bookingDurationHours;
                BookingSlotPicker.renderDurationPicker({
                    container: slotContainer,
                    durationHours: durationHours,
                    date: date,
                    availableSlots: data.slots || [],
                    occupiedSlots: data.occupied || [],
                    savedStart: data.saved_start_time || currentStart,
                    savedEnd: data.saved_end_time || currentEnd,
                    savedDate: data.saved_booking_date || currentDate,
                    formatTime: formatTime,
                    onSelect: function (start, end) {
                        hiddenStart.value = start;
                        hiddenEnd.value = end;
                        selectedText.textContent = formatTime(start) + ' – ' + formatTime(end);
                        selectedDisplay.style.display = 'block';
                        submitBtn.disabled = false;
                        updateQuote();
                    }
                });
            })
            .catch(() => {
                slotContainer.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Failed to load time slots. Please try again.</div>';
                clearQuotePreview();
            });
    }

    function formatTime(t) {
        const [h, m] = t.split(':').map(Number);
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12  = h % 12 || 12;
        return `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
    }

    function formatCurrency(num) {
        return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(Number(num || 0));
    }

    function formatSignedDelta(diff) {
        const n = Number(diff);
        if (!Number.isFinite(n) || Math.abs(n) < 0.005) {
            return { html: '<span class="text-muted">No change</span>' };
        }
        const sign = n > 0 ? '+' : '−';
        const cls = n > 0 ? 'text-danger' : 'text-success';
        const body = formatCurrency(Math.abs(n));
        return { html: `<span class="${cls}">${sign} ${body}</span>` };
    }

    function applyQuoteDeltas(quote) {
        if (!quoteDeltaTotal || !quoteDeltaBalance) return;
        quoteDeltaTotal.innerHTML = formatSignedDelta(Number(quote.total_amount) - savedTotalAmount).html;
        quoteDeltaBalance.innerHTML = formatSignedDelta(Number(quote.balance_amount) - savedBalanceAmount).html;
    }

    function clearQuoteDeltas() {
        if (quoteDeltaTotal) quoteDeltaTotal.textContent = '-';
        if (quoteDeltaBalance) quoteDeltaBalance.textContent = '-';
    }

    const defaultQuoteStatus = 'Select venue, date and slot to preview.';

    function clearQuotePreview(statusMsg) {
        if (quoteStatus) quoteStatus.textContent = statusMsg !== undefined ? statusMsg : defaultQuoteStatus;
        if (quoteBase) quoteBase.textContent = '-';
        if (quoteSubtotal) quoteSubtotal.textContent = '-';
        if (quoteTax) quoteTax.textContent = '-';
        if (quoteTotal) quoteTotal.textContent = '-';
        if (quoteBalance) quoteBalance.textContent = '-';
        clearQuoteDeltas();
    }

    function updateQuote() {
        if (!hiddenStart.value || !hiddenEnd.value || !dateInput.value) {
            clearQuotePreview();
            return;
        }
        const params = new URLSearchParams({
            space_id: String(venueSelect.value || ''),
            booking_date: String(dateInput.value),
            start_time: String(hiddenStart.value),
            end_time: String(hiddenEnd.value)
        });
        quoteStatus.textContent = 'Calculating...';
        fetch(`<?= base_url('bookings/reschedule-quote/' . intval($booking['id'] ?? 0)) ?>?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.quote) throw new Error();
                quoteBase.textContent = formatCurrency(data.quote.base_amount);
                quoteSubtotal.textContent = formatCurrency(data.quote.subtotal);
                quoteTax.textContent = formatCurrency(data.quote.tax_amount);
                quoteTotal.textContent = formatCurrency(data.quote.total_amount);
                quoteBalance.textContent = formatCurrency(data.quote.balance_amount);
                applyQuoteDeltas(data.quote);
                quoteStatus.textContent = 'Preview reflects current selection.';
            })
            .catch(() => {
                clearQuotePreview('Unable to calculate preview.');
            });
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
    venueSelect.addEventListener('change', function() {
        if (dateInput.value) loadSlots(dateInput.value);
    });

    document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
        if (!hiddenStart.value || !hiddenEnd.value) {
            e.preventDefault();
            alert('Please select a time slot before confirming.');
        }
    });

    if (dateInput.value) {
        loadSlots(dateInput.value);
    } else if (currentDate) {
        dateInput.value = currentDate;
        loadSlots(currentDate);
    }
})();
</script>
