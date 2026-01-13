<!-- Create Tenant Modal -->
<div class="modal fade" id="createTenantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Tenant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createTenantForm" action="<?= base_url('tenants/create') ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div id="tenant_creation_alert" class="alert alert-danger d-none"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_tenant_type" class="form-label">Tenant Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_tenant_type" name="tenant_type" required>
                                <option value="individual">Individual</option>
                                <option value="business">Business</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="modal_business_name" class="form-label">Business Name</label>
                            <input type="text" class="form-control" id="modal_business_name" name="business_name">
                            <small class="text-muted">Required for Business type</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="modal_contact_person" class="form-label">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_contact_person" name="contact_person" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="modal_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="modal_email" name="email" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="modal_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_phone" name="phone" required>
                        </div>

                        <div class="col-md-6">
                            <label for="modal_status" class="form-label">Status</label>
                            <select class="form-select" id="modal_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label for="modal_address" class="form-label">Address</label>
                            <textarea class="form-control" id="modal_address" name="address" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveTenant">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Create Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tenantWrapper = document.getElementById('createTenantModal');
    const tenantForm = document.getElementById('createTenantForm');
    const btnSave = document.getElementById('btnSaveTenant');
    const alertBox = document.getElementById('tenant_creation_alert');
    
    // IDs prefixed with modal_ to avoid collision with parent form
    const typeSelect = document.getElementById('modal_tenant_type');
    const bizNameInput = document.getElementById('modal_business_name');
    
    // Toggle business name requirement
    if(typeSelect && bizNameInput) {
        toggleBusinessName();
        typeSelect.addEventListener('change', toggleBusinessName);
    }
    
    function toggleBusinessName() {
        if (typeSelect.value === 'business') {
            bizNameInput.setAttribute('required', 'required');
        } else {
            bizNameInput.removeAttribute('required');
        }
    }

    // AJAX Submission
    tenantForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset state
        btnSave.disabled = true;
        btnSave.querySelector('.spinner-border').classList.remove('d-none');
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
        
        const formData = new FormData(tenantForm);
        
        fetch(tenantForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success!
                // 1. Add to parent dropdown
                const tenantSelect = document.getElementById('tenant_id');
                if(tenantSelect) {
                    const option = new Option(
                        (data.business_name || data.contact_person), 
                        data.tenant_id, 
                        true, // defaultSelected
                        true  // selected
                    );
                    tenantSelect.add(option, undefined); // add to end
                    
                    // Trigger change event if needed by other scripts
                    tenantSelect.dispatchEvent(new Event('change'));
                }
                
                // 2. Close modal
                const modalInstance = bootstrap.Modal.getInstance(tenantWrapper);
                if (modalInstance) {
                    modalInstance.hide();
                }
                
                // 3. Reset form
                tenantForm.reset();
                
            } else {
                // Show error
                showError(data.error || 'Failed to create tenant.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An unexpected error occurred.');
        })
        .finally(() => {
            btnSave.disabled = false;
            btnSave.querySelector('.spinner-border').classList.add('d-none');
        });
    });
    
    function showError(msg) {
        alertBox.textContent = msg;
        alertBox.classList.remove('d-none');
    }
});
</script>
