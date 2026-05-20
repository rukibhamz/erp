<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Optional vars: $search_placeholder, $search_col_class, $search_name
 */
$search_placeholder = $search_placeholder ?? 'Search by name, ID, email, or phone…';
$search_col_class = $search_col_class ?? 'col-md-4';
$search_name = $search_name ?? 'search';
render_list_search_field(list_search_term(), $search_placeholder, $search_name, $search_col_class);
