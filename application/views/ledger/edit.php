<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Journal Entry</h1>
        <a href="<?= base_url('ledger') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Journal Entry Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('ledger/edit/' . $entry['id']) ?>" id="journalForm">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="entry_date" class="form-label">Entry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="entry_date" name="entry_date" 
                                   value="<?= htmlspecialchars($entry['entry_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" 
                                   value="<?= htmlspecialchars($entry['reference'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   value="<?= htmlspecialchars($entry['description'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <hr>
                    <h5>Journal Entry Lines</h5>
                    <div id="linesContainer">
                        <?php if (!empty($lines)): ?>
                            <?php foreach ($lines as $index => $line): ?>
                                <div class="journal-line mb-3 p-3 border rounded">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label">Account <span class="text-danger">*</span></label>
                                            <select class="form-select account-select" name="lines[<?= $index ?>][account_id]" required>
                                                <option value="">Select Account</option>
                                                <?php if (!empty($accounts)): ?>
                                                    <?php foreach ($accounts as $acc): ?>
                                                        <option value="<?= $acc['id'] ?>" <?= ($line['account_id'] ?? '') == $acc['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Description</label>
                                            <input type="text" class="form-control" name="lines[<?= $index ?>][description]" 
                                                   value="<?= htmlspecialchars($line['description'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Debit</label>
                                            <input type="number" step="0.01" class="form-control debit-input" name="lines[<?= $index ?>][debit]" 
                                                   value="<?= htmlspecialchars($line['debit'] ?? 0) ?>" min="0" onchange="calculateTotals()">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Credit</label>
                                            <input type="number" step="0.01" class="form-control credit-input" name="lines[<?= $index ?>][credit]" 
                                                   value="<?= htmlspecialchars($line['credit'] ?? 0) ?>" min="0" onchange="calculateTotals()">
                                            <?php if ($index > 0): ?>
                                                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeLine(this)">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="journal-line mb-3 p-3 border rounded">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Account <span class="text-danger">*</span></label>
                                        <select class="form-select account-select" name="lines[0][account_id]" required>
                                            <option value="">Select Account</option>
                                            <?php if (!empty($accounts)): ?>
                                                <?php foreach ($accounts as $acc): ?>
                                                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Description</label>
                                        <input type="text" class="form-control" name="lines[0][description]">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Debit</label>
                                        <input type="number" step="0.01" class="form-control debit-input" name="lines[0][debit]" 
                                               value="0" min="0" onchange="calculateTotals()">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Credit</label>
                                        <input type="number" step="0.01" class="form-control credit-input" name="lines[0][credit]" 
                                               value="0" min="0" onchange="calculateTotals()">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary mb-3" onclick="addLine()">
                        <i class="bi bi-plus-circle"></i> Add Line
                    </button>
                    
                    <div class="alert alert-info">
                        <strong>Total Debits:</strong> <span id="totalDebit">0.00</span><br>
                        <strong>Total Credits:</strong> <span id="totalCredit">0.00</span><br>
                        <strong>Difference:</strong> <span id="difference" class="fw-bold">0.00</span>
                        <span id="balanceStatus" class="ms-2"></span>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('ledger') ?>" class="btn btn-primary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="bi bi-check-circle"></i> Update Journal Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let lineCount = <?= count($lines ?? []) ?>;

function addLine() {
    const container = document.getElementById('linesContainer');
    const newLine = document.createElement('div');
    newLine.className = 'journal-line mb-3 p-3 border rounded';
    newLine.innerHTML = `
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Account <span class="text-danger">*</span></label>
                <select class="form-select account-select" name="lines[${lineCount}][account_id]" required>
                    <option value="">Select Account</option>
                    <?php if (!empty($accounts)): ?>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" name="lines[${lineCount}][description]">
            </div>
            <div class="col-md-2">
                <label class="form-label">Debit</label>
                <input type="number" step="0.01" class="form-control debit-input" name="lines[${lineCount}][debit]" 
                       value="0" min="0" onchange="calculateTotals()">
            </div>
            <div class="col-md-2">
                <label class="form-label">Credit</label>
                <input type="number" step="0.01" class="form-control credit-input" name="lines[${lineCount}][credit]" 
                       value="0" min="0" onchange="calculateTotals()">
                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeLine(this)">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
        </div>
    `;
    container.appendChild(newLine);
    lineCount++;
}

function removeLine(btn) {
    btn.closest('.journal-line').remove();
    calculateTotals();
}

function calculateTotals() {
    let totalDebit = 0;
    let totalCredit = 0;
    
    document.querySelectorAll('.debit-input').forEach(input => {
        totalDebit += parseFloat(input.value) || 0;
    });
    
    document.querySelectorAll('.credit-input').forEach(input => {
        totalCredit += parseFloat(input.value) || 0;
    });
    
    document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
    document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);
    
    const difference = Math.abs(totalDebit - totalCredit);
    document.getElementById('difference').textContent = difference.toFixed(2);
    
    const statusEl = document.getElementById('balanceStatus');
    const submitBtn = document.getElementById('submitBtn');
    
    if (difference < 0.01) {
        statusEl.innerHTML = '<span class="badge bg-success">Balanced</span>';
        submitBtn.disabled = false;
    } else {
        statusEl.innerHTML = '<span class="badge bg-danger">Not Balanced</span>';
        submitBtn.disabled = true;
    }
}

// Initial calculation
calculateTotals();
</script>

