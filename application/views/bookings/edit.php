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
                        <label class="form-label">Venue <span class="text-danger">*</span></label>
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
                    <div class="col-md-4">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <?php
                        $editBookingDate = !empty($booking['booking_date'])
                            ? date('Y-m-d', strtotime($booking['booking_date']))
                            : date('Y-m-d');
                        ?>
                        <input type="date" name="booking_date" id="booking_date_input" class="form-control"
                               value="<?= htmlspecialchars($editBookingDate) ?>" required>
                    </div>
                </div>
                <input type="hidden" name="start_time" id="start_time_input" value="<?= htmlspecialchars(substr((string)($booking['start_time'] ?? ''), 0, 5)) ?>">
                <input type="hidden" name="end_time" id="end_time_input" value="<?= htmlspecialchars(substr((string)($booking['end_time'] ?? ''), 0, 5)) ?>">
                <div class="mb-4">
                    <h6 class="mb-2">Time Slots</h6>
                    <div class="d-flex gap-2 mb-2">
                        <span class="badge bg-success">Available</span>
                        <span class="badge bg-danger">Occupied</span>
                        <span class="badge bg-warning text-dark">Buffer (1 hour gap)</span>
                    </div>
                    <div id="time_slots_container" class="row g-2"></div>
                    <div id="selected-slot-display" class="alert alert-success mt-2 d-none">
                        Selected: <strong id="selected-slot-text"></strong>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="card border-info">
                        <div class="card-header bg-light"><strong>Updated Price Preview</strong></div>
                        <div class="card-body py-2">
                            <div id="quote_preview_status" class="small text-muted">Select venue, date, and time slot to preview updated price.</div>
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
    const currentDate = '<?= htmlspecialchars($booking['booking_date'] ?? '') ?>';
    const currentStart = '<?= htmlspecialchars(substr((string)($booking['start_time'] ?? ''), 0, 5)) ?>';
    const currentEnd = '<?= htmlspecialchars(substr((string)($booking['end_time'] ?? ''), 0, 5)) ?>';

    const venueSelect = document.getElementById('space_select');
    const dateInput = document.getElementById('booking_date_input');
    const slotContainer = document.getElementById('time_slots_container');
    const selectedDisplay = document.getElementById('selected-slot-display');
    const selectedText = document.getElementById('selected-slot-text');
    const startInput = document.getElementById('start_time_input');
    const endInput = document.getElementById('end_time_input');
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
    const discountInput = document.querySelector('input[name="discount_amount"]');
    const guestsInput = document.querySelector('input[name="number_of_guests"]');

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatTime(t) {
        const [h, m] = String(t || '').split(':').map(Number);
        if (isNaN(h) || isNaN(m)) return t;
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
    }

    function getFacilityId() {
        const option = venueSelect?.options?.[venueSelect.selectedIndex];
        return parseInt(option?.dataset?.facilityId || '0', 10);
    }

    function setSelectedSlot(start, end, activeBtn) {
        startInput.value = start || '';
        endInput.value = end || '';
        if (start && end) {
            selectedText.textContent = `${formatTime(start)} - ${formatTime(end)}`;
            selectedDisplay.classList.remove('d-none');
        } else {
            selectedDisplay.classList.add('d-none');
        }

        if (activeBtn) {
            slotContainer.querySelectorAll('.slot-card').forEach(btn => {
                btn.classList.remove('btn-success', 'active');
                btn.classList.add('btn-outline-success');
            });
            activeBtn.classList.remove('btn-outline-success');
            activeBtn.classList.add('btn-success', 'active');
        }
        updateQuote();
    }

    function formatCurrency(num) {
        return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(Number(num || 0));
    }

    function formatSignedDelta(diff) {
        const n = Number(diff);
        if (!Number.isFinite(n) || Math.abs(n) < 0.005) {
            return { html: '<span class="text-muted">No change</span>', raw: 'No change' };
        }
        const sign = n > 0 ? '+' : '−';
        const cls = n > 0 ? 'text-danger' : 'text-success';
        const body = formatCurrency(Math.abs(n));
        return { html: `<span class="${cls}">${sign} ${body}</span>`, raw: sign + ' ' + body };
    }

    function applyQuoteDeltas(quote) {
        if (!quoteDeltaTotal || !quoteDeltaBalance) return;
        const dt = formatSignedDelta(Number(quote.total_amount) - savedTotalAmount);
        const db = formatSignedDelta(Number(quote.balance_amount) - savedBalanceAmount);
        quoteDeltaTotal.innerHTML = dt.html;
        quoteDeltaBalance.innerHTML = db.html;
    }

    function clearQuoteDeltas() {
        if (quoteDeltaTotal) quoteDeltaTotal.textContent = '-';
        if (quoteDeltaBalance) quoteDeltaBalance.textContent = '-';
    }

    const defaultQuoteStatus = 'Select venue, date, and time slot to preview updated price.';

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
        const facilityId = getFacilityId();
        const dateValue = dateInput.value;
        const startValue = startInput.value;
        const endValue = endInput.value;
        if (!facilityId || !dateValue || !startValue || !endValue) {
            clearQuotePreview();
            return;
        }

        const params = new URLSearchParams({
            space_id: String(venueSelect.value || ''),
            booking_date: String(dateValue),
            start_time: String(startValue),
            end_time: String(endValue),
            discount_amount: String(discountInput?.value || 0),
            number_of_guests: String(guestsInput?.value || 1)
        });
        quoteStatus.textContent = 'Calculating...';
        fetch(`<?= base_url('bookings/reschedule-quote/' . intval($booking['id'] ?? 0)) ?>?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.quote) throw new Error(data.message || 'Failed to calculate quote');
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

    function renderSlots(data, dateValue) {
        const available = (data.slots || []).filter(s => !!s.available);
        const occupied = data.occupied || [];
        const cards = [];
        const today = (new Date()).toISOString().slice(0, 10);

        available.forEach(slot => {
            cards.push({
                start: slot.start,
                html: `<div class="col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-success w-100 slot-card available-slot" data-start="${escapeHtml(slot.start)}" data-end="${escapeHtml(slot.end)}">
                            <small class="d-block text-muted">${slot.date === today ? 'Today' : escapeHtml(slot.date)}</small>
                            <span class="fw-bold">${escapeHtml(formatTime(slot.start))} - ${escapeHtml(formatTime(slot.end))}</span>
                            <div class="small text-success">Available</div>
                        </button>
                    </div>`
            });
        });

        occupied.forEach(slot => {
            const isBuffer = !!slot.is_buffer;
            cards.push({
                start: slot.start,
                html: `<div class="col-md-6 col-lg-4">
                        <button type="button" class="btn ${isBuffer ? 'btn-warning text-dark' : 'btn-danger'} w-100" disabled>
                            <small class="d-block ${isBuffer ? 'text-dark' : 'text-white'}">${escapeHtml(slot.date || dateValue)}</small>
                            <span class="fw-bold">${escapeHtml(formatTime(slot.start))} - ${escapeHtml(formatTime(slot.end))}</span>
                            <div class="small ${isBuffer ? 'text-dark fw-bold' : 'text-white'}">${isBuffer ? 'Buffer' : 'Occupied'}</div>
                        </button>
                    </div>`
            });
        });

        cards.sort((a, b) => String(a.start).localeCompare(String(b.start)));
        if (cards.length === 0) {
            slotContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">No available time slots on this date.</div></div>';
            setSelectedSlot('', '');
            return;
        }

        slotContainer.innerHTML = cards.map(c => c.html).join('');
        slotContainer.querySelectorAll('.available-slot').forEach(btn => {
            btn.addEventListener('click', function () {
                setSelectedSlot(this.dataset.start, this.dataset.end, this);
            });
        });

        const currentBtn = Array.from(slotContainer.querySelectorAll('.available-slot'))
            .find(b => b.dataset.start === currentStart && b.dataset.end === currentEnd && dateValue === currentDate);
        if (currentBtn) {
            setSelectedSlot(currentStart, currentEnd, currentBtn);
        } else {
            setSelectedSlot('', '');
        }
    }

    function loadSlots() {
        const facilityId = getFacilityId();
        const dateValue = dateInput.value;
        if (!facilityId || !dateValue) {
            slotContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">Select venue and date to load slots.</div></div>';
            setSelectedSlot('', '');
            return;
        }

        clearQuotePreview();
        slotContainer.innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';
        fetch(`<?= base_url('bookings/get-slots') ?>?facility_id=${facilityId}&date=${encodeURIComponent(dateValue)}&exclude_booking_id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load slots.');
                }
                renderSlots(data, dateValue);
            })
            .catch(() => {
                slotContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">Failed to load slots. Try another date.</div></div>';
                clearQuotePreview();
                setSelectedSlot('', '');
            });
    }

    venueSelect?.addEventListener('change', loadSlots);
    dateInput?.addEventListener('change', loadSlots);
    discountInput?.addEventListener('input', updateQuote);
    guestsInput?.addEventListener('input', updateQuote);
    dateInput?.addEventListener('click', function () {
        if (typeof this.showPicker === 'function') this.showPicker();
    });

    loadSlots();
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
