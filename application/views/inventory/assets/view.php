<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include BASEPATH . 'views/layouts/header.php';
include BASEPATH . 'views/inventory/_nav.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><?= htmlspecialchars($page_title) ?></h3>
            <div>
                <?php if (hasPermission('inventory', 'update')): ?>
                    <a href="<?= base_url('inventory/assets/edit/' . $asset['id']) ?>" class="btn btn-dark">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('inventory/assets') ?>" class="btn btn-outline-dark">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <?php if (isset($flash) && $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Asset Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered mb-0">
                            <tr>
                                <th width="40%">Asset Tag</th>
                                <td><strong><?= htmlspecialchars($asset['asset_tag']) ?></strong></td>
                            </tr>
                            <tr>
                                <th>Asset Name</th>
                                <td><?= htmlspecialchars($asset['asset_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td><?= ucfirst($asset['asset_category']) ?></td>
                            </tr>
                            <tr>
                                <th>Location</th>
                                <td><?= htmlspecialchars($asset['location_name'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <th>Item</th>
                                <td><?= htmlspecialchars($asset['item_name'] ?? 'N/A') ?> <?= $asset['sku'] ? '(' . htmlspecialchars($asset['sku']) . ')' : '' ?></td>
                            </tr>
                            <tr>
                                <th>Supplier</th>
                                <td><?= htmlspecialchars($asset['supplier_name'] ?? 'N/A') ?></td>
                            </tr>
                            <?php if (!empty($asset['description'])): ?>
                                <tr>
                                    <th>Description</th>
                                    <td><?= nl2br(htmlspecialchars($asset['description'])) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-<?= $asset['asset_status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($asset['asset_status']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-currency-dollar"></i> Financial Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered mb-0">
                            <tr>
                                <th width="40%">Purchase Cost</th>
                                <td><?= format_currency($asset['purchase_cost']) ?></td>
                            </tr>
                            <tr>
                                <th>Purchase Date</th>
                                <td><?= date('F d, Y', strtotime($asset['purchase_date'])) ?></td>
                            </tr>
                            <tr>
                                <th>Depreciation Method</th>
                                <td><?= ucfirst(str_replace('_', ' ', $asset['depreciation_method'])) ?></td>
                            </tr>
                            <tr>
                                <th>Useful Life</th>
                                <td><?= $asset['useful_life_years'] ?> years</td>
                            </tr>
                            <tr>
                                <th>Salvage Value</th>
                                <td><?= format_currency($asset['salvage_value']) ?></td>
                            </tr>
                            <?php if ($depreciation): ?>
                                <tr>
                                    <th>Monthly Depreciation</th>
                                    <td><?= format_currency($depreciation['monthly_depreciation']) ?></td>
                                </tr>
                                <tr>
                                    <th>Accumulated Depreciation</th>
                                    <td><?= format_currency($depreciation['accumulated_depreciation']) ?></td>
                                </tr>
                                <tr>
                                    <th>Net Book Value</th>
                                    <td><strong><?= format_currency($depreciation['net_book_value']) ?></strong></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Current Value</th>
                                <td><strong><?= format_currency($asset['current_value']) ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASEPATH . 'views/layouts/footer.php'; ?>

