<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Payroll Diagnostic</h1>
        <a href="<?= base_url('payroll/process') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Payroll
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">System Status</h5>
    </div>
    <div class="card-body">
        
        <!-- Employees Check -->
        <h4>1. Employees</h4>
        <?php if (isset($diagnostics['employees']['error'])): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?= htmlspecialchars($diagnostics['employees']['error']) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-<?= $diagnostics['employees']['status'] === 'success' ? 'success' : 'warning' ?>">
                <p><strong>Total Employees:</strong> <?= $diagnostics['employees']['total'] ?></p>
                <p><strong>Active Employees:</strong> <?= $diagnostics['employees']['active'] ?></p>
                
                <?php if ($diagnostics['employees']['active'] == 0): ?>
                    <p class="mb-0">
                        <i class="bi bi-exclamation-triangle"></i> 
                        No active employees found. 
                        <a href="<?= base_url('employees/create') ?>" class="btn btn-sm btn-primary">Create Employee</a>
                    </p>
                <?php else: ?>
                    <h6 class="mt-3">Sample Active Employees:</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnostics['employees']['sample'] as $emp): ?>
                                <tr>
                                    <td><?= $emp['id'] ?></td>
                                    <td><?= htmlspecialchars($emp['employee_code'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($emp['email'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-success"><?= $emp['status'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <hr>
        
        <!-- Cash Accounts Check -->
        <h4>2. Cash Accounts</h4>
        <?php if (isset($diagnostics['cash_accounts']['error'])): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?= htmlspecialchars($diagnostics['cash_accounts']['error']) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-<?= $diagnostics['cash_accounts']['status'] === 'success' ? 'success' : 'warning' ?>">
                <p><strong>Total Cash Accounts:</strong> <?= $diagnostics['cash_accounts']['total'] ?></p>
                <p><strong>Active Cash Accounts:</strong> <?= $diagnostics['cash_accounts']['active'] ?></p>
                
                <?php if ($diagnostics['cash_accounts']['active'] == 0): ?>
                    <p class="mb-0">
                        <i class="bi bi-exclamation-triangle"></i> 
                        No active cash accounts found. 
                        <a href="<?= base_url('cash/accounts/create') ?>" class="btn btn-sm btn-primary">Create Cash Account</a>
                        or refresh the page to trigger AutoMigration.
                    </p>
                <?php else: ?>
                    <h6 class="mt-3">Sample Active Cash Accounts:</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Current Balance</th>
                                <th>Currency</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnostics['cash_accounts']['sample'] as $acc): ?>
                                <tr>
                                    <td><?= $acc['id'] ?></td>
                                    <td><?= htmlspecialchars($acc['account_name']) ?></td>
                                    <td><?= htmlspecialchars($acc['account_type'] ?? 'N/A') ?></td>
                                    <td><?= format_currency($acc['current_balance'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($acc['currency'] ?? 'NGN') ?></td>
                                    <td><span class="badge bg-success"><?= $acc['status'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <hr>
        
        <!-- Chart of Accounts Check -->
        <h4>3. Chart of Accounts</h4>
        <?php if (isset($diagnostics['accounts']['error'])): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?= htmlspecialchars($diagnostics['accounts']['error']) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <p class="mb-0"><strong>Active Accounts:</strong> <?= $diagnostics['accounts']['total'] ?></p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<div class="card">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Recommendations</h5>
    </div>
    <div class="card-body">
        <ul>
            <?php if (isset($diagnostics['employees']) && $diagnostics['employees']['active'] == 0): ?>
                <li>
                    <strong>Create Employees:</strong> 
                    <a href="<?= base_url('employees/create') ?>">Go to Create Employee</a>
                </li>
            <?php endif; ?>
            
            <?php if (isset($diagnostics['cash_accounts']) && $diagnostics['cash_accounts']['active'] == 0): ?>
                <li>
                    <strong>Create Cash Account:</strong> 
                    <a href="<?= base_url('cash/accounts/create') ?>">Go to Create Cash Account</a>
                    or refresh this page to trigger AutoMigration
                </li>
            <?php endif; ?>
            
            <?php if (isset($diagnostics['employees']) && $diagnostics['employees']['active'] > 0 && 
                      isset($diagnostics['cash_accounts']) && $diagnostics['cash_accounts']['active'] > 0): ?>
                <li class="text-success">
                    <i class="bi bi-check-circle"></i> 
                    <strong>All systems ready!</strong> 
                    You can now <a href="<?= base_url('payroll/process') ?>">process payroll</a>.
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
