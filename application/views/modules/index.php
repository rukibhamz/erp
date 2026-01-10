<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cubes me-2"></i>System Modules</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($flash) && $flash): ?>
                        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-4">
                        Manage system modules and their activation status. Disabled modules will not be accessible to users.
                    </p>

                    <div class="row">
                        <?php 
                        $defaultModules = [
                            ['key' => 'accounting', 'name' => 'Accounting', 'icon' => 'fa-calculator', 'description' => 'Chart of accounts, journal entries, financial reports'],
                            ['key' => 'bookings', 'name' => 'Bookings', 'icon' => 'fa-calendar-check', 'description' => 'Facility and resource booking management'],
                            ['key' => 'locations', 'name' => 'Locations', 'icon' => 'fa-building', 'description' => 'Property and space management'],
                            ['key' => 'utilities', 'name' => 'Utilities', 'icon' => 'fa-bolt', 'description' => 'Meter readings, utility bills, consumption tracking'],
                            ['key' => 'inventory', 'name' => 'Inventory', 'icon' => 'fa-boxes', 'description' => 'Stock management, purchase orders, fixed assets'],
                            ['key' => 'tax', 'name' => 'Tax Management', 'icon' => 'fa-file-invoice-dollar', 'description' => 'VAT, WHT, PAYE, CIT compliance'],
                            ['key' => 'pos', 'name' => 'Point of Sale', 'icon' => 'fa-cash-register', 'description' => 'POS terminals, sales, receipts'],
                            ['key' => 'staff_management', 'name' => 'Staff Management', 'icon' => 'fa-users', 'description' => 'Employee records, payroll processing'],
                        ];
                        
                        $modules = $modules ?? $defaultModules;
                        foreach ($modules as $module): 
                            $isActive = $module['is_active'] ?? true;
                        ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 <?= $isActive ? '' : 'border-secondary opacity-75' ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-<?= $isActive ? 'primary' : 'secondary' ?> text-white rounded-circle p-3 me-3">
                                                    <i class="fas <?= $module['icon'] ?? 'fa-cube' ?> fa-lg"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($module['name'] ?? '') ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($module['key'] ?? '') ?></small>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" 
                                                       id="module_<?= $module['key'] ?? '' ?>"
                                                       <?= $isActive ? 'checked' : '' ?>
                                                       onchange="toggleModule('<?= $module['key'] ?? '' ?>', this.checked)">
                                            </div>
                                        </div>
                                        <p class="card-text text-muted small mb-0">
                                            <?= htmlspecialchars($module['description'] ?? '') ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <span class="badge bg-<?= $isActive ? 'success' : 'secondary' ?>">
                                            <?= $isActive ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleModule(moduleKey, isActive) {
    fetch('<?= site_url('modules/toggle') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'module_key=' + encodeURIComponent(moduleKey) + '&is_active=' + (isActive ? '1' : '0') + '&<?= csrf_token_name() ?>=' + encodeURIComponent('<?= csrf_hash() ?>')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update module status');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating module status');
        location.reload();
    });
}
</script>
