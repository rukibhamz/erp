<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$perPage = intval($pagination['per_page'] ?? 50);
$hasFilters = list_has_active_filters(['source', 'search']);
?>

<div class="page-header list-filters-page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Customers</h1>
        <div class="btn-group list-filters-page-actions">
            <a href="<?= base_url('customer_types') ?>" class="btn btn-outline-primary">
                <i class="bi bi-people"></i> Customer Types
            </a>
            <a href="<?= base_url('receivables/customers/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Customer
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<style>
    .customer-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        flex-wrap: nowrap;
    }
    .customer-actions .btn {
        padding: 0.2rem 0.45rem;
    }
    .actions-cell {
        white-space: nowrap;
        min-width: 210px;
    }
</style>

<!-- Search & filters -->
<div class="card shadow-sm mb-4 list-filters-card">
    <div class="card-body">
        <form method="GET" action="<?= base_url('receivables/customers') ?>" class="list-filters-form">
            <div class="row g-2 align-items-end list-filters-row">
                <?php
                $search_col_class = 'col-12 col-md';
                $search_placeholder = 'Code, name, email, phone…';
                include(BASEPATH . 'views/partials/list_search_field.php');
                ?>
                <?php render_list_filter_per_page($perPage); ?>
                <?php render_list_filter_submit_buttons(base_url('receivables/customers')); ?>
            </div>

            <div class="list-filters-secondary list-source-pills">
                <span class="filter-group-label">Source</span>
                <?php
                $sources = [null => 'All', 'invoice' => 'Invoice', 'booking' => 'Booking', 'tenant' => 'Tenant'];
                foreach ($sources as $val => $label):
                    $active = ($source_filter === $val);
                    $href = base_url('receivables/customers') . list_filter_query(['source' => $val]);
                ?>
                <a href="<?= htmlspecialchars($href) ?>" class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-primary' ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($hasFilters): ?>
            <div class="list-active-filters">
                <span class="small text-muted me-1"><i class="bi bi-funnel"></i> Active:</span>
                <?php if (list_search_term() !== ''): ?>
                    <span class="badge bg-secondary">Search: <?= htmlspecialchars(list_search_term()) ?></span>
                <?php endif; ?>
                <?php if (!empty($source_filter)): ?>
                    <span class="badge bg-primary">Source: <?= htmlspecialchars(ucfirst($source_filter)) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-2">
        <span class="fw-semibold">Customer List</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th class="text-end">Outstanding</th>
                        <th>Status</th>
                        <th class="actions-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($customer['customer_code']) ?></strong></td>
                                <td>
                                    <a href="<?= base_url('receivables/customers/history/' . intval($customer['id'])) ?>">
                                        <?= htmlspecialchars($customer['company_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($customer['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($customer['phone'] ?? '-') ?></td>
                                <td class="text-end">
                                    <strong><?= format_currency($customer['outstanding'] ?? 0) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $customer['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($customer['status']) ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <div class="customer-actions">
                                        <a href="<?= base_url('receivables/customers/view/' . intval($customer['id'])) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('receivables', 'update')): ?>
                                            <a href="<?= base_url('receivables/customers/edit/' . intval($customer['id'])) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('receivables/invoices?customer_id=' . intval($customer['id'])) ?>" class="btn btn-primary" title="View Invoices">
                                            <i class="bi bi-file-text"></i>
                                        </a>
                                        <a href="<?= base_url('receivables/invoices/create?customer_id=' . intval($customer['id'])) ?>" class="btn btn-success" title="Create Invoice">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>
                                        <?php if (isSuperAdmin()): ?>
                                            <form method="POST" action="<?= base_url('receivables/deleteCustomer/' . intval($customer['id'])) ?>"
                                                  class="m-0 p-0"
                                                  onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <?php if (list_search_term() !== '' || !empty($source_filter)): ?>
                                        <p class="mb-2">No customers match your filters.</p>
                                        <a href="<?= base_url('receivables/customers') ?>" class="btn btn-outline-primary btn-sm">Clear filters</a>
                                    <?php else: ?>
                                        <p class="mb-0">No customers found.</p>
                                        <a href="<?= base_url('receivables/customers/create') ?>" class="btn btn-primary">
                                            <i class="bi bi-plus-circle"></i> Create First Customer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include BASEPATH . 'views/partials/accounting_table_footer.php'; ?>
</div>
