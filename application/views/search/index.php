<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header mb-4">
    <h1 class="page-title mb-0">Search</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('search') ?>" id="searchForm">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control" 
                               value="<?= htmlspecialchars($query ?? '') ?>" 
                               placeholder="Search across all modules..." 
                               autofocus
                               id="searchInput">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="module" class="form-select form-select-lg">
                        <option value="all" <?= ($module ?? 'all') === 'all' ? 'selected' : '' ?>>All Modules</option>
                        <option value="customers" <?= ($module ?? '') === 'customers' ? 'selected' : '' ?>>Customers</option>
                        <option value="invoices" <?= ($module ?? '') === 'invoices' ? 'selected' : '' ?>>Invoices</option>
                        <option value="bookings" <?= ($module ?? '') === 'bookings' ? 'selected' : '' ?>>Bookings</option>
                        <option value="items" <?= ($module ?? '') === 'items' ? 'selected' : '' ?>>Items</option>
                        <option value="vendors" <?= ($module ?? '') === 'vendors' ? 'selected' : '' ?>>Vendors</option>
                        <option value="transactions" <?= ($module ?? '') === 'transactions' ? 'selected' : '' ?>>Transactions</option>
                        <option value="properties" <?= ($module ?? '') === 'properties' ? 'selected' : '' ?>>Properties</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-dark btn-lg w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($query ?? '')): ?>
    <div class="mb-3">
        <p class="text-muted">
            Found <strong><?= count($results ?? []) ?></strong> result(s) for "<strong><?= htmlspecialchars($query) ?></strong>"
        </p>
    </div>
    
    <?php if (empty($results)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-search" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No results found</p>
                <p class="text-muted small">Try different keywords or search in a specific module</p>
            </div>
        </div>
    <?php else: ?>
        <?php
        // Group results by type
        $grouped = [];
        foreach ($results as $result) {
            $type = $result['type'] ?? 'other';
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $result;
        }
        ?>
        
        <?php foreach ($grouped as $type => $items): ?>
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-<?= $this->getTypeIcon($type) ?>"></i> 
                        <?= ucfirst($type) ?> (<?= count($items) ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($items as $item): ?>
                            <a href="<?= $this->getItemUrl($type, $item['id']) ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?= htmlspecialchars($this->getItemTitle($type, $item)) ?>
                                        </h6>
                                        <p class="mb-1 small text-muted">
                                            <?= $this->getItemSubtitle($type, $item) ?>
                                        </p>
                                        <?php if (!empty($item['description'])): ?>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr($item['description'], 0, 100)) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-secondary"><?= ucfirst($type) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-search" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">Enter a search term above to find records across all modules</p>
        </div>
    </div>
<?php endif; ?>

<script>
// Auto-search on input (debounced)
let searchTimeout;
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length >= 2) {
        searchTimeout = setTimeout(() => {
            // Could implement live search here
        }, 500);
    }
});
</script>

<?php
// Helper functions
function getTypeIcon($type) {
    $icons = [
        'customer' => 'person',
        'invoice' => 'receipt',
        'booking' => 'calendar-check',
        'item' => 'box',
        'vendor' => 'building',
        'transaction' => 'arrow-left-right',
        'property' => 'house'
    ];
    return $icons[$type] ?? 'file';
}

function getItemUrl($type, $id) {
    $urls = [
        'customer' => 'customers/view/' . $id,
        'invoice' => 'receivables/invoices/view/' . $id,
        'booking' => 'bookings/view/' . $id,
        'item' => 'inventory/items/view/' . $id,
        'vendor' => 'payables/vendors/view/' . $id,
        'transaction' => 'ledger/transactions/view/' . $id,
        'property' => 'properties/view/' . $id
    ];
    return base_url($urls[$type] ?? '#');
}

function getItemTitle($type, $item) {
    switch ($type) {
        case 'customer':
            return ($item['name'] ?? '') . ' (' . ($item['customer_code'] ?? '') . ')';
        case 'invoice':
            return ($item['invoice_number'] ?? 'Invoice #' . $item['id']);
        case 'booking':
            return ($item['booking_number'] ?? 'Booking #' . $item['id']);
        case 'item':
            return ($item['name'] ?? '') . ' (' . ($item['item_code'] ?? '') . ')';
        case 'vendor':
            return ($item['name'] ?? '') . ' (' . ($item['vendor_code'] ?? '') . ')';
        case 'transaction':
            return ($item['reference'] ?? 'Transaction #' . $item['id']);
        case 'property':
            return ($item['name'] ?? '') . ' (' . ($item['property_code'] ?? '') . ')';
        default:
            return 'Item #' . ($item['id'] ?? '');
    }
}

function getItemSubtitle($type, $item) {
    switch ($type) {
        case 'invoice':
            return 'Amount: ' . format_currency($item['total_amount'] ?? 0) . ' | Date: ' . date('M d, Y', strtotime($item['invoice_date'] ?? ''));
        case 'booking':
            return 'Amount: ' . format_currency($item['total_amount'] ?? 0) . ' | Date: ' . date('M d, Y', strtotime($item['booking_date'] ?? ''));
        case 'transaction':
            return 'Amount: ' . format_currency($item['amount'] ?? 0) . ' | Date: ' . date('M d, Y', strtotime($item['date'] ?? ''));
        case 'customer':
        case 'vendor':
            return ($item['email'] ?? '') . ($item['phone'] ? ' | ' . $item['phone'] : '');
        default:
            return '';
    }
}
?>



