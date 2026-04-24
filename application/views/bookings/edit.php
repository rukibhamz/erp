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
                
                <!-- Booking Info (Read-only) -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Booking Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($booking['booking_number'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" value="<?= date('M d, Y', strtotime($booking['booking_date'])) ?>" readonly>
                        <small class="text-muted">Use Reschedule to change date/time</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Time</label>
                        <input type="text" class="form-control" value="<?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?>" readonly>
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
