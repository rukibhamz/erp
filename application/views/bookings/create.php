<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<style>
    /* Fix for time slot button visibility and coloring - High Contrast Green */
    #time-slots-container .btn-outline-success {
        color: #198754 !important;
        border: 2px solid #198754 !important;
        background-color: #ffffff !important;
        font-weight: 600 !important;
    }
    #time-slots-container .btn-outline-success:hover,
    #time-slots-container .slot-btn.btn-success {
        background-color: #198754 !important;
        color: #ffffff !important;
        border-color: #198754 !important;
        box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3) !important;
    }
    #time-slots-container .btn-danger.disabled {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #ffffff !important;
        opacity: 0.6;
        cursor: not-allowed;
    }
    #time-slots-container .btn-warning.disabled {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        color: #212529 !important;
        opacity: 0.8;
        cursor: not-allowed;
    }
    /* Ensure the slot text is bold and readable */
    .slot-btn, #time-slots-container .btn {
        font-weight: 500;
        font-size: 0.9rem;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Booking</h1>
        <a href="<?= base_url('bookings') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Booking Details</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="bookingForm">
                <?php echo csrf_field(); ?>
                
                <!-- Location and Space Selection -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="location_id" id="location_id" class="form-select" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location['id'] ?>">
                                        <?= htmlspecialchars($location['Location_name'] ?? $location['location_name'] ?? $location['property_name'] ?? 'N/A') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Space <span class="text-danger">*</span></label>
                            <select name="space_id" id="space_id" class="form-select" required disabled>
                                <option value="">Select Location First</option>
                            </select>
                            <input type="hidden" name="facility_id" id="facility_id" value="">
                        </div>
                    </div>
                </div>

                <!-- Space Details (shown after space selection) -->
                <div id="spaceDetails" class="alert alert-info mb-4" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Capacity:</strong> <span id="spaceCapacity">-</span> guests
                        </div>
                        <div class="col-md-6">
                            <strong>Available Booking Types:</strong> <span id="spaceBookingTypes">-</span>
                        </div>
                    </div>
                </div>

                <!-- Booking Type Selection -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Booking Type <span class="text-danger">*</span></label>
                            <select name="booking_type" id="booking_type" class="form-select" required disabled>
                                <option value="">Select Space First</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= isset($old_input['booking_date']) ? $old_input['booking_date'] : '' ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Time Selection (Smart UI) -->
                <div class="row mb-4">
                     <!-- Duration Selection (Hidden by default, shown for hourly/daily) -->
                     <div class="col-md-4 mb-3" id="duration-container" style="display: none;">
                        <label class="form-label">Duration</label>
                        <select id="duration" class="form-select">
                            <!-- Options populated by JS -->
                        </select>
                    </div>

                    <div class="col-md-4 mb-3" id="end-date-container" style="display: none;">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="booking_end_date" class="form-control" 
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Number of Guests</label>
                        <input type="number" name="number_of_guests" id="number_of_guests" class="form-control" min="0" value="0">
                        <small class="text-muted" id="capacityWarning" style="display: none; color: red !important;">Exceeds space capacity!</small>
                    </div>
                </div>

                <!-- Hidden inputs for form submission -->
                <input type="hidden" name="start_time" id="start_time" required>
                <input type="hidden" name="end_time" id="end_time" required>

                <!-- Time Slot Grid / Half Day Buttons -->
                <div class="mb-4" id="time-slots-section" style="display: none;">
                    <label class="form-label fw-bold">Select Time Slot</label>
                    
                    <!-- Selected Summary -->
                    <div id="selected-time-summary" class="alert alert-success mb-3" style="display: none;">
                        <strong>Selected:</strong> <span id="selected-time-display"></span>
                    </div>

                    <!-- Legend -->
                    <div class="mb-2" id="time-slot-legend">
                        <span class="badge bg-white border border-success text-success me-2" style="border-width: 2px !important;">Available</span>
                        <span class="badge bg-danger me-2">Occupied</span>
                        <span class="badge bg-warning text-dark">Buffer</span>
                    </div>
                    
                    <!-- Grid Container -->
                    <div id="time-slots-container" class="row g-2">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Please select a date and booking type to see available time slots
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Price Preview -->
                <div class="alert alert-success" id="pricePreview" style="display: none;">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Subtotal:</strong> <span id="subTotal">₦0.00</span>
                        </div>
                        <div class="col-md-3">
                            <strong>VAT (7.5%):</strong> <span id="vatAmount">₦0.00</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Total:</strong> <span id="estimatedPrice">₦0.00</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Security Deposit:</strong> <span id="securityDeposit">₦0.00</span>
                        </div>
                    </div>
                    <input type="hidden" name="tax_amount" id="tax_amount_input" value="0">
                </div>

                <!-- Customer Information -->
                <h5 class="mb-3 mt-4">Customer Information</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" required value="<?= isset($old_input['customer_name']) ? htmlspecialchars($old_input['customer_name']) : '' ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control" value="<?= isset($old_input['customer_email']) ? htmlspecialchars($old_input['customer_email']) : '' ?>">
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="customer_phone" class="form-control" required value="<?= isset($old_input['customer_phone']) ? htmlspecialchars($old_input['customer_phone']) : '' ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Discount Amount</label>
                            <input type="number" name="discount_amount" id="discount_amount" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Payment Plan <span class="text-danger">*</span></label>
                            <select name="payment_plan" id="payment_plan" class="form-select" required>
                                <option value="full">Full Payment</option>
                                <option value="part">Part Payment (50% Deposit)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Customer Address</label>
                    <textarea name="customer_address" class="form-control" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Booking Notes</label>
                    <textarea name="booking_notes" class="form-control" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Special Requests</label>
                    <textarea name="special_requests" class="form-control" rows="2"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('bookings') ?>" class="btn btn-primary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function() {
    // Configuration & State
    let baseUrl = '<?= base_url() ?>';
    if (!baseUrl.endsWith('/')) baseUrl += '/';
    const BASE_URL = baseUrl;
    
    let currentSpaceData = null;
    let spacesData = {}; 
    let selectedDuration = 1;
    let selectedStartTime = '';
    let selectedEndTime = '';
    let selectedDate = '';
    let selectedEndDate = '';

    // Hoisted Functions (attached to window for cross-scope access)
    window.setSelection = function(start, end, display) {
        console.log('DEBUG: setSelection called', {start, end, display});
        selectedStartTime = start;
        selectedEndTime = end;
        
        const startInput = document.getElementById('start_time');
        const endInput = document.getElementById('end_time');
        if (startInput) startInput.value = start;
        if (endInput) endInput.value = end;
        
        const sumEl = document.getElementById('selected-time-summary');
        if (sumEl) sumEl.style.display = 'block';
        
        const dispEl = document.getElementById('selected-time-display');
        if (dispEl) dispEl.textContent = display;
        
        calculatePrice(); 
    };

    window.selectSlot = function(start, end) {
        console.log('DEBUG: selectSlot called', {start, end, selectedDuration});
        try {
            let [h, m] = start.split(':').map(Number);
            let dur = parseInt(selectedDuration) || 1;
            h += dur;
            
            let endH = h > 23 ? 23 : h; 
            let endM = m;
            let endStr = `${String(endH).padStart(2,'0')}:${String(endM).padStart(2,'0')}`;
            
            window.setSelection(start, endStr, `${start} - ${endStr}`);
        } catch (e) {
            console.error('selectSlot error:', e);
            alert('Error selecting slot.');
        }
    };

    function calculatePrice() {
        const pp = document.getElementById('pricePreview');
        if (!currentSpaceData) {
            if(pp) pp.style.display = 'none';
            return;
        }
        
        const bookingDate = document.getElementById('booking_date').value;
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const bookingType = document.getElementById('booking_type').value;
        const discount = parseFloat(document.getElementById('discount_amount')?.value) || 0;
        
        if (!bookingDate || !startTime || !endTime || !bookingType) {
            if(pp) pp.style.display = 'none';
            return;
        }
        
        const start = new Date(bookingDate + 'T' + startTime);
        const end = new Date(bookingDate + 'T' + endTime);
        const hours = (end - start) / (1000 * 60 * 60);
        
        if (hours <= 0) return;
        
        let basePrice = 0;
        const days = Math.ceil(hours / 24);
        
        switch(bookingType) {
            case 'hourly': basePrice = (currentSpaceData.hourly_rate || 0) * hours; break;
            case 'daily': 
            case 'full_day': basePrice = (currentSpaceData.daily_rate || 0) * days; break;
            case 'half_day': basePrice = (currentSpaceData.half_day_rate || 0) * Math.ceil(days * 2); break;
            case 'weekly': basePrice = (currentSpaceData.weekly_rate || 0) * Math.ceil(days / 7); break;
            case 'multi_day': basePrice = (currentSpaceData.daily_rate || 0) * days; break;
            default: basePrice = (currentSpaceData.hourly_rate || 0) * hours;
        }
        
        const subTotal = Math.max(0, basePrice - discount);
        const vat = subTotal * 0.075;
        const total = subTotal + vat;
        const deposit = currentSpaceData.security_deposit || 0;
        
        const subTotalEl = document.getElementById('subTotal');
        if (subTotalEl) subTotalEl.textContent = '₦' + subTotal.toLocaleString();
        
        const vatAmountEl = document.getElementById('vatAmount');
        if (vatAmountEl) vatAmountEl.textContent = '₦' + vat.toLocaleString();
        
        const taxInput = document.getElementById('tax_amount_input');
        if (taxInput) taxInput.value = vat.toFixed(2);
        
        const estPriceEl = document.getElementById('estimatedPrice');
        if (estPriceEl) estPriceEl.textContent = '₦' + total.toLocaleString();
        
        const secDepEl = document.getElementById('securityDeposit');
        if (secDepEl) secDepEl.textContent = '₦' + deposit.toLocaleString();
        
        if (pp) pp.style.display = 'block';
    }

    function loadSpaces() {
        const locationId = document.getElementById('location_id').value;
        const spaceSelect = document.getElementById('space_id');
        
        document.getElementById('time-slots-section').style.display = 'none';
        document.getElementById('duration-container').style.display = 'none';
        document.getElementById('end-date-container').style.display = 'none';
        document.getElementById('spaceDetails').style.display = 'none';
        
        if (!locationId) {
            spaceSelect.innerHTML = '<option value="">Select Location First</option>'; 
            spaceSelect.disabled = true;
            return;
        }
        
        spaceSelect.innerHTML = '<option value="">Loading spaces...</option>';
        spaceSelect.disabled = true;
        
        if (spacesData[locationId]) {
            populateSpaces(spacesData[locationId]);
            return;
        }
        
        fetch(BASE_URL + 'bookings/getSpacesForLocation?location_id=' + locationId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    spacesData[locationId] = data.spaces;
                    populateSpaces(data.spaces);
                } else {
                    spaceSelect.innerHTML = '<option value="">No spaces available</option>';
                    alert('Error: ' + data.error);
                }
            })
            .catch(e => {
                console.error(e);
                spaceSelect.innerHTML = '<option value="">Error loading spaces</option>';
                spaceSelect.disabled = false;
            });
    }

    function populateSpaces(spaces) {
        const spaceSelect = document.getElementById('space_id');
        if (!spaces || spaces.length === 0) {
            spaceSelect.innerHTML = '<option value="">No bookable spaces found</option>';
            spaceSelect.disabled = false;
            return;
        }
        
        let html = '<option value="">Select Space</option>';
        spaces.forEach(space => {
            html += `<option value="${space.id}" 
                data-facility-id="${space.facility_id || ''}"
                data-booking-types='${JSON.stringify(space.booking_types)}'
                data-hourly-rate="${space.hourly_rate || 0}"
                data-daily-rate="${space.daily_rate || 0}"
                data-half-day-rate="${space.half_day_rate || 0}"
                data-weekly-rate="${space.weekly_rate || 0}"
                data-security-deposit="${space.security_deposit || 0}"
                data-capacity="${space.capacity || 0}"
                data-minimum-duration="${space.minimum_duration || 1}"
                data-maximum-duration="${space.maximum_duration || ''}">${space.space_name} (${space.space_number || 'N/A'})</option>`;
        });
        spaceSelect.innerHTML = html;
        spaceSelect.disabled = false;
    }

    function loadSpaceDetails() {
        const spaceSelect = document.getElementById('space_id');
        const opt = spaceSelect.options[spaceSelect.selectedIndex];
        if (!opt || !opt.value) {
            document.getElementById('spaceDetails').style.display = 'none';
            return;
        }
        
        try {
            document.getElementById('facility_id').value = opt.dataset.facilityId || '';
            currentSpaceData = {
                id: opt.value,
                booking_types: JSON.parse(opt.dataset.bookingTypes || '[]'),
                hourly_rate: parseFloat(opt.dataset.hourlyRate),
                daily_rate: parseFloat(opt.dataset.dailyRate),
                half_day_rate: parseFloat(opt.dataset.halfDayRate),
                weekly_rate: parseFloat(opt.dataset.weeklyRate),
                security_deposit: parseFloat(opt.dataset.securityDeposit),
                capacity: parseInt(opt.dataset.capacity),
                minimum_duration: parseInt(opt.dataset.minimumDuration)
            };
            
            document.getElementById('spaceCapacity').textContent = currentSpaceData.capacity || 'N/A';
            document.getElementById('spaceBookingTypes').textContent = currentSpaceData.booking_types.map(t => t.toUpperCase()).join(', ');
            document.getElementById('spaceDetails').style.display = 'block';
            
            const typeSelect = document.getElementById('booking_type');
            typeSelect.innerHTML = '<option value="">Select Booking Type</option>';
            currentSpaceData.booking_types.forEach(t => {
                const o = document.createElement('option');
                o.value = t; o.textContent = t.replace('_', ' ').toUpperCase();
                typeSelect.appendChild(o);
            });
            typeSelect.disabled = false;
        } catch (e) {
            console.error('loadSpaceDetails error:', e);
        }
    }

    function updateDurationOptions(type) {
        const durSelect = document.getElementById('duration');
        const container = document.getElementById('duration-container');
        let options = '';
        container.style.display = 'block';
        
        if (type === 'hourly') {
            for(let i=1; i<=8; i++) options += `<option value="${i}">${i} Hour${i>1?'s':''}</option>`;
            selectedDuration = 1;
        } else if (type === 'daily' || type === 'full_day') {
            options = '<option value="8">8 Hours</option><option value="12">12 Hours</option><option value="24">Full Day (24h)</option>';
            selectedDuration = 8;
        } else {
            container.style.display = 'none';
            selectedDuration = (type === 'half_day') ? 4 : 24;
        }
        durSelect.innerHTML = options;
        durSelect.value = selectedDuration;
    }

    function loadTimeSlots(spaceId, date, endDate = null) {
        if (!spaceId || !date) return;
        const type = document.getElementById('booking_type').value;
        if (!type) return;
        
        const container = document.getElementById('time-slots-container');
        document.getElementById('time-slots-section').style.display = 'block';
        container.innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border text-primary"></div></div>';
        
        // Special types: Unify daily/full_day behavior if needed, otherwise fetch.
        // For now, let's allow fetching for all to see availability.
        
        if (type === 'half_day') { renderHalfDay(); return; }
        
        fetch(`${BASE_URL}bookings/getTimeSlots?space_id=${spaceId}&date=${date}&end_date=${endDate || date}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const all = [...(data.slots || []), ...(data.occupied || [])].sort((a,b) => a.start.localeCompare(b.start));
                    renderSlots(all);
                } else {
                    container.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                }
            })
            .catch(e => {
                console.error(e);
                container.innerHTML = '<div class="alert alert-danger">Error loading slots.</div>';
            });
    }

    function renderSlots(slots) {
        let html = '';
        slots.forEach(s => {
            let cls = 'btn-outline-secondary disabled';
            if (s.available) cls = 'btn-outline-success slot-btn';
            else if (s.is_buffer) cls = 'btn-warning disabled opacity-75';
            else cls = 'btn-danger disabled opacity-50';
            
            const action = s.available ? `data-action="selectSlot" data-start="${s.start}" data-end="${s.end}"` : '';
            html += `<div class="col-md-3 col-6"><button type="button" class="btn ${cls} w-100 shadow-sm mb-2" ${action}>${s.display}</button></div>`;
        });
        document.getElementById('time-slots-container').innerHTML = html || '<p class="text-center w-100">No slots available</p>';
    }

    function renderFullDay() {
        window.setSelection('09:00', '20:00', 'Full Day (9am - 8pm)');
        document.getElementById('time-slots-container').innerHTML = '<div class="col-12"><div class="alert alert-info">Full Day Selected (09:00 - 20:00)</div></div>';
    }

    function renderHalfDay() {
        document.getElementById('time-slots-container').innerHTML = `
            <div class="col-6"><button type="button" class="btn btn-outline-success w-100 py-3 slot-btn" data-action="setSelection" data-start="09:00" data-end="13:00" data-display="Morning Session">Morning (9am-1pm)</button></div>
            <div class="col-6"><button type="button" class="btn btn-outline-success w-100 py-3 slot-btn" data-action="setSelection" data-start="14:00" data-end="18:00" data-display="Afternoon Session">Afternoon (2pm-6pm)</button></div>
        `;
    }

    // Initialization & Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        const dom = {
            location: document.getElementById('location_id'),
            space: document.getElementById('space_id'),
            type: document.getElementById('booking_type'),
            date: document.getElementById('booking_date'),
            endDate: document.getElementById('booking_end_date'),
            duration: document.getElementById('duration'),
            container: document.getElementById('time-slots-container'),
            form: document.getElementById('bookingForm')
        };

        if (dom.location) dom.location.addEventListener('change', loadSpaces);
        if (dom.space) dom.space.addEventListener('change', loadSpaceDetails);
        
        if (dom.type) dom.type.addEventListener('change', function() {
            updateDurationOptions(this.value);
            const edc = document.getElementById('end-date-container');
            if (edc) edc.style.display = (this.value === 'multi_day' || this.value === 'weekly') ? 'block' : 'none';
            if (dom.date.value && currentSpaceData) loadTimeSlots(currentSpaceData.id, dom.date.value);
            calculatePrice();
        });

        if (dom.duration) dom.duration.addEventListener('change', function() {
            selectedDuration = parseInt(this.value) || 1;
            if (dom.date.value && currentSpaceData) loadTimeSlots(currentSpaceData.id, dom.date.value);
        });

        if (dom.date) dom.date.addEventListener('change', function() {
            selectedDate = this.value;
            if (dom.endDate) dom.endDate.min = this.value;
            if (currentSpaceData) loadTimeSlots(currentSpaceData.id, this.value);
            calculatePrice();
        });

        if (dom.endDate) dom.endDate.addEventListener('change', function() {
            if (currentSpaceData) loadTimeSlots(currentSpaceData.id, dom.date.value, this.value);
        });

        if (dom.container) dom.container.addEventListener('click', function(e) {
            const btn = e.target.closest('.slot-btn');
            if (btn && !btn.classList.contains('disabled')) {
                try {
                    const action = btn.dataset.action;
                    if (action === 'setSelection') {
                        window.setSelection(btn.dataset.start, btn.dataset.end, btn.dataset.display);
                    } else if (action === 'selectSlot') {
                        window.selectSlot(btn.dataset.start, btn.dataset.end);
                    }
                } catch (err) {
                    console.error('Selection Error:', err);
                }
            }
        });

        if (dom.form) dom.form.addEventListener('submit', function(e) {
            if (!currentSpaceData) { e.preventDefault(); alert('Please select a space.'); return; }
            if (!document.getElementById('start_time').value) { e.preventDefault(); alert('Please select a time slot.'); return; }
        });
    });
})();
</script>
