<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('list_search_term')) {
    /**
     * Sanitized search string from query (search or q).
     */
    function list_search_term(): string {
        $raw = $_GET['search'] ?? $_GET['q'] ?? '';
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        return sanitize_input($raw);
    }
}

if (!function_exists('standard_list_search_fields')) {
    /**
     * Common searchable columns by list type.
     */
    function standard_list_search_fields(string $entity): array {
        $map = [
            'booking' => ['booking_number', 'customer_name', 'customer_email', 'customer_phone', 'facility_name', 'facility_code', 'status', 'payment_status', 'id'],
            'customer' => ['customer_code', 'company_name', 'contact_name', 'email', 'phone', 'tax_id'],
            'vendor' => ['vendor_code', 'company_name', 'contact_name', 'email', 'phone', 'tax_id'],
            'invoice' => ['invoice_number', 'company_name', 'reference', 'status'],
            'bill' => ['bill_number', 'company_name', 'reference', 'status'],
            'payment' => ['payment_number', 'customer_name', 'company_name', 'payment_method', 'reference', 'status'],
            'account' => ['account_code', 'account_name', 'account_number', 'account_type'],
            'cash_account' => ['account_name', 'bank_name', 'account_number', 'account_type'],
            'transaction' => ['transaction_number', 'description', 'account_code', 'account_name', 'reference_type', 'status'],
            'journal' => ['entry_number', 'reference', 'description', 'status'],
            'user' => ['username', 'email', 'first_name', 'last_name', 'phone', 'role'],
            'product' => ['product_code', 'sku', 'name', 'description', 'category'],
            'item' => ['item_code', 'sku', 'item_name', 'name', 'description', 'category'],
            'employee' => ['employee_code', 'first_name', 'last_name', 'email', 'phone', 'department'],
            'tenant' => ['tenant_code', 'company_name', 'contact_name', 'email', 'phone'],
            'lease' => ['lease_number', 'tenant_name', 'space_name', 'status'],
            'supplier' => ['supplier_code', 'company_name', 'contact_name', 'email', 'phone'],
            'vendor_bill' => ['bill_number', 'vendor_name', 'reference', 'status'],
            'utility_bill' => ['bill_number', 'meter_name', 'reference', 'status'],
            'meter' => ['meter_number', 'meter_name', 'serial_number', 'location'],
            'promo' => ['code', 'promo_code', 'description', 'status'],
            'company' => ['name', 'company_code', 'email', 'phone'],
            'entity' => ['entity_name', 'entity_code', 'tax_id', 'registration_number'],
            'order' => ['order_number', 'po_number', 'vendor_name', 'status'],
            'grn' => ['grn_number', 'po_number', 'supplier_name', 'status'],
            'terminal' => ['terminal_name', 'terminal_code', 'location', 'status'],
            'notification' => ['title', 'message', 'type', 'module'],
            'module' => ['module_name', 'module_key', 'description'],
            'generic' => ['id', 'name', 'code', 'title', 'description', 'status'],
        ];
        return $map[$entity] ?? $map['generic'];
    }
}

if (!function_exists('filter_rows_by_search')) {
    /**
     * Filter in-memory rows where any listed field contains the search term (case-insensitive).
     */
    function filter_rows_by_search(array $rows, array $fields, string $search): array {
        $search = trim($search);
        if ($search === '' || empty($rows)) {
            return $rows;
        }
        $needle = mb_strtolower($search, 'UTF-8');
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach ($fields as $field) {
                if (!array_key_exists($field, $row)) {
                    continue;
                }
                $val = $row[$field];
                if ($val === null || $val === '') {
                    continue;
                }
                if (mb_strpos(mb_strtolower((string) $val, 'UTF-8'), $needle) !== false) {
                    $out[] = $row;
                    break;
                }
            }
        }
        return array_values($out);
    }
}

