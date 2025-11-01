<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Create Journal Entry</h1>
</div>

<!-- Accounting Navigation -->
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('accounting') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="<?= base_url('accounts') ?>">
            <i class="bi bi-diagram-3"></i> Chart of Accounts
        </a>
        <a class="nav-link" href="<?= base_url('cash') ?>">
            <i class="bi bi-wallet2"></i> Cash Management
        </a>
        <a class="nav-link" href="<?= base_url('receivables') ?>">
            <i class="bi bi-receipt"></i> Receivables
        </a>
        <a class="nav-link" href="<?= base_url('payables') ?>">
            <i class="bi bi-file-earmark-medical"></i> Payables
        </a>
        <a class="nav-link active" href="<?= base_url('ledger') ?>">
            <i class="bi bi-journal-text"></i> General Ledger
        </a>
    </nav>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Journal Entry Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= base_url('ledger/create') ?>" id="journalEntryForm">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="entry_date" class="form-label">Entry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="entry_date" name="entry_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" placeholder="e.g., Doc #123">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief description of the journal entry"></textarea>
                    </div>
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered" id="journalLinesTable">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Account</th>
                                    <th style="width: 25%;">Description</th>
                                    <th style="width: 15%;" class="text-end">Debit</th>
                                    <th style="width: 15%;" class="text-end">Credit</th>
                                    <th style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody id="journalLinesBody">
                                <tr>
                                    <td>
                                        <select class="form-select form-select-sm" name="lines[0][account_id]" required>
                                            <option value="">Select Account</option>
                                            <?php if (!empty($accounts)): ?>
                                                <?php foreach ($accounts as $account): ?>
                                                    <option value="<?= $account['id'] ?>">
                                                        <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="lines[0][description]" placeholder="Description">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end debit-input" name="lines[0][debit]" value="0.00" onchange="calculateTotals()">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm text-end credit-input" name="lines[0][credit]" value="0.00" onchange="calculateTotals()">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLine(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold" id="totalDebit">0.00</td>
                                    <td class="text-end fw-bold" id="totalCredit">0.00</td>
                                    <td></td>
                                </tr>
                                <tr id="balanceCheck" class="table-warning">
                                    <td colspan="5" class="text-center">
                                        <span id="balanceMessage">Debits and Credits must be equal</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="addLine()">
                            <i class="bi bi-plus-circle"></i> Add Line
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="bi bi-check-circle"></i> Create Journal Entry
                        </button>
                        <a href="<?= base_url('ledger') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let lineIndex = 1;

function addLine() {
    const tbody = document.getElementById('journalLinesBody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <select class="form-select form-select-sm" name="lines[${lineIndex}][account_id]" required>
                <option value="">Select Account</option>
                <?php if (!empty($accounts)): ?>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= $account['id'] ?>">
                            <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="lines[${lineIndex}][description]" placeholder="Description">
        </td>
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm text-end debit-input" name="lines[${lineIndex}][debit]" value="0.00" onchange="calculateTotals()">
        </td>
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm text-end credit-input" name="lines[${lineIndex}][credit]" value="0.00" onchange="calculateTotals()">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLine(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    lineIndex++;
}

function removeLine(btn) {
    if (document.getElementById('journalLinesBody').rows.length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    }
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
    const balanceCheck = document.getElementById('balanceCheck');
    const balanceMessage = document.getElementById('balanceMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    if (difference < 0.01) {
        balanceCheck.className = 'table-success';
        balanceMessage.textContent = '✓ Balanced';
        submitBtn.disabled = false;
    } else {
        balanceCheck.className = 'table-warning';
        balanceMessage.textContent = `Difference: ${difference.toFixed(2)} - Debits and Credits must be equal`;
        submitBtn.disabled = true;
    }
}

document.getElementById('journalEntryForm').addEventListener('submit', function(e) {
    const difference = Math.abs(
        parseFloat(document.getElementById('totalDebit').textContent) - 
        parseFloat(document.getElementById('totalCredit').textContent)
    );
    
    if (difference >= 0.01) {
        e.preventDefault();
        alert('Debits and Credits must be equal before submitting.');
        return false;
    }
});

calculateTotals();
</script>

<style>
.accounting-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.accounting-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.accounting-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.accounting-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.accounting-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

