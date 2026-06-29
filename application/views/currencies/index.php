<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Currencies</h1>
        <div class="d-flex gap-2">
            <?php if (has_permission('settings', 'create')): ?>
                <a href="<?= base_url('currencies/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Currency
                </a>
            <?php endif; ?>
            <?php if (has_permission('settings', 'read')): ?>
                <a href="<?= base_url('currencies/rates') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-graph-up"></i> Exchange Rates
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php
$list_filter_action = base_url('currencies');
$search_placeholder = 'Currency name, code…';
include(BASEPATH . 'views/partials/list_filters_bar.php');
?>

<div class="card shadow-sm">

    <div class="card-body">
        <?php
        $bulk_delete_enabled = has_permission('settings', 'delete');
        bulk_delete_render_toolbar($bulk_delete_enabled, $currencies, base_url('currencies/bulk-delete'), 'currency');
        ?>
        <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php bulk_delete_render_checkbox_th($bulk_delete_enabled); ?>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th>Exchange Rate</th>
                            <th>Position</th>
                            <th>Base Currency</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($currencies)): ?>
                            <?php foreach ($currencies as $currency): ?>
                                <tr>
                                    <?php bulk_delete_render_checkbox_td($bulk_delete_enabled, (int)$currency['id'], 'currency ' . ($currency['currency_name'])); ?>
                                    <td><strong><?= htmlspecialchars($currency['currency_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($currency['currency_name']) ?></td>
                                    <td><?= htmlspecialchars($currency['symbol']) ?></td>
                                    <td><?= number_format($currency['exchange_rate'], 4) ?></td>
                                    <td><?= ucfirst($currency['position'] ?? 'before') ?></td>
                                    <td>
                                        <?php if ($currency['is_base']): ?>
                                            <span class="badge bg-success">Base</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $currency['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($currency['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (has_permission('settings', 'read')): ?>
                                                <a href="<?= base_url('currencies/view/' . $currency['id']) ?>" class="btn btn-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_permission('settings', 'update')): ?>
                                                <a href="<?= base_url('currencies/edit/' . $currency['id']) ?>" class="btn btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (has_permission('settings', 'delete')): ?>
                                                <form method="post" action="<?= base_url('currencies/delete/' . $currency['id']) ?>" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this currency?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= bulk_delete_colspan(8, $bulk_delete_enabled) ?>" class="text-center text-muted">No currencies found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php render_pagination_controls($pagination ?? null); ?>
        </div>
    </div>
</div>


