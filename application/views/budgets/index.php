<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Budgets</h1>
        <div>
            <?php if (has_permission('budgets', 'create')): ?>
                <a href="<?= base_url('budgets/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create Budget
                </a>
            <?php endif; ?>
            <?php if (has_permission('budgets', 'read')): ?>
                <a href="<?= base_url('budgets/report') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-graph-up"></i> Budget vs Actual
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        <?php foreach ($financial_years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= ($selected_year_id ?? '') == $year['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year['year_name'] ?? date('Y', strtotime($year['start_date']))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Budgets Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Account Code</th>
                            <th>Budget Name</th>
                            <th>Total Budget</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($budgets)): ?>
                            <?php foreach ($budgets as $budget): ?>
                                <tr>
                                    <td><?= htmlspecialchars($budget['account_name']) ?></td>
                                    <td><?= htmlspecialchars($budget['account_code']) ?></td>
                                    <td><?= htmlspecialchars($budget['budget_name'] ?? '-') ?></td>
                                    <td><?= format_currency($budget['total'] ?? 0, 'USD') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $budget['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($budget['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (has_permission('budgets', 'update')): ?>
                                            <a href="<?= base_url('budgets/edit/' . $budget['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No budgets found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


