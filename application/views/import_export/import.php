<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$type = $type ?? 'customers';
$typeLabels = [
    'customers' => 'Customers',
    'items' => 'Items',
    'vendors' => 'Vendors'
];
$label = $typeLabels[$type] ?? ucfirst($type);
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">Import <?= $label ?></h1>
            <p class="text-muted mb-0">Upload a CSV file to import <?= strtolower($label) ?></p>
        </div>
        <a href="<?= base_url('import-export') ?>" class="btn btn-outline-dark">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-upload"></i> Upload File</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>File Requirements:</strong>
                    <ul class="mb-0 mt-2">
                        <li>File format: CSV (Comma Separated Values)</li>
                        <li>Maximum file size: 10MB</li>
                        <li>First row must contain column headers</li>
                        <li>Download template below for proper formatting</li>
                        <li>Duplicate records will be created (no automatic deduplication)</li>
                    </ul>
                </div>
                
                <form method="POST" action="<?= base_url('import-export/process-import') ?><?php echo csrf_field(); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                    
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Select CSV File</label>
                        <input type="file" name="import_file" id="import_file" class="form-control" accept=".csv,.xlsx,.xls" required>
                        <small class="text-muted">Accepted formats: CSV, Excel (XLS, XLSX)</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-upload"></i> Import Data
                        </button>
                        <a href="<?= base_url('import-export/download-template?type=' . $type) ?>" class="btn btn-outline-dark">
                            <i class="bi bi-download"></i> Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Import Instructions</h6>
            </div>
            <div class="card-body">
                <?php if ($type === 'customers'): ?>
                    <p><strong>Required Fields:</strong> Name</p>
                    <p><strong>Optional Fields:</strong> Customer Code, Email, Phone, Address, City, State, Country, Postal Code, Credit Limit, Payment Terms, Status</p>
                    <p><strong>Status Values:</strong> active, inactive</p>
                    <p><strong>Payment Terms:</strong> net_15, net_30, net_45, net_60, due_on_receipt</p>
                <?php elseif ($type === 'items'): ?>
                    <p><strong>Required Fields:</strong> Name</p>
                    <p><strong>Optional Fields:</strong> Item Code, Description, Category, Unit, Unit Cost, Unit Price, Item Type, Taxable, Status</p>
                    <p><strong>Item Types:</strong> product, service</p>
                    <p><strong>Taxable:</strong> Yes, No</p>
                    <p><strong>Status:</strong> active, inactive</p>
                <?php elseif ($type === 'vendors'): ?>
                    <p><strong>Required Fields:</strong> Name</p>
                    <p><strong>Optional Fields:</strong> Vendor Code, Email, Phone, Address, City, State, Country, Payment Terms, Status</p>
                    <p><strong>Status Values:</strong> active, inactive</p>
                    <p><strong>Payment Terms:</strong> net_15, net_30, net_45, net_60, due_on_receipt</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Tips</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i>
                        Always download and review the template first
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i>
                        Test with a small file before bulk import
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i>
                        Ensure all required fields are populated
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i>
                        Remove header row if it appears in data rows
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success"></i>
                        Save your original file as backup
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>


