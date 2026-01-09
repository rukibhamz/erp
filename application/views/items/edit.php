<?php
/**
 * Items Edit View - Alias to inventory/items/edit
 */
defined('BASEPATH') OR exit('No direct script access allowed');
if (isset($item['id'])) {
    redirect('items/edit/' . $item['id']);
}
redirect('items');
