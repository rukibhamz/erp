<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Estimates / Quotes</h1>
        <?php if (has_permission('estimates', 'create')): ?>
            <a href="<?= base_url('estimates/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Estimate
            </a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="draft" <?= $selected_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="sent" <?= $selected_status === 'sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="accepted" <?= $selected_status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                        <option value="rejected" <?= $selected_status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="converted" <?= $selected_status === 'converted' ? 'selected' : '' ?>>Converted</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary">Filter</button>
                    <a href="<?= base_url('estimates') ?>" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Estimates Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Estimate #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Expiry Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($estimates)): ?>
                            <?php foreach ($estimates as $estimate): ?>
                                <tr>
                                    <td><?= htmlspecialchars($estimate['estimate_number']) ?></td>
                                    <td><?= htmlspecialchars($estimate['customer_name'] ?? '-') ?></td>
                                    <td><?= date('M d, Y', strtotime($estimate['estimate_date'])) ?></td>
                                    <td><?= $estimate['expiry_date'] ? date('M d, Y', strtotime($estimate['expiry_date'])) : '-' ?></td>
                                    <td><?= format_currency($estimate['total_amount'], $estimate['currency'] ?? 'USD') ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'sent' => 'info',
                                            'accepted' => 'success',
                                            'rejected' => 'danger',
                                            'converted' => 'primary'
                                        ];
                                        $color = $statusColors[$estimate['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>">
                                            <?= ucfirst($estimate['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('estimates/view/' . $estimate['id']) ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($estimate['status'] === 'accepted' && has_permission('estimates', 'create')): ?>
                                            <a href="<?= base_url('estimates/convert/' . $estimate['id']) ?>" class="btn btn-sm btn-outline-success" 
                                               onclick="return confirm('Convert this estimate to an invoice?')">
                                                <i class="bi bi-arrow-right-circle"></i> Convert
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($estimate['status'] !== 'converted' && has_permission('estimates', 'delete')): ?>
                                            <a href="<?= base_url('estimates/delete/' . $estimate['id']) ?>" class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this estimate?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No estimates found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


