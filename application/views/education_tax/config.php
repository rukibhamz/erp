<div class="container-fluid px-4">
    <div class="mb-4">
        <?= back_button('education_tax') ?>
        <h1 class="h3 mt-3"><?= $page_title ?></h1>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Add/Update Configuration</h6>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Tax Year</label>
                            <input type="number" name="tax_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tax Rate (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="tax_rate" class="form-control" value="2.50" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Threshold Amount (Optional)</label>
                            <input type="number" step="0.01" name="threshold" class="form-control" value="0.00">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Configuration</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Configuration History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Year</th>
                                    <th>Rate</th>
                                    <th>Threshold</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($configs as $cfg): ?>
                                <tr>
                                    <td><strong><?= $cfg['tax_year'] ?></strong></td>
                                    <td><?= $cfg['tax_rate'] ?>%</td>
                                    <td>$<?= number_format($cfg['threshold'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $cfg['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $cfg['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
