<?php
/**
 * Items View View - Alias to inventory/items/view
 */
defined('BASEPATH') OR exit('No direct script access allowed');
if (isset($item['id'])) {
    redirect('items/view/' . $item['id']);
}
redirect('items');
