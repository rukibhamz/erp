<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Entity: <?= htmlspecialchars($entity['name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (has_permission('entities', 'update')): ?>
                <a href="<?= base_url('entities/edit/' . $entity['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('entities') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Entity Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Entity Name</dt>
                    <dd><strong><?= htmlspecialchars($entity['name'] ?? 'N/A') ?></strong></dd>
                    
                    <?php if (!empty($entity['tax_id'])): ?>
                    <dt>Tax ID</dt>
                    <dd><?= htmlspecialchars($entity['tax_id']) ?></dd>
                    <?php endif; ?>
                    
                    <dt>Currency</dt>
                    <dd><?= htmlspecialchars($entity['currency'] ?? 'USD') ?></dd>
                    
                    <?php if (!empty($entity['address'])): ?>
                    <dt>Address</dt>
                    <dd><?= htmlspecialchars($entity['address']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($entity['city']) || !empty($entity['state']) || !empty($entity['zip_code'])): ?>
                    <dt>Location</dt>
                    <dd>
                        <?php 
                        $location = array_filter([
                            $entity['city'] ?? '',
                            $entity['state'] ?? '',
                            $entity['zip_code'] ?? ''
                        ]);
                        echo htmlspecialchars(implode(', ', $location));
                        ?>
                    </dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($entity['country'])): ?>
                    <dt>Country</dt>
                    <dd><?= htmlspecialchars($entity['country']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Contact Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <?php if (!empty($entity['phone'])): ?>
                    <dt>Phone</dt>
                    <dd><?= htmlspecialchars($entity['phone']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($entity['email'])): ?>
                    <dt>Email</dt>
                    <dd><a href="mailto:<?= htmlspecialchars($entity['email']) ?>"><?= htmlspecialchars($entity['email']) ?></a></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($entity['website'])): ?>
                    <dt>Website</dt>
                    <dd><a href="<?= htmlspecialchars($entity['website']) ?>" target="_blank"><?= htmlspecialchars($entity['website']) ?></a></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($entity['created_at'])): ?>
                    <dt>Created</dt>
                    <dd><?= format_date($entity['created_at']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($entity['updated_at'])): ?>
                    <dt>Last Updated</dt>
                    <dd><?= format_date($entity['updated_at']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (has_permission('entities', 'update')): ?>
                        <a href="<?= base_url('entities/edit/' . $entity['id']) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Entity
                        </a>
                    <?php endif; ?>
                    <?php if (has_permission('entities', 'delete')): ?>
                        <a href="<?= base_url('entities/delete/' . $entity['id']) ?>" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this entity?')">
                            <i class="bi bi-trash"></i> Delete Entity
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

