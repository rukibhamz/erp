<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Credit Notes</h1>
        <?php if (has_permission('receivables', 'create')): ?>
            <a href="<?= base_url('credit-notes/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Credit Note
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
                        <option value="issued" <?= $selected_status === 'issued' ? 'selected' : '' ?>>Issued</option>
                        <option value="applied" <?= $selected_status === 'applied' ? 'selected' : '' ?>>Applied</option>
                        <option value="void" <?= $selected_status === 'void' ? 'selected' : '' ?>>Void</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary">Filter</button>
                    <a href="<?= base_url('credit-notes') ?>" class="btn btn-primary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Credit Notes Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Credit Note #</th>
                            <th>Customer</th>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($credit_notes)): ?>
                            <?php foreach ($credit_notes as $note): ?>
                                <tr>
                                    <td><?= htmlspecialchars($note['credit_note_number']) ?></td>
                                    <td><?= htmlspecialchars($note['customer_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($note['invoice_number'] ?? '-') ?></td>
                                    <td><?= date('M d, Y', strtotime($note['credit_date'])) ?></td>
                                    <td><?= format_currency($note['total_amount'], $note['currency'] ?? 'USD') ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'issued' => 'info',
                                            'applied' => 'success',
                                            'void' => 'danger'
                                        ];
                                        $color = $statusColors[$note['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>">
                                            <?= ucfirst($note['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('credit-notes/view/' . $note['id']) ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($note['status'] === 'issued' && has_permission('receivables', 'update')): ?>
                                            <a href="<?= base_url('credit-notes/apply/' . $note['id']) ?>" class="btn btn-sm btn-outline-success" 
                                               onclick="return confirm('Apply this credit note to an invoice?')">
                                                <i class="bi bi-check-circle"></i> Apply
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No credit notes found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


