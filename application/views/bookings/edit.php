<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Booking: <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h1>
        <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-primary">
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
            <h5 class="mb-0">Edit Booking Details</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <?php echo csrf_field(); ?>
                
                <!-- Booking Info -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Booking Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($booking['booking_number'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <select name="booking_date" id="booking_date_select" class="form-select" required>
                            <option value="<?= htmlspecialchars($booking['booking_date'] ?? '') ?>" selected>
                                <?= htmlspecialchars(date('M d, Y', strtotime($booking['booking_date'] ?? date('Y-m-d')))) ?> (current)
                            </option>
                        </select>
                        <small class="text-muted">Only dates with available slots are listed.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Time <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="start_time" id="start_time_select" class="form-select" required>
                                    <option value="<?= htmlspecialchars(substr((string)($booking['start_time'] ?? ''), 0, 5)) ?>" selected>
                                        <?= htmlspecialchars(date('h:i A', strtotime($booking['start_time'] ?? '00:00:00'))) ?> (current)
                                    </option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="end_time" id="end_time_select" class="form-select" required>
                                    <option value="<?= htmlspecialchars(substr((string)($booking['end_time'] ?? ''), 0, 5)) ?>" selected>
                                        <?= htmlspecialchars(date('h:i A', strtotime($booking['end_time'] ?? '00:00:00'))) ?> (current)
                                    </option>
                                </select>
                            </div>
                        </div>
                        <small class="text-muted" id="durationHint">Duration is automatically preserved.</small>
                    </div>
                </div>

                <!-- Customer Information -->
                <h5 class="mb-3">Customer Information</h5>
                <?php $isSuperAdmin = (($this->session['role'] ?? '') === 'super_admin'); ?>
                <?php if ($isSuperAdmin): ?>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="customer_id" class="form-label">Linked Customer</label>
                        <select name="customer_id" id="customer_id" class="form-select">
                            <?php foreach (($customers ?? []) as $customer): ?>
                                <?php
                                    $customerLabel = trim(($customer['company_name'] ?? '') !== '' ? $customer['company_name'] : ($customer['contact_name'] ?? ('Customer #' . intval($customer['id']))));
                                    $selected = intval($booking['customer_id'] ?? 0) === intval($customer['id']) ? 'selected' : '';
                                ?>
                                <option value="<?= intval($customer['id']) ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($customerLabel) ?><?= !empty($customer['email']) ? ' (' . htmlspecialchars($customer['email']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Changing this customer will transfer booking, linked invoice, and linked payment ownership.</small>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#inlineCustomerModal">
                            <i class="bi bi-person-plus"></i> Add Customer
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" 
                                   value="<?= htmlspecialchars($booking['customer_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control" 
                                   value="<?= htmlspecialchars($booking['customer_email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="customer_phone" class="form-control" 
                                   value="<?= htmlspecialchars($booking['customer_phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Number of Guests</label>
                            <input type="number" name="number_of_guests" class="form-control" min="0"
                                   value="<?= intval($booking['number_of_guests'] ?? 0) ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Customer Address</label>
                    <textarea name="customer_address" class="form-control" rows="2"><?= htmlspecialchars($booking['customer_address'] ?? '') ?></textarea>
                </div>

                <!-- Booking Notes -->
                <h5 class="mb-3 mt-4">Additional Information</h5>
                <div class="mb-3">
                    <label class="form-label">Booking Notes</label>
                    <textarea name="booking_notes" class="form-control" rows="2"><?= htmlspecialchars($booking['booking_notes'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Special Requests</label>
                    <textarea name="special_requests" class="form-control" rows="2"><?= htmlspecialchars($booking['special_requests'] ?? '') ?></textarea>
                </div>

                <!-- Pricing -->
                <h5 class="mb-3 mt-4">Pricing</h5>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Base Amount</label>
                        <input type="text" class="form-control" value="<?= format_currency($booking['base_amount'] ?? 0) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Discount Amount</label>
                        <input type="number" name="discount_amount" class="form-control" step="0.01" min="0"
                               value="<?= floatval($booking['discount_amount'] ?? 0) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" value="<?= format_currency($booking['total_amount'] ?? 0) ?>" readonly>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('bookings/view/' . $booking['id']) ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">
(function () {
    const bookingId = <?= intval($booking['id'] ?? 0) ?>;
    const facilityId = <?= intval($booking['facility_id'] ?? ($booking['space_id'] ?? 0)) ?>;
    const currentDate = '<?= htmlspecialchars($booking['booking_date'] ?? '') ?>';
    const currentStart = '<?= htmlspecialchars(substr((string)($booking['start_time'] ?? ''), 0, 5)) ?>';
    const currentEnd = '<?= htmlspecialchars(substr((string)($booking['end_time'] ?? ''), 0, 5)) ?>';
    const durationHours = <?= max(0.5, floatval($booking['duration_hours'] ?? 0)) ?>;
    const durationMinutes = Math.max(30, Math.round(durationHours * 60));

    const dateSelect = document.getElementById('booking_date_select');
    const startSelect = document.getElementById('start_time_select');
    const endSelect = document.getElementById('end_time_select');

    function toMinutes(timeValue) {
        const parts = String(timeValue || '').split(':');
        const h = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        if (isNaN(h) || isNaN(m)) return null;
        return (h * 60) + m;
    }

    function toTime(minutes) {
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    }

    function displayTime(timeValue) {
        const mins = toMinutes(timeValue);
        if (mins === null) return timeValue;
        let hour = Math.floor(mins / 60);
        const minute = mins % 60;
        const suffix = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        return hour + ':' + String(minute).padStart(2, '0') + ' ' + suffix;
    }

    function displayDate(dateValue) {
        const dt = new Date(dateValue + 'T00:00:00');
        if (isNaN(dt.getTime())) return dateValue;
        return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function setSelectOptions(select, options, selectedValue) {
        const normalizedSelected = String(selectedValue || '');
        let html = '';
        options.forEach(opt => {
            const selected = String(opt.value) === normalizedSelected ? ' selected' : '';
            html += '<option value="' + escapeHtml(opt.value) + '"' + selected + '>' + escapeHtml(opt.label) + '</option>';
        });
        select.innerHTML = html;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function computeValidStartTimes(availableSlots) {
        const starts = availableSlots
            .filter(slot => !!slot.available)
            .map(slot => toMinutes(slot.start))
            .filter(v => v !== null)
            .sort((a, b) => a - b);

        const startSet = new Set(starts);
        const requiredSteps = Math.max(1, Math.ceil(durationMinutes / 60));
        const valid = [];

        starts.forEach(startMin => {
            let ok = true;
            for (let step = 0; step < requiredSteps; step++) {
                const candidate = startMin + (step * 60);
                if (!startSet.has(candidate)) {
                    ok = false;
                    break;
                }
            }
            if (ok && (startMin + durationMinutes) <= (24 * 60)) {
                valid.push(startMin);
            }
        });

        return Array.from(new Set(valid));
    }

    function populateEndTimeFromStart() {
        const selectedStart = startSelect.value;
        if (!selectedStart) {
            setSelectOptions(endSelect, [{ value: '', label: 'Select start time first' }], '');
            return;
        }
        const startMin = toMinutes(selectedStart);
        const endMin = startMin + durationMinutes;
        const endValue = toTime(endMin);
        setSelectOptions(endSelect, [{ value: endValue, label: displayTime(endValue) }], endValue);
    }

    function loadSlotsForDate(dateValue) {
        if (!dateValue || !facilityId) return;

        setSelectOptions(startSelect, [{ value: '', label: 'Loading available times...' }], '');
        setSelectOptions(endSelect, [{ value: '', label: 'Loading...' }], '');

        fetch(`<?= base_url('bookings/get-slots') ?>?facility_id=${facilityId}&date=${encodeURIComponent(dateValue)}&exclude_booking_id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load slots.');
                }

                const validStartMinutes = computeValidStartTimes(data.slots || []);
                const startOptions = validStartMinutes.map(mins => {
                    const value = toTime(mins);
                    return { value: value, label: displayTime(value) };
                });

                if (dateValue === currentDate && currentStart) {
                    const exists = startOptions.some(opt => opt.value === currentStart);
                    if (!exists) {
                        startOptions.unshift({ value: currentStart, label: displayTime(currentStart) + ' (current)' });
                    }
                }

                if (startOptions.length === 0) {
                    setSelectOptions(startSelect, [{ value: '', label: 'No available times for selected date' }], '');
                    setSelectOptions(endSelect, [{ value: '', label: 'No end time available' }], '');
                    return;
                }

                const preferredStart = (dateValue === currentDate) ? currentStart : startOptions[0].value;
                setSelectOptions(startSelect, startOptions, preferredStart);
                populateEndTimeFromStart();
            })
            .catch(() => {
                setSelectOptions(startSelect, [{ value: '', label: 'Failed to load times. Try another date.' }], '');
                setSelectOptions(endSelect, [{ value: '', label: 'Unavailable' }], '');
            });
    }

    function loadAvailableDates() {
        if (!facilityId) {
            setSelectOptions(dateSelect, [{ value: currentDate, label: displayDate(currentDate) + ' (current)' }], currentDate);
            return;
        }

        setSelectOptions(dateSelect, [{ value: '', label: 'Loading available dates...' }], '');
        fetch(`<?= base_url('bookings/get-available-dates') ?>?facility_id=${facilityId}&exclude_booking_id=${bookingId}&days=60`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load dates.');
                }

                const options = (data.dates || []).map(item => ({
                    value: item.date,
                    label: displayDate(item.date) + ' (' + (item.slots_count || 0) + ' slots)'
                }));

                if (currentDate) {
                    const hasCurrent = options.some(opt => opt.value === currentDate);
                    if (!hasCurrent) {
                        options.unshift({ value: currentDate, label: displayDate(currentDate) + ' (current)' });
                    }
                }

                if (options.length === 0) {
                    options.push({ value: currentDate, label: displayDate(currentDate) + ' (current)' });
                }

                const selectedDate = currentDate || options[0].value;
                setSelectOptions(dateSelect, options, selectedDate);
                loadSlotsForDate(selectedDate);
            })
            .catch(() => {
                const fallbackDate = currentDate || '';
                setSelectOptions(dateSelect, [{ value: fallbackDate, label: displayDate(fallbackDate) + ' (current)' }], fallbackDate);
                loadSlotsForDate(fallbackDate);
            });
    }

    dateSelect.addEventListener('change', function () {
        loadSlotsForDate(this.value);
    });

    startSelect.addEventListener('change', populateEndTimeFromStart);
    loadAvailableDates();
})();
</script>

<?php if ($isSuperAdmin): ?>
<div class="modal fade" id="inlineCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="inlineCustomerAlert" class="alert d-none"></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" id="inline_company_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Name</label>
                        <input type="text" id="inline_contact_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" id="inline_email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" id="inline_phone" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Address</label>
                        <textarea id="inline_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="inlineCreateCustomerBtn" class="btn btn-primary">Create Customer</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const customers = <?= json_encode(array_values($customers ?? [])) ?>;
    const customerSelect = document.getElementById('customer_id');
    const nameInput = document.querySelector('input[name="customer_name"]');
    const emailInput = document.querySelector('input[name="customer_email"]');
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const addressInput = document.querySelector('textarea[name="customer_address"]');
    const createBtn = document.getElementById('inlineCreateCustomerBtn');
    const alertBox = document.getElementById('inlineCustomerAlert');

    function showAlert(message, type) {
        alertBox.className = 'alert alert-' + type;
        alertBox.textContent = message;
    }

    function hideAlert() {
        alertBox.className = 'alert d-none';
        alertBox.textContent = '';
    }

    function fillCustomerFields(customerId) {
        const selected = customers.find(c => Number(c.id) === Number(customerId));
        if (!selected) return;
        if (nameInput) {
            nameInput.value = selected.company_name || selected.contact_name || nameInput.value;
        }
        if (emailInput) {
            emailInput.value = selected.email || '';
        }
        if (phoneInput) {
            phoneInput.value = selected.phone || '';
        }
        if (addressInput) {
            addressInput.value = selected.address || '';
        }
    }

    if (customerSelect) {
        customerSelect.addEventListener('change', function () {
            fillCustomerFields(this.value);
        });
    }

    createBtn.addEventListener('click', function () {
        hideAlert();
        const companyName = document.getElementById('inline_company_name').value.trim();
        if (!companyName) {
            showAlert('Company name is required.', 'danger');
            return;
        }

        const formData = new FormData();
        formData.append('company_name', companyName);
        formData.append('contact_name', document.getElementById('inline_contact_name').value.trim());
        formData.append('email', document.getElementById('inline_email').value.trim());
        formData.append('phone', document.getElementById('inline_phone').value.trim());
        formData.append('address', document.getElementById('inline_address').value.trim());
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        createBtn.disabled = true;
        fetch('<?= base_url('bookings/createCustomerInline') ?>', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(async response => {
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Failed to create customer.');
            }
            return payload;
        })
        .then(payload => {
            const c = payload.customer;
            customers.push(c);
            const label = (c.company_name || c.contact_name || ('Customer #' + c.id)) + (c.email ? ' (' + c.email + ')' : '');
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = label;
            customerSelect.appendChild(opt);
            customerSelect.value = String(c.id);
            fillCustomerFields(c.id);

            const modalEl = document.getElementById('inlineCustomerModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        })
        .catch(err => {
            showAlert(err.message, 'danger');
        })
        .finally(() => {
            createBtn.disabled = false;
        });
    });
})();
</script>
<?php endif; ?>
