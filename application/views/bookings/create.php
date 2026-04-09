<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<style>
    /* Fix for time slot button visibility and coloring - High Contrast Green */
    #time-slots-container .btn-outline-success {
        color: #198754 !important;
        border: 2px solid #198754 !important;
        font-weight: bold !important;
        background-color: transparent !important;
    }
    #time-slots-container .btn-outline-success:hover,
    #time-slots-container .slot-btn.active {
        background-color: #198754 !important;
        color: #fff !important;
    }
    #time-slots-section {
        border-top: 1px solid #eee;
        padding-top: 20px;
    }
    .full-day-slot {
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        cursor: pointer;
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

                    <div class="col-md-4 mb-3" id="guests-container">
                        <label class="form-label">Number of Guests</label>
                        <input type="number" name="number_of_guests" id="number_of_guests" class="form-control" min="1" value="<?= isset($old_input['number_of_guests']) ? max(1, (int)$old_input['number_of_guests']) : 1 ?>">
                        <div id="guests-hint" class="form-text text-muted" style="display:none;">
                            <i class="bi bi-info-circle"></i> Minimum 5 guests required for Picnic bookings.
                        </div>
                        <small class="text-muted" id="capacityWarning" style="display: none; color: red !important;">Exceeds space capacity!</small>
                    </div>

                    <div class="col-md-4 mb-3" id="equipment-tier-container" style="display: none;">
                        <label class="form-label">Type <i class="bi bi-info-circle text-muted" title="Surcharge based on equipment/service level"></i></label>
                        <select name="equipment_tier" id="equipment_tier" class="form-select">
                            <option value="basic" <?= (isset($old_input['equipment_tier']) && $old_input['equipment_tier'] == 'basic') ? 'selected' : '' ?>>Basic</option>
                            <option value="standard" <?= (isset($old_input['equipment_tier']) && $old_input['equipment_tier'] == 'standard') ? 'selected' : '' ?>>Standard</option>
                            <option value="premium" <?= (isset($old_input['equipment_tier']) && $old_input['equipment_tier'] == 'premium') ? 'selected' : '' ?>>Premium</option>
                        </select>
                        <div id="tier-disclaimer" class="mt-2 small text-primary fw-medium" style="display:none;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <span id="tier-disclaimer-text"></span>
                        </div>
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

                <!-- Rental Items Section -->
                <?php if (!empty($rentable_items)): ?>
                <div class="mb-4">
                    <label class="form-label fw-bold">Equipment Rentals</label>
                    <div class="border rounded p-3 bg-light">
                        <div class="row g-3" id="rental-items-container">
                            <?php foreach($rentable_items as $index => $item): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0 text-truncate" title="<?= htmlspecialchars($item['name']) ?>">
                                                <?= htmlspecialchars($item['name']) ?>
                                            </h6>
                                            <span class="badge bg-primary rounded-pill">₦<?= number_format($item['rental_rate'], 2) ?>/<?= $item['rental_rate_type'] ?></span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-3">
                                            <small class="text-muted">Available: <span class="fw-bold"><?= $item['current_stock'] ?></span></small>
                                            <div class="input-group input-group-sm" style="width: 110px;">
                                                <input type="hidden" name="rental_items[<?= $index ?>][item_id]" value="<?= $item['id'] ?>">
                                                <input type="hidden" name="rental_items[<?= $index ?>][rental_rate]" value="<?= $item['rental_rate'] ?>">
                                                <button class="btn btn-outline-dark btn-rental-minus" type="button">-</button>
                                                <input type="number" name="rental_items[<?= $index ?>][quantity]" class="form-control text-center rental-qty" 
                                                       value="0" min="0" max="<?= $item['current_stock'] ?>" data-price="<?= $item['rental_rate'] ?>">
                                                <button class="btn btn-outline-dark btn-rental-plus" type="button">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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
    let lastSelectedBtn = null;
    
    // Helper for safe element access
    function safeStyle(id, prop, val) {
        const el = document.getElementById(id);
        if (el) el.style[prop] = val;
    }
    
    function safeText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    // Hoisted Functions (attached to window for cross-scope access)
    window.setSelection = function(start, end, display, btn = null) {
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

        // Visual Highlighting
        document.querySelectorAll('.slot-btn').forEach(b => {
             b.classList.remove('active', 'btn-success');
             if (b.classList.contains('available-slot')) {
                 b.classList.add('btn-outline-success');
             }
        });

        if (btn) {
            btn.classList.add('active', 'btn-success');
            btn.classList.remove('btn-outline-success');
            lastSelectedBtn = btn;
        } else {
            // Try to find the button if not provided
            const allBtn = document.querySelectorAll('.slot-btn');
            allBtn.forEach(b => {
                if (b.dataset.start === start) {
                    b.classList.add('active', 'btn-success');
                    b.classList.remove('btn-outline-success');
                    lastSelectedBtn = b;
                }
            });
        }
        
        calculatePrice(); 
    };

    window.selectSlot = function(start, end, btn = null) {
        // Simplified for pre-calculated blocks
        const startDisplay = formatDisplayTime(parseTime(start));
        const endDisplay = formatDisplayTime(parseTime(end));
        window.setSelection(start, end, `${startDisplay} - ${endDisplay}`, btn);
    };

    function updateTierDisclaimer() {
        const tierSelect = document.getElementById('equipment_tier');
        if (!tierSelect) return;
        
        const tierDisclaimer = document.getElementById('tier-disclaimer');
        const tierText = document.getElementById('tier-disclaimer-text');
        const val = tierSelect.value;
        
        const disclaimers = {
            'basic': 'This tier covers the use of a mobile phone only.',
            'standard': 'This tier covers the use of a professional camera.',
            'premium': 'This tier covers the use of production-grade equipment.'
        };
        
        const container = document.getElementById('equipment-tier-container');
        if (val && disclaimers[val] && container && container.style.display !== 'none') {
            tierText.textContent = disclaimers[val];
            tierDisclaimer.style.display = 'block';
        } else {
            tierDisclaimer.style.display = 'none';
        }
    }

    function parseTime(t) {
        if (!t) return 0;
        const [h, m] = t.split(':').map(Number);
        return h * 60 + m;
    }

    function formatTime(minutes) {
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
    }

    function formatDisplayTime(minutes) {
        let h = Math.floor(minutes / 60);
        const m = minutes % 60;
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        h = h ? h : 12;
        return `${h}:${String(m).padStart(2,'0')} ${ampm}`;
    }

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
        
        let guests = parseInt(document.getElementById('number_of_guests').value) || 1;
        if (bookingType === 'picnic' || bookingType === 'photoshoot' || bookingType === 'videoshoot' || bookingType === 'workspace') {
            // Per-person pricing logic
            let pRules = currentSpaceData.per_person_rates || {};
            let wRules = currentSpaceData.workspace_rates || {};
            
            if (bookingType === 'workspace') {
                let ppRate = wRules.per_person_daily || currentSpaceData.daily_rate || 0;
                if (days >= 7 && wRules.per_person_weekly) {
                    let weeks = Math.ceil(days / 7);
                    basePrice = parseFloat(wRules.per_person_weekly) * Math.max(1, guests) * weeks;
                } else if (days >= 28 && wRules.per_person_monthly) {
                    let months = Math.ceil(days / 28);
                    basePrice = parseFloat(wRules.per_person_monthly) * Math.max(1, guests) * months;
                } else {
                    basePrice = parseFloat(ppRate) * Math.max(1, guests) * Math.max(1, days);
                }
            } else {
                // Picnic/Photoshoot/Videoshoot
                let typeRates = pRules[bookingType] || {};
                let basePp = parseFloat(typeRates.base_per_person || currentSpaceData.hourly_rate || 0);
                let surcharge = 0;

                if (bookingType === 'picnic') {
                    // Tier auto-determined by guest count
                    let picnicTier = guests <= 20 ? 'basic' : (guests <= 40 ? 'standard' : 'premium');
                    if (typeRates.equipment_tiers && typeRates.equipment_tiers[picnicTier]) {
                        surcharge = parseFloat(typeRates.equipment_tiers[picnicTier].surcharge || 0);
                    }
                    basePrice = (basePp + surcharge) * Math.max(5, guests);
                } else {
                    // Photoshoot/Videoshoot: equipment tier surcharge, per-project
                    if (equipmentTier && typeRates.equipment_tiers && typeRates.equipment_tiers[equipmentTier]) {
                        surcharge = parseFloat(typeRates.equipment_tiers[equipmentTier].surcharge || 0);
                    }
                    basePrice = (basePp + surcharge) * Math.max(1, guests);
                }
            }
        } else {
            // Time-based pricing logic
            switch(bookingType) {
                case 'hourly': basePrice = (currentSpaceData.hourly_rate || 0) * hours; break;
                case 'daily': 
                case 'full_day': basePrice = (currentSpaceData.daily_rate || 0) * days; break;
                case 'half_day': basePrice = (currentSpaceData.half_day_rate || 0) * Math.ceil(days * 2); break;
                case 'weekly': basePrice = (currentSpaceData.weekly_rate || 0) * Math.ceil(days / 7); break;
                case 'multi_day': basePrice = (currentSpaceData.daily_rate || 0) * days; break;
                default: basePrice = (currentSpaceData.hourly_rate || 0) * hours;
            }
        }
        
        // Calculate Rentals
        let rentalTotal = 0;
        document.querySelectorAll('.rental-qty').forEach(input => {
            let qty = parseInt(input.value) || 0;
            let price = parseFloat(input.dataset.price) || 0;
            rentalTotal += (qty * price);
        });
        
        const subTotal = Math.max(0, basePrice - discount) + rentalTotal;
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
        if (!spaceSelect) return;
        
        safeStyle('time-slots-section', 'display', 'none');
        safeStyle('duration-container', 'display', 'none');
        safeStyle('end-date-container', 'display', 'none');
        safeStyle('spaceDetails', 'display', 'none');
        safeStyle('equipment-tier-container', 'display', 'none');
        
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
                data-maximum-duration="${space.maximum_duration || ''}"
                data-per-person-rates='${JSON.stringify(space.per_person_rates || {})}'
                data-workspace-rates='${JSON.stringify(space.workspace_rates || {})}'
                >${space.space_name} (${space.space_number || 'N/A'})</option>`;
        });
        spaceSelect.innerHTML = html;
        spaceSelect.disabled = false;
    }

    function loadSpaceDetails() {
        const spaceSelect = document.getElementById('space_id');
        const opt = spaceSelect.options[spaceSelect.selectedIndex];
        if (!opt || !opt.value) {
            safeStyle('spaceDetails', 'display', 'none');
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
                minimum_duration: parseInt(opt.dataset.minimumDuration),
                per_person_rates: JSON.parse(opt.dataset.perPersonRates || '{}'),
                workspace_rates: JSON.parse(opt.dataset.workspaceRates || '{}')
            };
            
            safeText('spaceCapacity', currentSpaceData.capacity || '0');
            safeText('spaceBookingTypes', currentSpaceData.booking_types.map(t => t.toUpperCase()).join(', '));
            safeStyle('spaceDetails', 'display', 'block');
            
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
        if (container) container.style.display = 'block';
        
        if (type === 'hourly') {
            for(let i=1; i<=8; i++) options += `<option value="${i}">${i} Hour${i>1?'s':''}</option>`;
            selectedDuration = 1;
        } else if (type === 'daily' || type === 'full_day') {
            options = '<option value="8">8 Hours</option><option value="12">12 Hours</option><option value="24">Full Day (24h)</option>';
            selectedDuration = 8;
        } else if (type === 'picnic' || type === 'photoshoot' || type === 'videoshoot') {
            options = '<option value="4">4 Hours</option><option value="5">5 Hours</option><option value="6">6 Hours</option><option value="7">7 Hours</option><option value="8">8 Hours</option>';
            selectedDuration = 4;
        } else {
            if (container) container.style.display = 'none';
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
        safeStyle('time-slots-section', 'display', 'block');
        container.innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border text-primary"></div></div>';
        
        if (type === 'half_day') { renderHalfDay(); return; }
        
        fetch(`${BASE_URL}bookings/getTimeSlots?space_id=${spaceId}&date=${date}&end_date=${endDate || date}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (type === 'full_day' || (type === 'daily' && selectedDuration == 24)) {
                        renderFullDay(data);
                    } else {
                        const all = [...(data.slots || []), ...(data.occupied || [])].sort((a,b) => a.start.localeCompare(b.start));
                        renderSlots(all);
                    }
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
        const container = document.getElementById('time-slots-container');
        let renderedBoxes = [];
        
        // Filter available slots
        const availableSlots = slots.filter(s => s.available);
        const occupiedSlots = slots.filter(s => !s.available);

        availableSlots.forEach(slot => {
            const startMin = parseTime(slot.start);
            const targetEndMin = startMin + (selectedDuration * 60);
            
            let isFeasible = true;
            if (selectedDuration > 1) {
                for (let i = 1; i < selectedDuration; i++) {
                    const requiredStart = formatTime(startMin + (i * 60));
                    const foundNext = availableSlots.find(s => s.start === requiredStart);
                    if (!foundNext) {
                        isFeasible = false;
                        break;
                    }
                }
            }

            if (isFeasible && targetEndMin <= 24 * 60) {
                const endDisplay = formatDisplayTime(targetEndMin);
                const endDbStr = formatTime(targetEndMin);
                
                renderedBoxes.push({
                    startMin: startMin,
                    html: `
                    <div class="col-md-4 col-lg-3">
                        <button type="button" class="btn btn-outline-success w-100 slot-btn available-slot h-100 py-3 mb-2" 
                                data-action="selectSlot"
                                data-start="${slot.start}" 
                                data-end="${endDbStr}">
                            <div class="fw-bold">${formatDisplayTime(startMin)} - ${endDisplay}</div>
                            <div class="small">${selectedDuration} Hour${selectedDuration > 1 ? 's' : ''}</div>
                        </button>
                    </div>`
                });
            }
        });

        occupiedSlots.forEach(slot => {
            const startMin = parseTime(slot.start);
            let btnClass = slot.is_buffer ? 'btn-warning' : 'btn-danger';
            let label = slot.is_buffer ? 'Buffer' : 'Occupied';
            
            renderedBoxes.push({
                startMin: startMin,
                html: `
                <div class="col-md-4 col-lg-3">
                    <button type="button" class="btn ${btnClass} w-100 slot-btn occupied disabled h-100 py-3 mb-2" disabled>
                        <div class="fw-bold">${slot.display}</div>
                        <div class="small">${label}</div>
                    </button>
                </div>`
            });
        });

        // Sort boxes by start time
        renderedBoxes.sort((a, b) => a.startMin - b.startMin);
        
        if (renderedBoxes.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-info">No available slots for the selected duration.</div></div>';
        } else {
            container.innerHTML = renderedBoxes.map(b => b.html).join('');
        }
    }

    function renderFullDay(data) {
        const container = document.getElementById('time-slots-container');
        const isOccupied = (data.occupied && data.occupied.length > 0);
        
        if (isOccupied) {
            container.innerHTML = `
                <div class="col-12">
                    <button type="button" class="btn btn-danger w-100 full-day-slot disabled" style="height:80px">
                        <b>Full Day Unavailable</b><br><small>Some hours are already booked</small>
                    </button>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="col-12">
                    <button type="button" class="btn btn-outline-success w-100 full-day-slot slot-btn" 
                        data-action="setSelection" data-start="09:00" data-end="20:00" data-display="Full Day (9am - 8pm)" 
                        style="height:80px">
                        <b>Full Day Available</b><br><small>Click to select (9am - 8pm)</small>
                    </button>
                </div>
            `;
        }
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
            const etc = document.getElementById('equipment-tier-container');
            const gtc = document.getElementById('guests-container');
            
            if (edc) edc.style.display = (this.value === 'multi_day' || this.value === 'weekly') ? 'block' : 'none';
            if (gtc) gtc.style.display = (this.value === 'picnic' || this.value === 'workspace') ? 'block' : 'none';
            if (etc) etc.style.display = (this.value === 'photoshoot' || this.value === 'videoshoot') ? 'block' : 'none';

            // Enforce minimum 5 guests for picnic
            const guestsInputEl = document.getElementById('number_of_guests');
            const guestsHintEl = document.getElementById('guests-hint');
            if (this.value === 'picnic' && guestsInputEl) {
                guestsInputEl.min = 5;
                if (parseInt(guestsInputEl.value) < 5) guestsInputEl.value = 5;
                if (guestsHintEl) guestsHintEl.style.display = 'block';
            } else if (guestsInputEl) {
                guestsInputEl.min = 1;
                if (guestsHintEl) guestsHintEl.style.display = 'none';
            }
            
            updateTierDisclaimer();
            
            if (dom.date.value && currentSpaceData) loadTimeSlots(currentSpaceData.id, dom.date.value);
            calculatePrice();
        });

        // Add listeners for per-person triggers
        const guestsInput = document.getElementById('number_of_guests');
        const equipmentTier = document.getElementById('equipment_tier');
        const discountInput = document.getElementById('discount_amount');
        
        if (guestsInput) guestsInput.addEventListener('input', calculatePrice);
        if (equipmentTier) {
            equipmentTier.addEventListener('change', function() {
                updateTierDisclaimer();
                calculatePrice();
            });
        }
        if (discountInput) discountInput.addEventListener('input', calculatePrice);
        
        // Rental items +/- buttons
        document.querySelectorAll('.btn-rental-plus').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const max = parseInt(input.getAttribute('max')) || 999;
                if (parseInt(input.value) < max) {
                    input.value = parseInt(input.value) + 1;
                    calculatePrice();
                }
            });
        });
        
        document.querySelectorAll('.btn-rental-minus').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.nextElementSibling;
                if (parseInt(input.value) > 0) {
                    input.value = parseInt(input.value) - 1;
                    calculatePrice();
                }
            });
        });
        
        document.querySelectorAll('.rental-qty').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.getAttribute('max')) || 999;
                const val = parseInt(this.value) || 0;
                if (val < 0) this.value = 0;
                if (val > max) this.value = max;
                calculatePrice();
            });
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
                        window.setSelection(btn.dataset.start, btn.dataset.end, btn.dataset.display, btn);
                    } else if (action === 'selectSlot') {
                        window.selectSlot(btn.dataset.start, btn.dataset.end, btn);
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
