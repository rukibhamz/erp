<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">POS Terminals</h1>
        <div class="d-flex gap-2">
            <?php if (in_array($current_user['role'] ?? '', ['super_admin', 'admin'])): ?>
                <button class="btn btn-dark" onclick="showCreateModal()">
                    <i class="bi bi-plus-circle"></i> Create Terminal
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-cash-register"></i> Terminals</h5>
    </div>
    <div class="card-body">
        <?php if (empty($terminals)): ?>
            <p class="text-muted text-center py-5">No terminals found. Create your first terminal to start using POS.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terminals as $terminal): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($terminal['terminal_code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($terminal['name']) ?></strong></td>
                                <td><?= htmlspecialchars($terminal['location'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $terminal['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($terminal['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('pos/index?terminal=' . $terminal['id']) ?>" class="btn btn-primary" title="Open POS">
                                            <i class="bi bi-cash-register"></i> Open POS
                                        </a>
                                        <?php if (in_array($current_user['role'] ?? '', ['super_admin', 'admin'])): ?>
                                            <a href="<?= base_url('pos/reports?terminal_id=' . $terminal['id']) ?>" class="btn btn-primary" title="View Reports">
                                                <i class="bi bi-bar-chart"></i> Reports
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Terminal Modal -->
<div class="modal fade" id="createTerminalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Create POS Terminal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= base_url('pos/create-terminal') ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Terminal Code</label>
                        <input type="text" name="terminal_code" class="form-control" placeholder="Auto-generated">
                        <small class="text-muted">Leave blank to auto-generate</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Terminal Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    
                    <hr>
                    <h6 class="mb-3">Default Accounts (Accounting)</h6>
                    <div class="mb-3">
                        <label class="form-label">Default Cash Account</label>
                        <select name="cash_account_id" class="form-select">
                            <option value="">Select Cash Account (Optional)</option>
                            <?php foreach ($accounts['assets'] ?? [] as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= $acc['account_code'] ?> - <?= $acc['account_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Used for cash payments.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Sales/Revenue Account</label>
                        <select name="sales_account_id" class="form-select">
                            <option value="">Select Revenue Account (Optional)</option>
                            <?php foreach ($accounts['revenue'] ?? [] as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= $acc['account_code'] ?> - <?= $acc['account_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                         <small class="text-muted">Used to record sales revenue.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Tax Liability Account</label>
                        <select name="tax_account_id" class="form-select">
                            <option value="">Select Liability Account (Optional)</option>
                            <?php foreach ($accounts['liabilities'] ?? [] as $acc): ?>
                                <option value="<?= $acc['id'] ?>"><?= $acc['account_code'] ?> - <?= $acc['account_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                         <small class="text-muted">Used to record tax/VAT liability.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark">Create Terminal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateModal() {
    new bootstrap.Modal(document.getElementById('createTerminalModal')).show();
}
</script>



