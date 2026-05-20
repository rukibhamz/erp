<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $page_title ?></h1>
        <a href="<?= base_url('customer_types/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add New Type
        </a>
    </div>

    <div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-end py-2">
        <form method="GET" action="" class="d-flex align-items-center gap-2 mb-0 flex-wrap">
            <input type="search" name="search" class="form-control form-control-sm" style="min-width:200px" value="<?= htmlspecialchars(list_search_term()) ?>" placeholder="Search name, ID, code…">
            <input type="hidden" name="page" value="1">
            <label class="small text-muted mb-0">Records</label>
            <?php render_pagination_per_page_select(intval($pagination['per_page'] ?? 50), 'per_page', 'form-select form-select-sm'); ?>
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Discount (%)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $type): ?>
                        <tr>
                            <td><strong><?= esc($type['name']) ?></strong></td>
                            <td><code><?= esc($type['code']) ?></code></td>
                            <td><?= number_format($type['discount_percentage'], 2) ?>%</td>
                            <td>
                                <span class="badge bg-<?= $type['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $type['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url('customer_types/edit/' . $type['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

    <?php render_pagination_controls($pagination ?? null); ?>
        </div>
    </div>
</div>
