<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $page_title ?></h1>
        <a href="<?= base_url('customer_types/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add New Type
        </a>
    </div>

    <div class="card shadow mb-4">
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
        </div>
    </div>
</div>