if (!function_exists('sql_append_search')) {
    /**
     * Append AND (col1 LIKE ? OR col2 LIKE ? ...) to SQL and bind params.
     *
     * @param string $sql SQL fragment to append to (by reference)
     * @param array $params Bind params (by reference)
     * @param array $columns Fully-qualified column expressions e.g. "i.invoice_number"
     */
    function sql_append_search(string &$sql, array &$params, array $columns, string $search): void {
        $search = trim($search);
        if ($search === '' || empty($columns)) {
            return;
        }
        $like = '%' . $search . '%';
        $parts = [];
        foreach ($columns as $col) {
            $parts[] = $col . ' LIKE ?';
            $params[] = $like;
        }
        $sql .= ' AND (' . implode(' OR ', $parts) . ')';
    }
}

if (!function_exists('list_filter_query')) {
    /**
     * Build query string preserving current filters (resets page by default).
     */
    function list_filter_query(array $overrides = [], bool $resetPage = true): string {
        $params = $_GET;
        if ($resetPage) {
            unset($params['page']);
        }
        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }
        $qs = http_build_query($params);
        return $qs !== '' ? '?' . $qs : '';
    }
}

if (!function_exists('list_has_active_filters')) {
    function list_has_active_filters(array $keys): bool {
        foreach ($keys as $key) {
            $val = $_GET[$key] ?? '';
            if ($val !== '' && $val !== null && $val !== 'all') {
                return true;
            }
        }
        return list_search_term() !== '';
    }
}

if (!function_exists('render_list_search_field')) {
    /**
     * Search input for list filter forms. Preserves other GET params via form submit.
     */
    function render_list_search_field(
        string $value = '',
        string $placeholder = 'Search name, ID, email, phone…',
        string $name = 'search',
        string $colClass = 'col-lg-4 col-md-6',
        bool $showLabel = true
    ): void {
        $value = $value !== '' ? $value : list_search_term();
        ?>
        <div class="<?= htmlspecialchars($colClass) ?> list-filter-search">
            <?php if ($showLabel): ?>
                <label class="form-label" for="list_search_<?= htmlspecialchars($name) ?>">Search</label>
            <?php endif; ?>
            <div class="input-group flex-nowrap w-100 list-filter-input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search"
                       class="form-control"
                       id="list_search_<?= htmlspecialchars($name) ?>"
                       name="<?= htmlspecialchars($name) ?>"
                       value="<?= htmlspecialchars($value) ?>"
                       placeholder="<?= htmlspecialchars($placeholder) ?>"
                       autocomplete="off">
            </div>
        </div>
        <?php
    }
}

if (!function_exists('render_list_filter_per_page')) {
    /**
     * Records-per-page dropdown (inline, place before Apply in list-filters-row).
     */
    function render_list_filter_per_page(int $perPage = 50, string $colClass = 'col-5 col-sm-4 col-md-auto'): void {
        ?>
        <div class="<?= htmlspecialchars($colClass) ?>">
            <label class="form-label" for="list_filter_per_page">Records</label>
            <?php render_pagination_per_page_select($perPage, 'per_page', 'form-select list-filters-per-page', 'list_filter_per_page'); ?>
        </div>
        <?php
    }
}

if (!function_exists('render_list_filter_submit_buttons')) {
    /**
     * Apply + Clear buttons (inline, after records dropdown).
     */
    function render_list_filter_submit_buttons(
        string $resetUrl = '',
        string $applyLabel = 'Apply',
        string $colClass = 'col-7 col-sm-8 col-md-auto'
    ): void {
        ?>
        <div class="<?= htmlspecialchars($colClass) ?> list-filters-actions-col">
            <label class="form-label list-filters-btn-spacer d-none d-md-block" aria-hidden="true">&nbsp;</label>
            <label class="form-label d-md-none">Actions</label>
            <div class="list-filters-btn-group">
                <input type="hidden" name="page" value="1">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel-fill me-1"></i><?= htmlspecialchars($applyLabel) ?>
                </button>
                <?php if ($resetUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($resetUrl) ?>" class="btn btn-outline-dark btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('render_list_filter_actions')) {
    /** @deprecated Use render_list_filter_per_page + render_list_filter_submit_buttons in list-filters-row */
    function render_list_filter_actions(
        int $perPage = 50,
        string $resetUrl = '',
        string $applyLabel = 'Apply',
        string $colClass = ''
    ): void {
        render_list_filter_per_page($perPage);
        render_list_filter_submit_buttons($resetUrl, $applyLabel);
    }
}
