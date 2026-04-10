<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Promo Codes</h1>
        <?php if (hasPermission('bookings', 'create')): ?>
            <a href="<?= base_url('promo-codes/create') ?>" class="btn btn-dark">
                <i class="bi bi-plus-circle"></i> Create Promo Code
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($codes)): ?>
            <div class="text-center py-5">
                <i class="bi bi-ticket-perforated" style="font-size:3rem;color:#ccc;display:block;margin-bottom:1rem;"></i>
                <p class="text-muted mb-3">No promo codes yet.</p>
                <?php if (hasPermission('bookings', 'create')): ?>
                    <a href="<?= base_url('promo-codes/create') ?>" class="btn btn-dark">
                        <i class="bi bi-plus-circle"></i> Create First Promo Code
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Discount</th>
                            <th>Valid Period</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codes as $c): ?>
                            <?php
                            $now = date('Y-m-d');
                            $expired = $c['valid_to'] < $now;
                            $notStarted = $c['valid_from'] > $now;
                            ?>
                            <tr>
                                <td><code class="fs-6"><?= htmlspecialchars($c['code']) ?></code></td>
                                <td class="text-muted small"><?= htmlspecialchars($c['description'] ?? '—') ?></td>
                                <td>
                                    <?php if ($c['discount_type'] === 'percentage'): ?>
                                        <span class="badge bg-info"><?= number_format($c['discount_value'], 0) ?>% off</span>
                                        <?php if ($c['maximum_discount']): ?>
                                            <small class="text-muted d-block">max <?= format_currency($c['maximum_discount']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= format_currency($c['discount_value']) ?> off</span>
                                    <?php endif; ?>
                                    <?php if ($c['minimum_amount']): ?>
                                        <small class="text-muted d-block">min <?= format_currency($c['minimum_amount']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?= date('M j, Y', strtotime($c['valid_from'])) ?> —
                                    <span class="<?= $expired ? 'text-danger' : '' ?>">
                                        <?= date('M j, Y', strtotime($c['valid_to'])) ?>
                                    </span>
                                </td>
                                <td class="small">
                                    <?= intval($c['used_count']) ?>
                                    <?= $c['usage_limit'] ? '/ ' . $c['usage_limit'] : '/ ∞' ?>
                                </td>
                                <td>
                                    <?php if (!$c['is_active']): ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php elseif ($expired): ?>
                                        <span class="badge bg-danger">Expired</span>
                                    <?php elseif ($notStarted): ?>
                                        <span class="badge bg-warning text-dark">Scheduled</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if (hasPermission('bookings', 'update')): ?>
                                            <a href="<?= base_url('promo-codes/edit/' . $c['id']) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="<?= base_url('promo-codes/toggle/' . $c['id']) ?>" style="display:inline">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-<?= $c['is_active'] ? 'warning' : 'success' ?>" title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="bi bi-<?= $c['is_active'] ? 'pause' : 'play' ?>-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (hasPermission('bookings', 'delete')): ?>
                                            <form method="POST" action="<?= base_url('promo-codes/delete/' . $c['id']) ?>" style="display:inline"
                                                  onsubmit="return confirm('Delete promo code <?= htmlspecialchars($c['code']) ?>?')">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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
