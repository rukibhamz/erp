<?php
$page_title = $page_title ?? 'Companies';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0"><?= htmlspecialchars($page_title) ?></h1>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Companies</li>
                </ol>
            </nav>
        </div>
        <a href="<?= base_url('companies/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Company
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
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
                                    <a href="<?= base_url('companies/edit/' . $company['id']) ?>" class="btn btn-sm btn-outline-primary">
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

