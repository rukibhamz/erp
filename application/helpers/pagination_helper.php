<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('pagination_per_page_options')) {
    function pagination_per_page_options() {
        return [25, 50, 75, 100, 200];
    }
}

if (!function_exists('pagination_resolve_request')) {
    /**
     * Read page/per_page from query string with validation.
     */
    function pagination_resolve_request($defaultPerPage = null) {
        $options = pagination_per_page_options();
        $perPage = intval($_GET['per_page'] ?? ($defaultPerPage ?? 50));
        if (!in_array($perPage, $options, true)) {
            $perPage = intval($defaultPerPage ?? 50);
            if (!in_array($perPage, $options, true)) {
                $perPage = 50;
            }
        }

        $page = max(1, intval($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset,
            'per_page_options' => $options,
        ];
    }
}

if (!function_exists('pagination_build_meta')) {
    function pagination_build_meta($totalRecords, $page, $perPage) {
        $totalRecords = max(0, intval($totalRecords));
        $perPage = max(1, intval($perPage));
        $totalPages = max(1, (int) ceil($totalRecords / $perPage));
        $page = max(1, min(intval($page), $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'per_page_options' => pagination_per_page_options(),
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'from' => $totalRecords > 0 ? ($offset + 1) : 0,
            'to' => min($offset + $perPage, $totalRecords),
        ];
    }
}

if (!function_exists('pagination_slice')) {
    /**
     * Slice an in-memory list and return items + pagination meta.
     */
    function pagination_slice(array $items, $page = null, $perPage = null) {
        $params = pagination_resolve_request($perPage);
        if ($page !== null) {
            $params['page'] = max(1, intval($page));
            $params['offset'] = ($params['page'] - 1) * $params['per_page'];
        }

        $totalRecords = count($items);
        $pagination = pagination_build_meta($totalRecords, $params['page'], $params['per_page']);
        $slice = array_slice($items, ($pagination['page'] - 1) * $pagination['per_page'], $pagination['per_page']);

        return [
            'items' => $slice,
            'pagination' => $pagination,
        ];
    }
}

if (!function_exists('pagination_page_url')) {
    function pagination_page_url($page, array $queryParams = null) {
        $params = $queryParams ?? $_GET;
        $params['page'] = max(1, intval($page));
        return '?' . http_build_query($params);
    }
}

if (!function_exists('render_pagination_controls')) {
    /**
     * Render shared pagination footer (First/Prev/pages/Next/Last + record count).
     */
    function render_pagination_controls($pagination, array $queryParams = null, $ariaLabel = 'Pagination') {
        if (empty($pagination) || intval($pagination['total_records'] ?? 0) <= 0) {
            return;
        }

        $queryParams = $queryParams ?? $_GET;
        $partial = BASEPATH . 'views/partials/pagination_controls.php';
        if (!file_exists($partial)) {
            return;
        }

        include $partial;
    }
}

if (!function_exists('render_pagination_per_page_select')) {
    function render_pagination_per_page_select($selected = 50, $name = 'per_page', $class = 'form-select') {
        $options = pagination_per_page_options();
        $selected = intval($selected);
        echo '<select name="' . htmlspecialchars($name) . '" class="' . htmlspecialchars($class) . '">';
        foreach ($options as $opt) {
            $sel = $selected === intval($opt) ? ' selected' : '';
            echo '<option value="' . intval($opt) . '"' . $sel . '>' . intval($opt) . '</option>';
        }
        echo '</select>';
    }
}
