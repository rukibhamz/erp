<?php
$page_title = $page_title ?? 'Companies';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Companies</h1>
        <a href="<?= base_url('companies/create') ?>" class="btn btn-primary">Create Company</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Currency</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td><?= $company['id'] ?></td>
                                <td><?= htmlspecialchars($company['name']) ?></td>
                                <td><?= htmlspecialchars($company['address'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($company['phone'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($company['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($company['currency']) ?></td>
                                <td><small class="text-muted"><?= date('M d, Y', strtotime($company['created_at'])) ?></small></td>
                                <td>
                                    <a href="<?= base_url('companies/edit/' . $company['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No companies found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

