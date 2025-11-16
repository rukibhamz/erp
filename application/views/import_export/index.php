<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header mb-4">
    <h1 class="page-title mb-0">Import / Export Data</h1>
    <p class="text-muted mb-0">Import data from CSV or export existing data</p>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Import Section -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-upload"></i> Import Data</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Import data from CSV files. Download templates below for proper formatting.</p>
                
                <div class="list-group">
                    <a href="<?= base_url('import-export/import?type=customers') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-people"></i> Import Customers</h6>
                                <small class="text-muted">Import customer data from CSV file</small>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                    
                    <a href="<?= base_url('import-export/import?type=items') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-box"></i> Import Items</h6>
                                <small class="text-muted">Import products/services from CSV file</small>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                    
                    <a href="<?= base_url('import-export/import?type=vendors') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-building"></i> Import Vendors</h6>
                                <small class="text-muted">Import vendor/supplier data from CSV file</small>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Section -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-download"></i> Export Data</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Export your data to CSV format for backup or external use.</p>
                
                <div class="list-group">
                    <a href="<?= base_url('import-export/export?type=customers') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-people"></i> Export Customers</h6>
                                <small class="text-muted">Export all customer data to CSV</small>
                            </div>
                            <i class="bi bi-download"></i>
                        </div>
                    </a>
                    
                    <a href="<?= base_url('import-export/export?type=items') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-box"></i> Export Items</h6>
                                <small class="text-muted">Export all items/products to CSV</small>
                            </div>
                            <i class="bi bi-download"></i>
                        </div>
                    </a>
                    
                    <a href="<?= base_url('import-export/export?type=vendors') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-building"></i> Export Vendors</h6>
                                <small class="text-muted">Export all vendors to CSV</small>
                            </div>
                            <i class="bi bi-download"></i>
                        </div>
                    </a>
                    
                    <a href="<?= base_url('import-export/export?type=invoices') ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-receipt"></i> Export Invoices</h6>
                                <small class="text-muted">Export invoice data to CSV</small>
                            </div>
                            <i class="bi bi-download"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Templates Section -->
<div class="card mt-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet"></i> Download Import Templates</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Download CSV templates to ensure proper formatting for imports.</p>
        
        <div class="row g-3">
            <div class="col-md-4">
                <a href="<?= base_url('import-export/download-template?type=customers') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-download"></i> Customers Template
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= base_url('import-export/download-template?type=items') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-download"></i> Items Template
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= base_url('import-export/download-template?type=vendors') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-download"></i> Vendors Template
                </a>
            </div>
        </div>
    </div>
</div>


